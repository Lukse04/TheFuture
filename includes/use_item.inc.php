<?php
// includes/use_item.inc.php

require_once 'dbh.inc.php';
require_once 'talent_functions.inc.php'; // Talentų funkcijos, kuriose yra addToInventory()

function useItem($conn, $userId, $itemId) {
    // Patikriname, ar daiktas priklauso vartotojui
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ? AND user_id = ?");
    if ($stmt === false) {
        return ["status" => "error", "message" => "SQL klaida ruošiant užklausą."];
    }
    $stmt->bind_param('ii', $itemId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if (!$item) {
        return ["status" => "error", "message" => "Daiktas nerastas arba jis jums nepriklauso."];
    }

    // Naudojame daiktą pagal jo tipą
    if ($item['item_type'] == 'chest') {
        // Jei tai skrynia, ją atidarome
        $message = openChest($conn, $userId, $item);
        return ["status" => "success", "message" => $message];
    } elseif ($item['item_type'] == 'talent') {
        // Jei tai talentas, jį naudojame
        return ["status" => "success", "message" => "Jūs panaudojote talentą: Lygis {$item['item_level']}, Galia: {$item['item_power']}"];
    }

    // Jei daikto tipas neatpažintas
    return ["status" => "error", "message" => "Nežinomas daikto tipas."];
}

// Funkcija skryniai atidaryti ir talentams sugeneruoti
function openChest($conn, $userId, $item) {
    // Pranešimas apie atidarytą skrynią
    $message = "Jūs atidarėte skrynią: lygis {$item['item_level']}!";

    // Sugeneruojame naujus talentus
    $talents = generateTalents($conn, $userId, $item['item_level']); // Generuojame talentus

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
