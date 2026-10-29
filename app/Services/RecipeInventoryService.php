<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientInventoryMovement;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecipeInventoryService
{
    /**
     * Persist the recipe at the time of sale and consume ingredients in the same order
     * transaction. Thousandths are used internally to avoid floating point drift.
     *
     * @param array<int, int|string> $requestedQuantities product ID => quantity
     */
    public function consumeForOrder(Order $order, array $requestedQuantities, ?int $userId): void
    {
        $productIds = array_values(array_map('intval', array_keys($requestedQuantities)));
        if ($productIds === []) return;

        $rows = DB::table('product_recipe_ingredients as recipe')
            ->join('ingredients as ingredient', 'ingredient.id', '=', 'recipe.ingredient_id')
            ->whereIn('recipe.product_id', $productIds)
            ->orderBy('recipe.ingredient_id')
            ->orderBy('recipe.product_id')
            ->get([
                'recipe.product_id', 'recipe.ingredient_id', 'recipe.quantity',
                'ingredient.name', 'ingredient.unit', 'ingredient.cost_per_unit', 'ingredient.is_active',
            ]);

        if ($rows->isEmpty()) return;

        $byProduct = $rows->groupBy('product_id');
        $neededMilli = [];
        foreach ($byProduct as $productId => $components) {
            foreach ($components as $component) {
                if (!(bool) $component->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "ماده اولیه «{$component->name}» غیرفعال است؛ پیش از ثبت سفارش، رسپی را اصلاح کن.",
                    ]);
                }
                $perItemMilli = $this->toMilli($component->quantity);
                $ingredientId = (int) $component->ingredient_id;
                $neededMilli[$ingredientId] = ($neededMilli[$ingredientId] ?? 0)
                    + $perItemMilli * (int) ($requestedQuantities[(int) $productId] ?? 0);
            }
        }

        $ingredientIds = array_keys($neededMilli);
        sort($ingredientIds, SORT_NUMERIC);
        $ingredients = Ingredient::query()->whereIn('id', $ingredientIds)
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        if ($ingredients->count() !== count($ingredientIds)) {
            throw ValidationException::withMessages(['items' => 'یکی از مواد اولیه رسپی دیگر در دسترس نیست.']);
        }

        foreach ($ingredientIds as $ingredientId) {
            $ingredient = $ingredients->get($ingredientId);
            $beforeMilli = $this->toMilli($ingredient->stock_quantity);
            $requiredMilli = $neededMilli[$ingredientId];
            if ($beforeMilli < $requiredMilli) {
                throw ValidationException::withMessages([
                    'items' => "موجودی ماده اولیه «{$ingredient->name}» کافی نیست؛ موجودی فعلی "
                        .$this->fromMilli($beforeMilli).' '.$ingredient->unit.' و نیاز سفارش '
                        .$this->fromMilli($requiredMilli).' '.$ingredient->unit.' است.',
                ]);
            }
        }

        foreach ($order->items()->get() as $item) {
            $snapshot = ($byProduct[(string) $item->product_id] ?? collect())->map(fn ($component) => [
                'ingredient_id' => (int) $component->ingredient_id,
                'name' => (string) $component->name,
                'unit' => (string) $component->unit,
                'quantity' => $this->fromMilli($this->toMilli($component->quantity)),
                'cost_per_unit' => (int) $component->cost_per_unit,
            ])->values()->all();

            if ($snapshot !== []) {
                DB::table('order_items')->where('id', $item->id)->update([
                    'recipe_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                ]);
            }
        }

        foreach ($ingredientIds as $ingredientId) {
            $ingredient = $ingredients->get($ingredientId);
            $beforeMilli = $this->toMilli($ingredient->stock_quantity);
            $afterMilli = $beforeMilli - $neededMilli[$ingredientId];
            $ingredient->forceFill(['stock_quantity' => $this->fromMilli($afterMilli)])->save();

            IngredientInventoryMovement::query()->create([
                'ingredient_id' => $ingredientId,
                'order_id' => $order->id,
                'user_id' => $userId,
                'movement_type' => 'order',
                'quantity_delta' => $this->fromMilli(-$neededMilli[$ingredientId]),
                'stock_before' => $this->fromMilli($beforeMilli),
                'stock_after' => $this->fromMilli($afterMilli),
                'reason' => 'مصرف رسپی برای سفارش '.$order->number,
            ]);
        }
    }

    public function restoreForCancelledOrder(Order $order, ?int $userId): void
    {
        $neededMilli = [];
        foreach ($order->items as $item) {
            foreach (($item->recipe_snapshot ?? []) as $component) {
                $ingredientId = (int) ($component['ingredient_id'] ?? 0);
                if ($ingredientId < 1) continue;

                $perItemMilli = $this->toMilli($component['quantity'] ?? 0);
                $neededMilli[$ingredientId] = ($neededMilli[$ingredientId] ?? 0)
                    + $perItemMilli * (int) $item->quantity;
            }
        }

        if ($neededMilli === []) return;

        $ingredientIds = array_keys($neededMilli);
        sort($ingredientIds, SORT_NUMERIC);
        $ingredients = Ingredient::query()->whereIn('id', $ingredientIds)
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($ingredientIds as $ingredientId) {
            $ingredient = $ingredients->get($ingredientId);
            if (!$ingredient) continue;

            $beforeMilli = $this->toMilli($ingredient->stock_quantity);
            $afterMilli = $beforeMilli + $neededMilli[$ingredientId];
            $ingredient->forceFill(['stock_quantity' => $this->fromMilli($afterMilli)])->save();

            IngredientInventoryMovement::query()->create([
                'ingredient_id' => $ingredientId,
                'order_id' => $order->id,
                'user_id' => $userId,
                'movement_type' => 'order_cancelled',
                'quantity_delta' => $this->fromMilli($neededMilli[$ingredientId]),
                'stock_before' => $this->fromMilli($beforeMilli),
                'stock_after' => $this->fromMilli($afterMilli),
                'reason' => 'بازگشت مواد اولیه پس از لغو سفارش '.$order->number,
            ]);
        }
    }

    private function toMilli(mixed $value): int
    {
        return (int) round(((float) $value) * 1000);
    }

    private function fromMilli(int $value): string
    {
        return number_format($value / 1000, 3, '.', '');
    }
}
