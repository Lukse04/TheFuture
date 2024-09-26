<?php
// Įjunkite klaidų pranešimus (tik vystymo aplinkoje)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pradėkite sesiją
session_start();

// CSRF tokeno generavimas
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Užtikrinkite, kad vartotojas yra prisijungęs ir yra administratorius
if (isset($_SESSION['usertype'])) {
    $mysqliType = $_SESSION['usertype'];

    if ($mysqliType !== 'admin') {
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}

// Įtraukite duomenų bazės prisijungimo failą
require_once 'dbh.inc.php';

// Gaukite vartotojų duomenis saugiai
$query = "SELECT usersId, usersName, usersEmail, user_type FROM users";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Klaida ruošiant užklausą: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

// Sukurkite vartotojų sąrašą
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
$conn->close();
