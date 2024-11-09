<?php
// cancel_rental.php
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

if (!isset($data['user_facility_id'], $data['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data']);
    exit();
}

$user_facility_id = intval($data['user_facility_id']);
$csrf_token = $data['csrf_token'];

if (!check_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

$user_id = get_user_id();

$stmt = mysqli_prepare($conn, "
    SELECT uf.id, uf.start_date, uf.end_date, uf.rental_term, uf.termination_fee, uf.cancellation_policy, uf.status, f.price
    FROM user_facilities uf
    JOIN facilities f ON uf.facility_id = f.id
    WHERE uf.id = ? AND uf.user_id = ? AND uf.status = 'active'
");
mysqli_stmt_bind_param($stmt, "ii", $user_facility_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$facility = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$facility) {
    http_response_code(404);
    echo json_encode(['error' => 'Facility not found or not active']);
    exit();
}

$today = date('Y-m-d');
$start_date = $facility['start_date'];
$end_date = $facility['end_date'];
$remaining_weeks = ceil((strtotime($end_date) - strtotime($today)) / (7 * 24 * 60 * 60));
$total_remaining_price = $facility['price'] * ($remaining_weeks / 4);

$penalty = 0;

if ($facility['cancellation_policy'] === 'fee_only') {
    $penalty = $facility['termination_fee'];
} elseif ($facility['cancellation_policy'] === 'pay_remaining') {
    $penalty = $total_remaining_price;
} elseif ($facility['cancellation_policy'] === 'fee_and_remaining') {
    $penalty = $facility['termination_fee'] + $total_remaining_price;
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

if ($account_balance < $penalty) {
    http_response_code(400);
    echo json_encode(['error' => 'Insufficient funds to pay cancellation penalty']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare($conn, "UPDATE card_numbers SET account_balance = account_balance - ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "di", $penalty, $card['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "UPDATE user_facilities SET status = 'cancelled', end_date = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $today, $user_facility_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    echo json_encode(['success' => 'Rental contract cancelled successfully']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred during the cancellation']);
}
?>
