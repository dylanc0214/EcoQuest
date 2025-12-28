<?php
// pages/admin/create_badge.php
require_once '../../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['badge_name']);
    $icon = trim($_POST['badge_icon']); // Emojis are just text! 💡
    $xp = intval($_POST['require_exp']);

    if (empty($name) || empty($icon) || $xp < 0) {
        $error = "Please fill in all fields correctly.";
    } else {
        $stmt = $conn->prepare("INSERT INTO badge (Badge_Name, Badge_image, Require_Exp_Points) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $icon, $xp);
        
        if ($stmt->execute()) {
            $success = "Badge '$name' created successfully!";
        } else {
            $error = "Database Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<main class="page-content">
    <div class="container" style="max-width: 600px;">
        <header class="dashboard-header">
            <h1 class="page-title">✨ Create Badge</h1>
            <a href="manage_badges.php" style="color: #666; text-decoration: none;">&larr; Back to Badges</a>
        </header>

        <div class="auth-card" style="margin-top: 20px; padding: 30px;">
            <?php if ($error): ?>
                <div class="message error-message" style="color: red; margin-bottom: 15px;"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="message success-message" style="color: green; margin-bottom: 15px;"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Badge Name</label>
                    <input type="text" name="badge_name" class="form-input" required placeholder="e.g. Diamond Master" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Badge Icon (Emoji)</label>
                    <input type="text" name="badge_icon" class="form-input" required placeholder="e.g. 💎 or 👑" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <small style="color: #888;">Pro tip: Press <kbd>Win</kbd> + <kbd>.</kbd> (Windows) or <kbd>Cmd</kbd> + <kbd>Ctrl</kbd> + <kbd>Space</kbd> (Mac) to open emoji panel.</small>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">XP Required</label>
                    <input type="number" name="require_exp" class="form-input" required placeholder="e.g. 5000" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; background: #10b981; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Create Badge</button>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>