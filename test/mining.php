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
        /* ... existing styles ... */
        .facility {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
        }
        .facility h3 { margin-top: 0; }
    </style>
</head>
<body>

<div class="container">
    <!-- ... existing content ... -->

    <div id="facility-section">
        <h2>Patalpų Valdymas</h2>
        <div id="available-facilities"></div>
    </div>

    <div id="assign-equipment-section">
        <h2>Priskirti Įrangą Patalpoms</h2>
        <form id="assign-form">
            <label for="facility-select">Pasirinkite Patalpą:</label>
            <select id="facility-select" required></select>
            <br><br>
            <label for="equipment-select">Pasirinkite Įrangą:</label>
            <select id="equipment-select" required></select>
            <br><br>
            <label for="assign-quantity">Kiekis:</label>
            <input type="number" id="assign-quantity" min="1" value="1" required>
            <br><br>
            <button type="submit" class="button">Priskirti</button>
        </form>
        <div id="assign-result"></div>
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
                $('#mining-items').append('<li>' + item.item_name + ' - ' + item.hash_rate + ' H/s x ' + item.quantity + ' (' + item.cryptocurrency + ') Patalpoje: ' + item.facility_name + '</li>');
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

function fetchFacilities() {
    $.ajax({
        url: 'get_facilities.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            var container = $('#available-facilities');
            container.empty();
            data.facilities.forEach(function(facility) {
                var facilityDiv = $('<div class="facility"></div>');
                facilityDiv.append('<h3>' + facility.name + '</h3>');
                facilityDiv.append('<p>Tipas: ' + facility.type + '</p>');
                facilityDiv.append('<p>Kaina: ' + facility.price + '</p>');
                facilityDiv.append('<p>Talpa: ' + facility.capacity + '</p>');
                if (facility.type === 'rent') {
                    facilityDiv.append('<p>Nuomos terminas: ' + facility.rental_term + ' savaitės</p>');
                    facilityDiv.append('<p>Nutraukimo bauda: ' + facility.early_termination_fee + '</p>');
                }
                facilityDiv.append('<button class="button rent-button" data-facility-id="' + facility.id + '">Įsigyti</button>');
                container.append(facilityDiv);
            });
        },
        error: function(xhr) {
            console.error('Klaida gaunant patalpas:', xhr.responseText);
        }
    });
}

function fetchUserFacilities() {
    $.ajax({
        url: 'get_user_facilities.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#facility-select').empty();
            data.user_facilities.forEach(function(facility) {
                $('#facility-select').append('<option value="' + facility.user_facility_id + '">' + facility.name + ' (Talpa: ' + facility.capacity + ')</option>');
            });
        },
        error: function(xhr) {
            console.error('Klaida gaunant vartotojo patalpas:', xhr.responseText);
        }
    });
}

function fetchUserEquipment() {
    $.ajax({
        url: 'get_user_equipment.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#equipment-select').empty();
            data.user_equipment.forEach(function(equipment) {
                $('#equipment-select').append('<option value="' + equipment.item_id + '">' + equipment.item_name + ' (Turima: ' + equipment.quantity + ')</option>');
            });
        },
        error: function(xhr) {
            console.error('Klaida gaunant vartotojo įrangą:', xhr.responseText);
        }
    });
}

$(document).ready(function() {
    fetchMiningInfo();
    fetchShopItems();
    fetchFacilities();
    fetchUserFacilities();
    fetchUserEquipment();

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
                    fetchUserEquipment();
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

    $(document).on('click', '.rent-button', function() {
        var facility_id = $(this).data('facility-id');
        var csrf_token = '<?php echo $csrf_token; ?>';

        $.ajax({
            url: 'rent_facility.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                facility_id: facility_id,
                csrf_token: csrf_token
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.success);
                    fetchFacilities();
                    fetchUserFacilities();
                } else if (response.error) {
                    alert('Klaida: ' + response.error);
                }
            },
            error: function(xhr) {
                var error = 'Nepavyko įsigyti patalpos';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    error = xhr.responseJSON.error;
                }
                alert('Klaida: ' + error);
            }
        });
    });

    $('#assign-form').submit(function(event) {
        event.preventDefault();

        var facility_id = $('#facility-select').val();
        var item_id = $('#equipment-select').val();
        var quantity = $('#assign-quantity').val();
        var csrf_token = '<?php echo $csrf_token; ?>';

        if (!facility_id || !item_id) {
            alert('Pasirinkite patalpą ir įrangą');
            return;
        }

        if (quantity < 1) {
            alert('Kiekis turi būti bent 1');
            return;
        }

        $.ajax({
            url: 'assign_equipment.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                facility_id: facility_id,
                item_id: item_id,
                quantity: quantity,
                csrf_token: csrf_token
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#assign-result').text(response.success).removeClass('error').addClass('success');
                    fetchMiningInfo();
                    fetchUserEquipment();
                } else if (response.error) {
                    $('#assign-result').text('Klaida: ' + response.error).removeClass('success').addClass('error');
                }
            },
            error: function(xhr) {
                var error = 'Nepavyko priskirti įrangos';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    error = xhr.responseJSON.error;
                }
                $('#assign-result').text('Klaida: ' + error).removeClass('success').addClass('error');
            }
        });
    });

    setInterval(fetchMiningInfo, 60000);
});
</script>

</body>
</html>
