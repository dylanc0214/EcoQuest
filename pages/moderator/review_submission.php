<?php
// pages/moderator/review_submission.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION & INITIALIZATION
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

// Allow both admin and moderators to access this page
$allowed_roles = ['admin', 'moderator'];
if (!$is_logged_in || !in_array($user_role, $allowed_roles)) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// Get the correct reviewer ID (Admin_id or Moderator_id)
$reviewer_id = $_SESSION['moderator_id'] ?? null; 
if ($user_role === 'admin' && $reviewer_id === null) {
    $reviewer_id = null;
}

$submission_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = null;
$message_type = null;

if (!$submission_id) {
    header('Location: manage_submissions.php?error=no_id_provided');
    exit();
}

// =======================================================
// 2. FORM SUBMISSION (APPROVAL/REJECTION)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    $review_comment = trim($_POST['review_comment'] ?? '');

    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'completed' : 'rejected';

        $conn->begin_transaction();
        try {
            $sql_get_info = "
                SELECT s.Student_id, q.Points_award, q.Quest_id, ach.Exp_point, ach.Achievement_id
                FROM Student_Quest_Submissions s
                JOIN Quest q ON s.Quest_id = q.Quest_id
                LEFT JOIN Achievement ach ON q.Quest_id = ach.Achievement_id
                WHERE s.Student_quest_submission_id = ? AND s.Status = 'pending'";
            $stmt_info = $conn->prepare($sql_get_info);
            $stmt_info->bind_param('i', $submission_id);
            $stmt_info->execute();
            $submission_info = $stmt_info->get_result()->fetch_assoc();
            $stmt_info->close();

            if (!$submission_info) {
                throw new Exception("Submission not found or has already been reviewed.");
            }

            $student_id_to_update = $submission_info['Student_id'];
            $quest_id = $submission_info['Quest_id'];
            $points_to_award = $submission_info['Points_award'];
            $exp_to_award = $submission_info['Exp_point'] ?? 0;
            $achievement_id = $submission_info['Achievement_id'] ?? null;

            $sql_update = "
                UPDATE Student_Quest_Submissions
                SET Status = ?, Review_date = NOW(), Moderator_id = ?, Review_feedback = ?
                WHERE Student_quest_submission_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('sisi', $new_status, $reviewer_id, $review_comment, $submission_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            $sql_progress = "UPDATE Quest_Progress SET Status = ? WHERE Student_id = ? AND Quest_id = ?";
            $stmt_progress = $conn->prepare($sql_progress);
            $stmt_progress->bind_param('sii', $new_status, $student_id_to_update, $quest_id);
            $stmt_progress->execute();
            $stmt_progress->close();

            if ($action === 'approve') {
                $sql_award_points = "UPDATE Student SET Total_point = Total_point + ?, Total_Exp_Point = Total_Exp_Point + ? WHERE Student_id = ?";
                $stmt_points = $conn->prepare($sql_award_points);
                $stmt_points->bind_param('iii', $points_to_award, $exp_to_award, $student_id_to_update);
                $stmt_points->execute();
                $stmt_points->close();
                
                if ($achievement_id) {
                    $sql_log_ach = "INSERT INTO Student_Achievement (Achievement_id, Student_id, Status) VALUES (?, ?, 'Completed')
                                    ON DUPLICATE KEY UPDATE Status = 'Completed'";
                    $stmt_log_ach = $conn->prepare($sql_log_ach);
                    $stmt_log_ach->bind_param("ii", $achievement_id, $student_id_to_update);
                    $stmt_log_ach->execute();
                    $stmt_log_ach->close();
                }
            }

            $conn->commit();
            $message_type = 'success';
            $message = "Submission #{$submission_id} has been {$new_status}!";
            if ($action === 'approve') {
                $message .= " {$points_to_award} points and {$exp_to_award} EXP awarded.";
            }

        } catch (Exception $e) {
            $conn->rollback();
            $message_type = 'error';
            $message = "Failed to process submission. Error: " . $e->getMessage();
        }
    }
}

// =======================================================
// 3. FETCH SUBMISSION DETAILS FOR DISPLAY
// =======================================================
$submission = null;
$error_fetching = null;

if ($conn) {
    $query = "
        SELECT
            s.Student_quest_submission_id AS id, s.Status, s.Image, s.Submission_date, s.Review_feedback,
            u.Username,
            q.Title AS quest_title, q.Points_award, q.Instructions, q.Proof_type
        FROM Student_Quest_Submissions s
        JOIN Student st ON s.Student_id = st.Student_id
        JOIN User u ON st.User_id = u.User_id
        JOIN Quest q ON s.Quest_id = q.Quest_id
        WHERE s.Student_quest_submission_id = ?
    ";
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $submission_id);
        $stmt->execute();
        $submission = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$submission) {
             $error_fetching = "Submission with ID #{$submission_id} not found.";
        }
    } catch (Exception $e) {
        $error_fetching = "Database error fetching details: " . $e->getMessage();
    }
} else {
    $error_fetching = "No database connection.";
}
?>

<main class="page-content admin-review">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-search"></i> Review Submission #<?php echo htmlspecialchars($submission_id); ?></h1>
            <p class="subtitle">Evaluate the proof and update the quest status.</p>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_fetching): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_fetching); ?>
            </div>
        <?php elseif ($submission): ?>
            <section class="review-details-grid">
                <div class="card detail-card submission-meta">
                    <h3 class="card-title">Submission Info</h3>
                    <p><strong>Student:</strong> <span class="badge badge-user"><?php echo htmlspecialchars($submission['Username']); ?></span></p>
                    <p><strong>Quest:</strong> <?php echo htmlspecialchars($submission['quest_title']); ?></p>
                    <p><strong>Points Value:</strong> <span class="badge badge-points"><?php echo htmlspecialchars($submission['Points_award']); ?> Pts</span></p>
                    <p><strong>Current Status:</strong> <span class="status-badge status-<?php echo strtolower($submission['Status']); ?>"><?php echo ($submission['Status'] === 'completed') ? 'Approved' : ucfirst($submission['Status']); ?></span></p>
                    <p><strong>Submitted On:</strong> <?php echo $submission['Submission_date'] ? date('d M Y, h:i A', strtotime($submission['Submission_date'])) : 'N/A'; ?></p>
                </div>
                <div class="card detail-card proof-section">
                    <h3 class="card-title">Proof & Instructions</h3>
                    <div class="quest-instructions">
                        <h4>Quest Instructions:</h4>
                        <p><?php echo nl2br(htmlspecialchars($submission['Instructions'])); ?></p>
                    </div>
                    <div class="user-proof">
                        <h4>Submitted Proof:</h4>
                        <?php if (!empty($submission['Image'])): 
                            // Fix path logic: Move up two levels to reach uploads folder
                            $clean_db_path = ltrim($submission['Image'], '/');
                            $media_path = '../../' . $clean_db_path;
                        ?>
                            <div class="proof-box" style="margin-top: 15px; text-align: center;">
                                <p class="proof-text-label"><i class="fas fa-image"></i> Media Preview:</p>
                                <img src="<?php echo htmlspecialchars($media_path); ?>" 
                                     alt="Submitted Proof" 
                                     class="proof-image"
                                     style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #ddd;"
                                     onerror="this.onerror=null; this.src='https://placehold.co/600x400?text=File+Not+Found';">
                            </div>
                         <?php else: ?>
                            <div class="proof-box"><p>No media (image/video) was submitted.</p></div>
                        <?php endif; ?>
                        
                         <?php if (!empty($submission['Review_feedback']) && $submission['Status'] !== 'pending'): ?>
                            <div class="proof-box" style="margin-top: 15px; border-color: var(--color-accent);">
                                <p class="proof-text-label"><i class="fas fa-comment-dots"></i> Previous Review Comment:</p>
                                <p class="proof-text"><?php echo nl2br(htmlspecialchars($submission['Review_feedback'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card detail-card review-actions">
                    <h3 class="card-title">Take Action</h3>
                    <?php if ($submission['Status'] === 'pending'): ?>
                        <form method="POST" action="review_submission.php?id=<?php echo $submission_id; ?>">
                            <div class="form-group">
                                <label for="review_comment">Review Comment (Required for rejection)</label>
                                <textarea id="review_comment" name="review_comment" rows="3"></textarea>
                            </div>
                            <div class="action-buttons">
                                <button type="submit" name="action" value="reject" class="btn btn-error btn-lg">Reject</button>
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">Approve</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="already-reviewed"><h4>Review Complete</h4></div>
                    <?php endif; ?>
                    <div style="text-align:center; margin-top: 20px;">
                        <a href="manage_submissions.php" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<style>
    /* Desktop Styles (Default) */
    .proof-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        display: block;
        margin: 0 auto;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .page-content { padding: 20px 10px; }
        .page-title { font-size: 1.5rem; }
        .action-buttons { flex-direction: column; gap: 10px; margin-top: 20px; }
        .btn-lg { width: 100%; padding: 12px; font-size: 1rem; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>