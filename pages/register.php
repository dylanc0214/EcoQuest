<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <?php
    // pages/register.php
    session_start();

    // Redirect logged-in users away from the registration page
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    }

    include("../includes/header.php");
    include("../includes/navigation.php");

    $registration_message = '';
    $message_type = '';

    // SIMULATED DATABASE CREDENTIALS AND EXISTING USERS (for uniqueness check)
    $simulated_db_users = [
        'AliBinStudent' => 'ali@apu.my',
        'ModBoss' => 'mod@apu.my',
        'AdminLiao' => 'admin@apu.my',
    ];

    // PHP Logic for Registration Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $errors = [];

        // Validation Checks
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $errors[] = 'All fields are required lah.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email format invalid. Must be correct APU email style.';
        }

        // Check for unique username/email (Simulated DB lookup)
        if (in_array(strtolower($username), array_map('strtolower', array_keys($simulated_db_users)))) {
            $errors[] = 'Username is already taken. Try another one, please.';
        }
        if (in_array(strtolower($email), array_map('strtolower', $simulated_db_users))) {
            $errors[] = 'Email is already registered. Try logging in instead.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Password and confirm password do not match.';
        }

        // Process Registration if no errors
        if (empty($errors)) {
            // --- 1. HASH PASSWORD (CRUCIAL SECURITY STEP) ---
            // In a real application, you would use: $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $password_hash_simulated = 'hashed_and_secure';

            // --- 2. SIMULATE INSERT INTO DATABASE ---
            /* // Real MySQL Insertion using prepared statements:
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, user_role, total_points) VALUES (?, ?, ?, 'student', 0)");
            $stmt->execute([$username, $email, $password_hash]);
            */

            // For simulation: assume successful insertion

            $registration_message = 'Registration successful! You are now an official EcoQuest Student. You can log in now!';
            $message_type = 'success';

            // Clear POST data so form doesn't re-fill sensitive info
            $_POST = array();

        } else {
            $registration_message = implode('<br>', $errors);
            $message_type = 'error';
        }
    }
    ?>

    <main class="auth-page">
        <div class="auth-card">
            <h1 class="auth-title">Register for EcoQuest</h1>
            <p class="auth-subtitle">Join the mission to reduce plastic on campus and start earning points!</p>

            <?php if ($registration_message): ?>
                <div class="message <?php echo $message_type === 'error' ? 'error-message' : 'success-message'; ?>">
                    <?php echo $registration_message; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="auth-form">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required placeholder="e.g., AliBinStudent">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required placeholder="Your APU email address">
                </div>

                <div class="form-group">
                    <label for="password">Password (Min 8 characters)</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Set a secure password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Type your password again">
                </div>

                <button type="submit" class="btn-submit">Register & Start Questing</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account?</p>
                <a href="login.php" class="auth-link">Log In Here</a>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>

</body>
</html>