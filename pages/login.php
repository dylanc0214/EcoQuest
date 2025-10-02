<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <?php
        // Start session for handling login logic (PHP functionality)
        session_start();

        // Include the necessary components
        include("../includes/header.php");
        include("../includes/navigation.php");
    ?>

    <main class="auth-page">
        <div class="login-container">
            <div class="auth-card">
                <h2 class="auth-title">Welcome Back to EcoQuest!</h2>
                <p class="auth-subtitle">Log in to track your progress and plant real trees.</p>

                <form action="../includes/login_process.php" method="POST" class="auth-form" id="loginForm">

                    <div class="form-group">
                        <label for="username">Student ID / Email</label>
                        <input type="text" id="username" name="username" placeholder="e.g., TP123456" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <p class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary-auth">Login</button>
                </form>

                <p class="auth-footer">
                    Don't have an account?
                    <a href="register.php">Sign Up Here</a>
                </p>
                <p class="auth-footer-small"><a href="#">Forgot Password?</a></p>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>