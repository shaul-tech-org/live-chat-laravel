<?php

namespace App\Providers;

use App\Repositories\Contracts\AgentRepositoryInterface;
use App\Repositories\Contracts\FaqRepositoryInterface;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\ReactionRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\WidgetEventRepositoryInterface;
use App\Repositories\Eloquent\AgentRepository;
use App\Repositories\Eloquent\FaqRepository;
use App\Repositories\Eloquent\FeedbackRepository;
use App\Repositories\Eloquent\MessageRepository;
use App\Repositories\Eloquent\ReactionRepository;
use App\Repositories\Eloquent\RoomRepository;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Eloquent\WidgetEventRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoomRepositoryInterface::class, RoomRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(AgentRepositoryInterface::class, AgentRepository::class);
        $this->app->bind(FeedbackRepositoryInterface::class, FeedbackRepository::class);
        $this->app->bind(FaqRepositoryInterface::class, FaqRepository::class);
        $this->app->bind(ReactionRepositoryInterface::class, ReactionRepository::class);
        $this->app->bind(WidgetEventRepositoryInterface::class, WidgetEventRepository::class);
    }
}
