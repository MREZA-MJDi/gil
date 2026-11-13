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
        $email = strtolower(trim((string) config('admin.email')));
        $phone = PhoneNumber::normalize((string) config('admin.phone'));
        $password = (string) config('admin.password');
        $name = trim((string) config('admin.name', 'مدیر خانه گیلاسی'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! $phone
            || ! PhoneNumber::isValid($phone)
            || strlen($password) < 14) {
            throw new RuntimeException(
                'ADMIN_EMAIL معتبر، ADMIN_PHONE معتبر و ADMIN_PASSWORD با حداقل ۱۴ نویسه را در محیط امن تنظیم کنید.'
            );
        }

        $emailUser = User::query()->where('email', $email)->first();
        $phoneUser = User::query()->where('phone', $phone)->first();

        if ($emailUser && $phoneUser && $emailUser->getKey() !== $phoneUser->getKey()) {
            throw new RuntimeException(
                'ADMIN_EMAIL و ADMIN_PHONE به دو حساب متفاوت تعلق دارند؛ پیش از همگام‌سازی حساب مدیر، تداخل را برطرف کنید.'
            );
        }

        $user = $emailUser ?: $phoneUser;
        $attributes = [
            'name' => $name !== '' ? $name : 'مدیر خانه گیلاسی',
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'is_admin' => true,
        ];

        if ($user) {
            $user->fill($attributes)->save();

            return;
        }

        User::query()->create($attributes);
    }
}
