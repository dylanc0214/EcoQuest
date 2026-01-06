<?php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') header("Location: admin/dashboard.php");
    elseif ($_SESSION['user_role'] === 'moderator') header("Location: moderator/dashboard.php");
    else header("Location: dashboard.php");
    exit();
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $login_error = 'Please enter both username/email and password.';
    } else {
        // Updated function to return status codes
        function attempt_login($conn, $identifier, $password, $role) {
            $sql = "SELECT User_id, Username, Role, Password_hash FROM user WHERE Username = ? OR Email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && $user['Role'] === $role) {
                // Verify Password (Use password_verify if hashed, or == if plain text)
                // Your SQL dump shows hashes ($2y$10$...), so use password_verify
                if (password_verify($password, $user['Password_hash'])) {
                    
                    // --- 🛑 BAN CHECK FOR STUDENTS ---
                    if ($role === 'student') {
                        $ban_stmt = $conn->prepare("SELECT Student_id, Ban_time FROM student WHERE User_id = ?");
                        $ban_stmt->bind_param("i", $user['User_id']);
                        $ban_stmt->execute();
                        $s_check = $ban_stmt->get_result()->fetch_assoc();
                        $ban_stmt->close();
                        
                        if ($s_check) {
                            $_SESSION['student_id'] = $s_check['Student_id'];
                            
                            // Check if ban is active
                            if (!empty($s_check['Ban_time']) && $s_check['Ban_time'] !== '0000-00-00 00:00:00') {
                                $ban_expiry = new DateTime($s_check['Ban_time'], new DateTimeZone('UTC')); // Assume Ban_time is UTC
                                $now = new DateTime('now', new DateTimeZone('UTC')); // Compare with current UTC time
                                
                                if ($ban_expiry > $now) {
                                    $_SESSION['ban_time'] = $s_check['Ban_time'];
                                    return 'banned';
                                }
                            }
                        }
                    }
                    
                    $_SESSION['user_id'] = $user['User_id'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['user_role'] = $role;
                    return 'success';
                }
            }
            return 'fail';
        }
                // Verify Password (Use password_verify if hashed, or == if plain text)
                // Your SQL dump shows hashes ($2y$10$...), so use password_verify
                if (password_verify($password, $user['Password_hash'])) {
                    file_put_contents($debug_file, "Password verified!\n", FILE_APPEND);
                    
                    // --- 🛑 BAN CHECK FOR STUDENTS ---
                    if ($role === 'student') {
                        $ban_stmt = $conn->prepare("SELECT Student_id, Ban_time FROM student WHERE User_id = ?");
                        $ban_stmt->bind_param("i", $user['User_id']);
                        $ban_stmt->execute();
                        $s_check = $ban_stmt->get_result()->fetch_assoc();
                        $ban_stmt->close();
                        
                        if ($s_check) {
                            $_SESSION['student_id'] = $s_check['Student_id'];
                            
                            // Check if ban is active - simple timestamp comparison
                            if (!empty($s_check['Ban_time']) && $s_check['Ban_time'] !== '0000-00-00 00:00:00') {
                                $ban_timestamp = strtotime($s_check['Ban_time']);
                                $now_timestamp = time();
                                
                                if ($ban_timestamp > $now_timestamp) {
                                    // BAN IS ACTIVE
                                    $_SESSION['ban_time'] = $s_check['Ban_time'];
                                    return 'banned';
                                }
                            }
                        }
                    }
                    
                    $_SESSION['user_id'] = $user['User_id'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['user_role'] = $role;
                    file_put_contents($debug_file, "RETURNING SUCCESS\n", FILE_APPEND);
                    return 'success';
                }
            }
            return 'fail';
        }

        // Try Admin
        $status = attempt_login($conn, $identifier, $password, 'admin');
        if ($status === 'success') {
            header("Location: admin/dashboard.php"); exit();
        }
        
        // Try Moderator
        if ($status === 'fail') {
            $status = attempt_login($conn, $identifier, $password, 'moderator');
            if ($status === 'success') {
                header("Location: moderator/dashboard.php"); exit();
            }
        }

        // Try Student
        if ($status === 'fail') {
            $status = attempt_login($conn, $identifier, $password, 'student');
            // DEBUG
            error_log("Student login attempt for '$identifier': status = '$status'");
            if (isset($_SESSION['ban_time'])) {
                error_log("Ban time in session: " . $_SESSION['ban_time']);
            }
            // END DEBUG
            
            if ($status === 'success') {
                header("Location: dashboard.php"); exit();
            } elseif ($status === 'banned') {
                $ban_time = isset($_SESSION['ban_time']) ? $_SESSION['ban_time'] : null;
                if ($ban_time) {
                    $ban_dt = new DateTime($ban_time, new DateTimeZone('UTC'));
                    $formatted_time = $ban_dt->format('h:i A m/d/Y');
                } else {
                    $formatted_time = 'Unknown';
                }
                $login_error = "🚫 Your account is locked until " . $formatted_time . ". Please contact support.";
                unset($_SESSION['ban_time']); // Clear the session variable
            } else {
                $login_error = 'Invalid username/email or password.';
            }
        } elseif ($status === 'fail') {
             $login_error = 'Invalid username/email or password.';
        }
    }
}
?>

<main class="auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Log In to EcoQuest</h1>
        <p class="auth-subtitle">Welcome back, green hero!</p>

        <?php if ($login_error): ?>
            <div class="message error-message" style="background: #ffe6e6; color: #d63031; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px;">
                <?php echo $login_error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier" required placeholder="TP123456">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Your secret password">
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" class="btn-primary">Login</button>
            </div>
        </form>

        <div class="auth-footer" style="text-align: center;">
            <p>Don't have an account? <a href="register.php" class="auth-link">Register New Account</a></p>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>