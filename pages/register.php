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

    // --- DB Connection and Dependencies ---
    // This file should create the $conn (MySQLi connection object).
    include("../config/db.php");
    include("../includes/header.php");
    include("../includes/navigation.php");

    $registration_message = '';
    $message_type = '';
    $db_error = '';

    // Check if connection object exists and is successful
    $is_db_connected = isset($conn) && !$conn->connect_error;

    if (!$is_db_connected) {
        $db_error = 'Critical: Database connection failed. Cannot proceed with registration.';
    }

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

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Password and confirm password do not match.';
        }
        
        // --- Database Uniqueness Check (Only if other validations passed and DB connected) ---
        if (empty($errors) && $is_db_connected) {
            $sql_check = "SELECT user_id FROM users WHERE username = ? OR email = ?";
            
            if ($stmt_check = $conn->prepare($sql_check)) {
                $stmt_check->bind_param("ss", $username, $email);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows > 0) {
                    // Fetch to see which one already exists (optional, but nice feedback)
                    $existing_user = $result_check->fetch_assoc(); 
                    
                    // Simple check: if a row exists, either username or email is taken
                    $errors[] = 'Username or Email is already registered. Try logging in instead.';
                }
                $stmt_check->close();
            } else {
                $db_error = 'Database check preparation failed: ' . $conn->error;
            }
        }

        // Process Registration if no errors
        if (empty($errors) && $is_db_connected) {
            
            // --- 1. HASH PASSWORD (CRUCIAL SECURITY STEP) ---
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // --- 2. INSERT INTO DATABASE using MySQLi Prepared Statement ---
            $sql_insert = "INSERT INTO users (username, email, password_hash, user_role, total_points) VALUES (?, ?, ?, 'student', 0)";
            
            if ($stmt_insert = $conn->prepare($sql_insert)) {
                // Bind parameters: sss = string, string, string
                $stmt_insert->bind_param("sss", $username, $email, $password_hash);

                if ($stmt_insert->execute()) {
                    $registration_message = 'Registration successful! You are now an official EcoQuest Student. You can log in now!';
                    $message_type = 'success';
                    
                    // Clear POST data so form doesn't re-fill sensitive info
                    $_POST = array();

                } else {
                    $db_error = 'Registration query execution failed: ' . $stmt_insert->error;
                    $message_type = 'error';
                }
                $stmt_insert->close();
            } else {
                $db_error = 'Registration query preparation failed: ' . $conn->error;
                $message_type = 'error';
            }

        } else {
             // Handle connection failure and validation errors
            if ($db_error) {
                $registration_message = 'Cannot register right now due to a critical database connection failure.';
            } else {
                $registration_message = implode('<br>', $errors);
            }
            $message_type = 'error';
        }
    }
    
    // Close the connection explicitly if it was successfully established
    if ($is_db_connected) {
        // $conn->close(); // PHP will usually close this automatically at the end of the script
    }
    ?>

    <main class="auth-page">
        <div class="auth-card">
            <h1 class="auth-title">Register for EcoQuest</h1>
            <p class="auth-subtitle">Join the mission to reduce plastic on campus and start earning points!</p>

            <?php if ($registration_message || $db_error): ?>
                <div class="message <?php echo ($message_type === 'error' || $db_error) ? 'error-message' : 'success-message'; ?>">
                    <?php 
                        if ($db_error) {
                            echo "Database Error: " . htmlspecialchars($db_error);
                        } else {
                            echo $registration_message;
                        }
                    ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="auth-form">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            required placeholder="e.g., TP123456">
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
