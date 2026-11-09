<?php

return [
    // Set in the private deployment environment; never commit an actual secret.
    // X-Gil-Signature: sha256=<HMAC-SHA256 of the exact raw HTTP request body>.
    'order_webhook_secret' => env('ORDER_WEBHOOK_SECRET'),
];
