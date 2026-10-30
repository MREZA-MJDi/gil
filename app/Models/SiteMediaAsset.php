<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Support\\Facades\\Storage;

class SiteMediaAsset extends Model
{
    protected $fillable = ['slot', 'path', 'alt_text', 'uploaded_by'];

    public static function urlFor(string $slot, ?string $fallback = null): ?string
    {
        $asset = static::query()->where('slot', $slot)->first();

        return $asset ? Storage::disk('public')->url($asset->path) : $fallback;
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
