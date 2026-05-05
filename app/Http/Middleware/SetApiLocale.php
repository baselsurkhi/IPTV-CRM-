<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        if ($request->query->has('locale')) {
            $q = strtolower((string) $request->query('locale'));
            $locale = Locales::isSupported($q) ? $q : null;
        }

        if ($locale === null && $request->headers->has('Accept-Language')) {
            foreach ($request->getLanguages() as $lang) {
                $lang = strtolower(str_replace('_', '-', $lang));
                $primary = explode('-', $lang)[0] ?? '';
                if (Locales::isSupported($primary)) {
                    $locale = $primary;
                    break;
                }
                if ($lang === 'iw') {
                    $locale = 'he';
                    break;
                }
            }
        }

        if ($locale === null && Schema::hasTable('site_settings')) {
            $site = SiteSetting::getValue('api.default_locale');
            if ($site !== null && Locales::isSupported($site)) {
                $locale = $site;
            }
        }

        if ($locale === null) {
            $locale = strtolower((string) config('iptv.api_fallback_locale', 'ar'));
        }

        app()->setLocale($locale);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('Content-Language', str_replace('_', '-', app()->getLocale()));

        return $response;
    }
}
