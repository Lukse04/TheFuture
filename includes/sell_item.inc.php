<?php
// includes/sell_item.inc.php

require_once 'dbh.inc.php';

function sellItem($conn, $userId, $itemId) {
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

    // Apskaičiuojame daikto pardavimo kainą
    $sellPrice = calculateSellPrice($item['item_level'], $item['item_type']);

    // Pridedame pardavimo kainą į vartotojo sąskaitą iš lentelės card_numbers
    $stmt = $conn->prepare("UPDATE card_numbers SET account_balance = account_balance + ? WHERE user_id = ?");
    if ($stmt === false) {
        return ["status" => "error", "message" => "Klaida atnaujinant balansą."];
    }
    $stmt->bind_param('di', $sellPrice, $userId);
    $stmt->execute();

    // Pašaliname daiktą iš inventoriaus
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    if ($stmt === false) {
        return ["status" => "error", "message" => "Klaida šalinant daiktą iš inventoriaus."];
    }
    $stmt->bind_param('i', $itemId);
    $stmt->execute();

    return ["status" => "success", "message" => "Sėkmingai pardavėte daiktą už $sellPrice €."]; // Pakeičiau į € vietoj monetų
}

// Funkcija, kuri apskaičiuoja daikto pardavimo kainą pagal jo lygį ir tipą
function calculateSellPrice($level, $type) {
    if ($type == 'chest') {
        return $level * 10; // Pvz., 1 lygio skrynia bus verta 10 €
    } elseif ($type == 'talent') {
        return $level * 20; // Pvz., 1 lygio talentas bus vertas 20 €
    }
    return 0; // Jeigu tipas neatitinka, grąžiname 0
}
