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
    echo "Vartotojo ID yra: " . $userId . "<br>";
}

require_once 'includes/dbh.inc.php';

// Patikrinkite ryšį
if ($conn->connect_error) {
    die("Nepavyko prisijungti prie duomenų bazės: " . $conn->connect_error . "<br>");
} else {
    echo "Duomenų bazės ryšys sėkmingas.<br>";
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

// Patikrinkime, ar vartotojas jau turi kortelę
function checkIfUserHasCard($conn, $userId) {
    $sql = "SELECT card_number, account_balance FROM card_numbers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Klaida ruošiant SQL užklausą: " . $conn->error . "<br>"); // Diagnostikos klaidos pranešimas
    }
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        die("Klaida vykdant SQL užklausą: " . $stmt->error . "<br>"); // Diagnostikos klaidos pranešimas
    }
    $result = $stmt->get_result();
    $userHasCard = $result->num_rows > 0;
    
    if ($userHasCard) {
        $row = $result->fetch_assoc();
        // Jei vartotojas jau turi kortelę, grąžiname kortelės informaciją
        return $row;
    }
    return false; // Kortelės nėra
}

// Sugeneruokite kortelę, jei jos nėra
$userCard = checkIfUserHasCard($conn, $userId);

if ($userCard) {
    // Jei vartotojas turi kortelę, parodykite kortelės informaciją
    echo "Vartotojas jau turi kortelės numerį: " . $userCard['card_number'] . "<br>";
    echo "Sąskaitos balansas: " . $userCard['account_balance'] . "<br>";
} else {
    // Jei vartotojas neturi kortelės, sugeneruokite naują
    do {
        $randomCardNumber = generateCardNumber();
    } while (isDuplicate($conn, $randomCardNumber)); // Užtikrinkite, kad numeris nėra dubliuojamas

    // Sąskaitos balansas
    $accountBalance = 10.0;

    // Diagnostikos pranešimai apie įvedamus duomenis
    echo "Generuojamas naujas kortelės numeris: $randomCardNumber<br>";
    echo "Vartotojo ID: $userId<br>";
    echo "Sąskaitos balansas: $accountBalance<br>";

    // SQL įterpimo užklausa
    $stmt = $conn->prepare("INSERT INTO card_numbers (card_number, account_balance, user_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("Klaida ruošiant įterpimo užklausą: " . $conn->error . "<br>"); // Diagnostikos klaidos pranešimas
    }

    $stmt->bind_param("sdi", $randomCardNumber, $accountBalance, $userId);

    if ($stmt->execute()) {
        echo "Kortelės numeris sėkmingai įterptas: $randomCardNumber<br>";
    } else {
        // Išveskite klaidos pranešimą, jei įterpimas nepavyko
        echo "Klaida vykdant įterpimo užklausą: " . $stmt->error . "<br>"; // Diagnostikos klaidos pranešimas
    }

    $stmt->close();
}

// Funkcija tikrinanti, ar kortelės numeris nėra dubliuojamas
function isDuplicate($conn, $cardNumber) {
    $sql = "SELECT card_number FROM card_numbers WHERE card_number = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Klaida ruošiant SQL užklausą dubliavimo patikrinimui: " . $conn->error . "<br>"); // Diagnostikos klaidos pranešimas
    }

    $stmt->bind_param("s", $cardNumber);
    if (!$stmt->execute()) {
        die("Klaida vykdant SQL užklausą dubliavimo patikrinimui: " . $stmt->error . "<br>"); // Diagnostikos klaidos pranešimas
    }

    $stmt->store_result();
    $duplicate = $stmt->num_rows > 0;
    $stmt->close();
    return $duplicate;
}

$conn->close();
?>
