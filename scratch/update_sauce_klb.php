<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");

$outlets = [5, 8];

// Helper to get sauce image
function getSauceImage($name) {
    $n = strtolower($name);
    if (strpos($n, 'pedas') !== false || strpos($n, 'sadis') !== false || strpos($n, 'spicy') !== false) {
        return 'images/pos-products/sauces/pedas klb.png';
    }
    if (strpos($n, 'keju') !== false || strpos($n, 'cheese') !== false) {
        return 'images/pos-products/sauces/keju klb.png';
    }
    if (strpos($n, 'black pepper') !== false || strpos($n, 'lada hitam') !== false) {
        return 'images/pos-products/sauces/black pepper klb.png';
    }
    if (strpos($n, 'bbq') !== false || strpos($n, 'barbeque') !== false) {
        return 'images/pos-products/sauces/bbq klb.png';
    }
    if (strpos($n, 'teriyaki') !== false) {
        return 'images/pos-products/sauces/teriyaki klb.png';
    }
    // Carbonara, Garlic, Mentai, Original+Saus, etc. fallback to default (2).png
    // But ONLY if it actually implies a sauce. If it's pure original, it should be ayam-klb.png
    // Wait, the user said "untuk saus ... yang gaada gunakan default 2"
    // How do we know it has sauce?
    if (strpos($n, 'saus') !== false || strpos($n, 'carbonara') !== false || strpos($n, 'garlic') !== false || strpos($n, 'mentai') !== false || strpos($n, 'sauce') !== false) {
        return 'images/pos-products/sauces/default (2).png';
    }
    return null;
}

$st = $pdo->prepare("SELECT * FROM product_variants WHERE outlet_id IN (5,8)");
$st->execute();
$variants = $st->fetchAll(PDO::FETCH_ASSOC);

$updatedCount = 0;
$updateVariant = $pdo->prepare("UPDATE product_variants SET image = ? WHERE id = ?");

foreach ($variants as $v) {
    // Check variant name first, then product name if needed
    // Actually, variants for chicken usually have the sauce in the variant name
    $fullName = $v['variant_name'] . ' ' . $v['sku'];
    
    // We should also look at the parent product to make sure it's a chicken product?
    // User said "untuk saus juga saya ingin yang di klb untuk outlet 8 di giniin"
    // Let's just match any variant name that contains these sauces, because what else would have "Carbonara" or "Saus"?
    // Let's get parent product name to be safe
    $pst = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $pst->execute([$v['product_id']]);
    $parent = $pst->fetch(PDO::FETCH_ASSOC);
    $parentName = $parent ? $parent['name'] : '';
    
    $searchString = $parentName . ' ' . $v['variant_name'];
    
    $newImage = getSauceImage($searchString);
    if ($newImage) {
        $updateVariant->execute([$newImage, $v['id']]);
        echo "Updated Variant ID {$v['id']} ({$searchString}) -> {$newImage}\n";
        $updatedCount++;
    }
}
echo "Total updated: $updatedCount\n";
