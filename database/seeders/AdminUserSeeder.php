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
        $email = trim((string) config('admin.email'));
        $password = (string) config('admin.password');
        $name = trim((string) config('admin.name', 'مدیر خانه گیلاسی'));

        if (! $phone || ! PhoneNumber::isValid($phone) || strlen($password) < 14) {
            throw new RuntimeException(
                'ADMIN_PHONE معتبر و ADMIN_PASSWORD با حداقل ۱۴ نویسه را در محیط امن تنظیم کنید.'
            );
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ADMIN_EMAIL باید یک ایمیل معتبر باشد یا خالی بماند.');
        }

        $phoneUser = User::query()->where('phone', $phone)->first();
        $emailUser = $email !== ''
            ? User::query()->where('email', $email)->first()
            : null;

        if ($phoneUser && $emailUser && $phoneUser->getKey() !== $emailUser->getKey()) {
            throw new RuntimeException(
                'ADMIN_PHONE و ADMIN_EMAIL به دو حساب متفاوت تعلق دارند؛ ابتدا تداخل حساب‌ها را برطرف کنید.'
            );
        }

        $user = $phoneUser ?: $emailUser;
        $attributes = [
            'name' => $name !== '' ? $name : 'مدیر خانه گیلاسی',
            'email' => $email !== '' ? $email : null,
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
