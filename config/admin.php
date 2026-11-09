<?php

return [
    'name' => env('ADMIN_NAME', 'مدیر خانه گیلاسی'),
    'phone' => env('ADMIN_PHONE'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
    // Registration stays disabled until a high-entropy invitation key is explicitly configured.
    'registration_key' => env('ADMIN_REGISTRATION_KEY'),
];
