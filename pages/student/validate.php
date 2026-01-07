<?php
// pages/validate.php
include("../../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$db_error = '';
$message = []; 
$submission_history = [];
$is_db_connected = isset($conn) && !$conn->connect_error;

// FIXED: Capture quest_id from either GET (initial load) or POST (form submission) to prevent "No quest identified" error
$quest_id = filter_input(INPUT_GET, 'quest_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'quest_id', FILTER_VALIDATE_INT);
$quest_title = "Selected Mission";

if (!$is_db_connected) {
    $db_error = 'Error: Database connection failed. Cannot proceed.';
}

// Fetch the specific quest title for display
if ($is_db_connected && $quest_id) {
    $stmt_title = $conn->prepare("SELECT Title FROM Quest WHERE Quest_id = ?");
    $stmt_title->bind_param("i", $quest_id);
    $stmt_title->execute();
    $res_title = $stmt_title->get_result();
    if ($row = $res_title->fetch_assoc()) {
        $quest_title = $row['Title'];
    }
    $stmt_title->close();
}

// =========================================================================
// 1. POST Request Handler (Submission Logic)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_db_connected) {
    $file_destination = null;
    $has_error = false;

    if (!$quest_id) {
        $message = ['type' => 'error', 'text' => 'Aiyo! No quest identified. Please try again from the Quest page.'];
        $has_error = true;
    }
    
    // --- File Upload Logic ---
    if (!$has_error && isset($_FILES['proof_media']) && $_FILES['proof_media']['error'] == 0) {
        $target_dir = "../../uploads/activities/"; 
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["proof_media"]["name"], PATHINFO_EXTENSION));
        $file_name = uniqid('proof_', true) . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        // Validation (REMOVED Size limitation as requested)
        if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov'])) {
            $message = ['type' => 'error', 'text' => 'Only JPG, PNG, GIF, MP4, MOV files are allowed.'];
            $has_error = true;
        } 

        if (!$has_error && move_uploaded_file($_FILES["proof_media"]["tmp_name"], $target_file)) {
            $file_destination = 'uploads/activities/' . $file_name; 
        } elseif (!$has_error) {
            $message = ['type' => 'error', 'text' => 'Aiyo! Failed to upload file. Check server permissions.'];
            $has_error = true;
        }
    } else if (!$has_error) {
         $message = ['type' => 'error', 'text' => 'Proof media (image/video) is required.'];
         $has_error = true;
    }

    if (!$has_error) {
        try {
            $sql_insert = "
                INSERT INTO Student_Quest_Submissions 
                    (Student_id, Quest_id, Image, Submission_date, Status)
                VALUES (?, ?, ?, NOW(), 'pending')
            ";
            
            if ($stmt = $conn->prepare($sql_insert)) {
                $stmt->bind_param("iis", $student_id, $quest_id, $file_destination);
                if ($stmt->execute()) {
                    $conn->query("UPDATE Quest_Progress SET Status = 'pending' WHERE Student_id = $student_id AND Quest_id = $quest_id");
                    $message = ['type' => 'success', 'text' => 'Proof submitted! Your mission is now pending review.'];
                } else {
                    throw new Exception("Update failed: " . $stmt->error);
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $message = ['type' => 'error', 'text' => 'An error occurred: ' . $e->getMessage()];
        }
    }
}

// =========================================================================
// 3. Fetch Submission History
// =========================================================================
if ($is_db_connected) {
    $sql_history = "
        SELECT s.Student_quest_submission_id, s.Status, s.Submission_date, s.Review_date, s.Review_feedback,
               q.Title AS quest_title, q.Points_award
        FROM Student_Quest_Submissions s
        JOIN Quest q ON s.Quest_id = q.Quest_id
        WHERE s.Student_id = ?
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
        }
        $stmt_history->close();
    }
}

function get_status_class($status) {
    return 'status-' . strtolower($status);
}
?>

<main class="validate-page">
    <div class="container">
        <h1 class="page-title">Submit Proof: <?php echo htmlspecialchars($quest_title); ?> 📸</h1>
        <p class="page-subtitle">Upload your evidence to verify your green impact.</p>

        <?php if ($db_error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message" style="max-width: 700px; margin: 20px auto; text-align:center;">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($message['type']) || $message['type'] !== 'success'): ?>
        <div class="auth-card submission-form-card" style="max-width: 700px; margin: 20px auto;">
            
            <?php if (!$quest_id): ?>
                <div class="message info-message">
                    Aiyo, no quest selected! <br>
                    Go back to the <a href="../quests.php">Quests Page</a> and click "Submit Proof" on an active mission.
                </div>
            <?php else: ?>
                <form action="validate.php" method="POST" enctype="multipart/form-data" class="auth-form">
                    <input type="hidden" name="quest_id" value="<?php echo $quest_id; ?>">

                    <div class="form-group">
                        <label>Mission to Complete</label>
                        <div class="static-display" style="padding: 12px; background: #f0fdf4; border: 1px solid #71B48D; border-radius: 8px; font-weight: bold; color: #1D4C43;">
                            <i class="fas fa-leaf"></i> <?php echo htmlspecialchars($quest_title); ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="proof_media">Upload Your Evidence</label>
                        <div class="custom-file-upload">
                            <label for="proof_media" class="file-drop-area" id="drop-area" style="display: block; border: 2px dashed #71B48D; padding: 40px; text-align: center; border-radius: 12px; cursor: pointer; background: #fafafa; transition: 0.3s; position: relative; overflow: hidden;">
                                <div id="upload-instruction">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #71B48D; margin-bottom: 15px;"></i>
                                    <p style="margin: 0; font-weight: 600; color: #555;">Click to upload or drag photo/video here</p>
                                    <p style="font-size: 0.8rem; color: #888;">(JPG, PNG, MP4, MOV)</p>
                                </div>
                                
                                <div id="preview-container" style="display: none; margin-bottom: 15px;">
                                    <img id="image-preview" style="display: none; max-width: 100%; max-height: 300px; border-radius: 8px; margin: 0 auto;">
                                    <video id="video-preview" style="display: none; max-width: 100%; max-height: 300px; border-radius: 8px; margin: 0 auto;" controls></video>
                                </div>

                                <input type="file" id="proof_media" name="proof_media" accept="image/*,video/*" required style="display:none;" onchange="updateFileName(this)">
                                <div id="file-name-display" style="margin-top: 10px; color: #1D4C43; font-weight: bold;"></div>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions" style="text-align:center; margin-top: 30px;">
                        <button type="submit" class="btn-primary" style="padding: 15px 40px; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 4px 15px rgba(113, 180, 141, 0.3);">
                            Confirm Submission! <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <section class="submission-history" style="max-width: 800px; margin: 60px auto;">
            <h2 style="text-align:center; font-size: 1.8rem; color: var(--color-primary);"><i class="fas fa-history"></i> My Submission History</h2>
            
            <?php if (empty($submission_history)): ?>
                <div class="message info-message" style="text-align: center;">You haven't submitted any proof yet. Let's get to work!</div>
            <?php else: ?>
                <div class="history-list-container" style="display: grid; gap: 20px;">
                    <?php foreach ($submission_history as $submission): ?>
                        <div class="history-item <?php echo get_status_class($submission['Status']); ?>" style="padding: 20px; border-left: 6px solid; border-radius: 12px; background-color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <div class="quest-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span class="quest-title" style="font-weight: 800; font-size: 1.1rem; color: #1D4C43;"><?php echo htmlspecialchars($submission['quest_title']); ?></span>
                                <span class="quest-points" style="font-weight: 700; color: #71B48D;">+<?php echo number_format($submission['Points_award']); ?> PTS</span>
                            </div>

                            <?php if (!empty($submission['Review_feedback'])): ?>
                                <div class="review-feedback-content" style="margin-bottom: 15px; padding: 10px; background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.95rem;">
                                    <i class="fas fa-comment-dots" style="color: #64748b; margin-right: 5px;"></i>
                                    <strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($submission['Review_feedback'])); ?>
                                </div>
                            <?php endif; ?>

                            <div class="status-row" style="display: flex; align-items: center; gap: 15px;">
                                <span class="status-badge <?php echo get_status_class($submission['display_status']); ?>" style="padding: 5px 12px; border-radius: 50px; font-size: 0.85rem; font-weight: 700; color: #fff;">
                                    <?php echo htmlspecialchars($submission['display_status']); ?>
                                </span>
                                <span style="font-size: 0.8rem; color: #999;"><i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($submission['Submission_date'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
function updateFileName(input) {
    const display = document.getElementById('file-name-display');
    const instruction = document.getElementById('upload-instruction');
    const previewContainer = document.getElementById('preview-container');
    const imgPreview = document.getElementById('image-preview');
    const videoPreview = document.getElementById('video-preview');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        display.innerHTML = '<i class="fas fa-file-alt"></i> ' + file.name;
        input.parentElement.style.background = "#f0fdf4";
        input.parentElement.style.borderColor = "#1D4C43";
        
        instruction.style.display = "none";
        previewContainer.style.display = "block";

        const reader = new FileReader();
        reader.onload = function(e) {
            if (file.type.startsWith('image/')) {
                imgPreview.src = e.target.result;
                imgPreview.style.display = "block";
                videoPreview.style.display = "none";
            } else if (file.type.startsWith('video/')) {
                videoPreview.src = e.target.result;
                videoPreview.style.display = "block";
                imgPreview.style.display = "none";
            }
        };
        reader.readAsDataURL(file);
    }
}
</script>

<style>
    .status-badge.status-pending { background-color: #ecc94b; }
    .status-badge.status-approved, .status-badge.status-completed { background-color: #48bb78; }
    .status-badge.status-rejected { background-color: #f56565; }

    .history-item.status-pending { border-left-color: #ecc94b; }
    .history-item.status-completed, .history-item.status-approved { border-left-color: #48bb78; }
    .history-item.status-rejected { border-left-color: #f56565; }
    
    .file-drop-area:hover { border-color: #1D4C43 !important; background: #f0fdf4 !important; }
</style>

<?php include("../../includes/footer.php"); ?>