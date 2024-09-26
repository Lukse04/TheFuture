<?php
// inventory.php

require_once 'includes/dbh.inc.php';
require_once 'includes/use_item.inc.php';
require_once 'includes/sell_item.inc.php';
require_once 'includes/talent_functions.inc.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['userid'])) {
    die("Jūs nesate prisijungęs!");
}

$userId = $_SESSION['userid'];

// Sukuriame CSRF apsaugos žymeklį (token)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// Jei tai AJAX užklausa daiktui naudoti
if (isset($_POST['action']) && $_POST['action'] === 'useItem') {
    header('Content-Type: application/json');
    if (!hash_equals($csrfToken, $_POST['csrf_token'])) {
        echo json_encode(["status" => "error", "message" => "Neteisingas CSRF žymeklis."]);
        exit();
    }
    $itemId = intval($_POST['id']);
    $result = useItem($conn, $userId, $itemId);
    echo json_encode($result);
    exit();
}

// Jei tai AJAX užklausa daiktui parduoti
if (isset($_POST['action']) && $_POST['action'] === 'sellItem') {
    header('Content-Type: application/json');
    if (!hash_equals($csrfToken, $_POST['csrf_token'])) {
        echo json_encode(["status" => "error", "message" => "Neteisingas CSRF žymeklis."]);
        exit();
    }
    $itemId = intval($_POST['id']);
    $result = sellItem($conn, $userId, $itemId);
    echo json_encode($result);
    exit();
}

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

// Jei tai AJAX užklausa inventoriaus atnaujinimui
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    echo fetchInventory($conn, $userId);
    exit();
}

?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <?php
    $titel = 'Jūsų inventorius';
    include_once 'include_once/header.php';
    ?>
    <link rel="stylesheet" type="text/css" href="css/inventory.css">
</head>
<body>
    <?php include_once 'include_once/navbar.php'; ?>

    <h2>Jūsų inventorius</h2>
    <table>
        <tr>
            <th>Daikto pavadinimas</th>
            <th>Tipas</th>
            <th>Lygis</th>
            <th>Galia</th>
            <th>Veiksmai</th>
        </tr>
        <tbody id="inventoryTable">
            <?php echo fetchInventory($conn, $userId); ?>
        </tbody>
    </table>

    <!-- Pridėsime pranešimų vietą -->
    <div id="message"></div>

    <script>
    const csrfToken = "<?php echo $csrfToken; ?>";

    // Funkcija daiktui naudoti per AJAX
    function useItem(itemId) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "inventory.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    document.getElementById("message").innerHTML = response.message;

                    if (response.status === "success") {
                        updateInventory(); // Po sėkmingo daikto panaudojimo atnaujiname inventorių
                    }
                } catch (e) {
                    document.getElementById("message").innerHTML = "Klaida apdorojant serverio atsakymą.";
                }
            }
        };
        xhr.send("action=useItem&id=" + itemId + "&csrf_token=" + encodeURIComponent(csrfToken));
    }

    // Funkcija daiktui parduoti per AJAX
    function sellItem(itemId) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "inventory.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    document.getElementById("message").innerHTML = response.message;

                    if (response.status === "success") {
                        updateInventory(); // Po sėkmingo pardavimo atnaujiname inventorių
                    }
                } catch (e) {
                    document.getElementById("message").innerHTML = "Klaida apdorojant serverio atsakymą.";
                }
            }
        };
        xhr.send("action=sellItem&id=" + itemId + "&csrf_token=" + encodeURIComponent(csrfToken));
    }

    // Funkcija, kuri AJAX būdu atnaujina inventorių
    function updateInventory() {
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "inventory.php?ajax=1", true);
        xhr.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                document.getElementById("inventoryTable").innerHTML = this.responseText;
            }
        };
        xhr.send();
    }
    </script>
</body>
</html>
