<?php
require_once 'includes/dbh.inc.php';
session_start();

// Tikriname CSRF žymeklį
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(["status" => "error", "message" => "Neteisingas CSRF žymeklis."]);
    exit();
}

// Tikriname, ar vartotojas prisijungęs
if (!isset($_SESSION['userid'])) {
    echo json_encode(["status" => "error", "message" => "Neprisijungta."]);
    exit();
}

$userId = $_SESSION['userid'];
$itemId = $_POST['id']; // Pakeičiame į POST, nes naudojame AJAX užklausą

// Patikriname, ar daiktas priklauso vartotojui
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $itemId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    echo json_encode(["status" => "error", "message" => "Daiktas nerastas arba jis jums nepriklauso."]);
    exit();
}

// Apskaičiuojame daikto pardavimo kainą
$sellPrice = calculateSellPrice($item['item_level'], $item['item_type']);

// Pridedame pardavimo kainą į vartotojo sąskaitą iš lentelės card_numbers
$stmt = $conn->prepare("UPDATE card_numbers SET account_balance = account_balance + ? WHERE user_id = ?");
$stmt->bind_param('ii', $sellPrice, $userId);
$stmt->execute();

// Pašaliname daiktą iš inventoriaus
$stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
$stmt->bind_param('i', $itemId);
$stmt->execute();

echo json_encode(["status" => "success", "message" => "Sėkmingai pardavėte daiktą už $sellPrice monetų."]);

// Funkcija, kuri apskaičiuoja daikto pardavimo kainą pagal jo lygį ir tipą
function calculateSellPrice($level, $type) {
    if ($type == 'chest') {
        return $level * 10; // Pvz., 1 lygio skrynia bus verta 10 monetų
    } elseif ($type == 'talent') {
        return $level * 20; // Pvz., 1 lygio talentas bus vertas 20 monetų
    }
    return 0; // Jeigu tipas neatitinka, grąžiname 0
}
?>
