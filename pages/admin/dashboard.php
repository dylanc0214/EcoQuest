<?php
// pages/admin/dashboard.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK & SAFETY
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// =======================================================
// 2. DATA FETCHING (Admin Stats) - UPDATED
// =======================================================
$total_students = 0;
$total_points_awarded = 0;
$pending_submissions_count = 0;
$recent_submissions = [];
$error_message = null;

if (!$conn) {
    $error_message = "Database connection failed. Check your config/db.php file.";
} else {
    try {
        // --- Fetch key metrics ---

        // 1. Total Students - FIXED: Fetches from the 'Student' table
        $stmt = $conn->prepare("SELECT COUNT(Student_id) AS total FROM Student");
        $stmt->execute();
        $total_students = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // 2. Total Points Awarded - FIXED: Fetches from the 'Student' table
        $stmt = $conn->prepare("SELECT SUM(Total_point) AS total FROM Student");
        $stmt->execute();
        $total_points_awarded = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // 3. Pending Submissions Count - FIXED: Fetches from 'Student_Quest_Submissions'
        $stmt = $conn->prepare("SELECT COUNT(Student_quest_submission_id) AS total FROM Student_Quest_Submissions WHERE Status = 'pending'");
        $stmt->execute();
        $pending_submissions_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // --- 2.2 Fetch recent pending submissions (Top 5) - FIXED QUERY ---
        // Joins User -> Student -> Student_Quest_Submissions
        $stmt = $conn->prepare("
            SELECT
                s.Student_quest_submission_id,
                u.Username,
                q.Title,
                s.Submission_date,
                s.Status
            FROM Student_Quest_Submissions s
            JOIN Student st ON s.Student_id = st.Student_id
            JOIN User u ON st.User_id = u.User_id
            JOIN Quest q ON s.Quest_id = q.Quest_id
            WHERE s.Status = 'pending'
            ORDER BY s.Submission_date ASC -- Oldest first
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_submissions[] = $row;
        }
        $stmt->close();

    } catch (Exception $e) {
        $error_message = "SQL ERROR: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-dashboard">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title">EcoQuest Admin Hub</h1>
            <p class="subtitle">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>!</p>
        </header>

        <?php if (isset($error_message) && $error_message): ?>
            <div class="alert alert-error">
                <strong>Data Fetching Alert:</strong> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="stat-cards-grid">
            <div class="stat-card stat-card-students">
                <i class="fas fa-users icon"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_students); ?></span>
                    <span class="stat-label">Total Student Users</span>
                </div>
            </div>

            <div class="stat-card stat-card-points">
                <i class="fas fa-trophy icon"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_points_awarded); ?></span>
                    <span class="stat-label">Total EcoPoints Awarded</span>
                </div>
            </div>

            <div class="stat-card stat-card-pending stat-card-cta">
                <i class="fas fa-hourglass-half icon"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($pending_submissions_count); ?></span>
                    <span class="stat-label">Submissions Awaiting Review</span>
                </div>
                <a href="manage_submissions.php" class="btn btn-sm btn-accent">Review Now <i class="fas fa-angle-right"></i></a>
            </div>
        </div>

        <section class="admin-data-section">
            <header class="section-header">
                <h2><i class="fas fa-clock"></i> Urgent Review Queue (Oldest First)</h2>
                <a href="manage_submissions.php" class="btn-link-sm">See Full List <i class="fas fa-arrow-right"></i></a>
            </header>

            <?php if (!empty($recent_submissions)): ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Quest Title</th>
                                <th>Submitted On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent_submissions as $submission): ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($submission['Student_quest_submission_id']); ?></td>
                                <td data-label="Student"><i class="fas fa-user-circle user-icon"></i> <?php echo htmlspecialchars($submission['Username']); ?></td>
                                <td data-label="Quest Title"><?php echo htmlspecialchars($submission['Title']); ?></td>
                                <td data-label="Submitted On"><?php echo date('d M Y, h:i A', strtotime($submission['Submission_date'])); ?></td>
                                <td data-label="Action">
                                    <a href="review_submission.php?id=<?php echo $submission['Student_quest_submission_id']; ?>" class="btn btn-sm btn-primary">Review Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle large-icon"></i>
                    <h3>All Clear!</h3>
                    <p>No new submissions are pending review.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="student-actions-grid admin-actions">
            <div class="section-card action-card">
                <h2><i class="fas fa-id-badge"></i> Manage Users</h2>
                <p>View, edit, or manage student and staff accounts.</p>
                <a href="manage_users.php" class="btn btn-secondary">User Accounts</a>
            </div>
            <div class="section-card action-card">
                <h2><i class="fas fa-chart-bar"></i> View Reports</h2>
                <p>Access overall reports on platform trends and top students.</p>
                <a href="reports.php" class="btn btn-secondary">View Analytics</a>
            </div>
            <div class="section-card action-card">
                <h2><i class="fas fa-cogs"></i> Manage Quests</h2>
                <p>Create new environmental challenges, update instructions, or delete old quests.</p>
                <a href="manage_quests.php" class="btn btn-secondary">Manage Quests</a>
            </div>
        </section>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>

<style>
/* --- Admin Dashboard Mobile Optimization --- */
@media (max-width: 768px) {
    /* 1. Header and Subtitle adjustment */
    .dashboard-header { text-align: center; padding-bottom: 20px; }
    .page-title { font-size: 1.5rem; }

    /* 2. Stat Cards: Stack them in a single column */
    .stat-cards-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    .stat-card {
        padding: 15px;
        justify-content: center;
        text-align: center;
        flex-direction: column; /* Icon above text */
    }
    .stat-card .icon { margin-right: 0; margin-bottom: 10px; font-size: 2rem; }

    /* 3. Table Transformation: Change Rows into Cards */
    .admin-data-table thead { display: none; } /* Hide headers */

    .admin-data-table, .admin-data-table tbody,
    .admin-data-table tr, .admin-data-table td {
        display: block;
        width: 100%;
    }

    .admin-data-table tr {
        margin-bottom: 15px;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        background: #fff;
    }

    .admin-data-table td {
        text-align: right;
        padding: 10px 15px;
        position: relative;
        padding-left: 45%; /* Space for the label */
        border-bottom: 1px dashed #f0f0f0;
    }

    .admin-data-table td:last-child { border-bottom: none; text-align: center; padding-left: 15px; }

    .admin-data-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: 40%;
        text-align: left;
        font-weight: 700;
        color: var(--color-primary);
        text-transform: uppercase;
        font-size: 0.75rem;
    }

    /* 4. Quick Actions: Stack buttons full-width */
    .admin-actions {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    .action-card { padding: 20px; }
    .btn-secondary { width: 100%; }
}
</style>