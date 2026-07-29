<?php
$vars = [
'Dada Original Tanpa Nasi',
'Dada BBQ Spicy Tanpa Nasi',
'Dada Keju Tanpa Nasi',
'Dada Lada Hitam Tanpa Nasi',
'Dada Sadis Tanpa Nasi',
'Dada Sambal Geprek Tanpa Nasi',
'Dada Mentai Tanpa Nasi',
'Dada Teriyaki Tanpa Nasi',
'Dada Geprek Extra Mozzarella Tanpa Nasi',
'Dada Geprek Extra Mentai Tanpa Nasi',
'Dada Garlic Tanpa Nasi'
];
$sauceDefs = [
    ['key'=>'keju', 'match'=>['keju', 'cheese']],
    ['key'=>'lada_hitam', 'match'=>['lada hitam', 'black pepper', 'blackpepper']],
    ['key'=>'garlic', 'match'=>['garlic', 'bawang', 'sicilian']],
    ['key'=>'teriyaki', 'match'=>['teriyaki']],
    ['key'=>'sadis_mozzarella', 'match'=>['smashed chili extra mozzarella', 'geprek extra mozzarella']],
    ['key'=>'sadis_mentai', 'match'=>['smashed chili extra mentai', 'geprek extra mentai']],
    ['key'=>'sadis', 'match'=>['sadis', 'geprek', 'pedas', 'smashed chili']],
    ['key'=>'bbq', 'match'=>['bbq', 'barbeque', 'italian barbeque', 'bbq spicy']],
    ['key'=>'mentai', 'match'=>['mentai', 'mayo', 'mayonnaise']],
    ['key'=>'picante', 'match'=>['picante']],
    ['key'=>'mozzarella', 'match'=>['mozzarella']],
    ['key'=>'carbonara', 'match'=>['carbonara']]
];
$found = [];
foreach($vars as $v) {
    $v = strtolower($v);
    foreach($sauceDefs as $def) {
        foreach($def['match'] as $m) {
            if(strpos($v, $m) !== false) {
                $found[$def['key']] = true;
                break 2;
            }
        }
    }
}
print_r(array_keys($found));
