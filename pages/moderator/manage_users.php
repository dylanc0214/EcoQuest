<?php
// pages/moderator/manage_users.php
require_once '../../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['moderator', 'admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$students = [];
if ($conn) {
    // Fetches Students + Ban Status
    $query = "
        SELECT s.Student_id, u.Username, u.Email, s.Total_point, u.Created_at, s.Ban_time
        FROM student s
        JOIN user u ON s.User_id = u.User_id
        ORDER BY u.Created_at DESC
    ";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}
?>

<main class="page-content admin-users">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-users"></i> View Students</h1>
        </header>
        
        <section class="admin-data-section user-list">
            <div class="table-responsive">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php 
                                // Check if Banned
                                $is_banned = ($student['Ban_time'] && new DateTime($student['Ban_time']) > new DateTime());
                            ?>
                            <tr style="<?php echo $is_banned ? 'background-color: #fff0f0;' : ''; ?>">
                                <td><?php echo htmlspecialchars($student['Student_id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($student['Username']); ?>
                                    <br><small><?php echo htmlspecialchars($student['Email']); ?></small>
                                </td>
                                <td>
                                    <?php if ($is_banned): ?>
                                        <span style="color:red; font-weight:bold;">🚫 Banned</span>
                                    <?php else: ?>
                                        <span style="color:green;">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($student['Total_point']); ?></td>
                                <td>
                                    <div class="action-dropdown">
                                        <button class="action-btn"><i class="fas fa-ellipsis-v"></i></button>
                                        <div class="action-menu">
                                            <a href="view_student.php?student_id=<?php echo $student['Student_id']; ?>">View Profile</a>
                                            
                                            <?php if ($is_banned): ?>
                                                <a href="../../pages/ban_handler.php?student_id=<?php echo $student['Student_id']; ?>&action=unban" style="color:green;">
                                                    <i class="fas fa-unlock"></i> Unban User
                                                </a>
                                            <?php else: ?>
                                                <a href="../../pages/ban_handler.php?student_id=<?php echo $student['Student_id']; ?>&action=ban" style="color:red;" onclick="return confirm('Are you sure you want to ban this user?');">
                                                    <i class="fas fa-ban"></i> Ban User
                                                </a>
                                            <?php endif; ?>
                                            
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<script>
// Simple Dropdown Script
document.addEventListener('click', e => {
    const btn = e.target.closest('.action-btn');
    document.querySelectorAll('.action-dropdown.show').forEach(d => d !== (btn?.parentElement) && d.classList.remove('show'));
    if (btn) btn.parentElement.classList.toggle('show');
});
</script>
<style>
    .action-dropdown { position: relative; display: inline-block; }
    .action-menu { display: none; position: absolute; right: 0; background: #fff; border: 1px solid #ccc; z-index: 10; width: 150px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .action-dropdown.show .action-menu { display: block; }
    .action-menu a { display: block; padding: 10px; text-decoration: none; color: #333; }
    .action-menu a:hover { background: #f0f0f0; }
</style>
<?php require_once '../../includes/footer.php'; ?>