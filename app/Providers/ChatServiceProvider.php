<?php

namespace App\Providers;

use App\Services\BuiltinAuthService;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BuiltinAuthService::class, function () {
            return new BuiltinAuthService(
                config('chat.admin_email', ''),
                config('chat.admin_password', ''),
            );
        });
    }
}
