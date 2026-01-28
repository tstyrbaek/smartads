<?php

use App\Models\User;
use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\GeminiWebhookController;
use App\Http\Controllers\CronQueueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/cron/queue', [CronQueueController::class, 'run']);

Route::post('/webhooks/gemini', [GeminiWebhookController::class, 'handle']);

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
    });
});
