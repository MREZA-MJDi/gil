<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $legacyNames = ['خانه گیلاسی', 'Q Food'];
        $settings = DB::table('restaurant_settings')->where('singleton_key', 'main')->first();

        if ($settings) {
            $configuredName = trim((string) config('restaurant.name', 'نام مجموعه'));
            $currentName = in_array(trim((string) $settings->name), $legacyNames, true)
                ? $configuredName
                : (string) $settings->name;

            $replaceLegacyName = function ($value) use (&$replaceLegacyName, $legacyNames, $currentName) {
                if (is_string($value)) {
                    return str_replace($legacyNames, $currentName, $value);
                }

                if (is_array($value)) {
                    return array_map($replaceLegacyName, $value);
                }

                return $value;
            };

            $changes = [];
            if ($currentName !== (string) $settings->name) {
                $changes['name'] = $currentName;
            }

            $homepageContent = json_decode((string) $settings->homepage_content, true);
            if (is_array($homepageContent)) {
                $updatedContent = $replaceLegacyName($homepageContent);
                if ($updatedContent !== $homepageContent) {
                    $changes['homepage_content'] = json_encode(
                        $updatedContent,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                }
            }

            $footer = $replaceLegacyName($settings->receipt_footer);
            if ($footer !== $settings->receipt_footer) {
                $changes['receipt_footer'] = $footer;
            }

            if ($changes !== []) {
                DB::table('restaurant_settings')
                    ->where('id', $settings->id)
                    ->update($changes);
            }

            DB::table('site_media_assets')
                ->where('slot', 'brand_logo')
                ->where(function ($query): void {
                    $query->where('alt_text', 'like', '%خانه گیلاسی%')
                        ->orWhere('alt_text', 'like', '%Q Food%');
                })
                ->update(['alt_text' => 'لوگوی مجموعه']);
        }

        DB::table('users')
            ->where('name', 'مدیر خانه گیلاسی')
            ->update(['name' => 'مدیر سامانه']);
    }

    public function down(): void
    {
        // This data-only cleanup is intentionally not reversed: restoring a legacy
        // brand label could overwrite a name that an operator configured later.
    }
};
