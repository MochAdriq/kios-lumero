<?php
$file = 'c:\xampp\htdocs\kios-lumero\modules\products\ProductController.php';
$content = file_get_contents($file);

$content = str_replace('(new Model())', '$prodModel', $content);

file_put_contents($file, $content);
echo "Replaced (new Model()) with \$prodModel in $file\n";
