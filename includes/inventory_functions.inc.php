<?php
// includes/inventory_functions.inc.php

// Pridedame daiktą į vartotojo inventorių
function addToInventory($conn, $userId, $itemType, $level, $power = null) {
    $stmt = $conn->prepare("INSERT INTO inventory (user_id, item_type, item_level, item_power) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die("Klaida ruošiant užklausą (addToInventory): " . $conn->error);
    }
    $stmt->bind_param('isii', $userId, $itemType, $level, $power);
    if (!$stmt->execute()) {
        die("Klaida vykdant užklausą (addToInventory): " . $stmt->error);
    }
}
