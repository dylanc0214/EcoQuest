<?php
// pages/moderator/manage_quests.php
require_once '../../includes/header.php';

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$error_message = null;
$quests = [];

// 2. FETCH QUESTS (SQL FIXED)
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
        <h1 class="admin-title"><i class="fas fa-map"></i> View Quests</h1>
        <p class="admin-subtitle">Browse and review all available learning quests. (Read-only access)</p>

        <?php if ($error_message): ?>
            <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (empty($quests) && !$error_message): ?>
            <div class="empty-state"><p>No quests found at the moment.</p></div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th> <th>Points</th>
                            <th>Creator</th>
                            <th>Created On</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quests as $quest): ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($quest['Quest_id']); ?></td>
                                <td data-label="Title"><?php echo htmlspecialchars($quest['Title']); ?></td>
                                <td data-label="Category"><?php echo htmlspecialchars($quest['Category_Name'] ?? 'N/A'); ?></td>
                                <td data-label="Points">
                                    <span class="badge points-badge">
                                        <?php echo htmlspecialchars($quest['Points_award']) . ' Pts'; ?>
                                    </span>
                                </td>
                                <td data-label="Creator"><?php echo htmlspecialchars($quest['creator_username'] ?? 'N/A'); ?></td>
                                <td data-label="Created On"><?php echo date('Y-m-d', strtotime($quest['Created_at'])); ?></td>
                                <td data-label="Description" class="small-text">
                                    <?php echo htmlspecialchars($quest['Description']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .admin-page { padding: 30px 20px; background-color: #FAFAF0; min-height: 90vh; }
    .admin-content-card { max-width: 1200px; margin: 0 auto; background: #FFFFFF; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
    .admin-title { font-size: 2rem; font-weight: 700; color: #1D4C43; margin-bottom: 5px; border-bottom: 2px solid #DDEEE5; padding-bottom: 10px; }
    .admin-subtitle { font-size: 1rem; color: #5A7F7C; margin-bottom: 25px; }
    .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .error-message { background-color: #FADBD8; border: 1px solid #E74C3C; color: #C0392B; }
    .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
    .data-table { width: 100%; border-collapse: collapse; background-color: #FFFFFF; }
    .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
    .data-table th { background-color: #1D4C43; color: #FFFFFF; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
    .data-table tbody tr:hover { background-color: #f9fafb; }
    .badge { display: inline-block; padding: 5px 10px; border-radius: 9999px; font-weight: 600; font-size: 0.8rem; }
    .points-badge { background-color: #F8D7DA; color: #721C24; white-space: nowrap; }
    .small-text { font-size: 0.85rem; line-height: 1.4; color: #374151; }
    .empty-state { text-align: center; padding: 50px 20px; color: #A9A9A9; background-color: #fcfcfc; border-radius: 8px; }
    @media (max-width: 768px) {
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table tr { margin-bottom: 20px; border: 1px solid #DCDCDC; border-radius: 12px; padding: 12px; background: #ffffff; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
        .data-table td { padding: 10px 0 10px 0; text-align: left; position: relative; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .data-table td:last-child { border-bottom: none; margin-top: 8px; }
        .data-table td::before { content: attr(data-label); font-weight: 700; color: #4A5568; text-align: left; font-size: 0.8rem; min-width: 80px; text-transform: uppercase; }
        .data-table td:last-child::before { display: none; }
    }
    
    @media (max-width: 600px) {
        .data-table tr { padding: 10px; }
        .data-table td { flex-direction: column; align-items: flex-start; padding: 8px 0; }
        .data-table td::before { display: block; margin-bottom: 4px; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>