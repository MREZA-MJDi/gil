<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
