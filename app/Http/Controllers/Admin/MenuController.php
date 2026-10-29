<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with(['category:id,name', 'options' => fn ($q) => $q->where('is_active', true)])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.trim($request->string('q')->toString()).'%'))
            ->orderBy('sort_order')->orderBy('name');

        return response()->json([
            'data' => $query->paginate(min(100, max(10, (int) $request->input('per_page', 50)))),
            'categories' => Category::query()->ordered()->get(['id', 'name', 'sort_order', 'is_active']),
        ]);
    }
}
