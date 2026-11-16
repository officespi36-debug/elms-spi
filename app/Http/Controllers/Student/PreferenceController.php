<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    /**
     * Update user preferences (sidebar collapse, theme, locale).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'sidebar_collapsed' => ['nullable', 'boolean'],
            'theme'             => ['nullable', 'string', 'in:light,dark,system'],
            'locale'            => ['nullable', 'string', 'in:km,en'],
            'courses_view'      => ['nullable', 'string', 'in:grid,list'],
        ]);

        $currentPreferences = $user->preferences ?? [];
        if (!is_array($currentPreferences)) {
            $currentPreferences = [];
        }

        // Whitelist merge
        foreach (['sidebar_collapsed', 'theme', 'locale', 'courses_view'] as $key) {
            if (array_key_exists($key, $validated)) {
                $currentPreferences[$key] = $validated[$key];
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
