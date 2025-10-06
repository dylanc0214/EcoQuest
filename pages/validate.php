<?php
// pages/validate.php
session_start();

// --- DB Connection and Dependencies ---
// Includes session_start(), DB connection ($conn is now available if successful), and navigation
require_once "../includes/header.php"; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$message = [];
$quests = []; // Array to hold the list of available quests

// --- DATABASE CONNECTION CHECK ---
// IMPORTANT: Check if the connection variable $conn is set and valid before running any queries.
$is_db_connected = isset($conn) && $conn instanceof mysqli && $conn->ping();


if ($is_db_connected) {
    // --- 1. Fetch Live Quests AVAILABLE TO THIS USER for Submission ---
    // NEW LOGIC: Only show quests that are active (q.is_active=1) AND
    // the user has explicitly started (INNER JOIN user_quests with status='active') AND
    // the user has NOT yet submitted proof for (not in activities table with pending/approved status).
    
    $sql_quests = "
        SELECT 
            q.quest_id, 
            q.title, 
            q.points_award 
        FROM quests q
        INNER JOIN user_quests uq 
            ON q.quest_id = uq.quest_id AND uq.user_id = ? /* BIND 1: For the join condition */
        WHERE q.is_active = 1
        AND uq.status = 'active' -- Must be a quest the user clicked 'Start Quest' for
        AND NOT EXISTS (
            SELECT 1 
            FROM activities a 
            WHERE a.quest_id = q.quest_id 
            AND a.user_id = ? /* BIND 2: For the NOT EXISTS subquery */
            AND a.status IN ('pending', 'approved')
        )
        ORDER BY q.points_award DESC";
        
    // Using Prepared Statements for security (since we use user input/session data in the query)
    if ($stmt = $conn->prepare($sql_quests)) {
        // Bind the user_id twice for the two placeholders (?)
        $stmt->bind_param("ii", $current_user_id, $current_user_id);
        $stmt->execute();
        $result_quests = $stmt->get_result();

        if ($result_quests && $result_quests->num_rows > 0) {
            while ($row = $result_quests->fetch_assoc()) {
                $quests[] = [
                    'id' => $row['quest_id'], 
                    // Using points_award from the database query
                    'title' => htmlspecialchars($row['title']) . " ({$row['points_award']} Points)"
                ];
            }
        } else {
            // Message when no available quests for submission are found
            $message = ['type' => 'info', 'text' => 'Aiyo, you currently have no new quests available for proof submission! You must click "Start Quest" on the Quests page first.'];
        }
        $stmt->close();

    } else {
        $message = ['type' => 'error', 'text' => 'SQL Prepare Error. Please check the database connection and schema.'];
    }

} else {
    // If connection check failed, show a critical error message
    $message = ['type' => 'error', 'text' => 'CRITICAL ERROR: The EcoQuest database is down. Cannot load quests. Please ensure your database settings in config/db.php are correct!'];
}

// --- 2. Handle Proof Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_db_connected) {
    // NOTE: We only allow submission if the database is connected
    $selected_quest_id = $_POST['quest_id'] ?? null;
    $upload_file = $_FILES['proof_photo'] ?? null;
    $description = $_POST['description'] ?? '';

    // Basic Validation Checks
    if (empty($selected_quest_id) || !is_numeric($selected_quest_id)) {
        $message = ['type' => 'error', 'text' => 'Aiyo! Must select a Quest first!'];
    } elseif ($upload_file['error'] === UPLOAD_ERR_NO_FILE) {
        $message = ['type' => 'error', 'text' => 'Must upload a photo to prove your action. No cheating!'];
    } elseif ($upload_file['error'] !== UPLOAD_ERR_OK) {
        $message = ['type' => 'error', 'text' => 'File upload error. Try a different photo.'];
    } else {
        // --- Photo Upload Logic Placeholder (To be replaced with real activity record) ---
        
        $uploaded_filename = $upload_file['name'];

        // --- SUCCESS MESSAGE (Dummy Submission) ---
        $message = [
            'type' => 'success', 
            'text' => "Wah! Proof for Quest ID #{$selected_quest_id} submitted! File: {$uploaded_filename}. It is now waiting for a Moderator to review and approve your points. Thanks for saving the environment!",
        ];
        
        // You would typically redirect after a successful POST
        // header("Location: dashboard.php?submission=success"); exit();
    }
}

// Find the title of the selected quest if a POST request failed, to keep the form data
$current_quest_title = 'Select a Quest';
if (isset($selected_quest_id)) {
    // We check the $quests array (which is now populated from the DB)
    foreach ($quests as $quest) {
        if ($quest['id'] == $selected_quest_id) {
            $current_quest_title = $quest['title'];
            break;
        }
    }
}
?>

<main class="validate-page">
    <div class="container">
        <h1 class="page-title">Quest Proof Submission 📸</h1>
        <p class="page-subtitle">Select the quest you completed and upload a photo as evidence for verification.</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <div class="auth-card validation-card">
            <form action="validate.php" method="POST" enctype="multipart/form-data" class="auth-form">
                
                <h3><i class="fas fa-bullseye"></i> Quest Details</h3>

                <!-- Quest Selection Dropdown -->
                <div class="form-group">
                    <label for="quest_id">Which Quest Did You Complete?</label>
                    <!-- IMPORTANT: Disable the select if the DB connection failed or NO quests are available -->
                    <select id="quest_id" name="quest_id" required 
                        <?php echo (!$is_db_connected || empty($quests)) ? 'disabled' : ''; ?>>
                        
                        <option value="">
                            <?php echo empty($quests) ? '-- No Quests Available (Must Start First) --' : '-- Select a Quest --'; ?>
                        </option>
                        
                        <?php foreach ($quests as $quest): ?>
                            <option value="<?php echo $quest['id']; ?>"
                                <?php echo (isset($selected_quest_id) && $selected_quest_id == $quest['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($quest['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Proof Photo Upload -->
                <div class="form-group">
                    <label for="proof_photo">Upload Proof Photo (Max 5MB)</label>
                    <!-- IMPORTANT: The 'accept' attribute helps the user select the right file type -->
                    <input type="file" id="proof_photo" name="proof_photo" accept="image/*" required <?php echo (!$is_db_connected || empty($quests)) ? 'disabled' : ''; ?>>
                    <p class="input-hint">Image should clearly show your action (e.g., reusable cup, recycling bin).</p>
                </div>
                
                <!-- Description/Notes -->
                <div class="form-group">
                    <label for="description">Add Notes / Description (Optional)</label>
                    <textarea id="description" name="description" rows="3" placeholder="E.g., Took this photo at the APU Starbucks at 1 PM today." <?php echo (!$is_db_connected || empty($quests)) ? 'disabled' : ''; ?>><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>

                <div class="form-submit">
                    <!-- The primary button uses the shared style `.btn-submit` -->
                    <button type="submit" class="btn-submit" <?php echo (!$is_db_connected || empty($quests)) ? 'disabled' : ''; ?>>Submit Proof & Earn Points</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>
