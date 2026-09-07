<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetStoreLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');

        if (!$locale) {
            $locale = Setting::get('storefront_lang', 'en');
        }

        if (!in_array($locale, ['en', 'ar'], true)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
