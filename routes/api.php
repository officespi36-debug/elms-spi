<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

// Telegram Bot Webhooks (Supporting all standard endpoint URL conventions)
Route::match(['get', 'post'], '/telegram/webhook', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'handleWebhook']);
Route::match(['get', 'post'], '/telegram-webhook', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'handleWebhook']);
Route::match(['get', 'post'], '/webhook/telegram', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'handleWebhook']);

// System Diagnostic & Database Migration Endpoint
Route::get('/system/migrate', function () {
    try {
        $path = database_path('database.sqlite');
        if (!file_exists($path)) {
            @touch($path);
        }
        $exitCode = \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return response()->json([
            'status' => 'success',
            'sqlite_file' => file_exists($path),
            'sqlite_writable' => is_writable($path),
            'exitCode' => $exitCode,
            'output' => $output,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

Route::middleware(['web', 'auth'])->prefix('v1')->group(function () {
    // Download package សម្រាប់ offline
    Route::get('/offline/course/{course}', [Api\OfflineController::class, 'package']);
    // Sync ពី offline → server
    Route::post('/sync/progress', [Api\SyncController::class, 'progress']);
    Route::post('/sync/quiz-attempt', [Api\SyncController::class, 'quizAttempt']);
});

Route::middleware('auth:sanctum')->prefix('v1/token')->group(function () {
    Route::post('/sync/progress', [Api\SyncController::class, 'progress']);
    Route::post('/sync/quiz-attempt', [Api\SyncController::class, 'quizAttempt']);
});

// Email OTP Authentication via Resend
Route::post('/auth/email-otp/send', [\App\Http\Controllers\AuthController::class, 'sendEmailOtp']);
Route::post('/auth/email-otp/verify', [\App\Http\Controllers\AuthController::class, 'verifyEmailOtp']);

// Phone OTP Authentication via PlasGate
Route::post('/auth/phone-otp/send', [\App\Http\Controllers\AuthController::class, 'sendPhoneOtp']);
Route::post('/auth/phone-otp/verify', [\App\Http\Controllers\AuthController::class, 'verifyPhoneOtp']);

// ─── Cloudflare Workers AI & AI Gateway Endpoints ───
Route::prefix('ai')->name('api.ai.')->group(function () {
    Route::get('/verify', [Api\CloudflareAIController::class, 'verify'])->name('verify');
    Route::post('/verify', [Api\CloudflareAIController::class, 'verify'])->name('verify.post');
    Route::post('/chat', [Api\CloudflareAIController::class, 'chat'])->name('chat');
    Route::post('/recommendation', [Api\CloudflareAIController::class, 'recommendation'])->name('recommendation');
    Route::post('/generate-quiz', [Api\CloudflareAIController::class, 'generateQuiz'])->name('generate-quiz');
    Route::post('/summarize', [Api\CloudflareAIController::class, 'summarize'])->name('summarize');
    Route::post('/code-review', [Api\CloudflareAIController::class, 'codeReview'])->name('code-review');
    Route::post('/agri-diagnosis', [Api\CloudflareAIController::class, 'agriDiagnosis'])->name('agri-diagnosis');
    Route::post('/english-review', [Api\CloudflareAIController::class, 'englishReview'])->name('english-review');
    Route::post('/social-work-case', [Api\CloudflareAIController::class, 'socialWorkCase'])->name('social-work-case');
});

