<?php
// get_mining_info.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

check_auth();

$user_id = get_user_id();

// Get mining equipment assigned to facilities
$stmt_mining = mysqli_prepare($conn, "
    SELECT sm.item_name, sm.hash_rate, ea.quantity, sm.cryptocurrency, f.name as facility_name
    FROM equipment_assignments ea
    JOIN shop_items sm ON ea.item_id = sm.id
    JOIN facilities f ON ea.facility_id = f.id
    WHERE ea.user_id = ?
");
mysqli_stmt_bind_param($stmt_mining, "i", $user_id);
mysqli_stmt_execute($stmt_mining);
$result_mining = mysqli_stmt_get_result($stmt_mining);
$mining_items = mysqli_fetch_all($result_mining, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_mining);

$total_hash_rates = [
    'Monero' => 0,
    'Bitcoin' => 0,
    'Dogecoin' => 0
];

foreach ($mining_items as $item) {
    if (isset($total_hash_rates[$item['cryptocurrency']])) {
        $total_hash_rates[$item['cryptocurrency']] += $item['hash_rate'] * $item['quantity'];
    }
}

$response = [
    'total_hash_rates' => $total_hash_rates,
    'mining_items' => $mining_items
];

echo json_encode($response);
?>
