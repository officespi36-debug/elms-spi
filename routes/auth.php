<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::post('verify-reset-otp', [PasswordResetLinkController::class, 'verifyOtp'])->name('password.verify_otp');
    Route::post('reset-password', [PasswordResetLinkController::class, 'resetPassword'])->name('password.update');
    Route::post('api/auth/forgot-password', [PasswordResetLinkController::class, 'store'])->name('api.password.email');
    Route::post('api/auth/verify-reset-otp', [PasswordResetLinkController::class, 'verifyOtp'])->name('api.password.verify_otp');
    Route::match(['get', 'post'], 'api/auth/check-user', [AuthController::class, 'checkIdentifier'])->name('api.auth.check-user');
    Route::match(['get', 'post'], 'api/auth/check-identifier', [AuthController::class, 'checkIdentifier'])->name('api.auth.check-identifier');
    Route::post('api/auth/quick-register', [AuthController::class, 'quickRegister'])->name('api.auth.quick-register');
});

// ─── Email OTP Authentication via Resend (Direct Access, No Guest Redirect) ───
Route::match(['get', 'post'], 'auth/email-otp/send', [AuthController::class, 'sendEmailOtp'])->name('auth.email-otp.send');
Route::match(['get', 'post'], 'auth/email-otp/verify', [AuthController::class, 'verifyEmailOtp'])->name('auth.email-otp.verify');
Route::match(['get', 'post'], 'api/auth/email-otp/send', [AuthController::class, 'sendEmailOtp'])->name('api.auth.email-otp.send');
Route::match(['get', 'post'], 'api/auth/email-otp/verify', [AuthController::class, 'verifyEmailOtp'])->name('api.auth.email-otp.verify');

// ─── Phone OTP Authentication via PlasGate SMS (Direct Access) ───
Route::match(['get', 'post'], 'auth/phone-otp/send', [AuthController::class, 'sendPhoneOtp'])->name('auth.phone-otp.send');
Route::match(['get', 'post'], 'auth/phone-otp/verify', [AuthController::class, 'verifyPhoneOtp'])->name('auth.phone-otp.verify');
Route::match(['get', 'post'], 'api/auth/phone-otp/send', [AuthController::class, 'sendPhoneOtp'])->name('api.auth.phone-otp.send');
Route::match(['get', 'post'], 'api/auth/phone-otp/verify', [AuthController::class, 'verifyPhoneOtp'])->name('api.auth.phone-otp.verify');

// ─── Telegram OAuth Widget, QR Code & Direct Redirect Routes ───
Route::post('auth/telegram/qr-init', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'initQrSession'])->name('auth.telegram.qr-init');
Route::get('auth/telegram/qr-status', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'checkQrStatus'])->name('auth.telegram.qr-status');
Route::get('auth/telegram/confirm-sheet', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'showConfirmSheet'])->name('auth.telegram.confirm-sheet');
Route::post('auth/telegram/confirm-sheet/submit', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'submitConfirmSheet'])->name('auth.telegram.confirm-sheet.submit');
Route::post('auth/telegram/confirm-sheet/lookup', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'lookupAccount'])->name('auth.telegram.confirm-sheet.lookup');
Route::match(['get', 'post'], 'auth/telegram', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'handleCallback'])->name('auth.telegram');
Route::get('auth/telegram/callback', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'handleCallback'])->name('auth.telegram.callback');
Route::match(['get', 'post'], 'api/auth/telegram', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'handleCallback'])->name('api.auth.telegram');
Route::get('api/auth/telegram/callback', [\App\Http\Controllers\Auth\TelegramAuthController::class, 'handleCallback'])->name('api.auth.telegram.callback');

// ─── Clerk & Google OAuth Routes ───
Route::get('auth/google/redirect', [\App\Http\Controllers\Auth\GoogleController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::match(['get', 'post'], 'auth/google/callback', [\App\Http\Controllers\Auth\GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::match(['get', 'post'], 'auth/clerk', [\App\Http\Controllers\Auth\ClerkAuthController::class, 'handleCallback'])->name('auth.clerk');
Route::match(['get', 'post'], 'auth/google', [\App\Http\Controllers\Auth\GoogleController::class, 'handleGoogleCallback'])->name('auth.google');
Route::get('auth/clerk/callback', [\App\Http\Controllers\Auth\ClerkAuthController::class, 'handleCallback'])->name('auth.clerk.callback');
Route::match(['get', 'post'], 'api/auth/clerk', [\App\Http\Controllers\Auth\ClerkAuthController::class, 'handleCallback'])->name('api.auth.clerk');

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
