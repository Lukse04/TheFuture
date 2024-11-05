<?php
// buy_mining_item.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Tik POST užklausos yra leidžiamos']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Netinkami duomenys']);
    exit();
}

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Jūs nesate prisijungęs']);
    exit();
}

$user_id = get_user_id();

if (!isset($data['item_id'], $data['quantity'], $data['csrf_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Trūksta reikiamų duomenų']);
    exit();
}

$item_id = intval($data['item_id']);
$quantity = intval($data['quantity']);
$csrf_token = $data['csrf_token'];

if (!check_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Neteisingas CSRF žetonas']);
    exit();
}

if ($quantity < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Neteisingas kiekis']);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT price, currency, cryptocurrency FROM shop_items WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $price, $currency, $cryptocurrency);
$fetch_result = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$fetch_result) {
    http_response_code(404);
    echo json_encode(['error' => 'Prekė nerasta']);
    exit();
}

$total_price = $price * $quantity;

$stmt = mysqli_prepare($conn, "SELECT id, account_balance FROM card_numbers WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$card = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$card) {
    http_response_code(404);
    echo json_encode(['error' => 'Kortelė nerasta']);
    exit();
}

$account_balance = $card['account_balance'];

if ($account_balance < $total_price) {
    http_response_code(400);
    echo json_encode(['error' => 'Neužtenka lėšų']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare($conn, "UPDATE card_numbers SET account_balance = account_balance - ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "di", $total_price, $card['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT id FROM user_mining WHERE user_id = ? AND item_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_mining_id);
    $exists = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($exists) {
        $stmt = mysqli_prepare($conn, "UPDATE user_mining SET quantity = quantity + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $quantity, $user_mining_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO user_mining (user_id, item_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $item_id, $quantity);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conn);
    echo json_encode(['success' => 'Pirkimas sėkmingas']);
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['error' => 'Pirkimo metu įvyko klaida']);
    exit();
}
?>
