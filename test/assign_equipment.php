<?php
// assign_equipment.php
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

if (!isset($data['facility_id'], $data['item_id'], $data['quantity'], $data['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data']);
    exit();
}

$facility_id = intval($data['facility_id']);
$item_id = intval($data['item_id']);
$quantity = intval($data['quantity']);
$csrf_token = $data['csrf_token'];

if (!check_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

if ($quantity < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid quantity']);
    exit();
}

$user_id = get_user_id();

// Check if facility exists and is owned by user
$stmt = mysqli_prepare($conn, "SELECT uf.id, f.capacity FROM user_facilities uf JOIN facilities f ON uf.facility_id = f.id WHERE uf.id = ? AND uf.user_id = ? AND uf.status = 'active'");
mysqli_stmt_bind_param($stmt, "ii", $facility_id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $uf_id, $facility_capacity);
$facility_exists = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$facility_exists) {
    http_response_code(404);
    echo json_encode(['error' => 'Facility not found or not active']);
    exit();
}

// Get total hash rate of the equipment being assigned
$stmt = mysqli_prepare($conn, "SELECT hash_rate FROM shop_items WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $hash_rate_per_unit);
$item_exists = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$item_exists) {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
    exit();
}

$total_hash_rate = $hash_rate_per_unit * $quantity;

// Check if the user has enough equipment
$stmt = mysqli_prepare($conn, "SELECT quantity FROM user_mining WHERE user_id = ? AND item_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $owned_quantity);
$fetch_result = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$fetch_result || $owned_quantity < $quantity) {
    http_response_code(400);
    echo json_encode(['error' => 'Not enough mining equipment']);
    exit();
}

// Calculate current used capacity
$stmt = mysqli_prepare($conn, "
    SELECT SUM(ea.total_hash_rate) FROM equipment_assignments ea
    WHERE ea.facility_id = ? AND ea.user_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $facility_id, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $used_capacity);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$used_capacity = $used_capacity ?? 0;

// Check if facility has enough capacity
if ($used_capacity + $total_hash_rate > $facility_capacity) {
    http_response_code(400);
    echo json_encode(['error' => 'Not enough capacity in the facility']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    // Assign equipment to facility
    $stmt = mysqli_prepare($conn, "
        INSERT INTO equipment_assignments (user_id, item_id, facility_id, quantity, total_hash_rate)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + ?, total_hash_rate = total_hash_rate + ?
    ");
    mysqli_stmt_bind_param($stmt, "iiiiidi", $user_id, $item_id, $facility_id, $quantity, $total_hash_rate, $quantity, $total_hash_rate);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Update user's mining equipment
    $stmt = mysqli_prepare($conn, "UPDATE user_mining SET quantity = quantity - ? WHERE user_id = ? AND item_id = ?");
    mysqli_stmt_bind_param($stmt, "iii", $quantity, $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    echo json_encode(['success' => 'Equipment assigned successfully']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred during the assignment']);
}
?>
