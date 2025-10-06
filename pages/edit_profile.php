<?php
// pages/edit_profile.php
session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php"); // Assumes this file establishes $conn
include("../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$db_error = '';
$message = '';
$user_data = null;
$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Error: Database connection failed. Cannot load profile data.';
} else {
    // --- 1. HANDLE POST REQUEST (Form Submission) ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Flag to check if we should proceed with the update
        $proceed_update = true;

        // Validation for Username and Email (Required fields)
        if (empty($username) || empty($email)) {
            $message = ['type' => 'error', 'text' => 'Aiyo! Username and Email cannot be empty.'];
            $proceed_update = false;
        }

        // --- Handle Password Change ---
        if ($proceed_update && !empty($new_password)) {
            if (empty($current_password)) {
                $message = ['type' => 'error', 'text' => 'Must enter current password to change password!'];
                $proceed_update = false;
            } elseif ($new_password !== $confirm_password) {
                $message = ['type' => 'error', 'text' => 'New password and confirm password do not match.'];
                $proceed_update = false;
            } else {
                // Verify Current Password (Need to fetch the stored hash first)
                $verify_sql = "SELECT password_hash FROM users WHERE user_id = ?";
                if ($v_stmt = $conn->prepare($verify_sql)) {
                    $v_stmt->bind_param("i", $current_user_id);
                    $v_stmt->execute();
                    $v_result = $v_stmt->get_result();
                    $user_record = $v_result->fetch_assoc();
                    $v_stmt->close();

                    if (!$user_record || !password_verify($current_password, $user_record['password_hash'])) {
                        $message = ['type' => 'error', 'text' => 'The current password you entered is incorrect.'];
                        $proceed_update = false;
                    }
                } else {
                    $db_error = 'Password verification setup failed: ' . $conn->error;
                    $proceed_update = false;
                }
            }
        }

        // --- Execute Update ---
        if ($proceed_update) {
            $update_parts = [];
            $bind_types = '';
            $bind_params = [];

            // Add username and email updates
            $update_parts[] = "username = ?";
            $bind_types .= 's';
            $bind_params[] = $username;

            // Add email updates
            $update_parts[] = "email = ?";
            $bind_types .= 's';
            $bind_params[] = $email;
            
            // Add password update if new password provided
            if (!empty($new_password)) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_parts[] = "password_hash = ?";
                $bind_types .= 's';
                $bind_params[] = $new_hash;
            }

            // Construct and execute the final update query
            $set_clause = implode(', ', $update_parts);
            $sql_update = "UPDATE users SET {$set_clause} WHERE user_id = ?";
            $bind_types .= 'i';
            $bind_params[] = $current_user_id;

            if ($u_stmt = $conn->prepare($sql_update)) {
                $u_stmt->bind_param($bind_types, ...$bind_params);
                if ($u_stmt->execute()) {
                    // Update session variables if username changed
                    $_SESSION['username'] = $username;
                    $message = ['type' => 'success', 'text' => 'Profile updated successfully!'];
                } else {
                    $message = ['type' => 'error', 'text' => 'Database update failed: ' . $u_stmt->error];
                }
                $u_stmt->close();
            } else {
                $db_error = 'Update query preparation failed: ' . $conn->error;
            }
        }
    }


    // --- 2. FETCH CURRENT USER DATA (for GET or failed POST) ---
    $sql_fetch = "SELECT username, email FROM users WHERE user_id = ?";
    
    if ($f_stmt = $conn->prepare($sql_fetch)) {
        $f_stmt->bind_param("i", $current_user_id);
        if ($f_stmt->execute()) {
            $result = $f_stmt->get_result();
            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
            } else {
                $db_error = 'User data not found.';
            }
        } else {
            $db_error = 'Query execution failed: ' . $f_stmt->error;
        }
        $f_stmt->close();
    } else {
        $db_error = 'Database query preparation failed: ' . $conn->error;
    }
}
?>

<main class="edit-profile-page">
    <div class="container">
        <h1 class="page-title">Edit My Profile 📝</h1>
        <p class="page-subtitle">Update your account information and password.</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo $db_error; ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <div class="auth-card">
                <form action="edit_profile.php" method="POST" class="auth-form">
                    
                    <!-- Basic Information Section -->
                    <h3 style="margin-top: 0;"><i class="fas fa-id-card"></i> Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>

                    <!-- Password Update Section -->
                    <h3><i class="fas fa-lock"></i> Change Password (Optional)</h3>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 25px;">Only fill these fields if you want to change your password.</p>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password (Required if changing)</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter your current password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Leave blank if not changing">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                    </div>

                    <div class="form-submit">
                        <button type="submit" class="btn-submit">Save Changes</button>
                        <a href="profile.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include("../includes/footer.php"); ?>
