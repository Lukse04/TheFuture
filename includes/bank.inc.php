<?php
// Įjungti klaidų pranešimus
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Patikrinkite, ar vartotojo ID yra nustatytas sesijoje
if (!isset($_SESSION["userid"])) {
    die("Vartotojo ID nėra nustatytas. Patikrinkite prisijungimą.<br>");
} else {
    $userId = $_SESSION["userid"];
}

// Įtraukite duomenų bazės prisijungimą
require_once 'dbh.inc.php';

// Patikrinkite ryšį
if ($conn->connect_error) {
    die("Nepavyko prisijungti prie duomenų bazės: " . $conn->connect_error . "<br>");
}

// Kortelės numerio generavimo funkcija
function generateCardNumber() {
    $cardNumber = '';
    for ($i = 0; $i < 16; $i++) {
        $cardNumber .= rand(0, 9);
        if (($i + 1) % 4 == 0 && $i !== 15) {
            $cardNumber .= ' ';
        }
    }
    return $cardNumber;
}

// Funkcija tikrinanti, ar kortelės numeris nėra dubliuojamas
function isDuplicate($conn, $cardNumber) {
    $sql = "SELECT card_number FROM card_numbers WHERE card_number = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Klaida ruošiant SQL užklausą dubliavimo patikrinimui: " . $conn->error . "<br>");
    }

    $stmt->bind_param("s", $cardNumber);
    if (!$stmt->execute()) {
        die("Klaida vykdant SQL užklausą dubliavimo patikrinimui: " . $stmt->error . "<br>");
    }

    $stmt->store_result();
    $duplicate = $stmt->num_rows > 0;
    $stmt->close();
    return $duplicate;
}

// Patikrinkime, ar vartotojas jau turi kortelę
function checkIfUserHasCard($conn, $userId) {
    $sql = "SELECT card_number, account_balance FROM card_numbers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Klaida ruošiant SQL užklausą: " . $conn->error . "<br>");
    }
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        die("Klaida vykdant SQL užklausą: " . $stmt->error . "<br>");
    }
    $result = $stmt->get_result();
    $userHasCard = $result->num_rows > 0;

    if ($userHasCard) {
        $row = $result->fetch_assoc();
        return $row;
    }
    return false;
}

// Funkcija, kuri sukuria naują kortelę vartotojui
function createNewCard($conn, $userId) {
    do {
        $randomCardNumber = generateCardNumber();
    } while (isDuplicate($conn, $randomCardNumber));

    $accountBalance = 10.0;

    $stmt = $conn->prepare("INSERT INTO card_numbers (card_number, account_balance, user_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("Klaida ruošiant įterpimo užklausą: " . $conn->error . "<br>");
    }

    $stmt->bind_param("sdi", $randomCardNumber, $accountBalance, $userId);

    if ($stmt->execute()) {
        return [
            'card_number' => $randomCardNumber,
            'account_balance' => $accountBalance
        ];
    } else {
        die("Klaida vykdant įterpimo užklausą: " . $stmt->error . "<br>");
    }
}

// Pagrindinė logika
$userCard = checkIfUserHasCard($conn, $userId);

if ($userCard) {
    // Vartotojas jau turi kortelę
    $cardInfo = $userCard;
} else {
    // Sukuriame naują kortelę
    $cardInfo = createNewCard($conn, $userId);
}

$conn->close();
