<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(): JsonResponse
    {
        $agents = Agent::orderByDesc('created_at')->paginate(20);

        return response()->json($agents);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|uuid',
            'user_id' => 'required|string|max:255',
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'role' => 'required|string|in:admin,agent',
        ]);

        $agent = Agent::create($validated);

        return response()->json($agent, 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return response()->json(['message' => '상담원을 찾을 수 없습니다.'], 404);
        }

        $agent->delete();

        return response()->json(['message' => '상담원이 삭제되었습니다.']);
    }
}
