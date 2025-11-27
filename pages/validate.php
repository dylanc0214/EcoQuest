<?php
// pages/validate.php
include("../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$db_error = '';
$message = []; // Initialized as empty array
$active_quests = [];
$submission_history = [];
$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Error: Database connection failed. Cannot proceed.';
}

// =========================================================================
// 1. POST Request Handler (Submission Logic - FIXED)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_db_connected) {
    $quest_id = filter_input(INPUT_POST, 'quest_id', FILTER_VALIDATE_INT);
    // $proof_text = trim($_POST['proof_text'] ?? ''); // <-- REMOVED
    $file_destination = null;
    $has_error = false;

    // Basic validation
    if (!$quest_id) {
        $message = ['type' => 'error', 'text' => 'Aiyo! Please select a quest.'];
        $has_error = true;
    }
    
    // --- File Upload Logic ---
    if (!$has_error && isset($_FILES['proof_media']) && $_FILES['proof_media']['error'] == 0) {
        $target_dir = "../uploads/activities/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = uniqid('proof_', true) . '_' . basename($_FILES["proof_media"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allow common image and video types
        if (!in_array($file_type, ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'wmv'])) {
            $message = ['type' => 'error', 'text' => 'Only JPG, PNG, GIF, MP4, MOV, AVI files are allowed.'];
            $has_error = true;
        } elseif ($_FILES["proof_media"]["size"] > 20 * 1024 * 1024) { // Increased to 20MB for video
            $message = ['type' => 'error', 'text' => 'File is too large (max 20MB).'];
            $has_error = true;
        }

        if (!$has_error && move_uploaded_file($_FILES["proof_media"]["tmp_name"], $target_file)) {
            $file_destination = 'uploads/activities/' . $file_name; // This goes into the 'Image' column
        } elseif (!$has_error) {
            $message = ['type' => 'error', 'text' => 'Aiyo! Failed to upload file.'];
            $has_error = true;
        }
    } else if (!$has_error) {
         $message = ['type' => 'error', 'text' => 'Proof media (image/video) is required.'];
         $has_error = true;
    }

    // --- Database Insert (FIXED: No more proof_text/Review_feedback) ---
    if (!$has_error) {
        try {
            $sql_insert = "
                INSERT INTO Student_Quest_Submissions 
                    (Student_id, Quest_id, Image, Submission_date, Status)
                VALUES (?, ?, ?, NOW(), 'pending')
            ";
            
            if ($stmt = $conn->prepare($sql_insert)) {
                // Bind params (i: integer, s: string)
                $stmt->bind_param("iis", $student_id, $quest_id, $file_destination);

                if ($stmt->execute()) {
                    // Update Quest_Progress to 'pending' as well
                    $conn->query("UPDATE Quest_Progress SET Status = 'pending' WHERE Student_id = $student_id AND Quest_id = $quest_id");
                    $message = ['type' => 'success', 'text' => 'Proof submitted successfully! Your submission is now pending review.'];
                } else {
                     if ($conn->errno == 1062) { // Duplicate key
                        throw new Exception("You have already submitted proof for this quest. It is pending review.");
                    }
                    throw new Exception("Database update failed: " . $stmt->error);
                }
                $stmt->close();
            } else {
                throw new Exception("Database query preparation failed: " . $conn->error);
            }
        } catch (Exception $e) {
            $message = ['type' => 'error', 'text' => 'A critical error occurred: ' . $e->getMessage()];
        }
    }
}

// =========================================================================
// 2. Fetch Active Quests (for Form Dropdown - UNCHANGED)
// =========================================================================
if ($is_db_connected) {
    $sql_fetch_active = "
        SELECT
            q.Quest_id,
            q.Title
        FROM Quest q
        JOIN Quest_Progress p ON q.Quest_id = p.Quest_id
        WHERE
            p.Student_id = ? AND p.Status = 'active'
        ORDER BY q.Title ASC";

    if ($stmt_fetch = $conn->prepare($sql_fetch_active)) {
        $stmt_fetch->bind_param("i", $student_id);
        if ($stmt_fetch->execute()) {
            $result = $stmt_fetch->get_result();
            while ($quest = $result->fetch_assoc()) {
                $active_quests[] = $quest;
            }
        } else {
            $db_error .= ' Could not load active quests: ' . $stmt_fetch->error;
        }
        $stmt_fetch->close();
    } else {
        $db_error .= ' DB query preparation failed for active quests.';
    }
}

// =========================================================================
// 3. Fetch Submission History (for display - FIXED)
// =========================================================================
if ($is_db_connected) {
    // Note: We removed s.Review_feedback AS student_notes, as it's no longer used for that
    $sql_history = "
        SELECT
            s.Student_quest_submission_id,
            s.Status,
            s.Submission_date,
            s.Review_date,
            s.Review_feedback,
            q.Title AS quest_title,
            q.Points_award
        FROM Student_Quest_Submissions s
        JOIN Quest q ON s.Quest_id = q.Quest_id
        WHERE
            s.Student_id = ?
        ORDER BY s.Submission_date DESC";

    if ($stmt_history = $conn->prepare($sql_history)) {
        $stmt_history->bind_param("i", $student_id);
        if ($stmt_history->execute()) {
            $result_history = $stmt_history->get_result();
            while ($item = $result_history->fetch_assoc()) {
                $item['display_status'] = ucfirst($item['Status']);
                if (in_array($item['display_status'], ['Completed', 'Approved'])) {
                    $item['display_status'] = 'Approved';
                }
                $submission_history[] = $item;
            }
        } else {
            $db_error .= ' Could not load submission history: ' . $stmt_history->error;
        }
        $stmt_history->close();
    }
}

// Helper function for status styling
function get_status_class($status) {
    return 'status-' . strtolower($status);
}
?>

<main class="validate-page">
    <div class="container">
        <h1 class="page-title">Submit Quest Proof 📸</h1>
        <p class="page-subtitle">Upload your photo/video to complete your mission.</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        
        <?php 
        // Only show the form card IF the submission was NOT a success
        if (!isset($message['type']) || $message['type'] !== 'success'): 
        ?>
        <div class="auth-card submission-form-card" style="max-width: 700px; margin: 20px auto;">
            
            <?php if (empty($active_quests)): ?>
                <div class="message info-message">
                    Aiyo, you have no quests "In Progress"! <br>
                    Go to the <a href="quests.php">Quests Page</a> to start one first.
                </div>
            <?php else: ?>
                <form action="validate.php" method="POST" enctype="multipart/form-data" class="auth-form">
                    <h3><i class="fas fa-tasks"></i> Select Quest to Complete</h3>
                    <div class="form-group">
                        <label for="quest_id">Quest In Progress</label>
                        <select id="quest_id" name="quest_id" required>
                            <option value="">-- Choose one of your active quests --</option>
                            <?php foreach ($active_quests as $quest): ?>
                                <option value="<?php echo $quest['Quest_id']; ?>">
                                    <?php echo htmlspecialchars($quest['Title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h3><i class="fas fa-camera"></i> Upload Proof (Photo/Video)</h3>
                    <div class="form-group">
                        <label for="proof_media">File (Max 20MB: JPG, PNG, MP4, MOV)</label>
                        <input type="file" id="proof_media" name="proof_media" accept="image/*,video/*" required>
                    </div>

                    <div class="form-actions" style="text-align:center; margin-top: 20px;">
                        <button type="submit" class="btn-primary" style="padding: 12px 30px; font-size: 1.1rem;">Submit Proof!</button>
                    </div>
                </form>
            <?php endif; ?>
        
        </div>
        <?php endif; // End of the "is not success" check ?>


        <section class="submission-history" style="max-width: 800px; margin: 50px auto;">
            <h2 style="text-align:center; font-size: 1.8rem; color: var(--color-primary);"><i class="fas fa-history"></i> My Submission History</h2>
            <p style="text-align:center; margin-bottom: 30px;">Keep track of your submitted proofs and their review status.</p>

            <?php if (empty($submission_history)): ?>
                <div class="message info-message">
                    No past submissions found. Go complete a quest!
                </div>
            <?php else: ?>
                <div class="history-list-container" style="display: grid; gap: 20px;">
                    <?php foreach ($submission_history as $submission): ?>
                        <div class="history-item <?php echo get_status_class($submission['Status']); ?>" style="padding: 15px; border-left: 5px solid; border-radius: 5px; background-color: #f9f9f9;">
                            <div class="quest-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span class="quest-title" style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($submission['quest_title']); ?></span>
                                <span class="quest-points" style="font-weight: 600; color: var(--color-accent);"><?php echo number_format($submission['Points_award']); ?> PTS</span>
                            </div>
                            <div class="status-info" style="margin-bottom: 10px;">
                                <span class="status-badge <?php echo get_status_class($submission['display_status']); ?>" style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.9rem; font-weight: 700; color: #fff;">
                                    <?php echo htmlspecialchars($submission['display_status']); ?>
                                </span>
                            </div>
                            <div class="dates-info" style="font-size: 0.85rem; color: #888;">
                                <p>Submitted: <?php echo $submission['Submission_date'] ? date('d M Y, h:i A', strtotime($submission['Submission_date'])) : 'N/A'; ?></p>
                                <?php if ($submission['Review_date']): ?>
                                    <p>Reviewed: <?php echo date('d M Y, h:i A', strtotime($submission['Review_date'])); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($submission['Status'] === 'rejected' && !empty($submission['Review_feedback'])): ?>
                                <div class="review-notes" style="margin-top: 10px; padding: 10px; background-color: #fff; border: 1px solid var(--color-error); border-radius: 5px;">
                                    <strong>Moderator Notes:</strong> <?php echo nl2br(htmlspecialchars($submission['Review_feedback'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
    /* Add some specific colors for the status badges */
    .status-badge.status-pending { background-color: #F6AD55; }
    .status-badge.status-approved { background-color: #48BB78; }
    .status-badge.status-rejected { background-color: #F56565; }

    .history-item.status-pending { border-left-color: #F6AD55; }
    .history-item.status-completed, .history-item.status-approved { border-left-color: #48BB78; }
    .history-item.status-rejected { border-left-color: #F56565; }
</style>

<?php include("../includes/footer.php"); ?>