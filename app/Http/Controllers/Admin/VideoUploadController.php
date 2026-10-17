<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Receives a video in small pieces (1.5 MB each) and glues them together.
 *
 * Why chunks: a normal form upload dies at PHP's upload_max_filesize / post_max_size
 * (2 MB and 8 MB by default). Small pieces need no php.ini changes, show a real
 * progress bar, and a dropped connection only re-sends one piece.
 *
 * Videos are stored on the PRIVATE "local" disk (storage/app/private/lesson-videos).
 * Students watch them through the courses.lessons.video route, which checks enrollment.
 */
class VideoUploadController extends Controller
{
    /** extension => MIME types a genuine file of that kind reports */
    private const ALLOWED = [
        'mp4' => ['video/mp4', 'video/x-m4v'],
        'm4v' => ['video/x-m4v', 'video/mp4'],
        'mov' => ['video/quicktime', 'video/mp4'],
        'webm' => ['video/webm'],
    ];

    /** Must match CHUNK in the browser code (1.5 MB). */
    private const CHUNK_BYTES = 1572864;

    public function chunk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'regex:/^[A-Za-z0-9-]{16,64}$/'],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1', 'max:20000'],
            'filename' => ['required', 'string', 'max:255'],
            'chunk' => ['required', 'file', 'max:4096'],
        ]);

        $index = (int) $data['index'];
        $total = (int) $data['total'];
        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));

        if (! isset(self::ALLOWED[$ext])) {
            return $this->fail('Upload an MP4, WebM, MOV or M4V video.');
        }

        $maxMb = (int) config('admin.video_max_mb', 2048);

        if ($index >= $total || $total * self::CHUNK_BYTES > ($maxMb * 1024 * 1024) + self::CHUNK_BYTES) {
            return $this->fail("That video is bigger than the {$maxMb} MB limit.");
        }

        $disk = Storage::disk('local');
        $dir = 'chunks/'.$data['upload_id'];

        if ($index === 0) {
            $this->purgeStale($disk);
        }

        $disk->putFileAs($dir, $request->file('chunk'), $index.'.part');

        if (count($disk->files($dir)) < $total) {
            return response()->json(['done' => false]);
        }

        return $this->assemble($disk, $dir, $total, $ext, $maxMb * 1024 * 1024);
    }

    private function assemble($disk, string $dir, int $total, string $ext, int $maxBytes): JsonResponse
    {
        $disk->makeDirectory('lesson-videos');

        $name = 'lesson-videos/'.Str::uuid().'.'.$ext;
        $target = $disk->path($name);
        $out = @fopen($target, 'wb');

        if ($out === false) {
            return $this->fail('The server could not write the video file. Check that storage/app is writable.', 500);
        }

        for ($i = 0; $i < $total; $i++) {
            $part = $disk->path($dir.'/'.$i.'.part');
            $in = is_file($part) ? fopen($part, 'rb') : false;

            if ($in === false) {
                fclose($out);
                @unlink($target);
                $disk->deleteDirectory($dir);

                return $this->fail("Part {$i} went missing. Please upload the video again.");
            }

            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);
        $disk->deleteDirectory($dir);

        $size = (int) filesize($target);

        if ($size > $maxBytes) {
            @unlink($target);

            return $this->fail('That video is bigger than the allowed size.');
        }

        // Trust the file's real content, not the name the browser gave it.
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($target);

            if (! in_array($mime, self::ALLOWED[$ext], true)) {
                @unlink($target);

                return $this->fail("That file is not a real .{$ext} video.");
            }
        }

        return response()->json(['done' => true, 'path' => $name, 'size' => $size]);
    }

    /** Half-finished uploads older than a day are cleaned up. */
    private function purgeStale($disk): void
    {
        foreach ($disk->directories('chunks') as $dir) {
            $modified = @filemtime($disk->path($dir));

            if ($modified && $modified < time() - 86400) {
                $disk->deleteDirectory($dir);
            }
        }
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
