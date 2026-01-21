<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('smartadd:create-admin {email} {password} {name?}', function () {
    $email = (string) $this->argument('email');
    $password = (string) $this->argument('password');
    $name = (string) ($this->argument('name') ?? 'Admin');

    $existing = User::query()->where('email', $email)->first();
    if ($existing) {
        $existing->forceFill([
            'name' => $name,
            'password' => Hash::make($password),
            'role' => 'admin',
        ])->save();

        $this->info('updated_user_id=' . $existing->id);
        return 0;
    }

    $user = User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'role' => 'admin',
    ]);

    $this->info('created_user_id=' . $user->id);
    return 0;
})->purpose('Create the initial admin user');
