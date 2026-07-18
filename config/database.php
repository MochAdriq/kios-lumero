<?php
/**
 * Database Configuration
 * 
 * Semua credential diambil dari .env file.
 * Tidak ada lagi hard-coded credential di source code.
 * Cukup buat .env yang berbeda di local dan production.
 */
$mode = function_exists('active_db_mode') ? active_db_mode() : 'local';

if ($mode === 'production') {
    return [
        'host'     => app_env('PROD_DB_HOST', app_env('DB_HOST', '127.0.0.1')),
        'port'     => app_env('PROD_DB_PORT', app_env('DB_PORT', '3306')),
        'database' => app_env('PROD_DB_DATABASE', app_env('PROD_DB_NAME', app_env('DB_DATABASE', 'dcc'))),
        'username' => app_env('PROD_DB_USERNAME', app_env('PROD_DB_USER', app_env('DB_USERNAME', 'root'))),
        'password' => app_env('PROD_DB_PASSWORD', app_env('PROD_DB_PASS', app_env('DB_PASSWORD', ''))),
        'charset'  => app_env('PROD_DB_CHARSET', app_env('DB_CHARSET', 'utf8mb4')),
    ];
}

return [
    'host'     => app_env('DB_HOST', '127.0.0.1'),
    'port'     => app_env('DB_PORT', '3306'),
    'database' => app_env('DB_DATABASE', app_env('DB_NAME', 'dcc')),
    'username' => app_env('DB_USERNAME', app_env('DB_USER', 'root')),
    'password' => app_env('DB_PASSWORD', app_env('DB_PASS', '')),
    'charset'  => app_env('DB_CHARSET', 'utf8mb4'),
];

