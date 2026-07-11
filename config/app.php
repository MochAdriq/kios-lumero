<?php
return [
    'name' => app_env('APP_NAME', 'SIM Resto Sempurna'),
    'company' => app_env('APP_COMPANY', 'PT. Lokapedia Karya Bersama'),
    'timezone' => app_env('APP_TIMEZONE', 'Asia/Jakarta'),
    'base_url' => rtrim((string)app_env('APP_URL', ''), '/'),
    'debug' => app_bool(app_env('APP_DEBUG', app_is_local() ? 'true' : 'false'), app_is_local()),
    'default_outlet_id' => (int)app_env('DEFAULT_OUTLET_ID', 1),
];
