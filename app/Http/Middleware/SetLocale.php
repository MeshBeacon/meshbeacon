<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * The locales supported by the application.
     *
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['en', 'ms'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            // Machine health routes deliberately skip StartSession so they
            // remain useful during a database outage.
            ?? ($request->hasSession() ? $request->session()->get('locale') : null)
            ?? config('app.locale');

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($locale);
            // Carbon has its own independent locale state (not tied to
            // App::setLocale) that drives translatedFormat()/diffForHumans()
            // month/day names and relative-time phrasing — without this,
            // those always render in English regardless of the chosen UI language.
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
