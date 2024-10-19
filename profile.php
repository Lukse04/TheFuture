<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: signin.php"); // Pataisykite failo pavadinimą, jei reikia
    exit();
}

require_once 'includes/dbh.inc.php'; // Pridėkite duomenų bazės prisijungimą, jei reikia
require_once 'includes/profile_functions.inc.php';

// Sukuriame CSRF žetoną
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION["userid"];

// Gauname vartotojo duomenis iš duomenų bazės
$fetch_profile = getUserProfile($conn, $user_id);

// Patikriname, ar yra klaidų pranešimų iš sesijos
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['errors']);

// Patikriname, ar yra sėkmės pranešimas
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);

// Nustatome aktyvią sekciją
$activeSection = 'profile-section';
if (isset($_SESSION['active_section'])) {
    $activeSection = $_SESSION['active_section'];
    unset($_SESSION['active_section']);
}
?> 

<!DOCTYPE HTML>
<html lang="lt">
<head>
    <?php
    $title = 'Jūsų profilis';
    include_once 'include_once/header.php';
    ?>
    <link rel="stylesheet" href="css/profile.css">
    <!-- Pridėkite Font Awesome CSS, jei dar nepridėjote -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include_once 'include_once/navbar.php'; ?>

    <section class="hero">
        <div class="container2">
            <div id="logo-profile">
                <h1 class="logo-profile">Profilis</h1>
            </div>
            <div class="leftbox">
                <nav>
                    <a href="#" class="nav-item <?php echo ($activeSection == 'profile-section') ? 'active' : ''; ?>" data-target="profile-section">
                        <i class="fa fa-user"></i>
                        <span>Asmeninė informacija</span>
                    </a>
                    <a href="#" class="nav-item <?php echo ($activeSection == 'picture-section') ? 'active' : ''; ?>" data-target="picture-section">
                        <i class="fa fa-images"></i>
                        <span>Profilio nuotraukos</span>
                    </a>
                    <a href="#" class="nav-item <?php echo ($activeSection == 'delete-section') ? 'active' : ''; ?>" data-target="delete-section">
                        <i class="fa fa-trash"></i>
                        <span>Ištrinti profilį</span>
                    </a>
                </nav>
            </div>
            <div class="rightbox">
                <?php if (!empty($successMessage)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Asmeninės informacijos sekcija -->
                <div class="content-section" id="profile-section" style="<?php echo ($activeSection == 'profile-section') ? '' : 'display: none;'; ?>">
                    <form action="includes/profile.inc.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="profile">
                            <h1>Asmeninė informacija</h1>
                            <h2>Vartotojo vardas</h2>
                            <p><input type="text" id="username" name="new_username" placeholder="Vartotojo vardas" value="<?= htmlspecialchars($fetch_profile['usersName']); ?>"></p>
                            <h2>El. paštas</h2>
                            <p><input type="email" id="email" name="new_email" placeholder="El. paštas" value="<?= htmlspecialchars($fetch_profile['usersEmail']); ?>"></p>
                            <h2>Senas slaptažodis</h2>
                            <p><input type="password" id="old_password" name="old_password" placeholder="Senas slaptažodis"></p>
                            <h2>Naujas slaptažodis</h2>
                            <p><input type="password" id="new_password" name="new_password" placeholder="Naujas slaptažodis"></p>
                        </div>
                        <div class="button">
                            <input type="submit" name="update_profile" value="Atnaujinti profilį">
                        </div>
                    </form>
                </div>

                <!-- Profilio nuotraukų sekcija -->
                <div class="content-section" id="picture-section" style="<?php echo ($activeSection == 'picture-section') ? '' : 'display: none;'; ?>">
                    <h1>Profilio nuotraukos</h1>

                    <!-- Pranešimai vartotojui -->
                    <?php if (!empty($_SESSION['upload_success'])): ?>
                        <div class="success-message">
                            <?php echo htmlspecialchars($_SESSION['upload_success']); ?>
                        </div>
                        <?php unset($_SESSION['upload_success']); ?>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['upload_errors'])): ?>
                        <div class="error-messages">
                            <?php foreach ($_SESSION['upload_errors'] as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                            <?php unset($_SESSION['upload_errors']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Pasirinkti iš iš anksto paruoštų nuotraukų -->
                    <h2>Pasirinkite profilio nuotrauką</h2>
                    <form action="includes/select_profile_picture.inc.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="predefined-pictures">
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                $avatar = "avatar{$i}.webp"; // Pakeiskite plėtinį, jei reikia
                                $isChecked = ($fetch_profile['profile_picture'] === $avatar) ? 'checked' : '';
                                echo '
                                <label>
                                    <input type="radio" name="selected_picture" value="' . $avatar . '" ' . $isChecked . '>
                                    <img src="image/assets/profile_pictures/' . $avatar . '" alt="Avatar ' . $i . '" class="avatar-image">
                                </label>
                                ';
                            }
                            ?>
                        </div>
                        <div class="button">
                            <button type="submit" class="save-button">Išsaugoti pasirinktą nuotrauką</button>
                        </div>
                    </form>

                    <?php
                        include_once 'include_once/profile_picture.php';
                    ?>

                </div>

                <!-- Profilio ištrynimo sekcija -->
                <div class="content-section" id="delete-section" style="<?php echo ($activeSection == 'delete-section') ? '' : 'display: none;'; ?>">
                    <h1>Profilių ištrynimas</h1>
                    <form action="includes/delete_profile.inc.php" method="post" onsubmit="return confirm('Ar tikrai norite ištrinti savo profilį? Šio veiksmo atšaukti negalėsite.');">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="button2">    
                            <input type="submit" value="Ištrinti profilį">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- JavaScript kodas -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var navItems = document.querySelectorAll('.nav-item');
        var contentSections = document.querySelectorAll('.content-section');

        navItems.forEach(function(navItem) {
            navItem.addEventListener('click', function(event) {
                event.preventDefault();

                // Pašaliname 'active' klasę iš visų navigacijos elementų
                navItems.forEach(function(item) {
                    item.classList.remove('active');
                });

                // Pridedame 'active' klasę paspaustam navigacijos elementui
                navItem.classList.add('active');

                // Paslepiame visas turinio sekcijas
                contentSections.forEach(function(section) {
                    section.style.display = 'none';
                });

                // Parodome atitinkamą turinio sekciją
                var targetId = navItem.getAttribute('data-target');
                var targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }
            });
        });
    });
    </script>
    <?php include_once 'include_once/footer.php'; ?>
</body>
</html>
