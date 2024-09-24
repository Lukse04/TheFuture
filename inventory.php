<?php
require_once 'includes/dbh.inc.php';
session_start();

if (!isset($_SESSION['userid'])) {
    die("Jūs nesate prisijungęs!");
}

$userId = $_SESSION['userid'];

// Sukuriame CSRF apsaugos žymeklį (token)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Užtikriname, kad kintamasis $csrfToken yra priskirtas ir naudojamas tinkamai
$csrfToken = $_SESSION['csrf_token'];

// Funkcija, kuri ištraukia inventoriaus elementus
function fetchInventory($conn, $userId, $csrfToken) {
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
                    <button onclick='useItem({$row['id']}, \"$csrfToken\")'>Naudoti</button> | 
                    <button onclick='sellItem({$row['id']}, \"$csrfToken\")'>Parduoti</button>
                </td>
              </tr>";
        }
    } else {
        $inventoryHtml = "<tr><td colspan='5'>Jūsų inventorius tuščias.</td></tr>";
    }
    return $inventoryHtml;
}

if (!isset($_GET['ajax'])) {
    // Pagrindinis puslapis
    ?>
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
            <?php echo fetchInventory($conn, $userId, $csrfToken); ?>
        </tbody>
    </table>

    <!-- Pridėsime pranešimų vietą -->
    <div id="message"></div>

    <script>
    
    // Funkcija daiktui naudoti per AJAX
    function useItem(itemId, csrfToken) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "use_item.php", true);
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
        xhr.send("id=" + itemId + "&csrf_token=" + encodeURIComponent(csrfToken));
    }

    // Funkcija daiktui parduoti per AJAX
    function sellItem(itemId, csrfToken) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "sell_item.php", true);
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
        xhr.send("id=" + itemId + "&csrf_token=" + encodeURIComponent(csrfToken));
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

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fafafa;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        button {
            background-color: #0077ff;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        button:hover {
            background-color: #005bb5;
        }
        #message {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #0077ff;
            color: #0077ff;
        }
    </style>
    <?php
} else {
    // Kai inventoriaus atnaujinimas siunčiamas per AJAX
    echo fetchInventory($conn, $userId, $csrfToken);
}
?>
