<?php
session_start();
require 'dbh.inc.php'; // Jūsų duomenų bazės prisijungimo failas

if (!isset($_SESSION['userid'])) {
    header('Location: ../singin.php');
    exit;
}

$userId = $_SESSION['userid'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pradėkite tranzakciją
    $conn->begin_transaction();

    try {

        // Ištrinkite vartotojo įrašą iš 'users' lentelės
        $stmt = $conn->prepare("DELETE FROM users WHERE usersId = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Patvirtinkite tranzakciją
        $conn->commit();

        // Išvalykite sesiją ir nukreipkite vartotoją
        session_unset();
        session_destroy();

        echo "Jūsų profilis ir visi susiję duomenys buvo sėkmingai ištrinti.";
        header('Location: ../index.php');
        exit;

    } catch (Exception $e) {
        // Atšaukite tranzakciją, jei įvyko klaida
        $conn->rollback();
        echo "Įvyko klaida ištrinant profilį: " . $e->getMessage();
    }
} else {
    header('Location: ../profile.php');
    exit;
}
?>
