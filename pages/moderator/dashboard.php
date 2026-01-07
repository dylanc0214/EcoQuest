<?php
// pages/moderator/dashboard.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in  = $is_logged_in ?? false;
$user_role     = $user_role ?? 'guest';
$conn          = $conn ?? null;
$moderator_id  = $_SESSION['moderator_id'] ?? null;

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin']) || !$moderator_id) {
    header('Location: ../../index.php?error=unauthorized_mod');
    exit;
}

// =======================================================
// 2. DATA FETCHING
// =======================================================
$stats = [
    'pending' => 0,
    'approved_today' => 0,
    'total_reviewed' => 0,
];

$recent_submissions = [];
$error_message = null;
$current_username = htmlspecialchars($_SESSION['username'] ?? 'Moderator');

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    try {
        // Pending
        $stmt = $conn->prepare("SELECT COUNT(*) total FROM Student_Quest_Submissions WHERE Status='pending'");
        $stmt->execute();
        $stats['pending'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Approved today
        $stmt = $conn->prepare("
            SELECT COUNT(*) total FROM Student_Quest_Submissions
            WHERE Moderator_id=? AND Status='completed' AND DATE(Review_date)=CURDATE()
        ");
        $stmt->bind_param("i", $moderator_id);
        $stmt->execute();
        $stats['approved_today'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Total reviewed
        $stmt = $conn->prepare("
            SELECT COUNT(*) total FROM Student_Quest_Submissions
            WHERE Moderator_id=? AND Status IN ('completed','rejected')
        ");
        $stmt->bind_param("i", $moderator_id);
        $stmt->execute();
        $stats['total_reviewed'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Recent pending
        $stmt = $conn->prepare("
            SELECT s.Student_quest_submission_id, u.Username, q.Title quest_title, s.Submission_date
            FROM Student_Quest_Submissions s
            JOIN Student st ON s.Student_id = st.Student_id
            JOIN User u ON st.User_id = u.User_id
            JOIN Quest q ON s.Quest_id = q.Quest_id
            WHERE s.Status='pending'
            ORDER BY s.Submission_date ASC
            LIMIT 5
        ");
        $stmt->execute();
        $recent_submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

    } catch (Exception $e) {
        $error_message = "SQL Error.";
    }
}
?>

<main class="page-content admin-dashboard">
<div class="container">

<header class="dashboard-header">
    <h1 class="page-title">Moderator Hub</h1>
    <p class="subtitle">Welcome back, <strong><?= $current_username ?></strong></p>
</header>

<?php if ($error_message): ?>
<div class="alert alert-error"><?= $error_message ?></div>
<?php endif; ?>

<!-- ================= STATS / SHORTCUTS ================= -->
<section class="stat-cards-grid">
    <div class="stat-card stat-card-pending">
        <i class="fas fa-hourglass-half icon"></i>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['pending']) ?></span>
            <span class="stat-label">Pending Reviews</span>
        </div>
        <a href="manage_submissions.php" class="btn btn-sm btn-accent">Review Now →</a>
    </div>

    <div class="stat-card">
        <i class="fas fa-check-circle icon"></i>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['approved_today']) ?></span>
            <span class="stat-label">Reviewed Today</span>
        </div>
    </div>

    <div class="stat-card">
        <i class="fas fa-history icon"></i>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['total_reviewed']) ?></span>
            <span class="stat-label">Total Reviewed</span>
        </div>
    </div>
</section>

<!-- ================= URGENT QUEUE ================= -->
<section class="admin-data-section">
<header class="section-header">
    <h2>Urgent Review Queue</h2>
    <a href="manage_submissions.php" class="btn-link-sm">See Full List →</a>
</header>

<?php if ($recent_submissions): ?>
<div class="table-responsive">
<table class="admin-data-table submissions-table">
<thead>
<tr>
    <th>Student</th>
    <th>Quest</th>
    <th>Submitted</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($recent_submissions as $s): ?>
<tr>
    <td data-label="Student"><?= htmlspecialchars($s['Username']) ?></td>
    <td data-label="Quest"><?= htmlspecialchars($s['quest_title']) ?></td>
    <td data-label="Submitted"><?= date('d M Y, h:i A', strtotime($s['Submission_date'])) ?></td>
    <td data-label="Action">
        <a href="review_submission.php?id=<?= $s['Student_quest_submission_id'] ?>" class="btn btn-sm btn-primary">
            Review
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<div class="empty-state">
    <p>No pending submissions 🎉</p>
</div>
<?php endif; ?>
</section>

<!-- ================= QUICK ACTIONS ================= -->
<section class="student-actions-grid admin-actions">
    <div class="section-card action-card">
        <h2>Manage Submissions</h2>
        <p>Review all submissions</p>
        <a href="manage_submissions.php" class="btn btn-secondary">Open</a>
    </div>

    <div class="section-card action-card">
        <h2>Community Forum</h2>
        <p>Moderate discussions</p>
        <a href="../forum.php" class="btn btn-secondary">View</a>
    </div>

    <div class="section-card action-card">
        <h2>My Profile</h2>
        <p>Account settings</p>
        <a href="../profile.php" class="btn btn-secondary">Profile</a>
    </div>
</section>

</div>
</main>

<style>
html,body{overflow-x:hidden}
.container{padding:0 12px}

/* MOBILE TABLE → CARDS */
@media (max-width:768px){
.submissions-table thead{display:none}
.submissions-table,
.submissions-table tbody,
.submissions-table tr,
.submissions-table td{
    display:block;
    width:100%;
}
.submissions-table tr{
    margin-bottom:16px;
    padding:14px;
    border:1px solid #ddd;
    border-radius:12px;
    background:#fff;
}
.submissions-table td{
    display:flex;
    justify-content:space-between;
    padding:8px 0;
    border-bottom:1px solid #eee;
}
.submissions-table td:last-child{border-bottom:none}
.submissions-table td::before{
    content:attr(data-label);
    font-weight:700;
    font-size:.75rem;
    color:#555;
}
.btn{width:100%;text-align:center}
.stat-cards-grid,
.student-actions-grid{grid-template-columns:1fr}
}
</style>

<?php require_once '../../includes/footer.php'; ?>
