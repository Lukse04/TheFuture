<?php
// buy_mining_item.php
require '../includes/dbh.inc.php';
require '../includes/auth.inc.php';

header('Content-Type: application/json');

// Tikrinti, ar užklausa yra POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Tik POST užklausos yra leidžiamos']);
    exit();
}

// Gauti ir iškoduoti JSON duomenis iš užklausos kūno
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Patikrinkite, ar JSON iškoduotas teisingai
if (!is_array($data)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Netinkami duomenys']);
    exit();
}

// Patikrinkite, ar vartotojas yra prisijungęs
if (!isset($_SESSION['userid'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Jūs nesate prisijungęs']);
    exit();
}

$user_id = get_user_id();

// Patikrinkite, ar buvo pateikta prekės ID, kiekis ir CSRF žetonas
if (!isset($data['item_id'], $data['quantity'], $data['csrf_token'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Trūksta reikiamų duomenų']);
    exit();
}

$item_id = intval($data['item_id']);
$quantity = intval($data['quantity']);
$csrf_token = $data['csrf_token'];

// Patikrinkite CSRF žetoną
if (!check_csrf_token($csrf_token)) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Neteisingas CSRF žetonas']);
    exit();
}

if ($quantity < 1) {
    // Klaida: neteisingas kiekis
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Neteisingas kiekis']);
    exit();
}

// Gauti prekės informaciją
$stmt = mysqli_prepare($conn, "SELECT price, currency, cryptocurrency FROM shop_items WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $price, $currency, $cryptocurrency);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$price) {
    // Klaida: prekė nerasta
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Prekė nerasta']);
    exit();
}

// Apskaičiuokite bendrą kainą
$total_price = $price * $quantity;

// Gauti vartotojo kortelės informaciją (tik vieną kortelę)
$stmt = mysqli_prepare($conn, "SELECT id, account_balance FROM card_numbers WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$card = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$card) {
    // Klaida: vartotojas neturi kortelės
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Kortelė nerasta']);
    exit();
}

$account_balance = $card['account_balance'];

// Patikrinkite, ar vartotojui yra pakankamai lėšų
if ($account_balance < $total_price) {
    // Klaida: nepakanka lėšų
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Neužtenka lėšų']);
    exit();
}

// Atlikite pirkimą tranzakciją
mysqli_begin_transaction($conn);

try {
    // Atnaujinkite vartotojo kortelės balansą
    $stmt = mysqli_prepare($conn, "UPDATE card_numbers SET account_balance = account_balance - ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "di", $total_price, $card['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Patikrinkite, ar vartotojui jau yra ši prekė
    $stmt = mysqli_prepare($conn, "SELECT id, quantity FROM user_mining WHERE user_id = ? AND item_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_mining_id, $current_quantity);
    $exists = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($exists) {
        // Atnaujinkite esamą kiekį
        $stmt = mysqli_prepare($conn, "UPDATE user_mining SET quantity = quantity + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $quantity, $user_mining_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Įrašykite naują įrangą
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
    // Klaidos tvarkymas
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Pirkimo metu įvyko klaida']);
    exit();
}
?>
