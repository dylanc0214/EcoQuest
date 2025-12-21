<?php
// pages/moderator/manage_users.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// =======================================================
// 2. DATA FETCHING (UPDATED TO JOIN User and Student)
// =======================================================
$error_message = null;
$students = [];

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    try {
        // This query now fetches users from the 'User' table
        // and joins 'Student' to get student-specific data
        $query = "
            SELECT 
                s.Student_id, 
                u.Username, 
                u.Email, 
                s.Total_point, 
                u.Created_at
            FROM Student s
            JOIN User u ON s.User_id = u.User_id
            ORDER BY u.Created_at DESC
        ";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
        } else {
            throw new Exception("Query failed: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = "A database query error occurred: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-users">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-users"></i> View Students</h1>
            <p class="subtitle">A list of all registered students on the EcoQuest platform.</p>
        </header>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <section class="admin-data-section user-list">
            <header class="section-header">
                <h2>All Students (<?php echo count($students); ?>)</h2>
            </header>

            <style>
            /* Small dropdown styles for the three-dot actions */
            .action-dropdown{position:relative;display:inline-block}
            .action-btn{background:transparent;border:0;padding:6px 8px;border-radius:6px;color:var(--color-text, #123);cursor:pointer}
            .action-btn i{font-size:14px}
            .action-menu{position:absolute;right:0;top:28px;min-width:140px;background:#fff;border:1px solid #e6e6e6;border-radius:6px;box-shadow:0 6px 18px rgba(17,24,39,0.06);display:none;z-index:40}
            .action-menu a{display:block;padding:8px 12px;color:#1f2937;text-decoration:none;border-bottom:1px solid #f2f2f2}
            .action-menu a:hover{background:#f6f9f6}
            .action-dropdown.show .action-menu{display:block}
            .actions-cell{width:80px;text-align:right}
            @media(max-width:720px){.actions-cell{text-align:left}}
            </style>

            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Points</th>
                                <th>Joined On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($student['Student_id']); ?></td>
                                    <td data-label="Username">
                                        <i class="fas fa-user-graduate user-icon"></i> 
                                        <?php echo htmlspecialchars($student['Username']); ?>
                                    </td>
                                    <td data-label="Email"><?php echo htmlspecialchars($student['Email']); ?></td>
                                    <td data-label="Points"><?php echo number_format($student['Total_point']); ?> Pts</td>
                                    <td data-label="Joined On"><?php echo date('d M Y', strtotime($student['Created_at'])); ?></td>
                                    <td class="actions-cell" data-label="Actions">
                                        <div class="action-dropdown">
                                            <button class="action-btn" aria-expanded="false" aria-haspopup="true" title="Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="action-menu" role="menu">
                                                <a class="action-item" role="menuitem" href="view_student.php?student_id=<?php echo urlencode($student['Student_id']); ?>">View Profile</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><h3>No Students Found</h3></div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
// Toggle action dropdowns (three-dot menu)
document.addEventListener('click', function(e){
    const btn = e.target.closest('.action-btn');
    // close all first
    document.querySelectorAll('.action-dropdown.show').forEach(function(dd){ if(!dd.contains(e.target)) dd.classList.remove('show'); });
    if(btn){
        const wrapper = btn.closest('.action-dropdown');
        const expanded = wrapper.classList.toggle('show');
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
});

// Close dropdowns on Escape
document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ document.querySelectorAll('.action-dropdown.show').forEach(dd=>dd.classList.remove('show')); } });
</script>

<?php require_once '../../includes/footer.php'; ?>