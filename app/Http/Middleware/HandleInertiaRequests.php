<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        $locale = $request->cookie('elms_lang') ?? ($_COOKIE['elms_lang'] ?? session('elms_lang', 'km'));
        if (in_array($locale, ['km', 'en'])) {
            app()->setLocale($locale);
        }

        return parent::handle($request, $next);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $botToken = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        $botId = config('services.telegram.bot_id') ?? env('TELEGRAM_BOT_ID');
        if (!$botId && !empty($botToken) && str_contains((string) $botToken, ':')) {
            $parts = explode(':', (string) $botToken);
            $botId = $parts[0] ?? '8828915669';
        }

        $locale = $request->cookie('elms_lang') ?? ($_COOKIE['elms_lang'] ?? session('elms_lang', 'km'));
        if (in_array($locale, ['km', 'en'])) {
            app()->setLocale($locale);
        }

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $request->user(),
            ],
            'telegram' => [
                'bot_username' => config('services.telegram.bot_username') ?? env('TELEGRAM_BOT_USERNAME', 'spi_elms_auth_bot'),
                'bot_id' => $botId ?? '8828915669',
                'is_configured' => !empty($botToken),
            ],
            'clerk' => [
                'publishable_key' => config('services.clerk.publishable_key') ?? env('VITE_CLERK_PUBLISHABLE_KEY'),
                'app_id' => config('services.clerk.app_id') ?? env('CLERK_APP_ID', 'app_3HuqsrwyUIBYDv90aKgOiwfsKkx'),
                'is_configured' => !empty(config('services.clerk.publishable_key') ?? env('VITE_CLERK_PUBLISHABLE_KEY')),
            ],
            'flash' => [
                'success'           => fn () => $request->session()->get('success'),
                'error'             => fn () => $request->session()->get('error'),
                'status'            => fn () => $request->session()->get('status'),
                'message'           => fn () => $request->session()->get('message'),
                'sent_to_telegram'  => fn () => $request->session()->get('sent_to_telegram'),
                'has_telegram'      => fn () => $request->session()->get('has_telegram'),
                'link_telegram_url' => fn () => $request->session()->get('link_telegram_url') ?? $request->session()->get('telegram_url'),
                'telegram_url'      => fn () => $request->session()->get('link_telegram_url') ?? $request->session()->get('telegram_url'),
                'telegram_bot_name' => fn () => $request->session()->get('telegram_bot_name'),
                'reset_user'        => fn () => $request->session()->get('reset_user'),
            ],
        ];
    }
}
