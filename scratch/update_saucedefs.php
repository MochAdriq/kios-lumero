<?php
$files = [
    'c:\xampp\htdocs\kios-lumero\public\assets\js\pos-preadmin.js',
    'c:\xampp\htdocs\kios-lumero\public\assets\js\self-order-ui.js'
];

$newSauceDefs = <<<JS
const sauceDefs = [
    { key: 'keju', label: 'Keju', img: assets.keju, match: ['keju', 'cheese'] },
    { key: 'lada_hitam', label: 'Lada Hitam', img: assets.lada_hitam, match: ['lada hitam', 'black pepper', 'blackpepper'] },
    { key: 'garlic', label: 'Garlic / Bawang', img: assets.garlic, match: ['garlic', 'bawang'] },
    { key: 'teriyaki', label: 'Teriyaki', img: assets.teriyaki, match: ['teriyaki'] },
    { key: 'sadis_mozzarella', label: 'Geprek Mozzarella', img: assets.dummy, match: ['smashed chili extra mozzarella'] },
    { key: 'sadis_mentai', label: 'Geprek Mentai', img: assets.dummy, match: ['smashed chili extra mentai'] },
    { key: 'sadis', label: 'Sadis / Geprek', img: assets.sadis, match: ['sadis', 'geprek', 'pedas', 'spicy', 'smashed chili'] },
    { key: 'bbq', label: 'BBQ Spicy', img: assets.bbq, match: ['bbq', 'barbeque'] },
    { key: 'mentai', label: 'Mentai / Mayo', img: assets.mentai, match: ['mentai', 'mayo', 'mayonnaise'] },
    { key: 'picante', label: 'Italian Picante', img: assets.dummy, match: ['picante'] },
    { key: 'mozzarella', label: 'Mozzarella', img: assets.dummy, match: ['mozzarella'] },
    { key: 'carbonara', label: 'Carbonara', img: assets.dummy, match: ['carbonara'] }
  ];
JS;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    $content = preg_replace('/const sauceDefs = \[[^\]]*\];/s', $newSauceDefs, $content);
    
    file_put_contents($file, $content);
    echo "Updated sauceDefs in $file\n";
}
