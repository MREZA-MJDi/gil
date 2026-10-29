<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');
        $name = config('admin.name', 'مدیر خانه گیلاسی');

        // Local-only shortcut for a disposable development database. Production always requires a strong password.
        $minimumPasswordLength = app()->environment('local') ? 1 : 14;

        if (! $email || ! $password || strlen($password) < $minimumPasswordLength) {
            throw new RuntimeException('ADMIN_EMAIL و ADMIN_PASSWORD را تعریف کنید؛ در production رمز باید حداقل ۱۴ کاراکتر باشد.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'is_admin' => true],
        );
    }
}
