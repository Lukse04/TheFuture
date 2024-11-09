<?php
// rent_facility.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['facility_id'], $data['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data']);
    exit();
}

$facility_id = intval($data['facility_id']);
$csrf_token = $data['csrf_token'];

if (!check_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

$user_id = get_user_id();

$stmt = mysqli_prepare($conn, "
    SELECT id, type, price, rental_term, early_termination_fee, cancellation_policy
    FROM facilities
    WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $facility_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $id, $type, $price, $rental_term, $early_termination_fee, $cancellation_policy);
$fetch_result = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$fetch_result) {
    http_response_code(404);
    echo json_encode(['error' => 'Facility not found']);
    exit();
}

if ($type === 'rent') {
    $total_price = $price * ($rental_term / 4); // Assuming price is per month
} else {
    $total_price = $price;
}

$stmt = mysqli_prepare($conn, "SELECT id, account_balance FROM card_numbers WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$card = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$card) {
    http_response_code(404);
    echo json_encode(['error' => 'Card not found']);
    exit();
}

$account_balance = $card['account_balance'];

if ($account_balance < $total_price) {
    http_response_code(400);
    echo json_encode(['error' => 'Insufficient funds']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare($conn, "UPDATE card_numbers SET account_balance = account_balance - ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "di", $total_price, $card['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $start_date = date('Y-m-d');
    $end_date = $type === 'rent' ? date('Y-m-d', strtotime("+$rental_term weeks")) : null;

    $stmt = mysqli_prepare($conn, "
        INSERT INTO user_facilities (user_id, facility_id, start_date, end_date, rental_term, termination_fee, cancellation_policy)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param(
        $stmt,
        "iissids",
        $user_id,
        $facility_id,
        $start_date,
        $end_date,
        $rental_term,
        $early_termination_fee,
        $cancellation_policy
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    echo json_encode(['success' => 'Facility acquired successfully']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred during the transaction']);
}
?>
