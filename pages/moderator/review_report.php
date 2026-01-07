<?php
// pages/moderator/review_report.php
require_once '../../includes/header.php';

// 1. Authorization
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$type = $_GET['type'] ?? '';
$report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$report = null;
$message = '';

// 2. Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $table = ($type === 'post') ? 'Post_report' : 'Comment_report';
    $id_col = ($type === 'post') ? 'Post_report_id' : 'Comment_report_id';
    
    if ($conn) {
        $stmt = $conn->prepare("UPDATE $table SET Status = ? WHERE $id_col = ?");
        $stmt->bind_param("si", $new_status, $report_id);
        if ($stmt->execute()) {
            // Refresh the page data after update
            $report['Status'] = $new_status; 
            $message = "Status updated to " . htmlspecialchars($new_status);
        }
    }
}

// 3. Fetch Report Details
if ($conn && $report_id) {
    if ($type === 'post') {
        $sql = "
            SELECT r.*, u.Username as reporter_name, 
            p.Title as content_title, p.Post_id as content_link_id
            FROM Post_report r
            JOIN User u ON r.Reported_by = u.User_id
            LEFT JOIN Post p ON r.Post_id = p.Post_id
            WHERE r.Post_report_id = ?
        ";
    } elseif ($type === 'comment') {
        $sql = "
            SELECT r.*, u.Username as reporter_name, 
            'Comment' as content_title, c.Post_id as content_link_id
            FROM Comment_report r
            JOIN User u ON r.Reported_by = u.User_id
            LEFT JOIN Comment c ON r.Comment_id = c.Comment_id
            WHERE r.Comment_report_id = ?
        ";
    }

    if (isset($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $report = $stmt->get_result()->fetch_assoc();
    }
}

if (!$report) {
    echo "<div class='container' style='padding:40px;'><p>Report not found.</p></div>";
    include '../../includes/footer.php';
    exit;
}
?>

<main class="page-content admin-page">
    <div class="container" style="max-width: 800px;">
        
        <h1 class="page-title">Review Report #<?php echo $report_id; ?></h1>
        <p class="subtitle">Check and evaluate the report submitted by the student!</p>
        <hr style="border: 0; border-top: 2px solid #1D4C43; margin-bottom: 30px;">

        <?php if ($message): ?>
            <div class="message success-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="review-card">
            <div class="review-row">
                <span class="label">Reported By:</span>
                <span class="value-box"><?php echo htmlspecialchars($report['reporter_name']); ?></span>
            </div>
            
            <div class="review-row">
                <span class="label">Case Type:</span>
                <span class="value"><?php echo ucfirst($type); ?></span>
            </div>

            <div class="review-row">
                <span class="label">Reason:</span>
                <span class="value"><?php echo htmlspecialchars($report['Reason']); ?></span>
            </div>

            <div class="review-row">
                <span class="label">Current Status:</span>
                <span class="status-badge status-<?php echo strtolower($report['Status']); ?>">
                    <?php echo htmlspecialchars($report['Status']); ?>
                </span>
            </div>

            <div class="review-row">
                <span class="label">Reported On:</span>
                <span class="value"><?php echo date('d M Y, h:i A', strtotime($report['Report_time'])); ?></span>
            </div>

            <div class="review-row">
                <span class="label">Case Link:</span>
                <a href="../../pages/post_detail.php?id=<?php echo $report['content_link_id']; ?>" target="_blank" class="case-link-btn">
                    Click Here To Case Page
                </a>
            </div>

            <div class="review-actions-footer">
                <a href="manage_reports.php" class="btn-back">Back</a>
                
                <div class="action-buttons">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="status" value="Pending">
                        <button type="submit" class="btn-status btn-pending <?php echo ($report['Status'] == 'Pending') ? 'active' : ''; ?>">Pending</button>
                    </form>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="status" value="Completed">
                        <button type="submit" class="btn-status btn-complete <?php echo ($report['Status'] == 'Completed') ? 'active' : ''; ?>">Complete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    .review-card {
        background: #fff;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #eee;
    }
    .review-row { margin-bottom: 20px; display: flex; align-items: center; }
    .label { font-weight: 700; width: 150px; color: #333; }
    .value-box { background: #eef; padding: 5px 10px; border-radius: 5px; font-weight: 600; font-size: 0.9rem; }
    .case-link-btn { background: #e0e0e0; color: #000; text-decoration: none; padding: 5px 10px; border-radius: 5px; font-weight: 700; font-size: 0.9rem; border: 1px solid #ccc; }
    .case-link-btn:hover { background: #d0d0d0; }

    /* --- FOOTER BUTTON STYLES (UPDATED) --- */
    .review-actions-footer {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f0f0f0;
        padding-top: 20px;
    }

    /* 1. Back Button: Light Blue */
    .btn-back {
        padding: 10px 25px;
        background-color: #EBF8FF; /* Light Blue */
        color: #2C5282;           /* Dark Blue Text */
        text-decoration: none;
        border-radius: 8px;
        font-weight: 700;
        border: 1px solid #BEE3F8;
        transition: all 0.3s ease;
    }
    .btn-back:hover {
        background-color: #BEE3F8;
    }

    /* Shared Action Button Styles */
    .btn-status {
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        margin-left: 10px;
        transition: all 0.3s ease;
    }

    /* 2. Pending Button: Yellow/Brown (Like Status Badge) */
    .btn-pending {
        background-color: #FEF3C7; /* Yellow background */
        color: #92400E;           /* Brown text */
        border: 1px solid #FCD34D;
    }
    .btn-pending:hover, .btn-pending.active {
        background-color: #FDE68A;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }

    /* 3. Complete Button: Green/White (Like Save Changes) */
    .btn-complete {
        background-color: #1D4C43; /* EcoQuest Green */
        color: #FFFFFF;
        border: 1px solid #1D4C43;
    }
    .btn-complete:hover, .btn-complete.active {
        background-color: #14352f;
        box-shadow: 0 2px 8px rgba(29, 76, 67, 0.3);
    }

    /* Active State for Context */
    .btn-status.active {
        transform: scale(0.98);
        opacity: 0.9;
        box-shadow: inset 0 3px 5px rgba(0,0,0,0.2);
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .page-content {
            padding: 20px 10px;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .report-summary {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .summary-card {
            padding: 15px;
        }

        .report-details {
            padding: 15px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .status-dropdown select {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
        }

        .action-buttons {
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-complete,
        .btn-back {
            width: 100%;
            padding: 12px;
            text-align: center;
        }

        .btn-status {
            width: 100%;
            display: block;
            margin-bottom: 8px;
        }
    }

    @media (max-width: 600px) {
        .page-title {
            font-size: 1.3rem;
        }

        .summary-card h3 {
            font-size: 1rem;
        }

        .detail-item {
            font-size: 0.9rem;
        }

        .status-dropdown select {
            font-size: 0.9rem;
        }

        .btn-complete,
        .btn-back {
            font-size: 0.9rem;
            padding: 10px;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>