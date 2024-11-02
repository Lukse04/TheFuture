<?php
// statistics.php
require '../includes/auth.inc.php'; // Autentifikacijos failas
require '../includes/dbh.inc.php';   // Duomenų bazės prisijungimo failas

// Patikrinkite, ar vartotojas yra prisijungęs
check_auth();

// Pagrindiniai duomenys gali būti palikti tuščia, nes duomenys bus užkraunami per AJAX
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Kasimo Statistika</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 900px; margin: auto; padding: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background-color: #f4f4f4; }
        .error { color: red; }
        .success { color: green; }
    </style>
    <!-- Įtraukite jQuery biblioteką -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container">
    <h1>Kasimo Statistika</h1>
    <table id="statistics-table">
        <thead>
            <tr>
                <th>Valiuta</th>
                <th>Kasinta per Valandą</th>
                <th>Kasinta per Dieną</th>
                <th>Kasinta per Savaitę</th>
                <th>Liko Iškasti</th>
                <th>Dabartinis Blokų Apdovanojimas</th>
                <th>Blokų Skaičius</th>
            </tr>
        </thead>
        <tbody>
            <!-- Dinamiškai pridedami duomenys per AJAX -->
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    // Funkcija duomenims gauti ir atnaujinti
    function updateStatistics() {
        $.ajax({
            url: 'get_statistics.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Atnaujinti statistikos lentelę
                var tbody = $('#statistics-table tbody');
                tbody.empty();
                $.each(data.stats, function(currency, stats) {
                    var row = '<tr>' +
                        '<td>' + htmlspecialchars(currency) + '</td>' +
                        '<td>' + number_format(stats.hourly, 9) + '</td>' +
                        '<td>' + number_format(stats.daily, 9) + '</td>' +
                        '<td>' + number_format(stats.weekly, 9) + '</td>' +
                        '<td>' + number_format(stats.remaining, 9) + '</td>' +
                        '<td>' + number_format(stats.block_reward, 8) + ' ' + htmlspecialchars(currency) + '</td>' +
                        '<td>' + stats.current_block_count + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });

                // Jei norite rodyti bendrą pinigų sumą, galite pridėti papildomą elementą
                // Šiuo atveju, pašalinome „Papildoma Statistika“ sekciją
            },
            error: function(xhr) {
                console.error('Klaida gaunant statistiką:', xhr.responseText);
            }
        });
    }

    // Funkcijos HTML specialių simbolių kodavimui ir skaičių formatavimui
    function htmlspecialchars(text) {
        return $('<div>').text(text).html();
    }

    function number_format(number, decimals) {
        return Number(number).toFixed(decimals);
    }

    // Atlikti pirmą atnaujinimą, kai puslapis įkeliamas
    updateStatistics();

    // Nustatyti intervalą, kas 10 sekundžių atnaujinti statistiką
    setInterval(updateStatistics, 10000); // 10000 milisekundžių = 10 sekundžių
});
</script>

</body>
</html>
