<?php
// pages/moderator/manage_reports.php
require_once '../../includes/header.php';

// 1. Authorization
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// 2. Filter Logic
$type_filter = $_GET['type'] ?? 'all'; 
$show_completed = isset($_GET['show_completed']) && $_GET['show_completed'] == '1';

$reports = [];
$error_message = '';

if ($conn) {
    try {
        $sql_post = "
            SELECT 'Post' as type, r.Post_report_id as id, r.Report_time, r.Reason, r.Status, u.Username as reporter_name
            FROM Post_report r JOIN User u ON r.Reported_by = u.User_id
        ";
        $sql_comment = "
            SELECT 'Comment' as type, r.Comment_report_id as id, r.Report_time, r.Reason, r.Status, u.Username as reporter_name
            FROM Comment_report r JOIN User u ON r.Reported_by = u.User_id
        ";

        if ($type_filter === 'post') {
            $query = $sql_post;
        } elseif ($type_filter === 'comment') {
            $query = $sql_comment;
        } else {
            $query = "($sql_post) UNION ALL ($sql_comment)";
        }

        if (!$show_completed) {
            $query = "SELECT * FROM ($query) AS combined WHERE Status != 'Completed'";
        }

        $query .= " ORDER BY Report_time DESC";
        $result = $conn->query($query);
        if ($result) $reports = $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        $error_message = "Error loading reports.";
    }
}
?>

<main class="page-content admin-page">
    <div class="container">

        <header class="dashboard-header">
            <h1 class="page-title">Manage Reports</h1>
            <p class="subtitle">Review and process student community reports</p>
        </header>

        <!-- Control Bar -->
        <div class="report-controls-bar">
            <div class="report-tabs">
                <a href="?type=post&show_completed=<?php echo $show_completed; ?>" class="tab-btn <?php echo $type_filter === 'post' ? 'active' : ''; ?>">Post</a>
                <a href="?type=comment&show_completed=<?php echo $show_completed; ?>" class="tab-btn <?php echo $type_filter === 'comment' ? 'active' : ''; ?>">Comment</a>
                <a href="?type=all&show_completed=<?php echo $show_completed; ?>" class="tab-btn <?php echo $type_filter === 'all' ? 'active' : ''; ?>">View All</a>
            </div>

            <div class="report-toggle-container">
                <span class="toggle-text">Show Completed</span>
                <label class="switch-wrapper">
                    <input type="checkbox"
                        onchange="window.location.href='?type=<?php echo $type_filter; ?>&show_completed=' + (this.checked ? '1' : '0')"
                        <?php echo $show_completed ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <!-- Reports -->
        <section class="admin-data-section">
            <h3 class="section-title"><?php echo ucfirst($type_filter); ?> Reports</h3>

            <?php if (empty($reports)): ?>
                <div class="empty-state">No reports found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-data-table data-table">
                        <thead>
                            <tr>
                                <th>Datetime</th>
                                <th>Student</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td data-label="Datetime"><?php echo date('d M Y, h:i A', strtotime($r['Report_time'])); ?></td>
                                <td data-label="Student"><strong><?php echo htmlspecialchars($r['reporter_name']); ?></strong></td>
                                <td data-label="Type">
                                    <span class="badge badge-<?php echo strtolower($r['type']); ?>">
                                        <?php echo $r['type']; ?>
                                    </span>
                                </td>
                                <td data-label="Reason"><?php echo htmlspecialchars($r['Reason']); ?></td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?php echo strtolower($r['Status']); ?>">
                                        <?php echo htmlspecialchars($r['Status']); ?>
                                    </span>
                                </td>
                                <td data-label="Action">
                                    <a class="btn-review"
                                       href="review_report.php?type=<?php echo strtolower($r['type']); ?>&id=<?php echo $r['id']; ?>">
                                        Review Now
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
/* ===== GLOBAL SAFETY ===== */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

.container {
    width: 100%;
    padding: 0 12px;
}

/* ===== CONTROL BAR ===== */
.report-controls-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    background: #fff;
    padding: 14px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.report-tabs {
    display: flex;
    gap: 8px;
}

.tab-btn {
    padding: 8px 16px;
    border-radius: 20px;
    background: #f2f2f2;
    color: #555;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}

.tab-btn.active {
    background: #1D4C43;
    color: #fff;
}

/* Toggle */
.report-toggle-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.switch-wrapper {
    position: relative;
    width: 44px;
    height: 22px;
}

.switch-wrapper input {
    opacity: 0;
}

.slider {
    position: absolute;
    inset: 0;
    background: #ccc;
    border-radius: 22px;
}

.slider:before {
    content: "";
    position: absolute;
    width: 18px;
    height: 18px;
    left: 2px;
    bottom: 2px;
    background: white;
    border-radius: 50%;
    transition: .3s;
}

input:checked + .slider {
    background: #1D4C43;
}

input:checked + .slider:before {
    transform: translateX(22px);
}

/* ===== TABLE ===== */
.admin-data-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-data-table th,
.admin-data-table td {
    padding: 12px;
    text-align: left;
}

.badge-post {
    background: #e0f2f1;
    color: #00695c;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.badge-comment {
    background: #fff3e0;
    color: #ef6c00;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.btn-review {
    display: inline-block;
    padding: 8px 12px;
    background: #71B48D;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.85rem;
}

/* ===== MOBILE CARD VIEW ===== */
@media (max-width: 768px) {

    .report-controls-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .report-tabs {
        flex-wrap: wrap;
    }

    .tab-btn {
        flex: 1;
        text-align: center;
    }

    .admin-data-table thead {
        display: none;
    }

    .admin-data-table,
    .admin-data-table tbody,
    .admin-data-table tr,
    .admin-data-table td {
        display: block;
        width: 100%;
    }

    .admin-data-table tr {
        margin-bottom: 16px;
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 12px;
        background: #fff;
    }

    .admin-data-table td {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .admin-data-table td:last-child {
        border-bottom: none;
    }

    .admin-data-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.75rem;
        color: #555;
    }

    .btn-review {
        width: 100%;
        text-align: center;
        margin-top: 6px;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
