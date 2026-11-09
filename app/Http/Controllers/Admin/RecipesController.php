<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientInventoryMovement;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecipesController extends Controller
{
    public function index(): JsonResponse
    {
        $ingredients = Ingredient::query()->orderBy('name')->limit(500)->get([
            'id', 'name', 'unit', 'stock_quantity', 'low_stock_threshold', 'cost_per_unit', 'is_active',
        ]);
        $products = Product::query()
            ->with(['category:id,name', 'recipeIngredients:id,name,unit,cost_per_unit,is_active'])
            ->orderBy('name')->limit(200)->get(['id', 'category_id', 'name', 'base_price', 'status']);

        return response()->json(['data' => ['ingredients' => $ingredients, 'products' => $products]]);
    }

    public function storeIngredient(Request $request, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:ingredients,name'],
            'unit' => ['required', Rule::in(['g', 'ml', 'pcs'])],
            'stock_quantity' => ['required', 'numeric', 'min:0', 'max:99999999999.999'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0', 'max:99999999999.999'],
            'cost_per_unit' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $ingredient = DB::transaction(function () use ($data, $request, $audit) {
            $ingredient = Ingredient::query()->create([
                ...$data,
                'stock_quantity' => number_format((float) $data['stock_quantity'], 3, '.', ''),
                'low_stock_threshold' => isset($data['low_stock_threshold'])
                    ? number_format((float) $data['low_stock_threshold'], 3, '.', '') : null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if ((float) $ingredient->stock_quantity > 0) {
                IngredientInventoryMovement::query()->create([
                    'ingredient_id' => $ingredient->id,
                    'user_id' => $request->user()->id,
                    'movement_type' => 'opening',
                    'quantity_delta' => $ingredient->stock_quantity,
                    'stock_before' => '0.000',
                    'stock_after' => $ingredient->stock_quantity,
                    'reason' => 'ثبت موجودی اولیه ماده اولیه',
                ]);
            }

            $audit->record('ingredient.created', $ingredient, [], [
                'name' => $ingredient->name, 'unit' => $ingredient->unit,
                'stock_quantity' => $ingredient->stock_quantity, 'cost_per_unit' => $ingredient->cost_per_unit,
            ], null, $request->user()->id);

            return $ingredient;
        }, attempts: 3);

        return response()->json(['data' => $ingredient], 201);
    }

    public function updateIngredient(Request $request, Ingredient $ingredient, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('ingredients', 'name')->ignore($ingredient->id)],
            'unit' => ['required', Rule::in(['g', 'ml', 'pcs'])],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0', 'max:99999999999.999'],
            'cost_per_unit' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $updated = DB::transaction(function () use ($data, $ingredient, $request, $audit) {
            $locked = Ingredient::query()->whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
            if ($locked->unit !== $data['unit'] && ($locked->products()->exists() || $locked->movements()->exists())) {
                throw ValidationException::withMessages([
                    'unit' => 'واحد ماده‌ای که در رسپی یا سوابق انبار استفاده شده قابل تغییر نیست؛ ماده جدید بساز و رسپی را اصلاح کن.',
                ]);
            }

            $before = $locked->only(['name', 'unit', 'low_stock_threshold', 'cost_per_unit', 'is_active']);
            $locked->fill([
                ...$data,
                'low_stock_threshold' => isset($data['low_stock_threshold'])
                    ? number_format((float) $data['low_stock_threshold'], 3, '.', '') : null,
            ])->save();

            $audit->record('ingredient.updated', $locked, $before, [
                'name' => $locked->name, 'unit' => $locked->unit,
                'low_stock_threshold' => $locked->low_stock_threshold,
                'cost_per_unit' => $locked->cost_per_unit, 'is_active' => $locked->is_active,
            ], null, $request->user()->id);

            return $locked;
        }, attempts: 3);

        return response()->json(['data' => $updated]);
    }

    public function adjustIngredient(Request $request, Ingredient $ingredient, AuditLogger $audit): JsonResponse
    {
        $request->merge([
            'idempotency_key' => trim((string) $request->header('Idempotency-Key', $request->input('idempotency_key'))),
        ]);
        $data = $request->validate([
            'direction' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999999999.999'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $canonical = [
            'ingredient_id' => $ingredient->id, 'direction' => $data['direction'],
            'quantity' => number_format((float) $data['quantity'], 3, '.', ''), 'reason' => trim($data['reason']),
        ];
        $hash = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));

        $movement = DB::transaction(function () use ($data, $ingredient, $canonical, $hash, $request, $audit) {
            $existing = IngredientInventoryMovement::query()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if (!hash_equals((string) $existing->idempotency_hash, $hash)) {
                    throw ValidationException::withMessages(['idempotency_key' => 'این کلید قبلاً برای حرکت انبار دیگری استفاده شده است.']);
                }
                return $existing;
            }

            $locked = Ingredient::query()->whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
            $quantityMilli = (int) round((float) $canonical['quantity'] * 1000);
            $beforeMilli = (int) round((float) $locked->stock_quantity * 1000);
            $deltaMilli = $data['direction'] === 'in' ? $quantityMilli : -$quantityMilli;
            $afterMilli = $beforeMilli + $deltaMilli;

            if ($afterMilli < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'موجودی کافی نیست. موجودی فعلی: '.$locked->stock_quantity.' '.$locked->unit,
                ]);
            }

            $before = number_format($beforeMilli / 1000, 3, '.', '');
            $after = number_format($afterMilli / 1000, 3, '.', '');
            $locked->forceFill(['stock_quantity' => $after])->save();

            $movement = IngredientInventoryMovement::query()->create([
                'ingredient_id' => $locked->id, 'user_id' => $request->user()->id,
                'movement_type' => $data['direction'] === 'in' ? 'inbound' : 'outbound',
                'quantity_delta' => number_format($deltaMilli / 1000, 3, '.', ''),
                'stock_before' => $before, 'stock_after' => $after,
                'reason' => trim($data['reason']), 'idempotency_key' => $data['idempotency_key'],
                'idempotency_hash' => $hash,
            ]);

            $audit->record('ingredient.stock_adjusted', $locked, ['stock_quantity' => $before], [
                'stock_quantity' => $after, 'movement_type' => $movement->movement_type,
                'quantity_delta' => $movement->quantity_delta,
            ], $movement->reason, $request->user()->id);

            return $movement;
        }, attempts: 3);

        return response()->json(['data' => $movement->load('ingredient:id,name,unit,stock_quantity')], $movement->wasRecentlyCreated ? 201 : 200);
    }

    public function saveRecipe(Request $request, Product $product, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'ingredients' => ['present', 'array', 'max:100'],
            'ingredients.*.ingredient_id' => ['required', 'integer', 'distinct', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ]);

        $updated = DB::transaction(function () use ($data, $product, $request, $audit) {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $ids = collect($data['ingredients'])->pluck('ingredient_id')->map(fn ($id) => (int) $id)->unique()->sort()->values();
            $lockedIngredients = $ids->isEmpty() ? collect() : Ingredient::query()
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($ids as $id) {
                $ingredient = $lockedIngredients->get($id);
                if (!$ingredient || !$ingredient->is_active) {
                    throw ValidationException::withMessages(['ingredients' => 'همه مواد اولیه رسپی باید فعال باشند.']);
                }
            }

            $before = $lockedProduct->recipeIngredients()->get(['ingredients.id', 'ingredients.name'])
                ->map(fn ($item) => ['ingredient_id' => $item->id, 'name' => $item->name, 'quantity' => $item->pivot->quantity])
                ->values()->all();

            $sync = [];
            foreach ($data['ingredients'] as $line) {
                $sync[(int) $line['ingredient_id']] = [
                    'quantity' => number_format((float) $line['quantity'], 3, '.', ''),
                ];
            }
            $lockedProduct->recipeIngredients()->sync($sync);

            $updatedProduct = $lockedProduct->load([
                'recipeIngredients:id,name,unit,cost_per_unit,is_active', 'category:id,name',
            ]);
            $audit->record('product.recipe_updated', $lockedProduct, ['ingredients' => $before], [
                'ingredients' => collect($data['ingredients'])->map(fn ($line) => [
                    'ingredient_id' => (int) $line['ingredient_id'],
                    'quantity' => number_format((float) $line['quantity'], 3, '.', ''),
                ])->values()->all(),
            ], null, $request->user()->id);

            return $updatedProduct;
        }, attempts: 3);

        return response()->json(['data' => $updated]);
    }
}
