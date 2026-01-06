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

        $query = "";
        if ($type_filter === 'post') $query = $sql_post;
        elseif ($type_filter === 'comment') $query = $sql_comment;
        else $query = "($sql_post) UNION ALL ($sql_comment)";

        if (!$show_completed) {
            $query = "SELECT * FROM ($query) as combined_table WHERE Status != 'Completed'";
        }

        $query .= " ORDER BY Report_time DESC";
        $result = $conn->query($query);
        if ($result) $reports = $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        $error_message = "Error loading reports: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-page">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title">Manage Reports</h1>
            <p class="subtitle">Review and process student community reports</p>
        </header>

        <div class="report-controls-bar">
            <div class="report-tabs">
                <a href="?type=post&show_completed=<?php echo $show_completed; ?>" class="tab-btn <?php echo $type_filter === 'post' ? 'active' : ''; ?>">Post</a>
                <a href="?type=comment&show_completed=<?php echo $show_completed; ?>" class="tab-btn <?php echo $type_filter === 'comment' ? 'active' : ''; ?>">Comment</a>
                <a href="?type=all&show_completed=<?php echo $show_completed; ?>" class="tab-btn <?php echo $type_filter === 'all' ? 'active' : ''; ?>">View All</a>
            </div>

            <div class="report-toggle-container">
                <span class="toggle-text">Show Completed</span>
                <label class="switch-wrapper">
                    <input type="checkbox" onchange="window.location.href='?type=<?php echo $type_filter; ?>&show_completed=' + (this.checked ? '1' : '0')" <?php echo $show_completed ? 'checked' : ''; ?>>
                    <span class="slider round"></span>
                </label>
            </div>
        </div>

        <section class="admin-data-section">
            <h3 class="section-title"><?php echo ucfirst($type_filter); ?> Reports</h3>
            
            <?php if (empty($reports)): ?>
                <div class="empty-state"><p>No reports found matching your criteria.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>Datetime</th>
                                <th>Student</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><?php echo date('d M Y, h:i A', strtotime($r['Report_time'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($r['reporter_name']); ?></strong></td>
                                    <td><span class="badge badge-<?php echo strtolower($r['type']); ?>"><?php echo $r['type']; ?></span></td>
                                    <td><?php echo htmlspecialchars($r['Reason']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($r['Status']); ?>">
                                            <?php echo htmlspecialchars($r['Status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="review_report.php?type=<?php echo strtolower($r['type']); ?>&id=<?php echo $r['id']; ?>" class="btn-review">Review Now</a>
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
    /* Main Control Bar */
    .report-controls-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        /* Ensure the bar itself has enough height to center items */
        min-height: 60px; 
    }

    /* Tabs */
    .report-tabs { display: flex; gap: 10px; align-items: center; }
    .tab-btn {
        padding: 8px 20px;
        border-radius: 20px;
        text-decoration: none;
        color: #555;
        font-weight: 600;
        background: #f4f4f4;
        transition: 0.3s;
        display: inline-block;
        line-height: normal; /* Fix vertical alignment */
    }
    .tab-btn.active, .tab-btn:hover { background: #1D4C43; color: #fff; }
    
    /* === FORCE FIX FOR TOGGLE ALIGNMENT === */
    .report-toggle-container { 
        display: flex; 
        flex-direction: row;
        align-items: center; /* Critical for vertical centering */
        justify-content: flex-end;
        gap: 15px; 
        height: 40px; /* Fixed height to force alignment */
    }
    
    .toggle-text {
        font-weight: 600;
        color: #555;
        font-size: 0.95rem;
        margin: 0; 
        padding: 0;
        display: inline-block;
        line-height: 40px; /* Match container height to force center */
    }

    /* Wrapper for the switch to kill external margins */
    .switch-wrapper { 
        position: relative; 
        display: inline-block; 
        width: 46px; 
        height: 24px; 
        margin: 0 !important; /* Force remove margins */
        padding: 0 !important;
        vertical-align: middle;
    }
    
    .switch-wrapper input { opacity: 0; width: 0; height: 0; }
    
    .slider { 
        position: absolute; 
        cursor: pointer; 
        top: 0; left: 0; right: 0; bottom: 0; 
        background-color: #ccc; 
        transition: .4s; 
        border-radius: 34px;
    }
    
    .slider:before { 
        position: absolute; 
        content: ""; 
        height: 18px; width: 18px; 
        left: 3px; bottom: 3px; 
        background-color: white; 
        transition: .4s; 
        border-radius: 50%;
    }
    
    input:checked + .slider { background-color: #1D4C43; }
    input:checked + .slider:before { transform: translateX(22px); }

    /* Table Badges & Buttons */
    .badge-post { background: #e0f2f1; color: #00695c; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; }
    .badge-comment { background: #fff3e0; color: #ef6c00; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; }
    .btn-review { background: #71B48D; color: white; padding: 6px 12px; text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; }
    .btn-review:hover { background: #1D4C43; }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .page-content {
            padding: 20px 10px;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .filter-bar {
            flex-direction: column;
            gap: 8px;
        }

        .filter-bar .btn,
        .filter-bar input {
            width: 100%;
        }

        .data-table thead {
            display: none;
        }

        .data-table tbody,
        .data-table tr,
        .data-table td {
            display: block;
            width: 100%;
        }

        .data-table tr {
            margin-bottom: 20px;
            border: 1px solid #DCDCDC;
            border-radius: 12px;
            padding: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .data-table td {
            padding: 10px 0 10px 0;
            text-align: left;
            position: relative;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .data-table td:last-child {
            border-bottom: none;
            margin-top: 8px;
        }

        .data-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #4A5568;
            font-size: 0.8rem;
            min-width: 80px;
            text-transform: uppercase;
        }

        .data-table td:last-child::before {
            display: none;
        }

        .btn-review {
            width: 100%;
            display: block;
            text-align: center;
            padding: 10px 8px;
        }
    }

    @media (max-width: 600px) {
        .page-title {
            font-size: 1.3rem;
        }

        .data-table tr {
            padding: 10px;
        }

        .data-table td {
            flex-direction: column;
            align-items: flex-start;
            padding: 8px 0;
        }

        .data-table td::before {
            display: block;
            margin-bottom: 4px;
        }

        .badge-post,
        .badge-comment {
            font-size: 0.7rem;
            padding: 2px 6px;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>