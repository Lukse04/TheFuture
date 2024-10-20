<?php
require_once '../../dbh.inc.php';
require_once '../../auth.inc.php';

header('Content-Type: application/json');

check_auth();

$user_id = get_user_id();

// Gauti duomenis iš prašymo (naudojant POST metodą)
$item_id = $_POST['item_id'] ?? null;
$recipient_id = $_POST['recipient_id'] ?? null;

// Validacija
if (!$item_id || !$recipient_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Neteisingi duomenys']);
    exit;
}

// Patikrinti, ar vartotojas turi šį daiktą
$stmt = $conn->prepare('SELECT * FROM inventory WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $item_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    http_response_code(403);
    echo json_encode(['error' => 'Jūs neturite šio daikto']);
    exit;
}

// Pradėti transakciją
$conn->begin_transaction();

try {
    // Perduoti daiktą gavėjui
    $stmt = $conn->prepare('UPDATE inventory SET user_id = ? WHERE id = ?');
    $stmt->bind_param('ii', $recipient_id, $item_id);
    $stmt->execute();

    // Įrašyti transakciją
    $stmt = $conn->prepare('INSERT INTO transactions (item_id, buyer_id, seller_id, transaction_type) VALUES (?, ?, ?, "gift")');
    $stmt->bind_param('iii', $item_id, $recipient_id, $user_id);
    $stmt->execute();

    // Patvirtinti transakciją
    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Atšaukti transakciją
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Įvyko klaida', 'details' => $e->getMessage()]);
}
?>
