<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with(['category:id,name', 'options' => fn ($q) => $q->orderBy('sort_order')])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.trim($request->string('q')->toString()).'%'))
            ->orderBy('sort_order')->orderBy('name');

        return response()->json($query->paginate(min(100, max(10, (int) $request->input('per_page', 50)))));
    }

    public function store(StoreProductRequest $request, ProductService $products): JsonResponse
    {
        $data = $request->validated();
        $image = $data['image'] ?? null;
        unset($data['image']);
        $product = $products->create($data, $image, $request->user()->id);

        return response()->json(['data' => $product], 201);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $products): JsonResponse
    {
        $data = $request->validated();
        $image = $data['image'] ?? null;
        unset($data['image']);
        return response()->json(['data' => $products->update($product->id, $data, $image, $request->user()->id)]);
    }

    public function status(Request $request, Product $product, ProductService $products): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:active,inactive,unavailable']]);
        $updated = $products->setStatus($product->id, ProductStatus::from($data['status']), $request->user()->id);
        return response()->json(['data' => $updated]);
    }

    public function destroy(Product $product, AuditLogger $audit, Request $request): JsonResponse
    {
        // Orders preserve product snapshots and nullable product_id; deletion is allowed only when no audit-worthy stock activity exists.
        abort_if($product->options()->exists(), 409, 'ابتدا گزینه‌های محصول را از بین ببرید یا محصول را غیرفعال کنید.');
        $audit->record('product.deleted', $product, $product->only(['name','base_price','status']), [], 'product_delete', $request->user()->id);
        $product->delete();
        return response()->json(['message' => 'محصول حذف شد.']);
    }
}
