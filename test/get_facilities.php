<?php
// get_facilities.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

check_auth();

$stmt = mysqli_prepare($conn, "
    SELECT id, name, type, price, rental_term, early_termination_fee, cancellation_policy, description
    FROM facilities
");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$facilities = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

echo json_encode(['facilities' => $facilities]);
?>
