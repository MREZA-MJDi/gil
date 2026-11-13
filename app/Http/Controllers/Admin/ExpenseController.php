<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::query()->with('user:id,name')->latest('id');
        if ($request->filled('category')) $query->where('category', $request->string('category')->toString());
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->date('to'));
        return response()->json($query->paginate(min(100, max(10, (int) $request->input('per_page', 25)))));
    }

    public function store(StoreExpenseRequest $request, ExpenseService $expenses): JsonResponse
    {
        return response()->json(['data' => $expenses->create($request->validated(), $request->user()->id)], 201);
    }

    public function update(Request $request, Expense $expense, ExpenseService $expenses): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(['rent','payroll','utilities','ingredients','repairs','packaging','transport','other'])],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'payment_method' => ['required', Rule::in(['cash','card_terminal','online','other'])],
            'is_paid' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json(['data' => $expenses->update($expense->id, $data, $request->user()->id)]);
    }

    public function destroy(Request $request, Expense $expense, ExpenseService $expenses): JsonResponse
    {
        $expenses->deleteDemo($expense->id, $request->user()->id);
        return response()->json(['message' => 'هزینه نمونه حذف شد.']);
    }
}
