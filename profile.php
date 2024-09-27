<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: singin.php");
    exit();
}

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
?> 

<!DOCTYPE HTML>
<html lang="lt">
<head>
    <?php
    $titel = 'Jūsų profilis';
    include_once 'include_once/header.php';
    ?>

    <link rel="stylesheet" href="css/profile.css">
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
                    <a href="#" class="active">
                        <i class="fa fa-user"></i>
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
        </div>
    </section>
</body>
</html>
