<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(array $data, ?UploadedFile $image = null, ?int $userId = null): Product
    {
        unset($data['image']);
        $newPath = $image?->store('products', 'public');
        try {
            return DB::transaction(function () use ($data, $newPath, $userId) {
                $optionsProvided = array_key_exists('options', $data);
                $options = $data['options'] ?? [];
                unset($data['options']);
                $attributes = $data;
                $attributes['image_path'] = $newPath;
                $attributes['status'] = $attributes['status'] ?? ProductStatus::Active;
                $attributes['sort_order'] = $attributes['sort_order'] ?? 0;

                $product = Product::query()->create($attributes);
                if ($optionsProvided) $this->syncOptions($product, $options);

                $this->audit->record('product.created', $product, [], [
                    'name' => $product->name, 'base_price' => $product->base_price,
                    'category_id' => $product->category_id, 'status' => $product->status->value,
                ], null, $userId);

                return $product->load(['category', 'options']);
            }, attempts: 3);
        } catch (Throwable $e) {
            if ($newPath) Storage::disk('public')->delete($newPath);
            throw $e;
        }
    }

    public function update(int $productId, array $data, ?UploadedFile $image = null, ?int $userId = null): Product
    {
        unset($data['image']);
        $newPath = $image?->store('products', 'public');
        $oldPath = null;
        try {
            $product = DB::transaction(function () use ($productId, $data, $newPath, &$oldPath, $userId) {
                $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
                $oldValues = $product->only(['name', 'category_id', 'base_price', 'status', 'sort_order', 'stock_quantity', 'low_stock_threshold']);
                $optionsProvided = array_key_exists('options', $data);
                $options = $data['options'] ?? [];
                unset($data['options']);

                if ($newPath !== null) {
                    $oldPath = $product->image_path;
                    $data['image_path'] = $newPath;
                }

                // Only explicitly submitted fields are changed; omitted status/stock/options stay intact.
                $product->fill($data)->save();
                if ($optionsProvided) $this->syncOptions($product, $options);

                $this->audit->record('product.updated', $product, $oldValues, [
                    'name' => $product->name, 'category_id' => $product->category_id,
                    'base_price' => $product->base_price, 'status' => $product->status->value,
                    'sort_order' => $product->sort_order, 'stock_quantity' => $product->stock_quantity,
                ], null, $userId);

                return $product->load(['category', 'options']);
            }, attempts: 3);
        } catch (Throwable $e) {
            if ($newPath) Storage::disk('public')->delete($newPath);
            throw $e;
        }

        if ($oldPath && $oldPath !== $newPath) Storage::disk('public')->delete($oldPath);
        return $product;
    }

    public function setStatus(int $productId, ProductStatus $status, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($productId, $status, $userId) {
            $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
            $old = $product->status->value;
            $product->forceFill(['status' => $status])->save();
            $this->audit->record('product.status_changed', $product, ['status' => $old], ['status' => $status->value], null, $userId);
            return $product;
        }, attempts: 3);
    }

    /**
     * Omitted options means "leave them alone"; an explicit [] means "remove all".
     * Option ids are only allowed to update options belonging to this product.
     */
    private function syncOptions(Product $product, array $options): void
    {
        $keepIds = [];
        foreach ($options as $optionData) {
            $id = isset($optionData['id']) ? (int) $optionData['id'] : null;
            unset($optionData['id']);
            if ($id !== null) {
                $option = ProductOption::query()->where('product_id', $product->id)->whereKey($id)->lockForUpdate()->first();
                if (! $option) throw ValidationException::withMessages(['options' => 'یکی از گزینه‌ها متعلق به این محصول نیست.']);
                $option->fill($optionData)->save();
                $keepIds[] = $option->id;
            } else {
                $option = $product->options()->create($optionData);
                $keepIds[] = $option->id;
            }
        }

        $deleteQuery = $product->options();
        if ($keepIds !== []) $deleteQuery->whereNotIn('id', $keepIds);
        $deleteQuery->delete();
    }
}
