<?php

namespace App\Providers;

use App\Models\Brand;
use App\Policies\BrandPolicy;
use App\Services\Mail\MailServiceInterface;
use App\Services\Mail\LocalMailService;
use App\Services\Mail\MailJetService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MailServiceInterface::class, function ($app) {
            $service = config('mail.default_service', 'local');
            
            Log::info("Mail service selected: {$service}");
            
            return match($service) {
                'local' => new LocalMailService(),
                'mailjet' => new MailJetService(
                    config('mail.services.mailjet.api_key'),
                    config('mail.services.mailjet.secret_key')
                ),
                default => throw new \Exception("Unknown mail service: {$service}")
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Brand::class, BrandPolicy::class);
    }
}
