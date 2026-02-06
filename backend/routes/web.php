<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\AdController as AdminAdController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPlanController as AdminSubscriptionPlanController;
use App\Http\Controllers\Admin\CompanySubscriptionController as AdminCompanySubscriptionController;
use App\Http\Controllers\Admin\NotificationCampaignController as AdminNotificationCampaignController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySelectionController;
use App\Http\Controllers\CronQueueController;
use App\Models\Ad;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', function () {
        return view('dashboard', [
            'usersCount' => User::query()->count(),
            'companiesCount' => Company::query()->count(),
            'adsCount' => Ad::query()->count(),
        ]);
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/select-company', [CompanySelectionController::class, 'show'])->name('company.select');
        Route::post('/select-company', [CompanySelectionController::class, 'store'])->name('company.select.store');

        Route::middleware('active.company')->group(function () {
            Route::get('/company', [CompanyController::class, 'edit'])->name('company.edit');
            Route::patch('/company', [CompanyController::class, 'update'])->name('company.update');

            Route::get('/brand', fn () => redirect()->route('company.edit'))->name('brand.edit');
            Route::patch('/brand', fn () => redirect()->route('company.edit'))->name('brand.update');
        });
    });

    Route::middleware(['auth', 'admin'])->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/companies', [AdminCompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [AdminCompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [AdminCompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}/edit', [AdminCompanyController::class, 'edit'])->name('companies.edit');
        Route::patch('/companies/{company}', [AdminCompanyController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{company}', [AdminCompanyController::class, 'destroy'])->name('companies.destroy');

        // Company subscriptions
        Route::get('/companies/{company}/subscriptions/create', [AdminCompanySubscriptionController::class, 'create'])->name('companies.subscriptions.create');
        Route::post('/companies/{company}/subscriptions', [AdminCompanySubscriptionController::class, 'store'])->name('companies.subscriptions.store');

        Route::get('/ads', [AdminAdController::class, 'index'])->name('ads.index');

        // Subscription Plans
        Route::get('/subscription-plans', [AdminSubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
        Route::get('/subscription-plans/create', [AdminSubscriptionPlanController::class, 'create'])->name('subscription-plans.create');
        Route::post('/subscription-plans', [AdminSubscriptionPlanController::class, 'store'])->name('subscription-plans.store');
        Route::get('/subscription-plans/{subscriptionPlan}/edit', [AdminSubscriptionPlanController::class, 'edit'])->name('subscription-plans.edit');
        Route::patch('/subscription-plans/{subscriptionPlan}', [AdminSubscriptionPlanController::class, 'update'])->name('subscription-plans.update');
        Route::delete('/subscription-plans/{subscriptionPlan}', [AdminSubscriptionPlanController::class, 'destroy'])->name('subscription-plans.destroy');
        Route::patch('/subscription-plans/{subscriptionPlan}/toggle', [AdminSubscriptionPlanController::class, 'toggleActive'])->name('subscription-plans.toggle');

        // Subscriptions
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/create', [AdminSubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('/subscriptions', [AdminSubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::get('/subscriptions/{subscription}/edit', [AdminSubscriptionController::class, 'edit'])->name('subscriptions.edit');
        Route::patch('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::delete('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
        Route::post('/subscriptions/{subscription}/renew', [AdminSubscriptionController::class, 'renew'])->name('subscriptions.renew');

        // Notifications
        Route::get('/notifications', [AdminNotificationCampaignController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/create', [AdminNotificationCampaignController::class, 'create'])->name('notifications.create');
        Route::post('/notifications', [AdminNotificationCampaignController::class, 'store'])->name('notifications.store');
        Route::get('/notifications/{campaign}/edit', [AdminNotificationCampaignController::class, 'edit'])->name('notifications.edit');
        Route::patch('/notifications/{campaign}', [AdminNotificationCampaignController::class, 'update'])->name('notifications.update');
        Route::delete('/notifications/{campaign}', [AdminNotificationCampaignController::class, 'destroy'])->name('notifications.destroy');

        Route::get('/brands', fn () => redirect()->route('admin.companies.index'))->name('brands.index');
        Route::get('/brands/create', fn () => redirect()->route('admin.companies.create'))->name('brands.create');
        Route::post('/brands', fn () => redirect()->route('admin.companies.index'))->name('brands.store');
        Route::get('/brands/{brand}/edit', fn () => redirect()->route('admin.companies.index'))->name('brands.edit');
        Route::patch('/brands/{brand}', fn () => redirect()->route('admin.companies.index'))->name('brands.update');
        Route::delete('/brands/{brand}', fn () => redirect()->route('admin.companies.index'))->name('brands.destroy');
    });

    require __DIR__ . '/auth.php';
});

Route::get('/cron/queue', [CronQueueController::class, 'run']);

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::get('/{any}', function () {
    return response()->file(public_path('index.html'));
})->where('any', '^(?!api|storage|sanctum|admin).*$');
