<?php
// get_user_equipment.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

check_auth();

$user_id = get_user_id();

$stmt = mysqli_prepare($conn, "
    SELECT um.item_id, sm.item_name, um.quantity
    FROM user_mining um
    JOIN shop_items sm ON um.item_id = sm.id
    WHERE um.user_id = ? AND um.quantity > 0
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_equipment = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

echo json_encode(['user_equipment' => $user_equipment]);
?>
