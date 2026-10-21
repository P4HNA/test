<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\PublicProfileUpdateRequest;
use App\Services\EmailChangeVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Longest edge a stored avatar is allowed to keep. */
    private const AVATAR_PX = 512;

    public function __construct(private readonly EmailChangeVerifier $emailChangeVerifier) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'pendingEmailChange' => $this->emailChangeVerifier->pendingFor($user),
            'resendAvailableIn' => $this->emailChangeVerifier->secondsUntilResendAllowed($user),
            // Local-only hint so a developer isn't told "code sent" while
            // the mailer only writes to a log file / memory.
            'mailIsLogOnly' => app()->isLocal() && in_array(config('mail.default'), ['log', 'array'], true),
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * Name and phone are saved immediately. A changed email address is NOT
     * written here -- it starts the verification-code flow instead (see
     * EmailChangeVerifier::start()), and only becomes the user's email once
     * that code is confirmed via ProfileEmailChangeController.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $requestedEmail = $data['email'];
        unset($data['email']);

        $user->fill($data);
        $user->save();

        if ($requestedEmail === $user->email) {
            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }

        $result = $this->emailChangeVerifier->start($user, $requestedEmail);

        if ($result === 'sent') {
            return Redirect::route('profile.edit')->with('status', 'email-verification-sent');
        }

        // Name/phone were saved above; only the email step didn't go out.
        $message = $result === 'cooldown'
            ? ProfileEmailChangeController::cooldownMessage($this->emailChangeVerifier->secondsUntilResendAllowed($user))
            : ProfileEmailChangeController::sendFailedMessage();

        return Redirect::route('profile.edit')
            ->withErrors(['email' => $message])
            ->withInput($request->only('email'));
    }

    /**
     * Save the optional "about you" fields.
     *
     * Kept apart from update() so saving a bio never touches the email-change
     * handshake. The photo is replaced in place: the previous file is deleted
     * only once the new one is safely stored, so a failed upload can't leave
     * the account with no picture at all.
     */
    public function updatePublicProfile(PublicProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $previous = $user->avatar_path;

        if ($request->boolean('remove_avatar')) {
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $this->storeAvatar($request->file('avatar'));
        }

        $user->fill([
            // '' from an emptied input means "clear it", not "save a blank string".
            'headline' => $this->nullIfBlank($data['headline'] ?? null),
            'bio' => $this->nullIfBlank($data['bio'] ?? null),
            'location' => $this->nullIfBlank($data['location'] ?? null),
            'website' => $this->nullIfBlank($data['website'] ?? null),
        ]);

        $user->save();

        if ($previous && $previous !== $user->avatar_path) {
            Storage::disk('public')->delete($previous);
        }

        return Redirect::route('profile.edit')->with('status', 'public-profile-updated');
    }

    /**
     * Swap the profile photo from the profile page, leaving the other public
     * fields alone (updatePublicProfile() would clear any it isn't sent).
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        // Same limits as the photo on the profile form, but here it is the whole point.
        $form = new PublicProfileUpdateRequest;
        $request->validate(
            ['avatar' => ['required', ...array_diff($form->rules()['avatar'], ['nullable'])]],
            $form->messages(),
        );

        $user = $request->user();
        $previous = $user->avatar_path;

        $user->avatar_path = $this->storeAvatar($request->file('avatar'));
        $user->save();

        if ($previous && $previous !== $user->avatar_path) {
            Storage::disk('public')->delete($previous);
        }

        return Redirect::to(route('learning.profile'))->with('status', 'photo-updated');
    }

    /**
     * Square off and shrink the photo before it is stored.
     *
     * Straight off a phone an avatar is several megabytes, and the nav shows it
     * at 26px on every single page -- so the original is re-encoded to a
     * {@see self::AVATAR_PX}px square, which still covers the 88px profile
     * header on a 4x display. Re-encoding also drops the EXIF block, and with
     * it the GPS tag a phone photo usually carries.
     *
     * If GD can't read the upload (an exotic WebP, say) the file is stored as
     * it came in: a large picture beats a failed save.
     */
    private function storeAvatar(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return $file->store('avatars', 'public');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $edge = min($width, $height);
        $size = min(self::AVATAR_PX, $edge);

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        // Centre crop, because every place we show it is a circle anyway.
        imagecopyresampled(
            $canvas, $source,
            0, 0,
            intdiv($width - $edge, 2), intdiv($height - $edge, 2),
            $size, $size,
            $edge, $edge
        );

        // PNG keeps a cut-out avatar's transparency; everything else is smaller
        // as a JPEG, which is what most uploads will be.
        $transparent = in_array($file->getMimeType(), ['image/png', 'image/webp'], true);

        ob_start();
        $transparent ? imagepng($canvas, null, 9) : imagejpeg($canvas, null, 82);
        $bytes = (string) ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        $path = 'avatars/'.Str::random(40).($transparent ? '.png' : '.jpg');
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    private function nullIfBlank(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $avatar = $user->avatar_path;

        Auth::logout();

        $user->delete();

        // The payment rows deliberately outlive the account; the photo doesn't.
        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
