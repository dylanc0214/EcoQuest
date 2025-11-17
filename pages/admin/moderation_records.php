<?php
// pages/admin/moderation_records.php
require_once '../../includes/header.php';

// Authorization Check
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

$error_message = null;
$records = [];
$total_records = 0;

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    try {
        // Get total count first
        $count_sql = "
            SELECT COUNT(*) as total
            FROM Student_Quest_Submissions s
            WHERE s.Status IN ('completed', 'rejected')
        ";

        $count_result = $conn->query($count_sql);
        $total_records = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $records_per_page);

        // Main query with pagination
        $sql = "
            SELECT
                s.Student_quest_submission_id,
                s.Status,
                s.Submission_date,
                s.Review_date,
                s.Review_feedback,
                q.Title AS quest_title,
                u_student.Username AS student_username,
                u_mod.Username AS reviewer_username
            FROM Student_Quest_Submissions s
            JOIN Quest q ON s.Quest_id = q.Quest_id
            JOIN Student st ON s.Student_id = st.Student_id
            JOIN User u_student ON st.User_id = u_student.User_id
            LEFT JOIN Moderator m ON s.Moderator_id = m.Moderator_id
            LEFT JOIN User u_mod ON m.User_id = u_mod.User_id
            WHERE s.Status IN ('completed', 'rejected')
            ORDER BY s.Review_date DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $records_per_page, $offset);

        if (!$stmt->execute()) {
            throw new Exception("SQL execution failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();

    } catch (Exception $e) {
        $error_message = "A database query error occurred: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-page">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-history"></i> Moderation Activity Log</h1>
            <p class="subtitle">Track all submission review actions taken by Moderators and Admins.</p>
        </header>

        <?php if ($error_message): ?>
            <div class="message error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <section class="admin-data-section moderation-log">
            <header class="section-header">
                <h2>Reviewed Submissions (<?php echo $total_records; ?>)</h2>
            </header>

            <?php if (!empty($records)): ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Quest</th>
                                <th>Status</th>
                                <th>Reviewed By</th>
                                <th>Review Date</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($record['Student_quest_submission_id']); ?></td>
                                    <td data-label="Student">
                                        <i class="fas fa-user-circle user-icon"></i>
                                        <?php echo htmlspecialchars($record['student_username']); ?>
                                    </td>
                                    <td data-label="Quest"><?php echo htmlspecialchars($record['quest_title']); ?></td>
                                    <td data-label="Status">
                                        <span class="status-badge status-<?php echo strtolower($record['Status']); ?>">
                                            <?php echo ucfirst($record['Status']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Reviewed By" class="reviewer-cell">
                                        <strong><?php echo htmlspecialchars($record['reviewer_username'] ?? 'Admin/System'); ?></strong>
                                        <span>(<?php echo $record['reviewer_username'] ? 'Moderator' : 'Admin'; ?>)</span>
                                    </td>
                                    <td data-label="Review Date">
                                        <?php echo $record['Review_date'] ? date('d M Y, h:i A', strtotime($record['Review_date'])) : 'N/A'; ?>
                                    </td>
                                    <td data-label="Comment" class="comment-cell"
                                        title="<?php echo htmlspecialchars($record['Review_feedback']); ?>">
                                        <?php echo htmlspecialchars(substr($record['Review_feedback'], 0, 50)); ?>
                                        <?php echo (strlen($record['Review_feedback']) > 50) ? '...' : ''; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo ($current_page - 1); ?>" class="page-link">&laquo; Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>"
                                   class="page-link <?php echo ($i === $current_page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo ($current_page + 1); ?>" class="page-link">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Moderation Records Found</h3>
                    <p>No submissions have been approved or rejected yet.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
    /* NOTE: Many of these styles rely on classes defined in your main style.css.
       These rules primarily focus on the mobile transformation of the table. */

    .pagination { display: flex; justify-content: center; align-items: center; margin-top: 30px; gap: 10px; }
    .page-link { padding: 8px 16px; border: 1px solid #71B48D; border-radius: 4px; color: #1D4C43; text-decoration: none; }
    .page-link:hover { background-color: #71B48D; color: white; }
    .page-link.active { background-color: #1D4C43; color: white; border-color: #1D4C43; }

    /* --- Mobile Table Responsiveness --- */
    @media (max-width: 768px) {
        .pagination { flex-wrap: wrap; }

        .admin-data-table thead {
            display: none; /* Hide desktop headers */
        }

        .admin-data-table, .admin-data-table tbody, .admin-data-table tr, .admin-data-table td {
            display: block; /* Make all elements flow vertically */
            width: 100%;
        }

        .admin-data-table tr {
            margin-bottom: 15px;
            border: 1px solid #DCDCDC; /* Border around the "card" */
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
        }

        .admin-data-table td {
            /* Right align the data value */
            text-align: right;
            padding: 10px 15px; /* Add padding to cell */
            padding-left: 50%; /* Make space for the data label */
            position: relative;
            border-bottom: 1px dashed #f0f0f0;
        }

        .admin-data-table td:last-child {
            border-bottom: none;
        }

        .admin-data-table td::before {
            /* Create the data label from the data-label attribute */
            content: attr(data-label);
            position: absolute;
            left: 15px;
            width: calc(50% - 30px);
            text-align: left;
            font-weight: 600;
            color: #1D4C43; /* Forest Green label */
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        /* Specific styling for reviewer cell to improve stacking */
        .reviewer-cell strong, .reviewer-cell span {
            display: block;
            text-align: right;
            line-height: 1.2;
        }

        .reviewer-cell span {
            font-size: 0.7rem;
            margin-top: 3px;
        }

        /* Ensure comments are fully visible and wrap */
        .comment-cell {
            white-space: normal !important;
            max-width: 100% !important;
            text-overflow: unset !important;
            word-break: break-word;
            padding-bottom: 15px;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>