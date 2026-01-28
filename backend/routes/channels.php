<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('company.{companyId}', function ($user, int $companyId) {
    return $user->companies()->whereKey($companyId)->exists();
});
