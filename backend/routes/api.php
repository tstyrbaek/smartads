<?php

use App\Models\User;
use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\GeminiWebhookController;
use App\Http\Controllers\Api\MailController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\CronQueueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/cron/queue', [CronQueueController::class, 'run']);

Route::post('/webhooks/gemini', [GeminiWebhookController::class, 'handle']);

Route::post('/auth/forgot-password', function (Request $request) {
    Log::info('Forgot password request received', ['email' => $request->email]);
    
    $validated = $request->validate([
        'email' => ['required', 'email'],
    ]);

    $user = User::where('email', $validated['email'])->first();
    
    if ($user) {
        Log::info('User found, sending reset email', ['user_id' => $user->id, 'email' => $user->email]);
        
        // Generate password reset token
        $token = \Illuminate\Support\Str::random(60);
        $user->password_reset_token = $token;
        $user->password_reset_expires_at = now()->addHours(1);
        $user->save();

        // Send reset email using mail service
        try {
            $mailService = app(\App\Services\Mail\MailServiceInterface::class);
            $resetLink = url("/reset-password?token={$token}&email=" . urlencode($user->email));
            
            Log::info('Mail service resolved', ['service' => get_class($mailService)]);
            
            $htmlContent = view('emails.password_reset', [
                'name' => $user->name,
                'reset_link' => $resetLink
            ])->render();
            
            $result = $mailService->sendRaw(
                [$user->email],
                'Nulstil din adgangskode - SmartAds',
                $htmlContent,
                null
            );
            
            Log::info('Password reset email sent', ['result' => $result]);
        } catch (\Exception $e) {
            // Log error but don't reveal to user
            Log::error('Failed to send password reset email: ' . $e->getMessage(), [
                'email' => $user->email,
                'exception' => $e
            ]);
        }
    } else {
        Log::info('User not found', ['email' => $validated['email']]);
    }

    // Always return success to prevent email enumeration
    return response()->json([
        'message' => 'Hvis emailen findes i vores system, vil du modtage et reset link.'
    ]);
});

Route::post('/auth/reset-password', function (Request $request) {
    $validated = $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $user = User::where('email', $validated['email'])
        ->where('password_reset_token', $validated['token'])
        ->where('password_reset_expires_at', '>', now())
        ->first();

    if (!$user) {
        return response()->json([
            'error' => 'Ugyldigt eller udløbet reset link.'
        ], 400);
    }

    // Update password and clear reset token
    $user->password = Hash::make($validated['password']);
    $user->password_reset_token = null;
    $user->password_reset_expires_at = null;
    $user->save();

    return response()->json([
        'message' => 'Adgangskoden er blevet nulstillet.'
    ]);
});

Route::post('/auth/login', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
        'device_name' => ['nullable', 'string'],
    ]);

    $user = User::query()->where('email', $validated['email'])->first();
    if (!$user || !Hash::check((string) $validated['password'], (string) $user->password)) {
        return response()->json(['error' => 'invalid_credentials'], 401);
    }

    $tokenName = (string) ($validated['device_name'] ?? 'api');

    return response()->json([
        'token' => $user->createToken($tokenName)->plainTextToken,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', function (Request $request) {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['ok' => true]);
    });

    // Mail API routes
    Route::prefix('mail')->group(function () {
        Route::post('/send', [MailController::class, 'send']);
        Route::post('/send-raw', [MailController::class, 'sendRaw']);
    });

    Route::get('/me', function (Request $request) {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $companies = $user->companies()->with('brand')->orderBy('name')->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo_path' => $company->brand?->logo_path ? Illuminate\Support\Facades\Storage::url($company->brand->logo_path) : null,
                ];
            }),
        ]);
    });

    Route::put('/profile', function (Request $request) {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ])->save();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    });

    Route::put('/profile/password', function (Request $request) {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check((string) $validated['current_password'], (string) $user->password)) {
            return response()->json(['error' => 'invalid_current_password'], 422);
        }

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
        ])->save();

        return response()->json(['ok' => true]);
    });

    Route::middleware('api.company')->group(function () {
        Route::get('/brand', [BrandController::class, 'show']);
        Route::post('/brand', [BrandController::class, 'store']);

        Route::get('/ads', [AdController::class, 'index']);
        Route::post('/ads', [AdController::class, 'store']);
        Route::get('/ads/{id}', [AdController::class, 'show']);
        Route::get('/ads/{id}/image', [AdController::class, 'image']);
        Route::get('/ads/{id}/download', [AdController::class, 'download']);
        Route::delete('/ads/{id}', [AdController::class, 'destroy']);

        // Subscription routes
        Route::get('/subscription', [SubscriptionController::class, 'index']);
        Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);
        Route::get('/subscription/usage', [SubscriptionController::class, 'usage']);
        Route::get('/tokens/summary', [SubscriptionController::class, 'tokensSummary']);
        Route::post('/subscription/check-tokens', [SubscriptionController::class, 'checkTokens']);
        
        Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
        Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade']);
        Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    });
});
