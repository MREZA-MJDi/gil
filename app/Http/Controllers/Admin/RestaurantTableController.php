<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RestaurantTableController extends Controller
{
    public function index(): JsonResponse
    {
        $tables = RestaurantTable::query()
            ->withCount(['orders as open_orders_count' => fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled'])])
            ->with(['orders' => fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled'])->select(['id', 'restaurant_table_id', 'number', 'total', 'payment_status', 'status'])])
            ->orderBy('name')->paginate(100);

        return response()->json($tables);
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:restaurant_tables,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $table = RestaurantTable::query()->create([...$data, 'token' => Str::random(64), 'is_active' => true]);
        $audit->record('table.created', $table, [], ['name' => $table->name, 'is_active' => true], null, $request->user()->id);

        return response()->json(['data' => [
            'id' => $table->id, 'name' => $table->name, 'is_active' => $table->is_active,
            'token' => $table->token, 'menu_url' => $table->menuUrl(),
        ]], 201);
    }

    public function rotateToken(Request $request, RestaurantTable $table, AuditLogger $audit): JsonResponse
    {
        $old = ['token_fingerprint' => hash('sha256', $table->token)];
        $table->forceFill(['token' => Str::random(64)])->save();
        $audit->record('table.qr_rotated', $table, $old, ['token_fingerprint' => hash('sha256', $table->token)], 'QR token rotation', $request->user()->id);

        return response()->json(['data' => [
            'id' => $table->id, 'name' => $table->name, 'token' => $table->token, 'menu_url' => $table->menuUrl(),
        ]]);
    }
}
