<?php
// get_shop_items.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

check_auth();

$stmt = mysqli_prepare($conn, "
    SELECT id, item_name, price, currency, cryptocurrency, hash_rate, efficiency, supported_algorithms 
    FROM shop_items 
    WHERE cryptocurrency IN ('Bitcoin', 'Monero', 'Dogecoin')
");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$shop_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$formatted_shop_items = [];
foreach ($shop_items as $item) {
    $formatted_shop_items[] = [
        'id' => $item['id'],
        'item_name' => $item['item_name'],
        'price' => $item['price'],
        'currency' => $item['currency'],
        'cryptocurrency' => $item['cryptocurrency'],
        'hash_rate' => $item['hash_rate'],
        'efficiency' => $item['efficiency'],
        'supported_algorithms' => array_map('trim', explode(',', $item['supported_algorithms']))
    ];
}

echo json_encode(['shop_items' => $formatted_shop_items]);
?>
