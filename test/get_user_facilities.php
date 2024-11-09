<?php
// get_user_facilities.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

check_auth();

$user_id = get_user_id();

$stmt = mysqli_prepare($conn, "
    SELECT uf.id as user_facility_id, f.name, f.capacity
    FROM user_facilities uf
    JOIN facilities f ON uf.facility_id = f.id
    WHERE uf.user_id = ? AND uf.status = 'active'
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_facilities = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

echo json_encode(['user_facilities' => $user_facilities]);
?>
