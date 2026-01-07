<?php
// pages/admin/view_feedback.php
require_once '../../includes/header.php';

// 1. Authorization: Only Admins
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$error_message = null;
$success_message = filter_input(INPUT_GET, 'success', FILTER_SANITIZE_SPECIAL_CHARS);
$feedbacks = [];

// --- TOGGLE SORTING LOGIC ---
// Get current sort from URL, default to 'latest'
$current_sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'latest';

// Determine the SQL order based on current sort
$order_by = ($current_sort === 'oldest') ? 'ASC' : 'DESC';

// Determine what the NEXT sort will be when the user clicks the header
$next_sort = ($current_sort === 'latest') ? 'oldest' : 'latest';

// 2. Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
    if ($delete_id && $conn) {
        try {
            $stmt_del = $conn->prepare("DELETE FROM student_feedback WHERE Student_feedback_id = ?");
            $stmt_del->bind_param("i", $delete_id);
            if ($stmt_del->execute()) {
                $success_message = "Feedback deleted successfully.";
            } else {
                $error_message = "Failed to delete feedback.";
            }
            $stmt_del->close();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// 3. Fetch All Feedback - Corrected column names based on screenshots
if ($conn) {
    try {
        // SQL using $order_by variable determined above
        $sql = "
            SELECT 
                f.Student_feedback_id,
                f.Title,
                f.Description,
                f.Date_time,
                u.Username,
                u.Email,
                u.User_id,
                s.Total_point,
                s.Total_Exp_Point, 
                s.Student_id,
                (SELECT GROUP_CONCAT(b.Badge_image) FROM student_badge sb 
                 JOIN Badge b ON sb.Badge_id = b.Badge_id 
                 WHERE sb.Student_id = s.Student_id) as badges
            FROM student_feedback f
            JOIN Student s ON f.Student_id = s.Student_id
            JOIN User u ON s.User_id = u.User_id
            ORDER BY f.Date_time $order_by
        ";
        $result = $conn->query($sql);
        if ($result) {
            $feedbacks = $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        $error_message = "Failed to load feedback: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-page">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-comment-dots"></i> Student Feedback</h1>
            <p class="subtitle">Review suggestions, complaints, and ideas submitted by students.</p>
        </header>

        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <section class="admin-data-section">
            <?php if (empty($feedbacks)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox large-icon"></i>
                    <h3>No Feedback Yet</h3>
                    <p>Your students haven't submitted anything yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">
                                    <a href="?sort=<?php echo $next_sort; ?>" style="color: inherit; text-decoration: none; cursor: pointer; display: block; width: 100%;">
                                        Date <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th style="width: 20%;">Student</th>
                                <th style="width: 25%;">Title</th>
                                <th style="width: 35%;">Description</th>
                                <th style="width: 5%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbacks as $fb): ?>
                                <tr>
                                    <td data-label="Date"><?php echo date('d/m/Y', strtotime($fb['Date_time'])); ?></td>
                                    <td data-label="Student">
                                        <i class="fas fa-user-circle user-icon"></i>
                                        <a href="javascript:void(0)" onclick='openStudentModal(<?php echo json_encode($fb); ?>)'>
                                            <strong><?php echo htmlspecialchars($fb['Username']); ?></strong>
                                        </a>
                                    </td>
                                    <td data-label="Title" style="font-weight: 600; color: var(--color-primary);">
                                        <?php echo htmlspecialchars($fb['Title']); ?>
                                    </td>
                                    <td data-label="Description">
                                        <div style="max-height: 120px; overflow-y: auto;">
                                            <?php echo htmlspecialchars($fb['Description']); ?>
                                        </div>
                                    </td>
                                    <td data-label="Action">
                                        <form method="POST" onsubmit="return confirm('Delete this feedback?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $fb['Student_feedback_id']; ?>">
                                            <button type="submit" class="btn-action-icon btn-action-delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
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

<div id="studentModal" class="modal-overlay">
    <div class="modal-panel">
        <span class="close-modal" onclick="closeStudentModal()">&times;</span>
        <div class="modal-header-row">
            <span class="modal-user-info">Username: <span id="modal-username">XXX</span></span>
            <span class="modal-role-badge">Student</span>
        </div>
        <div class="modal-email-row">Email: <span id="modal-email">XXX@gmail.com</span></div>
        <div class="stats-container">
            <div class="stat-box">
                <p class="stat-label">Total Point</p>
                <h2 class="stat-value" id="modal-points">0</h2>
                <p class="stat-sub">Points</p>
            </div>
            <div class="stat-box">
                <p class="stat-label">Total Exp</p>
                <h2 class="stat-value" id="modal-exp">0</h2>
                <p class="stat-sub">Exp</p>
            </div>
        </div>
        <div class="badge-section">
            <h3 class="badge-title">Badge</h3>
            <div class="badge-grid" id="modal-badges">
                <div class="empty-badge-box"></div>
                <div class="empty-badge-box"></div>
                <div class="empty-badge-box"></div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Sort Dropdown removed in favor of direct header click toggle as requested */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 2000; align-items: center; justify-content: center; }
    .modal-panel { background: white; width: 90%; max-width: 500px; border-radius: 15px; padding: 30px; position: relative; border: 2px solid #3498db; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .close-modal { position: absolute; top: 5px; right: 15px; font-size: 28px; cursor: pointer; color: #888; }
    .modal-header-row { display: flex; justify-content: space-between; align-items: center; font-size: 1.2rem; font-weight: bold; margin-bottom: 5px; }
    .modal-role-badge { background: #e0e0e0; padding: 5px 20px; border-radius: 20px; font-size: 0.9rem; color: #333; }
    .modal-email-row { color: #555; margin-bottom: 25px; }
    .stats-container { display: flex; gap: 20px; margin-bottom: 25px; justify-content: center; }
    .stat-box { border: 1px solid #333; border-radius: 15px; padding: 15px; width: 140px; text-align: center; }
    .stat-label { font-size: 0.9rem; font-weight: bold; margin-bottom: 10px; }
    .stat-value { font-size: 2rem; margin: 5px 0; }
    .stat-sub { font-size: 0.8rem; color: #666; }
    .badge-title { font-size: 1.1rem; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 15px;}
    .badge-grid { display: flex; gap: 40px; justify-content: center; overflow-x: auto; }
    .empty-badge-box { width: 80px; height: 80px; background: #e0e0e0; border-radius: 10px; }
    .badge-img { width: 80px; height: 80px; object-fit: contain; }
</style>

<script>
    function openStudentModal(data) {
        document.getElementById('modal-username').innerText = data.Username;
        document.getElementById('modal-email').innerText = data.Email;
        document.getElementById('modal-points').innerText = data.Total_point || 0;
        document.getElementById('modal-exp').innerText = data.Total_Exp_Point || 0;

        const badgeContainer = document.getElementById('modal-badges');
        badgeContainer.innerHTML = ''; 
        if (data.badges) {
            const badgeList = data.badges.split(',');
            badgeList.forEach(img => {
                badgeContainer.innerHTML += `<img src="../../${img}" class="badge-img" onerror="this.src='../../assets/images/default-badge.png'">`;
            });
        } else {
            for(let i=0; i<3; i++) {
                badgeContainer.innerHTML += `<div class="empty-badge-box"></div>`;
            }
        }
        document.getElementById('studentModal').style.display = 'flex';
    }

    function closeStudentModal() {
        document.getElementById('studentModal').style.display = 'none';
    }

    window.onclick = function(event) {
        let modal = document.getElementById('studentModal');
        if (event.target == modal) { closeStudentModal(); }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>

<style>
    /* ... existing styles ... */

    @media screen and (max-width: 768px) {
        /* Hide traditional table headers */
        .admin-data-table thead {
            display: none;
        }

        /* Force table elements to stack */
        .admin-data-table,
        .admin-data-table tbody,
        .admin-data-table tr,
        .admin-data-table td {
            display: block;
            width: 100%;
        }

        .admin-data-table tr {
            margin-bottom: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .admin-data-table td {
            text-align: left;
            padding: 8px 10px;
            border: none;
            position: relative;
        }

        /* Add labels using data-attributes or manual labels */
        .admin-data-table td::before {
            content: attr(data-label);
            font-weight: bold;
            display: block;
            color: #888;
            font-size: 0.75rem;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        /* Adjust the delete button for mobile */
        .admin-data-table td:last-child {
            border-top: 1px solid #eee;
            margin-top: 10px;
            text-align: right;
        }
    }
</style>