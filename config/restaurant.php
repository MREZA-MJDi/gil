<?php

return [
    'name' => env('RESTAURANT_NAME', 'خانه گیلاسی'),
    'phone' => env('RESTAURANT_PHONE'),
    'address' => env('RESTAURANT_ADDRESS'),
    'currency_label' => env('RESTAURANT_CURRENCY_LABEL', 'تومان'),
    'tax_basis_points' => (int) env('RESTAURANT_TAX_BASIS_POINTS', 0),
    'service_basis_points' => (int) env('RESTAURANT_SERVICE_BASIS_POINTS', 0),
    'receipt_footer' => env('RESTAURANT_RECEIPT_FOOTER', 'از خرید شما سپاسگزاریم'),
    'paper_width' => (int) env('RESTAURANT_PAPER_WIDTH', 80),
    'customer_menu_enabled' => (bool) env('RESTAURANT_CUSTOMER_MENU_ENABLED', true),

    // Only explicitly configured real table names are seeded; no assumed room layout.
    'initial_table_names' => array_values(array_unique(array_filter(array_map(
        static fn (string $name): string => trim($name),
        explode(',', (string) env('RESTAURANT_TABLE_NAMES', '')),
    )))),
];
