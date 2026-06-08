<?php

return [
    'token'           => env('WHATSAPP_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'account_id'      => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'enabled'         => env('WHATSAPP_ENABLED', false),
    'api_version'     => 'v21.0',
];