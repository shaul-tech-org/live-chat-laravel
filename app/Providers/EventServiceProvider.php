<?php

namespace App\Providers;

use App\Events\MessageSent;
use App\Listeners\EmailNotifyOfflineListener;
use App\Listeners\FaqAutoReplyListener;
use App\Listeners\OfflineAutoReplyListener;
use App\Listeners\TelegramNotifyListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        MessageSent::class => [
            FaqAutoReplyListener::class,
            OfflineAutoReplyListener::class,
            TelegramNotifyListener::class,
            EmailNotifyOfflineListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
