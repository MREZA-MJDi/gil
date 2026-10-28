<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(array $data, ?int $userId = null): Category
    {
        return DB::transaction(function () use ($data, $userId) {
            $category = Category::query()->create([
                'name' => $data['name'],
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);
            $this->audit->record('category.created', $category, [], $category->only(['name', 'sort_order', 'is_active']), null, $userId);
            return $category;
        }, attempts: 3);
    }

    public function update(int $id, array $data, ?int $userId = null): Category
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $category = Category::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $old = $category->only(array_keys($data));
            $category->fill($data)->save();
            $this->audit->record('category.updated', $category, $old, $category->only(array_keys($data)), null, $userId);
            return $category;
        }, attempts: 3);
    }

    public function delete(int $id, ?int $userId = null): void
    {
        DB::transaction(function () use ($id, $userId) {
            $category = Category::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($category->products()->exists()) {
                throw ValidationException::withMessages(['category' => 'دسته‌بندی دارای محصول است؛ ابتدا محصولات را منتقل یا غیرفعال کنید.']);
            }
            $this->audit->record('category.deleted', $category, $category->only(['name', 'sort_order', 'is_active']), [], 'category_delete', $userId);
            $category->delete();
        }, attempts: 3);
    }
}
