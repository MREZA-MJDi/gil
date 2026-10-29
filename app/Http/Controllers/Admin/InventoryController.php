<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustInventoryRequest;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function movements(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $query = InventoryMovement::query()
            ->with(['product:id,name,stock_quantity', 'user:id,name', 'order:id,number'])
            ->when(isset($data['product_id']), fn ($q) => $q->where('product_id', $data['product_id']))
            ->when(isset($data['from']), fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
            ->when(isset($data['to']), fn ($q) => $q->whereDate('created_at', '<=', $data['to']))
            ->latest('id');

        return response()->json($query->paginate(min(100, max(10, (int) $request->input('per_page', 50)))));
    }

    public function adjust(AdjustInventoryRequest $request, InventoryService $inventory): JsonResponse
    {
        $data = $request->validated();
        $movement = $inventory->adjust(
            (int) $data['product_id'],
            $data['direction'],
            (int) $data['quantity'],
            $data['reason'],
            $data['idempotency_key'],
            $request->user()->id,
        );

        return response()->json(['data' => $movement], 201);
    }
}
