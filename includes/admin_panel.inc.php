<?php
// Įjunkite klaidų pranešimus (tik vystymo aplinkoje)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pradėkite sesiją
require_once 'includes/auth.inc.php';

check_auth();

if (!is_admin()) {
    header("Location: no_access.php");
    exit;
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
