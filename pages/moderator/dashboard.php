<?php
// pages/moderator/dashboard.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;
$moderator_id = $_SESSION['moderator_id'] ?? null; // Use the correct session ID

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin']) || !$moderator_id) {
    header('Location: ../../index.php?error=unauthorized_mod');
    exit;
}

// =======================================================
// 2. DATA FETCHING (Moderator Stats) - UPDATED
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
        // 1. Total pending submissions
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Student_Quest_Submissions WHERE Status = 'pending'");
        $stmt->execute();
        $stats['pending'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // 2. Submissions approved today by this moderator
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Student_Quest_Submissions WHERE Moderator_id = ? AND Status = 'completed' AND DATE(Review_date) = CURDATE()");
        $stmt->bind_param("i", $moderator_id);
        $stmt->execute();
        $stats['approved_today'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // 3. Total submissions reviewed by this moderator
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Student_Quest_Submissions WHERE Moderator_id = ? AND Status IN ('completed', 'rejected')");
        $stmt->bind_param("i", $moderator_id);
        $stmt->execute();
        $stats['total_reviewed'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // 4. Fetch recent pending submissions (Oldest First)
        $stmt = $conn->prepare("
            SELECT s.Student_quest_submission_id, u.Username, q.Title AS quest_title, s.Submission_date
            FROM Student_Quest_Submissions s
            JOIN Student st ON s.Student_id = st.Student_id
            JOIN User u ON st.User_id = u.User_id
            JOIN Quest q ON s.Quest_id = q.Quest_id
            WHERE s.Status = 'pending'
            ORDER BY s.Submission_date ASC
            LIMIT 5
        ");
        $stmt->execute();
        $recent_submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

    } catch (Exception $e) {
        $error_message = "SQL ERROR: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-dashboard">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title">Moderator Hub</h1>
            <p class="subtitle">Welcome back, <strong><?php echo $current_username; ?></strong>. Let's review some eco-actions!</p>
        </header>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><strong>Error:</strong> <?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="stat-cards-grid">
            <div class="stat-card stat-card-pending stat-card-cta">
                <i class="fas fa-hourglass-half icon"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($stats['pending']); ?></span>
                    <span class="stat-label">Submissions Awaiting Review</span>
                </div>
                <a href="manage_submissions.php" class="btn btn-sm btn-accent">Review Now <i class="fas fa-angle-right"></i></a>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle icon" style="color: var(--color-accent);"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($stats['approved_today']); ?></span>
                    <span class="stat-label">Approved By You Today</span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-history icon" style="color: #6B7280;"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($stats['total_reviewed']); ?></span>
                    <span class="stat-label">Total Reviewed By You</span>
                </div>
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
                                <th>Student</th>
                                <th>Quest</th>
                                <th>Submitted On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent_submissions as $submission): ?>
                            <tr>
                                <td data-label="Student"><i class="fas fa-user-circle user-icon"></i> <?php echo htmlspecialchars($submission['Username']); ?></td>
                                <td data-label="Quest"><?php echo htmlspecialchars($submission['quest_title']); ?></td>
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
                    <h3>All Clear, Boss!</h3>
                    <p>No new submissions are pending review.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="student-actions-grid admin-actions">
            <div class="section-card action-card">
                <h2><i class="fas fa-tasks"></i> Manage Submissions</h2>
                <p>View all pending, approved, and rejected submissions.</p>
                <a href="manage_submissions.php" class="btn btn-secondary">Go to Submissions</a>
            </div>
            <div class="section-card action-card">
                <h2><i class="fas fa-comments"></i> Community Forum</h2>
                <p>Monitor the forum for any inappropriate content.</p>
                <a href="../forum.php" class="btn btn-secondary">View Forum</a>
            </div>
            <div class="section-card action-card">
                <h2><i class="fas fa-user"></i> My Profile</h2>
                <p>View your own profile and account details.</p>
                <a href="../profile.php" class="btn btn-secondary">View My Profile</a>
            </div>
        </section>
    </div>
</main>

<style>
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .admin-dashboard {
            padding: 20px 10px;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .subtitle {
            font-size: 0.95rem;
        }

        .stat-cards-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .stat-card {
            padding: 20px;
        }

        .stat-value {
            font-size: 2rem;
        }

        .stat-label {
            font-size: 0.9rem;
        }

        .recent-submissions-section {
            margin-top: 30px;
        }

        .submissions-table {
            font-size: 0.9rem;
        }

        .submissions-table thead {
            display: none;
        }

        .submissions-table tbody,
        .submissions-table tr,
        .submissions-table td {
            display: block;
            width: 100%;
        }

        .submissions-table tr {
            margin-bottom: 20px;
            border: 1px solid #DCDCDC;
            border-radius: 12px;
            padding: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .submissions-table td {
            padding: 10px 0 10px 0;
            text-align: left;
            position: relative;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .submissions-table td:last-child {
            border-bottom: none;
            margin-top: 8px;
        }

        .submissions-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #4A5568;
            font-size: 0.8rem;
            min-width: 80px;
            text-transform: uppercase;
        }

        .submissions-table td:last-child::before {
            display: none;
        }

        .student-actions-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .action-card {
            padding: 20px;
        }

        .action-card h2 {
            font-size: 1.2rem;
        }

        .action-card p {
            font-size: 0.9rem;
        }

        .btn {
            width: 100%;
            text-align: center;
        }
    }

    @media (max-width: 600px) {
        .page-title {
            font-size: 1.3rem;
        }

        .stat-cards-grid {
            gap: 10px;
        }

        .stat-card {
            padding: 15px;
        }

        .stat-value {
            font-size: 1.8rem;
        }

        .icon {
            font-size: 2rem;
        }

        .submissions-table tr {
            padding: 10px;
        }

        .submissions-table td {
            flex-direction: column;
            align-items: flex-start;
            padding: 8px 0;
        }

        .submissions-table td::before {
            display: block;
            margin-bottom: 4px;
        }

        .action-card {
            padding: 15px;
        }

        .action-card h2 {
            font-size: 1rem;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>