<?php
// pages/validate.php
// Handles form submission, file upload, inserts activity proof, and updates quest status to 'pending'.

session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php"); 
include("../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db_error = '';
$message = []; // Array to store success or error messages
$active_quests = [];
$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Error: Database connection failed. Submission cannot proceed.';
}

// =========================================================================
// 1. POST Request Handler (Submission Logic)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_db_connected) {
    
    $quest_id = filter_input(INPUT_POST, 'quest_id', FILTER_VALIDATE_INT);
    $proof_text = trim($_POST['proof_text'] ?? '');
    $file_destination = null;
    $has_error = false;
    
    // Basic validation
    if (!$quest_id || empty($proof_text)) {
        $message = ['type' => 'error', 'text' => 'Aiyo! Please select a quest and provide a description/notes.'];
        $has_error = true;
    }

    // --- File Upload Logic ---
    if (!$has_error && isset($_FILES['proof_media']) && $_FILES['proof_media']['error'] == 0) {
        $target_dir = "../uploads/activities/";
        // Ensure the directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = uniqid('proof_', true) . '_' . basename($_FILES["proof_media"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allow certain file formats (simple check)
        if (!in_array($file_type, ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov'])) {
            $message = ['type' => 'error', 'text' => 'Only JPG, JPEG, PNG, GIF, MP4, and MOV files are allowed.'];
            $has_error = true;
        }

        // Check file size (e.g., limit to 10MB)
        if ($_FILES["proof_media"]["size"] > 10 * 1024 * 1024) {
            $message = ['type' => 'error', 'text' => 'File is too large (max 10MB). Please resize it!'];
            $has_error = true;
        }

        // Attempt to move file
        if (!$has_error && move_uploaded_file($_FILES["proof_media"]["tmp_name"], $target_file)) {
            // Path saved to DB is relative to the project root
            $file_destination = 'uploads/activities/' . $file_name;
        } elseif (!$has_error) {
            $message = ['type' => 'error', 'text' => 'Aiyo! Failed to upload file. Check folder permissions.'];
            $has_error = true;
        }
    }

    // --- Database Insertion (Atomic Transaction) ---
    if (!$has_error) {
        $conn->begin_transaction();
        $status = 'pending'; // The default status for a new submission

        try {
            // 1. INSERT proof into the 'activities' table
            // FIX: Using correct columns (proof_text, proof_media_url) and type specifier 'iisss'
            $sql_insert_activity = "INSERT INTO activities (user_id, quest_id, proof_text, proof_media_url, status) VALUES (?, ?, ?, ?, ?)";

            if ($stmt_insert = $conn->prepare($sql_insert_activity)) {
                // The correct type string is 'iisss': (i)user_id, (i)quest_id, (s)proof_text, (s)proof_media_url, (s)status
                $stmt_insert->bind_param("iisss", $user_id, $quest_id, $proof_text, $file_destination, $status);
                
                if (!$stmt_insert->execute()) {
                    throw new Exception("Error inserting activity: " . $stmt_insert->error);
                }
                $stmt_insert->close();
            } else {
                throw new Exception("Error preparing activity insert: " . $conn->error);
            }

            // 2. UPDATE the status in 'user_quests' to 'pending'
            $sql_update_user_quest = "UPDATE user_quests SET status = ? WHERE user_id = ? AND quest_id = ?";
            
            if ($stmt_update = $conn->prepare($sql_update_user_quest)) {
                $stmt_update->bind_param("sii", $status, $user_id, $quest_id);
                
                if (!$stmt_update->execute()) {
                    throw new Exception("Error updating user quest status: " . $stmt_update->error);
                }
                $stmt_update->close();
            } else {
                throw new Exception("Error preparing user quest update: " . $conn->error);
            }

            // Commit transaction on success
            $conn->commit();
            $message = ['type' => 'success', 'text' => 'Proof submitted successfully! Your submission is now pending review. Check the <a href="quests.php">Quests Page</a> for status updates.'];

        } catch (Exception $e) {
            $conn->rollback();
            $message = ['type' => 'error', 'text' => 'A critical database error occurred during submission: ' . $e->getMessage()];
        }
    }
}


// =========================================================================
// 2. Fetch Active Quests (for Form Dropdown)
// =========================================================================

if ($is_db_connected) {
    // Fetch all quests the user has started but not yet submitted ('active' in user_quests)
    $sql_fetch_active = "
        SELECT 
            q.quest_id, 
            q.title 
        FROM quests q
        JOIN user_quests uq ON q.quest_id = uq.quest_id
        WHERE 
            uq.user_id = ? AND uq.status = 'active'
        ORDER BY q.title ASC";

    if ($stmt_fetch = $conn->prepare($sql_fetch_active)) {
        $stmt_fetch->bind_param("i", $user_id);
        
        if ($stmt_fetch->execute()) {
            $result = $stmt_fetch->get_result();
            while ($quest = $result->fetch_assoc()) {
                $active_quests[] = $quest;
            }
        } else {
            $db_error = 'Could not load active quests: ' . $stmt_fetch->error;
        }
        $stmt_fetch->close();
    } else {
        $db_error = 'Database query preparation failed: ' . $conn->error;
    }
}
?>

<main class="validate-page">
    <div class="container">
        <h1 class="page-title">Submit Proof for Quest 📸</h1>
        <p class="page-subtitle">Time to show off your eco-action! Upload your photo/video and add a short story.</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <div class="auth-card">
            <?php if (empty($active_quests) && empty($message)): ?>
                <div class="message info-message" style="margin-top: 20px;">
                    Aiyo, you don't have any quests "In Progress" right now! <br> 
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
                                <option value="<?php echo $quest['quest_id']; ?>">
                                    <?php echo htmlspecialchars($quest['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h3><i class="fas fa-camera"></i> Proof of Action (Photo/Video)</h3>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">Max file size 10MB. Accepted formats: JPG, PNG, GIF, MP4, MOV.</p>
                    <div class="form-group">
                        <label for="proof_media">Upload File</label>
                        <input type="file" id="proof_media" name="proof_media" accept="image/*,video/*" required>
                    </div>

                    <h3><i class="fas fa-pencil-alt"></i> Notes & Description</h3>
                    <div class="form-group">
                        <label for="proof_text">Tell us about your action (min 10 words)</label>
                        <textarea id="proof_text" name="proof_text" rows="5" placeholder="e.g., I successfully recycled 10 plastic bottles from my college canteen..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Submit Proof!</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php include("../includes/footer.php"); ?>
