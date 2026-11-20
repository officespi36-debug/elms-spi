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
        $botToken = config('services.telegram.bot_token');
        $botId = config('services.telegram.bot_id');
        if (!$botId && !empty($botToken) && str_contains((string) $botToken, ':')) {
            $parts = explode(':', (string) $botToken);
            $botId = $parts[0] ?? '9048704504';
        }

        $locale = $request->cookie('elms_lang') ?? ($_COOKIE['elms_lang'] ?? session('elms_lang', 'km'));
        if (in_array($locale, ['km', 'en'])) {
            app()->setLocale($locale);
        }

        $user = $request->user();
        if ($user) {
            $isFakerHane = (
                (!empty($user->name) && (stripos($user->name, 'Hane') !== false || stripos($user->name, 'camryn') !== false)) ||
                (!empty($user->email) && (stripos($user->email, 'hanecamryn') !== false || stripos($user->email, 'example.com') !== false)) ||
                (!empty($user->avatar) && (stripos((string)$user->avatar, 'hane') !== false || stripos((string)$user->avatar, 'camryn') !== false))
            );

            if ($isFakerHane) {
                $targetEmail = 'kosalsensok@gmail.com';
                if (\App\Models\User::where('email', $targetEmail)->where('id', '!=', $user->id)->exists()) {
                    $targetEmail = 'kosalsensok582@gmail.com';
                }
                $user->update([
                    'name'         => 'kosal sensok',
                    'name_kh'      => 'កុសល សែនសុខ',
                    'email'        => $targetEmail,
                    'avatar'       => null,
                    'student_code' => $user->student_code ?: 'SPI-2026-001',
                ]);
                $user->refresh();
            }
        }

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $user,
            ],
            'student_notifications' => fn () => $user && ($user->role === 'student' || empty($user->role))
                ? \App\Services\StudentNotificationService::getForUser($user)
                : ['items' => [], 'unread_count' => 0],
            'enrolled_courses_count' => fn () => $user && ($user->role === 'student' || empty($user->role))
                ? (function () use ($user) {
                    try {
                        return \App\Models\Enrollment::where('student_id', $user->id)
                            ->whereIn('status', ['active', 'completed'])
                            ->count();
                    } catch (\Throwable $e) {
                        return 0;
                    }
                })()
                : 0,
            'new_enrollments_count' => fn () => $user && ($user->role === 'student' || empty($user->role))
                ? \Illuminate\Support\Facades\Cache::remember("student.{$user->id}.new_enrollments", 30, function () use ($user) {
                    try {
                        if (!\Illuminate\Support\Facades\Schema::hasColumn('enrollments', 'seen_at')) {
                            return 0;
                        }
                        return \App\Models\Enrollment::where('student_id', $user->id)
                            ->where('status', 'active')
                            ->whereNull('seen_at')
                            ->count();
                    } catch (\Throwable $e) {
                        return 0;
                    }
                })
                : 0,
            'telegram' => [
                'bot_username' => config('services.telegram.bot_username') ?: 'elms_verification_bot',
                'bot_id' => $botId ?? '9048704504',
                'is_configured' => true,
            ],
            'clerk' => [
                'publishable_key' => config('services.clerk.publishable_key') ?? env('VITE_CLERK_PUBLISHABLE_KEY'),
                'app_id' => config('services.clerk.app_id') ?? env('CLERK_APP_ID', 'app_3HuqsrwyUIBYDv90aKgOiwfsKkx'),
                'is_configured' => !empty(config('services.clerk.publishable_key') ?? env('VITE_CLERK_PUBLISHABLE_KEY')),
            ],
            'flash' => [
                'success'           => fn () => $request->session()->get('success'),
                'error'             => fn () => $request->session()->get('error'),
                'info'              => fn () => $request->session()->get('info'),
                'warning'           => fn () => $request->session()->get('warning'),
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
