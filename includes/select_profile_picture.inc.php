<?php

require 'dbh.inc.php'; // Jūsų duomenų bazės prisijungimo failas


require_once 'auth.inc.php';

// Patikrinkite, ar vartotojas prisijungęs
check_auth();

$userId = get_user_id();

$csrfToken = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Patikrinkite CSRF žetoną (jei naudojate)
    if (!isset($_POST['csrf_token']) || !check_csrf_token($_POST['csrf_token'])) {
        if (!isset($_SESSION['upload_errors'])) {
            $_SESSION['upload_errors'] = [];
        }
        $_SESSION['upload_errors'][] = "Neteisingas CSRF žetonas.";
        header('Location: ../profile.php');
        exit();
    }

    if (isset($_POST['selected_picture'])) {
        $selectedPicture = $_POST['selected_picture'];

        // Patikrinkite, ar pasirinkta nuotrauka yra leidžiamame sąraše
        $allowedPictures = [];
        for ($i = 1; $i <= 12; $i++) {
            $allowedPictures[] = "avatar{$i}.webp"; // Pakeiskite plėtinį, jei reikia
        }

        if (in_array($selectedPicture, $allowedPictures)) {
            // Atnaujinkite vartotojo profilio nuotrauką duomenų bazėje
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE usersId = ?");
            $stmt->bind_param("si", $selectedPicture, $userId);
            $stmt->execute();
            $stmt->close();

            $_SESSION['upload_success'] = "Profilio nuotrauka sėkmingai atnaujinta.";
        } else {
            $_SESSION['upload_errors'][] = "Pasirinkta neteisinga nuotrauka.";
        }
    } else {
        $_SESSION['upload_errors'][] = "Nepasirinkote profilio nuotraukos.";
    }

    // **Pridėta: nustatome aktyvią sekciją po formos pateikimo**
    $_SESSION['active_section'] = 'picture-section';

    header('Location: ../profile.php');
    exit();
}
?>
