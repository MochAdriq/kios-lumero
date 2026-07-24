<?php
$files = [
    __DIR__ . '/../views/auth/login.php',
    __DIR__ . '/../scratch/generate_midtrans_doc.py',
    __DIR__ . '/../.env.production',
    __DIR__ . '/../config/app.php',
    __DIR__ . '/../.env.example',
    __DIR__ . '/../.env'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $newContent = str_replace('Lokapedia Karya Bersama', 'Lokapedia Sukses Bersama', $content);
        if ($content !== $newContent) {
            file_put_contents($file, $newContent);
            echo "Updated: " . basename($file) . "\n";
        }
    }
}
