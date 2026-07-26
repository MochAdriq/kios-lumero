<?php
$_SERVER['REQUEST_URI'] = '/lumero/member?claim=LMR-DEF63612';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['route'] = 'member';
$_GET['claim'] = 'LMR-DEF63612';

ob_start();
require 'public/index.php';
$html = ob_get_clean();
file_put_contents('scratch/out.html', $html);
