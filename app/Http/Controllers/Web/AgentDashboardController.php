<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AgentDashboardController extends Controller
{
    public function index(): View
    {
        return view('agent.dashboard', [
            'adminToken' => session('admin_token', ''),
        ]);
    }
}
