<?php
require_once 'includes/dbh.inc.php';
require_once 'includes/talent_functions.php'; // Talentų funkcijos, kuriose yra addToInventory()
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
$itemId = $_POST['id']; // Naudojame POST užklausą, kai atsiunčiama per AJAX

// Patikriname, ar daiktas priklauso vartotojui
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ? AND user_id = ?");
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "SQL klaida ruošiant užklausą."]);
    exit();
}
$stmt->bind_param('ii', $itemId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    echo json_encode(["status" => "error", "message" => "Daiktas nerastas arba jis jums nepriklauso."]);
    exit();
}

// Naudojame daiktą pagal jo tipą
if ($item['item_type'] == 'chest') {
    // Jei tai skrynia, ją atidarome
    $message = openChest($userId, $item);
    echo json_encode(["status" => "success", "message" => $message]);
    exit();
} elseif ($item['item_type'] == 'talent') {
    // Jei tai talentas, jį naudojame
    echo json_encode(["status" => "success", "message" => "Jūs panaudojote talentą: Lygis {$item['item_level']}, Galia: {$item['item_power']}"]);
    exit();
}

// Jei daikto tipas neatpažintas
echo json_encode(["status" => "error", "message" => "Nežinomas daikto tipas."]);
exit();

// Funkcija skryniai atidaryti ir talentams sugeneruoti
function openChest($userId, $item) {
    global $conn;

    // Pranešimas apie atidarytą skrynią
    $message = "Jūs atidarėte skrynią: lygis {$item['item_level']}!";

    // Sugeneruojame naujus talentus
    $talents = generateTalents($item['id'], $item['item_level']); // Generuojame talentus

    // Pridedame naujus talentus į inventorių
    foreach ($talents as $talent) {
        if (!addToInventory($userId, 'talent', $talent['level'], $talent['power'])) {
            error_log("Klaida pridedant talentą į inventorių.");
        }
    }

    // Pašaliname skrynią iš inventoriaus
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    if ($stmt === false) {
        error_log("Klaida ruošiant SQL užklausą skrynios pašalinimui: " . $conn->error);
        return "Klaida šalinant skrynią.";
    }
    $stmt->bind_param('i', $item['id']);
    if (!$stmt->execute()) {
        error_log("Klaida šalinant skrynią: " . $stmt->error);
        return "Klaida šalinant skrynią.";
    }

    return $message;
}
?>
