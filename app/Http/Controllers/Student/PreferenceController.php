<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    /**
     * Update user preferences (sidebar collapse, theme, locale, notification_prefs, etc.).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'sidebar_collapsed'                  => ['nullable', 'boolean'],
            'theme'                              => ['nullable', 'string', 'in:light,dark,system'],
            'locale'                             => ['nullable', 'string', 'in:km,en'],
            'courses_view'                       => ['nullable', 'string', 'in:grid,list'],
            'offline_course_ids'                 => ['nullable', 'array'],
            'offline_course_ids.*'               => ['integer'],
            'email_notifications'                => ['nullable', 'boolean'],
            'app_notifications'                  => ['nullable', 'boolean'],
            'notification_prefs'                 => ['nullable', 'array'],
            'notification_prefs.email'           => ['nullable', 'boolean'],
            'notification_prefs.sms'             => ['nullable', 'boolean'],
            'notification_prefs.push'            => ['nullable', 'boolean'],
            'notification_prefs.systemAlerts'    => ['nullable', 'boolean'],
            'notification_prefs.teacherAlerts'   => ['nullable', 'boolean'],
            'notification_prefs.assignmentAlerts'=> ['nullable', 'boolean'],
            'notification_prefs.examAlerts'      => ['nullable', 'boolean'],
        ]);

        $currentPreferences = $user->preferences ?? [];
        if (!is_array($currentPreferences)) {
            $currentPreferences = [];
        }

        $scalarKeys = [
            'sidebar_collapsed',
            'theme',
            'locale',
            'courses_view',
            'offline_course_ids',
            'email_notifications',
            'app_notifications',
        ];

        foreach ($scalarKeys as $key) {
            if (array_key_exists($key, $validated)) {
                $currentPreferences[$key] = $validated[$key];
            }
        }

        if (array_key_exists('notification_prefs', $validated) && is_array($validated['notification_prefs'])) {
            $existingNotifPrefs = is_array($currentPreferences['notification_prefs'] ?? null)
                ? $currentPreferences['notification_prefs']
                : [];
            $currentPreferences['notification_prefs'] = array_merge($existingNotifPrefs, $validated['notification_prefs']);

            if (array_key_exists('email', $validated['notification_prefs'])) {
                $currentPreferences['email_notifications'] = (bool) $validated['notification_prefs']['email'];
            }
            if (array_key_exists('push', $validated['notification_prefs'])) {
                $currentPreferences['app_notifications'] = (bool) $validated['notification_prefs']['push'];
            }
        }

        $user->preferences = $currentPreferences;
        $user->save();

        if (isset($validated['locale']) && $request->hasSession()) {
            $request->session()->put('locale', $validated['locale']);
            app()->setLocale($validated['locale']);
        }

        return response()->json([
            'success'     => true,
            'preferences' => $user->preferences,
        ]);
    }
}
