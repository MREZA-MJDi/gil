<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Category::query()->withCount('products')->ordered()->get()]);
    }

    public function store(StoreCategoryRequest $request, CategoryService $categories): JsonResponse
    {
        return response()->json(['data' => $categories->create($request->validated(), $request->user()->id)], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category, CategoryService $categories): JsonResponse
    {
        return response()->json(['data' => $categories->update($category->id, $request->validated(), $request->user()->id)]);
    }

    public function destroy(Category $category, CategoryService $categories, Request $request): JsonResponse
    {
        $categories->delete($category->id, $request->user()->id);
        return response()->json(['message' => 'دسته‌بندی حذف شد.']);
    }
}
