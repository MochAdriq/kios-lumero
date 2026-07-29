<?php
require_once __DIR__ . '/../helpers/WhatsAppGateway.php';

// Try to include necessary files for app_env() to work
if (file_exists(__DIR__ . '/../config/env.php')) {
    require_once __DIR__ . '/../config/env.php';
} elseif (file_exists(__DIR__ . '/../helpers/utils.php')) {
    require_once __DIR__ . '/../helpers/utils.php';
}

if (!function_exists('app_env')) {
    function app_env($key, $default = '') {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

// Load .env file manually if needed?
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

echo "Testing WhatsApp OTP...\n";
$token = app_env('WA_GATEWAY_TOKEN', '');
echo "Using Token: '" . $token . "'\n";
$response = WhatsAppGateway::sendOtp('0895338977816', '123456');
echo "Response:\n";
print_r($response);
