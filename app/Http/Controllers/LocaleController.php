<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch the application locale for the current user (or guest session).
     */
    public function update(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED_LOCALES, true)) {
            abort(404);
        }

        if ($user = $request->user()) {
            $user->update(['locale' => $locale]);
        } else {
            $request->session()->put('locale', $locale);
        }

        return back();
    }
}
