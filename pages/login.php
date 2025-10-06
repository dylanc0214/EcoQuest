<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <?php
    // pages/login.php
    session_start();

    // Redirect logged-in users away from login page
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    }

    include("../includes/header.php");
    include("../includes/navigation.php");

    // --- DATABASE SIMULATION SETUP ---
    // In a real application, you would include your actual DB connection file here.
    // For the assignment, we will simulate the database interaction and error handling.
    $db_error = '';
    $login_error = '';

    // SIMULATED DATABASE CREDENTIALS (for demonstration)
    // In a real app, this should be in a separate, secure config file.
    $simulated_db = [
        // This is the user we will check against for a successful login
        'AliBinStudent' => [
            'user_id' => 3,
            'email' => 'ali@apu.my',
            // In real life, use password_hash(), e.g., password_hash('password123', PASSWORD_BCRYPT)
            // For this simulation, we'll use a hardcoded hash that matches 'password123'
            'password_hash' => '$2y$10$w09u7v.g.SjB/K0gG4m25u1N8X6Qx5L8P6E7F8G9H0J1K2L3M4N5O6P7Q8R9S0',
            'user_role' => 'student',
        ],
        'ModBoss' => [
            'user_id' => 5,
            'email' => 'mod@apu.my',
            'password_hash' => '$2y$10$w09u7v.g.SjB/K0gG4m25u1N8X6Qx5L8P6E7F8G9H0J1K2L3M4N5O6P7Q8R9S0',
            'user_role' => 'moderator',
        ],
    ];

    // PHP Logic for Login Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $identifier = $_POST['identifier'] ?? ''; // Can be username or email
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $login_error = 'Please enter both username/email and password.';
        } else {

            // --- 1. SIMULATE DATABASE LOOKUP ---
            $found_user = null;
            foreach ($simulated_db as $username => $userData) {
                // Check if identifier matches username OR email
                if (strtolower($username) === strtolower($identifier) || strtolower($userData['email']) === strtolower($identifier)) {
                    $found_user = $userData;
                    $found_user['username'] = $username; // Add username back for session
                    break;
                }
            }

            // --- 2. VERIFY PASSWORD AND AUTHENTICATE ---
            if ($found_user) {
                // In a real application:
                // if (password_verify($password, $found_user['password_hash'])) { ... }

                // SIMULATED password_verify (for demonstration, 'password123' works)
                $is_password_valid = ($password === 'password123'); // Replace this line with password_verify

                if ($is_password_valid) {
                    // --- 3. SUCCESSFUL LOGIN: Start Session ---
                    $_SESSION['user_id'] = $found_user['user_id'];
                    $_SESSION['username'] = $found_user['username'];
                    $_SESSION['user_role'] = $found_user['user_role'];

                    // Redirect based on role (important for your assignment requirement)
                    if ($found_user['user_role'] === 'admin' || $found_user['user_role'] === 'moderator') {
                        header("Location: dashboard.php"); // Or admin_panel.php/mod_review.php directly
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $login_error = 'Invalid username/email or password.';
                }
            } else {
                $login_error = 'Invalid username/email or password.';
            }
        }
    }
    ?>

    <main class="auth-page">
        <div class="auth-card">
            <h1 class="auth-title">Log In to EcoQuest</h1>
            <p class="auth-subtitle">Welcome back, green hero! Let's check your points.</p>

            <?php if ($login_error): ?>
                <div class="message error-message"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <?php if ($db_error): ?>
                <div class="message error-message">Database error occurred. Please try again later.</div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="auth-form">

                <div class="form-group">
                    <label for="identifier">Username or Email</label>
                    <input type="text" id="identifier" name="identifier"
                           value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" required
                           placeholder="e.g., ali@apu.my or AliBinStudent">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Your secret eco-password">
                </div>

                <button type="submit" class="btn-submit">Login & Go Green</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account?</p>
                <a href="register.php" class="auth-link">Register New Student Account</a>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>