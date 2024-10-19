<!-- singup.php -->
<?php
    session_start();
    // Generate CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>
<!DOCTYPE HTML>
<html lang="lt">
    <head>
        <?php
        $title = 'Registracija - The Future';
        include_once 'include_once/header.php';
        ?>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="css/navbar.css">
        <link rel="stylesheet" type="text/css" href="css/singup.css">
        <!-- Font Awesome for eye icon (Integrity atributas pašalintas) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    </head>
    <body>
        <?php include_once 'include_once/navbar.php'; ?>

        <section class="singup-section">
            <div class="singup-container form-container">
                <div class="heading">Registruotis</div>

                <!-- Pranešimų sekcija -->
                <?php if (isset($_GET["error"]) || isset($_GET["success"])): ?>
                <div class="error-message show <?php echo isset($_GET["success"]) ? 'success' : 'error'; ?>">
                    <?php
                        if (isset($_GET["error"])) {
                            switch ($_GET["error"]) {
                                case "emptyinput":
                                    echo "Užpildykite visus laukelius!";
                                    break;
                                case "invalidUserName":
                                    echo "Pasirinkite tinkamą vartotojo vardą!";
                                    break;
                                case "invalidEmail":
                                    echo "Pasirinkite galiojantį el. paštą!";
                                    break;
                                case "stmtfailed":
                                    echo "Įvyko klaida. Bandykite vėliau.";
                                    break;
                                case "passwordmismatch":
                                    echo "Slaptažodžiai nesutampa!";
                                    break;
                                case "usernametaken":
                                    echo "Vartotojo vardas jau užimtas!";
                                    break;
                                case "invalidcsrf":
                                    echo "Neteisingas CSRF token!";
                                    break;
                                case "dberror":
                                    echo "Įvyko duomenų bazės klaida!";
                                    break;
                                case "mailerror":
                                    echo "Nepavyko išsiųsti patvirtinimo el. laiško!";
                                    break;
                                case "weakpassword":
                                    echo "Slaptažodis per silpnas!";
                                    break;
                            }
                        }
                        if (isset($_GET["success"])) {
                            if ($_GET["success"] === "emailsent") {
                                echo "Patvirtinimo el. laiškas išsiųstas!";
                            } elseif ($_GET["success"] === "none") {
                                echo "Registracija sėkminga!";
                            }
                        }
                    ?>
                </div>
                <?php endif; ?>


                <!-- Signup form -->
                <form action="includes/singup.inc.php" method="post" class="singup-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-group">
                        <input type="text" id="username" name="username" placeholder="Įveskite savo vartotojo vardą" required>
                    </div>

                    <div class="form-group">
                        <input type="email" id="email" name="email" placeholder="Įveskite savo el. paštą" required>
                    </div>

                    <div class="form-group">
                        <div class="password-container">
                            <input type="password" id="password" name="pwd" placeholder="Įveskite savo slaptažodį" required>
                            <i class="fa fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                        <!-- Password Strength Bar -->
                        <div class="password-strength-bar" id="password-strength-bar">
                            <div class="progress-bar" id="password-progress"></div>
                        </div>
                        <!-- Password Requirements -->
                        <div class="password-requirements requirement-list" id="password-requirements">
                            <ul>
                                <li class="requirement requirement-item" id="length">Minimali 7 simbolių ilgio slaptažodis <span class="status">✖</span></li>
                                <li class="requirement requirement-item" id="uppercase">Didžioji raidė <span class="status">✖</span></li>
                                <li class="requirement requirement-item" id="lowercase">Mažoji raidė <span class="status">✖</span></li>
                                <li class="requirement requirement-item" id="number">Skaičius <span class="status">✖</span></li>
                                <li class="requirement requirement-item" id="symbol">Simbolis <span class="status">✖</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Patvirtinkite savo slaptažodį" required>
                            <i class="fa fa-eye toggle-password" id="toggleConfirmPassword"></i>
                        </div>
                        <small id="password-match-message"></small>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="singup_submit">Registruotis</button>
                    </div>
                </form>
            </div>
        </section>

        <script>
            // Automatically hide error message after 5 seconds
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.classList.remove('show', 'success');
                }, 5000); // Hide after 5 seconds
            }

            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const message = document.getElementById('password-match-message');
            const progressBar = document.getElementById('password-progress');

            // Password Requirements Elements
            const requirements = {
                length: document.querySelector('#length .status'),
                uppercase: document.querySelector('#uppercase .status'),
                lowercase: document.querySelector('#lowercase .status'),
                number: document.querySelector('#number .status'),
                symbol: document.querySelector('#symbol .status')
            };

            function checkPasswords() {
                const password = passwordField.value;
                const confirmPassword = confirmPasswordField.value;

                // Check if passwords match
                if (password === confirmPassword && confirmPassword.length > 0) {
                    confirmPasswordField.classList.add('valid');
                    confirmPasswordField.classList.remove('invalid');
                    message.textContent = 'Slaptažodžiai sutampa!';
                    message.style.color = 'var(--color-success)';
                } else if (confirmPassword.length > 0) {
                    confirmPasswordField.classList.add('invalid');
                    confirmPasswordField.classList.remove('valid');
                    message.textContent = 'Slaptažodžiai nesutampa!';
                    message.style.color = 'var(--color-error)';
                } else {
                    confirmPasswordField.classList.remove('invalid', 'valid');
                    message.textContent = '';
                }
            }

            function checkPasswordStrength() {
                const val = passwordField.value;
                let strength = 0;

                // Reset requirements
                let isLength = false, isUpper = false, isLower = false, isNumber = false, isSymbol = false;

                if (val.length >= 7) {
                    strength += 1;
                    isLength = true;
                }
                if (val.match(/[A-Z]/)) {
                    strength += 1;
                    isUpper = true;
                }
                if (val.match(/[a-z]/)) {
                    strength += 1;
                    isLower = true;
                }
                if (val.match(/[0-9]/)) {
                    strength += 1;
                    isNumber = true;
                }
                if (val.match(/[^a-zA-Z0-9]/)) {
                    strength += 1;
                    isSymbol = true;
                }

                // Update progress bar
                progressBar.style.width = `${(strength / 5) * 100}%`;
                if (strength <= 2) {
                    progressBar.style.backgroundColor = 'red';
                } else if (strength === 3 || strength === 4) {
                    progressBar.style.backgroundColor = 'orange';
                } else {
                    progressBar.style.backgroundColor = 'green';
                }

                // Update requirements status
                updateRequirementStatus('length', isLength);
                updateRequirementStatus('uppercase', isUpper);
                updateRequirementStatus('lowercase', isLower);
                updateRequirementStatus('number', isNumber);
                updateRequirementStatus('symbol', isSymbol);

                // Update password field border based on requirements
                if (strength === 5) {
                    passwordField.classList.add('valid');
                    passwordField.classList.remove('invalid');
                } else {
                    passwordField.classList.add('invalid');
                    passwordField.classList.remove('valid');
                }
            }

            function updateRequirementStatus(requirement, isValid) {
                const statusElement = requirements[requirement];
                const requirementItem = statusElement.parentElement;

                if (isValid) {
                    requirementItem.classList.add('valid');
                    requirementItem.classList.remove('invalid');
                    statusElement.textContent = '✔';
                } else {
                    requirementItem.classList.add('invalid');
                    requirementItem.classList.remove('valid');
                    statusElement.textContent = '✖';
                }
            }

            passwordField.addEventListener('input', () => {
                checkPasswords();
                checkPasswordStrength();
            });
            confirmPasswordField.addEventListener('input', checkPasswords);

            // Toggle password visibility
            const togglePasswords = document.querySelectorAll('.toggle-password');

            togglePasswords.forEach(toggle => {
                toggle.addEventListener('click', function () {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.classList.toggle('fa-eye-slash');
                });
            });

            // Animation for showing and hiding requirements and strength bar
            const requirementList = document.getElementById('password-requirements');
            const strengthBarContainer = document.getElementById('password-strength-bar');
            const requirementItems = document.querySelectorAll('.requirement-item');

            // Function to show requirements and strength bar with animation
            function showRequirements() {
                requirementList.classList.add('active');
                strengthBarContainer.classList.add('active');

                requirementItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.add('active');
                    }, index * 200); // Animate one requirement at a time
                });
            }

            // Function to hide requirements and strength bar with animation
            function hideRequirements() {
                requirementList.classList.remove('active');
                strengthBarContainer.classList.remove('active');

                requirementItems.forEach((item) => {
                    item.classList.remove('active');
                });
            }

            // Event listeners for focus and blur on password field
            passwordField.addEventListener('focus', showRequirements);
            passwordField.addEventListener('blur', hideRequirements);

            // Prevent hiding when clicking inside the requirements or strength bar
            requirementList.addEventListener('mousedown', (e) => {
                e.preventDefault();
            });

            strengthBarContainer.addEventListener('mousedown', (e) => {
                e.preventDefault();
            });
        </script>

        <?php include_once 'include_once/footer.php'; ?>
    </body>
</html>
