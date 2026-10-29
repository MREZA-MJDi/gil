<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $phone = PhoneNumber::normalize((string) config('admin.phone'));
        $password = (string) config('admin.password');
        $email = config('admin.email');
        $name = config('admin.name', 'مدیر خانه گیلاسی');

        if (! $phone || ! PhoneNumber::isValid($phone) || strlen($password) < 14) {
            throw new RuntimeException('ADMIN_PHONE معتبر و ADMIN_PASSWORD با حداقل ۱۴ نویسه را در محیط امن تنظیم کنید.');
        }

        $attributes = [
            'name' => $name,
            'email' => $email ?: null,
            'password' => $password,
            'is_admin' => true,
        ];

        User::query()->updateOrCreate(['phone' => $phone], $attributes);
    }
}
