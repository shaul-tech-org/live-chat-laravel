<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Mongo\Message;
use Illuminate\Http\Response;

class MetricsController extends Controller
{
    private float $appStartTime;

    public function __construct()
    {
        $this->appStartTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
    }

    public function index(): Response
    {
        $lines = [];

        // lchat_rooms_total — total chat rooms
        $roomsTotal = ChatRoom::count();
        $lines[] = '# HELP lchat_rooms_total Total number of chat rooms';
        $lines[] = '# TYPE lchat_rooms_total gauge';
        $lines[] = "lchat_rooms_total {$roomsTotal}";

        // lchat_messages_total — total messages (MongoDB)
        $messagesTotal = Message::count();
        $lines[] = '# HELP lchat_messages_total Total number of messages';
        $lines[] = '# TYPE lchat_messages_total gauge';
        $lines[] = "lchat_messages_total {$messagesTotal}";

        // lchat_agents_online — online agents count
        $agentsOnline = Agent::where('is_online', true)->count();
        $lines[] = '# HELP lchat_agents_online Number of currently online agents';
        $lines[] = '# TYPE lchat_agents_online gauge';
        $lines[] = "lchat_agents_online {$agentsOnline}";

        // lchat_uptime_seconds — app uptime
        $uptime = round(microtime(true) - $this->appStartTime, 2);
        $lines[] = '# HELP lchat_uptime_seconds Application uptime in seconds';
        $lines[] = '# TYPE lchat_uptime_seconds gauge';
        $lines[] = "lchat_uptime_seconds {$uptime}";

        $body = implode("\n", $lines) . "\n";

        return response($body, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
