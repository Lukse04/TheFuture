<?php
// includes/inventory.inc.php

require_once 'dbh.inc.php'; // Duomenų bazės ryšys

// Funkcija, kuri ištraukia inventoriaus elementus
function fetchInventory($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $inventoryHtml = '';
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $itemName = htmlspecialchars($row['item_name'] ?: 'Be pavadinimo', ENT_QUOTES, 'UTF-8');
            $itemType = htmlspecialchars(ucfirst($row['item_type']), ENT_QUOTES, 'UTF-8');
            $itemLevel = htmlspecialchars($row['item_level'], ENT_QUOTES, 'UTF-8');
            $itemPower = htmlspecialchars($row['item_power'] ?: 'N/A', ENT_QUOTES, 'UTF-8');

            // Atvaizduojame inventoriaus daiktus lentelėje
            $inventoryHtml .= "<tr id='item-{$row['id']}'>
                <td>$itemName</td>
                <td>$itemType</td>
                <td>$itemLevel</td>
                <td>$itemPower</td>
                <td>
                    <button onclick='useItem({$row['id']})'>Naudoti</button> | 
                    <button onclick='sellItem({$row['id']})'>Parduoti</button>
                </td>
              </tr>";
        }
    } else {
        $inventoryHtml = "<tr><td colspan='5'>Jūsų inventorius tuščias.</td></tr>";
    }
    return $inventoryHtml;
}

// Jei tai AJAX užklausa daiktui naudoti
if (isset($_POST['action']) && $_POST['action'] === 'useItem') {
    session_start();
    header('Content-Type: application/json');
    require_once 'use_item.inc.php';
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(["status" => "error", "message" => "Neteisingas CSRF žymeklis."]);
        exit();
    }
    $userId = $_SESSION['userid'];
    $itemId = intval($_POST['id']);
    $result = useItem($conn, $userId, $itemId);
    echo json_encode($result);
    exit();
}

// Jei tai AJAX užklausa daiktui parduoti
if (isset($_POST['action']) && $_POST['action'] === 'sellItem') {
    session_start();
    header('Content-Type: application/json');
    require_once 'sell_item.inc.php';
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(["status" => "error", "message" => "Neteisingas CSRF žymeklis."]);
        exit();
    }
    $userId = $_SESSION['userid'];
    $itemId = intval($_POST['id']);
    $result = sellItem($conn, $userId, $itemId);
    echo json_encode($result);
    exit();
}

// Jei tai AJAX užklausa inventoriaus atnaujinimui
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    session_start();
    $userId = $_SESSION['userid'];
    echo fetchInventory($conn, $userId);
    exit();
}
