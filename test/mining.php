<?php
// mining.php
require '../includes/auth.inc.php';
require '../includes/dbh.inc.php';

check_auth();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Bitcoin, Monero ir Dogecoin Kasimo Sistema</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 1000px; margin: auto; padding: 20px; }
        h1, h2, h3 { color: #333; }
        #mining-info, #purchase-section { margin-bottom: 30px; }
        #purchase-result { margin-top: 10px; }
        .error { color: red; }
        .success { color: green; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>

<div class="container">
    <h1>Bitcoin, Monero ir Dogecoin Kasimo Sistema</h1>

    <div id="mining-info">
        <h2>Bendra hash rate:</h2>
        <ul id="total-hash-rates"></ul>
        <h3>Kasimo įranga:</h3>
        <ul id="mining-items"></ul>
    </div>

    <div id="purchase-section">
        <h2>Pirkti Kasimo Įrangą</h2>
        <form id="purchase-form">
            <label for="shop-items">Pasirinkite įrangą:</label>
            <select id="shop-items" name="item_id" required>
                <option value="">Pasirinkite įrangą</option>
            </select>
            <br><br>
            <label for="quantity">Kiekis:</label>
            <input type="number" id="quantity" name="quantity" min="1" value="1" required>
            <br><br>
            <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <button type="submit" id="buy-button">Pirkti</button>
        </form>
        <div id="purchase-result"></div>
    </div>

    <div id="statistics-section">
        <h2>Kasimo Statistika</h2>
        <a href="statistics.php">Peržiūrėti Statistiką</a>
    </div>
</div>

<script>
function fetchMiningInfo() {
    $.ajax({
        url: 'get_mining_info.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#total-hash-rates').empty();
            for (const [currency, hash_rate] of Object.entries(data.total_hash_rates)) {
                $('#total-hash-rates').append('<li>' + currency + ': ' + hash_rate.toFixed(2) + ' H/s</li>');
            }

            $('#mining-items').empty();
            data.mining_items.forEach(function(item) {
                $('#mining-items').append('<li>' + item.item_name + ' - ' + item.hash_rate + ' H/s x ' + item.quantity + ' (' + item.cryptocurrency + ')</li>');
            });
        },
        error: function(xhr) {
            console.error('Klaida gaunant kasimo informaciją:', xhr.responseText);
        }
    });
}

function fetchShopItems() {
    $.ajax({
        url: 'get_shop_items.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#shop-items').empty();
            $('#shop-items').append('<option value="">Pasirinkite įrangą</option>');
            data.shop_items.forEach(function(item) {
                $('#shop-items').append('<option value="' + item.id + '">' + item.item_name + ' - ' + item.price + ' ' + item.currency + ' (' + item.cryptocurrency + '), Efektyvumas: ' + item.efficiency + ', Algoritmai: ' + item.supported_algorithms.join(', ') + '</option>');
            });
        },
        error: function(xhr) {
            console.error('Klaida gaunant parduotuvės įrenginius:', xhr.responseText);
        }
    });
}

$(document).ready(function() {
    fetchMiningInfo();
    fetchShopItems();

    $('#purchase-form').submit(function(event) {
        event.preventDefault();

        var item_id = $('#shop-items').val();
        var quantity = $('#quantity').val();
        var csrf_token = $('#csrf_token').val();

        if (!item_id) {
            alert('Pasirinkite įrangą');
            return;
        }

        if (quantity < 1) {
            alert('Kiekis turi būti bent 1');
            return;
        }

        $.ajax({
            url: 'buy_mining_item.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                item_id: item_id,
                quantity: quantity,
                csrf_token: csrf_token
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#purchase-result').text(response.success).removeClass('error').addClass('success');
                    fetchMiningInfo();
                } else if (response.error) {
                    $('#purchase-result').text('Klaida: ' + response.error).removeClass('success').addClass('error');
                }
            },
            error: function(xhr) {
                var error = 'Nepavyko atlikti pirkimo';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    error = xhr.responseJSON.error;
                }
                $('#purchase-result').text('Klaida: ' + error).removeClass('success').addClass('error');
            }
        });
    });

    setInterval(fetchMiningInfo, 60000);
});
</script>

</body>
</html>
