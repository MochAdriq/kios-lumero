<?php
$dir = __DIR__ . '/public/assets/qris/';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$test = file_put_contents($dir . 'test_write.txt', 'test');
echo 'Dir writable: ' . ($test !== false ? 'YES' : 'NO') . PHP_EOL;
echo 'Dir exists: ' . (is_dir($dir) ? 'YES' : 'NO') . PHP_EOL;
echo 'Abs path: ' . realpath($dir) . PHP_EOL;
if ($test !== false) unlink($dir . 'test_write.txt');
