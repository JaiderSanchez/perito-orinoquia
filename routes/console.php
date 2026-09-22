<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:ensure-initial-admin', function (): int {
    $email = trim((string) getenv('INITIAL_ADMIN_EMAIL'));
    $password = (string) getenv('INITIAL_ADMIN_PASSWORD');
    $name = trim((string) (getenv('INITIAL_ADMIN_NAME') ?: 'Administrador'));
    $username = trim((string) (getenv('INITIAL_ADMIN_USERNAME') ?: 'superadmin'));

    if ($email === '' && $password === '') {
        $this->info('Initial administrator provisioning is disabled.');

        return self::SUCCESS;
    }

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('INITIAL_ADMIN_EMAIL must contain a valid email address.');

        return self::FAILURE;
    }

    if (mb_strlen($password) < 12) {
        $this->error('INITIAL_ADMIN_PASSWORD must contain at least 12 characters.');

        return self::FAILURE;
    }

    $user = User::query()->where('email', $email)->first();

    if ($user) {
        $user->forceFill([
            'rol' => 'superadmin',
            'activo' => true,
            'oculto' => true,
        ])->save();

        $this->info('The initial administrator already exists; its password was not changed.');

        return self::SUCCESS;
    }

    User::query()->create([
        'name' => $name,
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'rol' => 'superadmin',
        'activo' => true,
        'oculto' => true,
    ]);

    $this->info('Initial administrator created successfully.');

    return self::SUCCESS;
})->purpose('Create the first production super administrator from environment variables');
