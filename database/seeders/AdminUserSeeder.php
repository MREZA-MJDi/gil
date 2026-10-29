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

        if (! $email || ! $password || strlen($password) < 14) {
            throw new RuntimeException('ADMIN_EMAIL و ADMIN_PASSWORD با حداقل ۱۴ کاراکتر را در .env تعریف کنید.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'is_admin' => true],
        );
    }
}
