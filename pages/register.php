<?php
// pages/register.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Redirect logged-in users away from the registration page
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once(__DIR__ . "/../config/db.php");
include("../includes/header.php");

$registration_message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // --- Basic Validation (No changes needed here) ---
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = 'All fields are required lah.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email format is invalid.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Password and confirm password do not match.';
    }

    // --- Database Uniqueness Check (UPDATED) ---
    // This query now checks all three tables to prevent duplicates.
    if (empty($errors) && isset($conn)) {
        $sql_check = "
            (SELECT email FROM students WHERE email = ? OR username = ?)
            UNION
            (SELECT email FROM moderators WHERE email = ? OR username = ?)
            UNION
            (SELECT email FROM admins WHERE email = ? OR username = ?)
        ";
        $stmt_check = $conn->prepare($sql_check);
        // We need to bind the params for each UNION part
        $stmt_check->bind_param("ssssss", $email, $username, $email, $username, $email, $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $errors[] = 'Username or Email is already registered. Try logging in instead.';
        }
        $stmt_check->close();
    }

    // --- Process Registration (UPDATED) ---
    // This now inserts directly into the 'students' table.
    if (empty($errors) && isset($conn)) {
        $sql_insert = "INSERT INTO students (username, email, password, total_points) VALUES (?, ?, ?, 0)";
        
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            // As per your setup, storing password as plain text.
            $stmt_insert->bind_param("sss", $username, $email, $password);

            if ($stmt_insert->execute()) {
                $registration_message = 'Registration successful! You can log in now.';
                $message_type = 'success';
                $_POST = []; // Clear form data on success
            } else {
                $registration_message = 'Registration failed: ' . $stmt_insert->error;
                $message_type = 'error';
            }
            $stmt_insert->close();
        } else {
            $registration_message = 'Database preparation failed: ' . $conn->error;
            $message_type = 'error';
        }
    } else {
        $registration_message = implode('<br>', $errors);
        $message_type = 'error';
    }
}
?>

<main class="auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Register for EcoQuest</h1>
        <p class="auth-subtitle">Join the mission to reduce plastic on campus!</p>

        <?php if ($registration_message): ?>
            <div class="message <?php echo ($message_type === 'error') ? 'error-message' : 'success-message'; ?>">
                <?php echo $registration_message; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required
                placeholder="e.g., TP123456">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required
                placeholder="Your APU email address">
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
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" class="btn-primary">Register</button>
            </div>
        </form>

        <div class="auth-footer" style="text-align: center;">
            <p>Already have an account? <a href="login.php" class="auth-link">Log In Here</a></p>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>