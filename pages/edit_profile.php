<?php
// pages/edit_profile.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . "/../config/db.php");
include("../includes/header.php");

// 1. Authorization Check
if (!isset($_SESSION['user_id'])) {
    header("Location: sign_up.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Password fields
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $proceed_update = true;

    // Basic Validation
    if (empty($username) || empty($email)) {
        $message = 'Username and Email cannot be empty.';
        $message_type = 'error-message';
        $proceed_update = false;
    }

    // Password Change Logic
    if ($proceed_update && !empty($new_password)) {
        if (empty($current_password)) {
            $message = 'To set a new password, you must enter your current one.';
            $message_type = 'error-message';
            $proceed_update = false;
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match.';
            $message_type = 'error-message';
            $proceed_update = false;
        } elseif (strlen($new_password) < 8) {
            $message = 'New password must be at least 8 characters.';
            $message_type = 'error-message';
            $proceed_update = false;
        } else {
            // Verify current password from DB
            $stmt = $conn->prepare("SELECT Password_hash FROM User WHERE User_id = ?");
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (!password_verify($current_password, $row['Password_hash'])) {
                    $message = 'Current password is incorrect.';
                    $message_type = 'error-message';
                    $proceed_update = false;
                }
            }
        }
    }

    // Execute Update
    if ($proceed_update) {
        $sql = "UPDATE User SET Username = ?, Email = ?";
        $params = [$username, $email];
        $types = "ss";

        if (!empty($new_password)) {
            $sql .= ", Password_hash = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            $types .= "s";
        }

        $sql .= " WHERE User_id = ?";
        $params[] = $current_user_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $message = 'Profile updated successfully!';
            $message_type = 'success-message';
            // Update session if username changed
            $_SESSION['username'] = $username;
        } else {
            $message = 'Update failed: ' . $conn->error;
            $message_type = 'error-message';
        }
    }
}

// 3. Fetch Current Data
$user_data = ['Username' => '', 'Email' => ''];
if ($conn) {
    $stmt = $conn->prepare("SELECT Username, Email FROM User WHERE User_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
}
?>

<main class="admin-form-container">
    <div class="modern-card">

        <div style="text-align: center; margin-bottom: 35px;">
            <h1 style="font-size: 2rem; font-weight: 800; color: #1e293b; margin-bottom: 10px;">
                Edit Profile
            </h1>
            <p style="color: #64748b; font-size: 0.95rem;">
                Manage your account settings for <strong><?php echo htmlspecialchars($user_data['Username']); ?></strong>
            </p>
        </div>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>" style="margin-bottom: 25px; padding: 15px; border-radius: 12px; text-align: center; font-weight: 600;">
                <?php if($message_type == 'success-message') echo '<i class="fas fa-check-circle"></i> '; ?>
                <?php if($message_type == 'error-message') echo '<i class="fas fa-exclamation-triangle"></i> '; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="edit_profile.php" method="POST">

            <div class="form-floating">
                <i class="fas fa-user"></i>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user_data['Username']); ?>" placeholder=" " required>
                <label for="username">Username</label>
            </div>

            <div class="form-floating">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['Email']); ?>" placeholder=" " required>
                <label for="email">Email Address</label>
            </div>

            <div class="security-zone">
                <span class="security-badge"><i class="fas fa-shield-alt"></i> Security</span>
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 20px; margin-top: 8px; line-height: 1.5;">
                    To change your password, please enter your current one for verification.
                </p>

                <div class="form-floating">
                    <i class="fas fa-lock-open"></i>
                    <input type="password" name="current_password" id="cur_pass" placeholder=" ">
                    <label for="cur_pass">Current Password</label>
                </div>

                <div class="form-floating" style="margin-bottom: 15px;">
                    <i class="fas fa-key"></i>
                    <input type="password" name="new_password" id="new_pass" placeholder=" ">
                    <label for="new_pass">New Password</label>
                </div>

                <div class="form-floating" style="margin-bottom: 0;">
                    <i class="fas fa-check-double"></i>
                    <input type="password" name="confirm_password" id="conf_pass" placeholder=" ">
                    <label for="conf_pass">Confirm New Password</label>
                </div>
            </div>

            <div style="margin-top: 35px; display: flex; gap: 15px;">
                <a href="profile.php" class="btn-modern" style="background: #f1f5f9; color: #475569; flex: 1; text-decoration: none;">
                    Cancel
                </a>
                <button type="submit" class="btn-modern" style="flex: 2;">
                    Save Changes <i class="fas fa-save"></i>
                </button>
            </div>

        </form>
    </div>
</main>

<?php include("../includes/footer.php"); ?>