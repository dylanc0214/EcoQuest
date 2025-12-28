<?php
// pages/admin/manage_badges.php
require_once '../../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

// --- HANDLE DELETE ACTION ---
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM badge WHERE Badge_id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $msg = "Badge deleted successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error deleting badge.";
        $msg_type = "error";
    }
    $stmt->close();
}

// --- FETCH BADGES ---
$badges = [];
$result = $conn->query("SELECT * FROM badge ORDER BY Require_Exp_Points ASC");
if ($result) {
    $badges = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="page-content">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title">🏅 Manage Badges</h1>
            <p class="subtitle">Set the ranks and XP milestones for students.</p>
        </header>

        <?php if (isset($msg)): ?>
            <div class="message <?php echo $msg_type; ?>-message" style="margin-bottom: 20px; padding: 10px; background: <?php echo $msg_type == 'success' ? '#d1e7dd' : '#f8d7da'; ?>; color: <?php echo $msg_type == 'success' ? '#0f5132' : '#842029'; ?>; border-radius: 5px;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 20px; text-align: right;">
            <a href="create_badge.php" class="btn btn-primary" style="background-color: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                <i class="fas fa-plus-circle"></i> Create New Badge
            </a>
        </div>

        <section class="admin-data-section">
            <div class="table-responsive">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Badge Name</th>
                            <th>XP Required</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($badges)): ?>
                            <tr><td colspan="4" style="text-align:center;">No badges found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($badges as $b): ?>
                                <tr>
                                    <td style="font-size: 2rem; text-align: center;"><?php echo $b['Badge_image']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($b['Badge_Name']); ?></strong></td>
                                    <td>
                                        <span style="background: #eef2ff; color: #4f46e5; padding: 5px 10px; border-radius: 15px; font-weight: bold;">
                                            <?php echo number_format($b['Require_Exp_Points']); ?> XP
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_badge.php?id=<?php echo $b['Badge_id']; ?>" title="Edit" style="color: #3b82f6; margin-right: 10px; font-size: 1.2rem;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage_badges.php?delete_id=<?php echo $b['Badge_id']; ?>" title="Delete" style="color: #ef4444; font-size: 1.2rem;" onclick="return confirm('Are you sure? This will remove the badge for everyone!');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<style>
    .admin-data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .admin-data-table th { background: #f8fafc; color: #64748b; font-weight: 600; text-align: left; padding: 15px; }
    .admin-data-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; }
    .admin-data-table tr:last-child td { border-bottom: none; }
</style>

<?php require_once '../../includes/footer.php'; ?>