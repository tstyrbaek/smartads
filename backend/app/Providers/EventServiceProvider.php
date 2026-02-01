<?php

namespace App\Providers;

use App\Events\AdUpdated;
use App\Listeners\SendAdCompletionEmail;
use App\Listeners\TestAdCompletionEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Events are auto-discovered from app/Listeners
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
