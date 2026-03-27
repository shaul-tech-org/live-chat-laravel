<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        $faqEntries = FaqEntry::orderByDesc('created_at')->paginate(20);

        return response()->json($faqEntries);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|uuid',
            'keyword' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq = FaqEntry::create($validated);

        return response()->json($faq, 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $faq = FaqEntry::find($id);

        if (!$faq) {
            return response()->json(['message' => 'FAQ를 찾을 수 없습니다.'], 404);
        }

        $faq->delete();

        return response()->json(['message' => 'FAQ가 삭제되었습니다.']);
    }
}
