<?php
/**
 * Database Configuration
 * 
 * Semua credential diambil dari .env file.
 * Tidak ada lagi hard-coded credential di source code.
 * Cukup buat .env yang berbeda di local dan production.
 */
return [
    'host'     => app_env('DB_HOST', '127.0.0.1'),
    'port'     => app_env('DB_PORT', '3306'),
    'database' => app_env('DB_DATABASE', app_env('DB_NAME', 'dcc')),
    'username' => app_env('DB_USERNAME', app_env('DB_USER', 'root')),
    'password' => app_env('DB_PASSWORD', app_env('DB_PASS', '')),
    'charset'  => app_env('DB_CHARSET', 'utf8mb4'),
];
