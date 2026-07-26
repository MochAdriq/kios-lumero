<?php
$f = __DIR__ . '/../member/index.php';
$c = file_get_contents($f);
$c = str_replace('href="login.php', 'href="<?= url(\'/member/login.php\') ?>', $c);
$c = str_replace('href="online-order.php', 'href="<?= url(\'/member/online-order.php\') ?>', $c);
$c = str_replace('window.location.href=\'login.php\'', 'window.location.href=\'<?= url(\'/member/login.php\') ?>\'', $c);
file_put_contents($f, $c);
echo "Done";
