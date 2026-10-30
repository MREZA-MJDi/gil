<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteMediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MediaController extends Controller
{
    private const SLOTS = [
        'hero',
        'login_background',
        'menu_cover',
        'brand_logo',
        'story_main',
        'story_small',
        'gallery_1',
        'gallery_2',
        'gallery_3',
    ];

    public function index(): JsonResponse
    {
        $assets = SiteMediaAsset::query()->orderBy('slot')->get()
            ->map(fn (SiteMediaAsset $asset) => [
                'id' => $asset->id,
                'slot' => $asset->slot,
                'path' => $asset->path,
                'url' => $asset->url,
                'alt_text' => $asset->alt_text,
                'updated_at' => $asset->updated_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $assets]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slot' => ['required', 'string', Rule::in(self::SLOTS)],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'alt_text' => ['nullable', 'string', 'max:180'],
        ], [
            'image.required' => 'یک تصویر برای بارگذاری انتخاب کن.',
            'image.image' => 'فایل انتخاب‌شده تصویر معتبر نیست.',
            'image.max' => 'حجم تصویر نباید بیشتر از ۸ مگابایت باشد.',
        ]);

        $slot = $data['slot'];
        $file = $data['image'];
        $extension = strtolower($file->extension() ?: 'jpg');
        $path = $file->storePubliclyAs(
            'site-media/'.$slot,
            Str::uuid().'.'.$extension,
            'public',
        );

        $asset = SiteMediaAsset::query()->firstOrNew(['slot' => $slot]);
        $oldPath = $asset->exists ? $asset->path : null;
        $asset->fill([
            'path' => $path,
            'alt_text' => $data['alt_text'] ?? null,
            'uploaded_by' => $request->user()?->id,
        ])->save();

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(['data' => [
            'id' => $asset->id,
            'slot' => $asset->slot,
            'path' => $asset->path,
            'url' => $asset->url,
            'alt_text' => $asset->alt_text,
            'updated_at' => $asset->updated_at?->toIso8601String(),
        ]], 201);
    }

    public function destroy(SiteMediaAsset $media): JsonResponse
    {
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return response()->json(['message' => 'تصویر از کتابخانه حذف شد.']);
    }
}
