<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Services\RestaurantTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantTableController extends Controller
{
    public function index(): JsonResponse
    {
        $tables = RestaurantTable::query()
            ->withCount(['orders as open_orders_count' => fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled'])])
            ->with(['orders' => fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled'])->select(['id', 'restaurant_table_id', 'number', 'total', 'payment_status', 'status'])])
            ->orderBy('name')->paginate(100);

        $tables->getCollection()->transform(function (RestaurantTable $table) {
            return [
                'id' => $table->id,
                'name' => $table->name,
                'description' => $table->description,
                'is_active' => $table->is_active,
                'open_orders_count' => $table->open_orders_count,
                'orders' => $table->orders,
                'menu_url' => $table->menuUrl(),
            ];
        });

        return response()->json($tables);
    }

    public function store(Request $request, RestaurantTableService $tables): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:restaurant_tables,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $table = $tables->create($data, $request->user()->id);

        return response()->json(['data' => [
            'id' => $table->id, 'name' => $table->name, 'is_active' => $table->is_active,
            'token' => $table->token, 'menu_url' => $table->menuUrl(),
        ]], 201);
    }

    public function update(Request $request, RestaurantTable $table, RestaurantTableService $tables): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:80', Rule::unique('restaurant_tables', 'name')->ignore($table->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);
        abort_if($data === [], 422, 'هیچ تغییری ارسال نشده است.');

        return response()->json(['data' => $tables->update($table->id, $data, $request->user()->id)]);
    }

    public function rotateToken(Request $request, RestaurantTable $table, RestaurantTableService $tables): JsonResponse
    {
        $updated = $tables->rotateToken($table->id, $request->user()->id);
        return response()->json(['data' => [
            'id' => $updated->id, 'name' => $updated->name,
            'token' => $updated->token, 'menu_url' => $updated->menuUrl(),
        ]]);
    }
}
