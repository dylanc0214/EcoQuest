<?php // Force PHP codes completion for the file: pages/admin/manage_quests.php
// pages/admin/manage_quests.php
require_once '../../includes/header.php'; 

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

$success_message = filter_input(INPUT_GET, 'success', FILTER_SANITIZE_SPECIAL_CHARS);
$error_message = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS);
$quests = [];

// 2. HANDLE QUEST DELETION (Unchanged)
$delete_id = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
if ($delete_id && isset($conn) && $conn) {
    try {
        // We must delete from child tables first if they have ON DELETE RESTRICT
        // 1. Delete from Quest_Progress
        $stmt_progress = $conn->prepare("DELETE FROM Quest_Progress WHERE Quest_id = ?");
        $stmt_progress->bind_param("i", $delete_id);
        $stmt_progress->execute();
        $stmt_progress->close();

        // 2. Delete from Student_Quest_Submissions
        $stmt_subs = $conn->prepare("DELETE FROM Student_Quest_Submissions WHERE Quest_id = ?");
        $stmt_subs->bind_param("i", $delete_id);
        $stmt_subs->execute();
        $stmt_subs->close();
        
        // 3. Now delete the quest
        $sql_delete = "DELETE FROM Quest WHERE Quest_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $delete_id);
        
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $success_message = "Quest ID {$delete_id} and all related progress/submissions deleted.";
            } else {
                $error_message = "Error: Quest ID {$delete_id} not found.";
            }
        } else {
            throw new Exception("Execution Failed: " . $stmt_delete->error);
        }
        $stmt_delete->close();
        
        // Redirect to clear the GET parameter
        $base_path = strtok($_SERVER["REQUEST_URI"], '?');
        $success_param = $success_message ? 'success=' . urlencode($success_message) : '';
        $error_param = $error_message ? 'error=' . urlencode($error_message) : '';
        header("Location: {$base_path}?{$success_param}{$error_param}");
        exit;
        
    } catch (Exception $e) {
        $error_message = "Database deletion failed. Details: " . $e->getMessage();
    }
}

// 3. FETCH ALL QUESTS (SQL FIXED)
if (isset($conn) && $conn) {
    try {
        $sql_fetch = "
            SELECT
                q.Quest_id, q.Title, q.Description, q.Points_award, 
                qc.Category_Name, -- <-- CHANGED
                q.Created_at,
                COALESCE(u.Username, 'N/A') AS creator_username
            FROM Quest q
            LEFT JOIN Quest_Categories qc ON q.CategoryID = qc.CategoryID -- <-- ADDED JOIN
            LEFT JOIN Admin a ON q.Created_by = a.Admin_id
            LEFT JOIN User u ON a.User_id = u.User_id
            ORDER BY q.Created_at DESC
        ";
        
        $result = $conn->query($sql_fetch);
        if ($result) {
            $quests = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            throw new Exception("SQL Fetch Error: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = "Failed to load quests. DB Error: " . $e->getMessage();
    }
} else {
    $error_message = "Database connection is unavailable.";
}
?>

<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Manage Quests</h1>
        <p class="admin-subtitle">View, create, and manage all available learning challenges.</p>
        <div class="flex justify-between items-center mb-6">
            <a href="create_quest.php" class="btn-primary-admin">
                <i class="fas fa-plus-circle mr-2"></i> Create New Quest
            </a>
            <span class="text-sm text-gray-500 font-medium">Total Quests: <?php echo count($quests); ?></span>
        </div>

        <?php if ($success_message): ?>
            <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th> <th>Points</th>
                        <th>Creator</th>
                        <th>Created On</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quests as $quest): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($quest['Quest_id']); ?></td>
                            <td data-label="Title"><?php echo htmlspecialchars($quest['Title']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($quest['Category_Name'] ?? 'N/A'); ?></td>
                            <td data-label="Points"><span class="badge points-badge"><?php echo htmlspecialchars($quest['Points_award']); ?> Pts</span></td>
                            <td data-label="Creator"><?php echo htmlspecialchars($quest['creator_username']); ?></td>
                            <td data-label="Created On"><?php echo date('Y-m-d', strtotime($quest['Created_at'])); ?></td>
                            <td data-label="Actions" class="actions-cell">
                                <a href="edit_quest.php?id=<?php echo $quest['Quest_id']; ?>" class="action-btn edit-btn" title="Edit Quest"><i class="fas fa-edit"></i></a>
                                <button onclick="confirmDelete(<?php echo $quest['Quest_id']; ?>)" class="action-btn delete-btn" title="Delete Quest">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    function confirmDelete(questId) {
        if (confirm(`Are you sure you want to delete Quest ID ${questId}? This will also delete all student progress and submissions for this quest. This action cannot be undone.`)) {
            window.location.href = `manage_quests.php?delete_id=${questId}`;
        }
    }
</script>

<style>
    /* Admin Base Layout */
    .admin-page { padding: 30px 20px; background-color: #FAFAF0; min-height: 90vh; }
    .admin-content-card { max-width: 1200px; margin: 0 auto; background: #FFFFFF; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
    .admin-title { font-size: 2rem; font-weight: 700; color: #1D4C43; margin-bottom: 5px; border-bottom: 2px solid #DDEEE5; padding-bottom: 10px; }
    .admin-subtitle { font-size: 1rem; color: #5A7F7C; margin-bottom: 25px; }
    /* Flex utilities */
    .flex { display: flex; }
    .justify-between { justify-content: space-between; }
    .items-center { align-items: center; }
    .mb-6 { margin-bottom: 1.5rem; }
    .mr-2 { margin-right: 0.5rem; }
    .text-sm { font-size: 0.875rem; }
    .text-gray-500 { color: #6b7280; }
    .font-medium { font-weight: 500; }
    /* Button */
    .btn-primary-admin { display: inline-flex; align-items: center; background: #71B48D; color: #1D4C43; padding: 10px 18px; font-size: 0.9rem; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; transition: background 0.3s, transform 0.1s; text-decoration: none; }
    .btn-primary-admin:hover { background: #5AA080; transform: translateY(-1px); }
    /* Messages */
    .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; font-weight: 500; }
    .error-message { background-color: #FADBD8; border: 1px solid #E74C3C; color: #C0392B; }
    .success-message { background-color: #DDEEE5; border: 1px solid #71B48D; color: #1D4C43; }
    /* Table */
    .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
    .data-table { width: 100%; border-collapse: collapse; background-color: #FFFFFF; }
    .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
    .data-table th { background-color: #1D4C43; color: #FFFFFF; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
    .data-table tbody tr:hover { background-color: #fafafa; }
    .badge { display: inline-block; padding: 5px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.8rem; }
    .points-badge { background-color: #F8D7DA; color: #721C24; }
    /* Actions */
    .actions-cell { white-space: nowrap; text-align: center; }
    .action-btn { background: none; border: none; cursor: pointer; font-size: 1.1rem; margin: 0 5px; transition: color 0.2s; padding: 8px; }
    .edit-btn { color: #2C3E50; }
    .edit-btn:hover { color: #71B48D; }
    .delete-btn { color: #C0392B; }
    .delete-btn:hover { color: #E74C3C; }
    /* Responsive */
    @media (max-width: 768px) {
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table tr { margin-bottom: 15px; border: 1px solid #f0f0f0; border-radius: 8px; }
        .data-table td { text-align: right; padding-left: 50%; position: relative; }
        .data-table td::before { content: attr(data-label); position: absolute; left: 10px; width: calc(50% - 20px); font-weight: 600; color: #5A7F7C; text-align: left; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>