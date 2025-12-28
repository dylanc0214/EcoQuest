<?php
// pages/admin/edit_badge.php
require_once '../../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../../index.php'); exit; }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$badge = null;

// Fetch Existing
if ($id > 0) {
    $res = $conn->query("SELECT * FROM badge WHERE Badge_id = $id");
    if ($res && $res->num_rows > 0) $badge = $res->fetch_assoc();
}

if (!$badge) { echo "<div class='container'><h3>Badge not found!</h3></div>"; include '../../includes/footer.php'; exit; }

$error = '';
$success = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['badge_name']);
    $icon = trim($_POST['badge_icon']);
    $xp = intval($_POST['require_exp']);

    if (empty($name) || empty($icon)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("UPDATE badge SET Badge_Name=?, Badge_image=?, Require_Exp_Points=? WHERE Badge_id=?");
        $stmt->bind_param("ssii", $name, $icon, $xp, $id);
        if ($stmt->execute()) {
            $success = "Badge updated!";
            // Refresh data
            $badge['Badge_Name'] = $name;
            $badge['Badge_image'] = $icon;
            $badge['Require_Exp_Points'] = $xp;
        } else {
            $error = "Error updating: " . $conn->error;
        }
    }
}
?>

<main class="page-content">
    <div class="container" style="max-width: 600px;">
        <header class="dashboard-header">
            <h1 class="page-title">✏️ Edit Badge</h1>
            <a href="manage_badges.php" style="color: #666; text-decoration: none;">&larr; Back to Badges</a>
        </header>

        <div class="auth-card" style="margin-top: 20px; padding: 30px;">
            <?php if ($success): ?>
                <div class="message success-message" style="color: green; margin-bottom: 15px;"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: bold;">Badge Name</label>
                    <input type="text" name="badge_name" value="<?php echo htmlspecialchars($badge['Badge_Name']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: bold;">Badge Icon (Emoji)</label>
                    <input type="text" name="badge_icon" value="<?php echo htmlspecialchars($badge['Badge_image']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: bold;">XP Required</label>
                    <input type="number" name="require_exp" value="<?php echo $badge['Require_Exp_Points']; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Update Badge</button>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>