<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <?php
// Start session for handling registration messages
session_start();

// Include the necessary components
include("../includes/header.php");
include("../includes/navigation.php");
?>

    <main class="auth-page">
        <div class="login-container">
            <div class="auth-card">
                <h2 class="auth-title">Join the Green Quest!</h2>
                <p class="auth-subtitle">Sign up to start earning points and planting trees today.</p>

                <form action="../includes/register_process.php" method="POST" class="auth-form" id="registerForm">

                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" placeholder="e.g., TP123456" required>
                    </div>

                    <div class="form-group">
                        <label for="email">APU Email Address</label>
                        <input type="email" id="email" name="email" placeholder="TP123456@mail.apu.edu.my" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Min 8 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Retype your password" required>
                    </div>

                    <?php if (isset($_SESSION['reg_message'])): ?>
                        <p class="message-status"><?php echo $_SESSION['reg_message']; unset($_SESSION['reg_message']); ?></p>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary-auth">Create Account</button>
                </form>

                <p class="auth-footer">
                    Already registered?
                    <a href="login.php">Login Here</a>
                </p>
            </div>
        </div>
    </main>

<?php
// For production, always clear session message immediately after display
unset($_SESSION['reg_message']);
include("../includes/footer.php");
?>
</body>
</html>