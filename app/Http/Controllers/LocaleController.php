<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    /**
     * The EN/KM switch. The choice is kept for this session and remembered in
     * a cookie for a year, so a returning visitor (signed in or not) keeps
     * their language and the automatic first pick never overrides it.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'in:en,km'],
        ]);

        $request->session()->put('locale', $data['locale']);
        Cookie::queue(SetLocale::CHOICE_COOKIE, $data['locale'], 60 * 24 * 365);

        return back();
    }
}
