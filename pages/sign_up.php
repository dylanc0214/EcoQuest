<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session BEFORE any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB connection
include("../config/db.php");

// Redirect logged-in users BEFORE including header (no output yet)
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($_SESSION['user_role'] === 'moderator') {
        header("Location: moderator/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
}

$login_error = '';
$registration_message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'login') {
        //===============
        // LOGIN PHP
        //===============
        $identifier = $_POST['identifier'] ?? ''; // This can be Username or Email
        $password   = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $login_error = 'Please enter both username/email and password.';
        } else {
            // Query only the user table (lowercase table name)
            $sql    = "SELECT User_id, Username, Email, Role, Password_hash 
                       FROM user 
                       WHERE Username = ? OR Email = ?";
            $result = $conn->execute_query($sql, [$identifier, $identifier]);
            $user   = $result->fetch_assoc();

            // Verify password
            if ($user && password_verify($password, $user['Password_hash'])) {

                // Base session vars
                $_SESSION['user_id']   = $user['User_id'];
                $_SESSION['username']  = $user['Username'];
                $_SESSION['user_role'] = $user['Role'];

                // Role-specific IDs
                if ($user['Role'] === 'student') {
                    // Get Student_id from student table (lowercase)
                    $stmt_role = $conn->prepare("SELECT Student_id FROM student WHERE User_id = ?");
                    $stmt_role->bind_param("i", $user['User_id']);
                    $stmt_role->execute();
                    $role_data = $stmt_role->get_result()->fetch_assoc();
                    $stmt_role->close();

                    if ($role_data) {
                        $_SESSION['student_id'] = $role_data['Student_id'];
                    }

                    // Ban check on student table (already lowercase in your code)
                    $ban_stmt = $conn->prepare("SELECT Student_id, Ban_time FROM student WHERE User_id = ?");
                    $ban_stmt->bind_param("i", $user['User_id']);
                    $ban_stmt->execute();
                    $s_check = $ban_stmt->get_result()->fetch_assoc();
                    $ban_stmt->close();

                    if ($s_check) {
                        $_SESSION['student_id'] = $s_check['Student_id'];

                        // Check if ban is active
                        if (!empty($s_check['Ban_time']) && $s_check['Ban_time'] !== '0000-00-00 00:00:00') {
                            $ban_expiry = new DateTime($s_check['Ban_time'], new DateTimeZone('UTC'));
                            $now        = new DateTime('now', new DateTimeZone('UTC'));

                            if ($ban_expiry > $now) {
                                $_SESSION['ban_time'] = $s_check['Ban_time'];
                                $login_error          = 'Your account is locked until ' . $s_check['Ban_time'] . '. Please contact support for more information.';
                                unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_role'], $_SESSION['student_id']);
                            } else {
                                // Ban expired, allow login
                                header("Location: student/dashboard.php");
                                exit();
                            }
                        } else {
                            // Not banned, allow login
                            header("Location: student/dashboard.php");
                            exit();
                        }
                    }

                } elseif ($user['Role'] === 'moderator') {
                    // moderator table is lowercase
                    $stmt_role = $conn->prepare("SELECT Moderator_id FROM moderator WHERE User_id = ?");
                    $stmt_role->bind_param("i", $user['User_id']);
                    $stmt_role->execute();
                    $role_data = $stmt_role->get_result()->fetch_assoc();
                    $stmt_role->close();

                    if ($role_data) {
                        $_SESSION['moderator_id'] = $role_data['Moderator_id'];
                    }

                    header("Location: moderator/dashboard.php");
                    exit();

                } elseif ($user['Role'] === 'admin') {
                    // admin table is lowercase
                    $stmt_role = $conn->prepare("SELECT Admin_id FROM admin WHERE User_id = ?");
                    $stmt_role->bind_param("i", $user['User_id']);
                    $stmt_role->execute();
                    $role_data = $stmt_role->get_result()->fetch_assoc();
                    $stmt_role->close();

                    if ($role_data) {
                        $_SESSION['admin_id'] = $role_data['Admin_id'];
                    }

                    header("Location: admin/dashboard.php");
                    exit();
                }

            } else {
                // Invalid username/email or password
                $login_error = 'Invalid username/email or password.';
            }
        }

    } elseif ($form_type === 'register') {
        //===============
        // REGISTER PHP
        //===============
        $username         = trim($_POST['username'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $reg_password     = $_POST['reg_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $errors           = [];

        // Basic validation
        if (empty($username) || empty($email) || empty($reg_password) || empty($confirm_password)) {
            $errors[] = 'All fields are required lah.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email format is invalid.';
        }
        if (strlen($reg_password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($reg_password !== $confirm_password) {
            $errors[] = 'Password and confirm password do not match.';
        }

        // Database uniqueness check (user table is lowercase)
        if (empty($errors) && isset($conn)) {
            $sql_check   = "SELECT User_id FROM user WHERE Username = ? OR Email = ?";
            $stmt_check  = $conn->prepare($sql_check);
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $errors[] = 'Username or Email is already registered. Try logging in instead.';
            }
            $stmt_check->close();
        }

        // Process registration (transaction)
        if (empty($errors) && isset($conn)) {
            $conn->begin_transaction();
            try {
                // 1. Insert into user table
                $password_hash    = password_hash($reg_password, PASSWORD_DEFAULT);
                $role             = 'student';
                $sql_insert_user  = "INSERT INTO user (Username, Email, Role, Password_hash) VALUES (?, ?, ?, ?)";
                $stmt_user        = $conn->prepare($sql_insert_user);
                $stmt_user->bind_param("ssss", $username, $email, $role, $password_hash);

                if (!$stmt_user->execute()) {
                    throw new Exception('Failed to create User record.');
                }

                $new_user_id = $conn->insert_id;
                $stmt_user->close();

                // 2. Insert into student table (lowercase)
                $sql_insert_student = "INSERT INTO student (User_id, Total_point, Total_Exp_Point) VALUES (?, 0, 0)";
                $stmt_student       = $conn->prepare($sql_insert_student);
                $stmt_student->bind_param("i", $new_user_id);

                if (!$stmt_student->execute()) {
                    throw new Exception('Failed to create Student record.');
                }
                $stmt_student->close();

                $conn->commit();
                $registration_message = 'Registration successful! You can log in now.';
                $message_type         = 'success';
                $_POST                = []; // Clear form data on success

            } catch (Exception $e) {
                $conn->rollback();
                $registration_message = 'Registration failed: ' . $e->getMessage();
                $message_type         = 'error';
            }
        } else {
            $registration_message = implode('<br>', $errors);
            $message_type         = 'error';
        }
    }
}

// AFTER all PHP logic, now we can output HTML
include("../includes/header.php");
?>

<div class="auth-page">
    <div class="auth-gradient"></div>
    <div class="auth-container">
        <!-- Login -->
        <div class="log-auth-card" id="login">
            <h1 class="auth-title">Log In to EcoQuest</h1>
            <p class="auth-subtitle">Welcome Back, Green Hero!</p>

            <?php if ($login_error): ?>
                <div class="message error-message"><?php echo $login_error; ?></div>
            <?php endif; ?>

            <form action="sign_up.php" method="POST" class="auth-form">
                <div class="input-group">
                    <label for="username" class="input-label">Username or Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="identifier" name="identifier" class="input-modern"
                               placeholder="TP123456 or TP123456@mail.apu.edu.my" autocomplete="username">
                    </div>
                </div>
                <div class="input-group">
                    <label for="password" class="input-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" class="input-modern"
                               placeholder="Your secret eco-password" autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword(event,'password')">🙈</button>
                    </div>
                </div>
                <input type="hidden" name="form_type" value="login">
                <button type="submit" class="btn-sub">Login</button>
            </form>
        </div>

        <!-- Register -->
        <div class="reg-auth-card" id="register">
            <h1 class="auth-title">Register for EcoQuest</h1>
            <p class="auth-subtitle">Join the mission to reduce plastic on campus!</p>

            <?php if ($registration_message): ?>
                <div class="message <?php echo $message_type; ?>-message"><?php echo $registration_message; ?></div>
            <?php endif; ?>

            <form action="sign_up.php" method="POST" class="auth-form">
                <div class="input-group-modern">
                    <label for="username" class="input-label">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="username" name="username" class="input-modern"
                               placeholder="e.g., TP123456" autocomplete="username">
                    </div>
                </div>

                <div class="input-group">
                    <label for="email" class="input-label">Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" id="email" name="email" class="input-modern"
                               placeholder="Your APU email address" autocomplete="email">
                    </div>
                </div>

                <div class="input-group">
                    <label for="reg_password" class="input-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="reg_password" name="reg_password" class="input-modern"
                               placeholder="Create a password (min 8 characters)" minlength="6" autocomplete="new-password">
                        <button type="button" class="password-toggle" onclick="togglePassword(event,'reg_password')">🙈</button>
                    </div>
                </div>

                <div class="input-group">
                    <label for="confirm_password" class="input-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="confirm_password" name="confirm_password" class="input-modern"
                               placeholder="Confirm your password" minlength="6" autocomplete="new-password">
                        <button type="button" class="password-toggle" onclick="togglePassword(event,'confirm_password')">🙈</button>
                    </div>
                </div>

                <input type="hidden" name="form_type" value="register">
                <button type="submit" class="btn-sub">Register</button>
            </form>
        </div>

        <div class="switch">
            <a href="#" class="login active" onclick="login()">Login</a>
            <a href="#" class="register" onclick="register()">Register</a>
            <div class="btn-active" id="switch-tab-btn"></div>
        </div>
    </div>

    <div class="auth-side-panel">
        <div class="side-content">
            <h2>Start Your Green Journey</h2>
            <p>Join thousands making a difference</p>
            <div class="feature-list">
                <div class="feature-item">
                    <span class="feature-icon">🎯</span>
                    <span>Join Weekly Challenges</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">⭐</span>
                    <span>Earn Rewards & Points</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🏆</span>
                    <span>Climb the Leaderboard</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🌳</span>
                    <span>Build a Greener Campus</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>

<script>
// Check URL from homepage btn and auto switch Login/Register view
window.addEventListener('DOMContentLoaded', (event) => {
    const urlParams = new URLSearchParams(window.location.search);
    const action    = urlParams.get('action');

    if (action === 'register') {
        register();
    } else if (action === 'login') {
        login();
    }
});
</script>