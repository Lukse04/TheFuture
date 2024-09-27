<?php
// inventory.php

session_start();

if (!isset($_SESSION['userid'])) {
    die("Jūs nesate prisijungęs!");
}

$userId = $_SESSION['userid'];

// Sukuriame CSRF apsaugos žymeklį (token)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

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
            <?php
            require_once 'includes/inventory.inc.php';
            echo fetchInventory($conn, $userId);
            ?>
        </tbody>
    </table>

    <!-- Pridėsime pranešimų vietą -->
    <div id="message"></div>

    <script>
    const csrfToken = "<?php echo $csrfToken; ?>";

    // Funkcija daiktui naudoti per AJAX
    function useItem(itemId) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "includes/inventory.inc.php", true);
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
        xhr.open("POST", "includes/inventory.inc.php", true);
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
        xhr.open("GET", "includes/inventory.inc.php?ajax=1", true);
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
