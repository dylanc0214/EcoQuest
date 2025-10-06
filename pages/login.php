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

    // --- DB Connection and Dependencies ---
    // This file should create the $conn (MySQLi connection object).
    include("../config/db.php");
    include("../includes/header.php");
    include("../includes/navigation.php");

    $db_error = '';
    $login_error = '';
    
    // Check if connection object exists and is successful based on db.php logic
    // Since db.php uses die() on critical failure, we check if $conn is set and not an error object.
    $is_db_connected = isset($conn) && !$conn->connect_error;

    if (!$is_db_connected) {
        $db_error = 'Critical: Database connection failed. Cannot proceed with login.';
    }

    // PHP Logic for Login Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $identifier = $_POST['identifier'] ?? ''; // Can be username or email
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $login_error = 'Please enter both username/email and password.';
        } elseif (!$is_db_connected) { 
             $login_error = 'Cannot log in right now due to a critical database connection failure.';
        } else {
            // --- 1. DATABASE LOOKUP using MySQLi Prepared Statement ---
            // We try to find a user matching either the username OR the email
            $sql = "SELECT user_id, username, email, password_hash, user_role 
                    FROM users 
                    WHERE username = ? OR email = ?";
            
            // Prepare statement
            if ($stmt = $conn->prepare($sql)) {
                
                // Bind parameters (s = string, used twice for username and email)
                $stmt->bind_param("ss", $identifier, $identifier);
                
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $found_user = $result->fetch_assoc();
                    $stmt->close(); // Close the statement after getting result

                    // --- 2. VERIFY PASSWORD AND AUTHENTICATE ---
                    if ($found_user) {
                        // CRUCIAL: Use password_verify() to securely check the entered password
                        if (password_verify($password, $found_user['password_hash'])) {
                            
                            // --- 3. SUCCESSFUL LOGIN: Start Session ---
                            $_SESSION['user_id'] = $found_user['user_id'];
                            $_SESSION['username'] = $found_user['username'];
                            $_SESSION['user_role'] = $found_user['user_role'];

                            // Redirect based on role
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            // Password incorrect
                            $login_error = 'Invalid username/email or password.';
                        }
                    } else {
                        // User not found
                        $login_error = 'Invalid username/email or password.';
                    }
                } else {
                    // Execution failed
                    $db_error = 'Query execution failed: ' . $stmt->error;
                }
                
            } else {
                // Preparation failed
                $db_error = 'Database query preparation failed: ' . $conn->error;
            }
        }
    }
    
    // Close the connection explicitly if it was successfully established
    if ($is_db_connected) {
        // $conn->close(); // PHP will usually close this automatically at the end of the script
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
                <div class="message error-message">Database error occurred. Please try again later. (Error: <?php echo $db_error; ?>)</div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="auth-form">

                <div class="form-group">
                    <label for="identifier">Username or Email</label>
                    <input type="text" id="identifier" name="identifier"
                            value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" required
                            placeholder="e.g., TP123456@mail.apu.edu.my or TP123456">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                            placeholder="Your secret eco-password">
                </div>

                <button type="submit" class="btn-submit">Login</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account?</p>
                <a href="register.php" class="auth-link">Register New Account</a>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>
