<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FeedbackStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CustomerFeedback::query()->with(['order:id,number', 'responder:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->string('kind')->toString()))
            ->latest('id');
        return response()->json($query->paginate(min(100, max(10, (int) $request->input('per_page', 25)))));
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['nullable','integer','exists:orders,id'],
            'customer_name' => ['nullable','string','max:150'],
            'phone' => ['nullable','string','max:30'],
            'email' => ['nullable','email','max:150'],
            'kind' => ['required', Rule::in(['complaint','suggestion','praise'])],
            'rating' => ['nullable','integer','min:1','max:5'],
            'message' => ['required','string','min:3','max:5000'],
        ]);
        $feedback = CustomerFeedback::query()->create([...$data, 'status' => FeedbackStatus::New]);
        $audit->record('feedback.created', $feedback, [], $feedback->only(['order_id','kind','rating','status']), null, $request->user()->id);
        return response()->json(['data' => $feedback], 201);
    }

    public function respond(Request $request, CustomerFeedback $feedback, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(FeedbackStatus::class)],
            'admin_response' => ['nullable','string','max:5000'],
        ]);
        $old = ['status' => $feedback->status->value, 'admin_response' => $feedback->admin_response];
        $feedback->forceFill([
            'status' => $data['status'],
            'admin_response' => $data['admin_response'] ?? null,
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
        ])->save();
        $audit->record('feedback.responded', $feedback, $old, ['status' => $feedback->status->value, 'admin_response' => $feedback->admin_response], null, $request->user()->id);
        return response()->json(['data' => $feedback]);
    }
}
