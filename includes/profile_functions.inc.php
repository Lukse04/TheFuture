<?php
// includes/profile_functions.inc.php

require_once 'dbh.inc.php';

// Funkcija gauti vartotojo profilio informaciją
function getUserProfile($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE usersId = ?");
    if (!$stmt) {
        die("Klaida ruošiant užklausą: " . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fetch_profile = $result->fetch_assoc();
    $stmt->close();
    return $fetch_profile;
}

// Funkcija atnaujinti vartotojo profilį
function updateUserProfile($conn, $user_id, $new_username, $new_email, $old_password, $new_password) {
    $errors = [];

    // Vartotojo vardo validacija
    if (empty($new_username)) {
        $errors[] = "Vartotojo vardas negali būti tuščias.";
    }

    // El. pašto validacija
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Įveskite teisingą el. pašto adresą.";
    }

    // Jei norima keisti slaptažodį, tikriname senąjį slaptažodį
    if (!empty($new_password)) {
        if (empty($old_password)) {
            $errors[] = "Norėdami pakeisti slaptažodį, įveskite senąjį slaptažodį.";
        } else {
            // Gauname dabartinį slaptažodžio hash iš duomenų bazės
            $stmt = $conn->prepare("SELECT usersPwd FROM users WHERE usersId = ?");
            if (!$stmt) {
                $errors[] = "Klaida ruošiant užklausą.";
                return $errors;
            }
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $hashedPwd = $row['usersPwd'];
                if (!password_verify($old_password, $hashedPwd)) {
                    $errors[] = "Neteisingas senasis slaptažodis.";
                }
            } else {
                $errors[] = "Vartotojas nerastas.";
            }
            $stmt->close();
        }
    }

    if (!empty($errors)) {
        return $errors;
    }

    // Atnaujiname vartotojo duomenis
    $stmt = $conn->prepare("UPDATE users SET usersName = ?, usersEmail = ? WHERE usersId = ?");
    if (!$stmt) {
        $errors[] = "Klaida ruošiant užklausą.";
        return $errors;
    }
    $stmt->bind_param('ssi', $new_username, $new_email, $user_id);
    $stmt->execute();
    $stmt->close();

    // Jei yra naujas slaptažodis, jį atnaujiname
    if (!empty($new_password)) {
        $hashedNewPwd = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET usersPwd = ? WHERE usersId = ?");
        if (!$stmt) {
            $errors[] = "Klaida ruošiant užklausą.";
            return $errors;
        }
        $stmt->bind_param('si', $hashedNewPwd, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    return true;
}
