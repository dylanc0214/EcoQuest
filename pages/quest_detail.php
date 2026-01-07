<?php
// pages/quest_detail.php
session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php");

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$db_error = '';
$quest = null;
$user_quest_status = 'New'; // Default status if no record exists

// 1. Check for a valid quest ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $db_error = 'Error: No valid quest ID provided.';
} else {
    $quest_id = (int)$_GET['id'];
}

$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Warning: Database connection failed.';
}

// --- 2. POST HANDLER: "START QUEST" ACTION ---
if ($is_db_connected && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'start_quest') {
    
    // Weekly Reset Check: Only block if they submitted within the current calendar window
    $sql_check_sub = "
        SELECT s.Student_quest_submission_id 
        FROM Student_Quest_Submissions s
        JOIN Quest_Calendar qc ON s.Quest_id = qc.Quest_id
        WHERE s.Student_id = ? AND s.Quest_id = ? 
        AND s.Status IN ('pending', 'completed', 'approved')
        AND s.Submission_date BETWEEN qc.Start_Date AND qc.End_Date
        AND NOW() BETWEEN qc.Start_Date AND qc.End_Date
    ";
    
    $stmt_check_sub = $conn->prepare($sql_check_sub);
    $stmt_check_sub->bind_param("ii", $student_id, $quest_id);
    $stmt_check_sub->execute();
    $result_check_sub = $stmt_check_sub->get_result();
    
    if ($result_check_sub->num_rows > 0) {
        $db_error = 'You have already submitted this quest for this period.';
    } else {
        $sql_start = "
            INSERT INTO Quest_Progress (Student_id, Quest_id, Status) 
            VALUES (?, ?, 'active')
            ON DUPLICATE KEY UPDATE Status = 'active'
        ";

        if ($stmt_start = $conn->prepare($sql_start)) {
            $stmt_start->bind_param("ii", $student_id, $quest_id);
            if ($stmt_start->execute()) {
                // Remove old rejected submissions for THIS period to allow retry
                $conn->query("DELETE s FROM Student_Quest_Submissions s 
                              JOIN Quest_Calendar qc ON s.Quest_id = qc.Quest_id 
                              WHERE s.Student_id = $student_id AND s.Quest_id = $quest_id 
                              AND s.Status = 'rejected' 
                              AND s.Submission_date BETWEEN qc.Start_Date AND qc.End_Date");
                
                header("Location: quest_detail.php?id=$quest_id&msg=Quest started! You can now submit your proof.");
                exit();
            } else {
                $db_error = 'Database error starting quest: ' . $stmt_start->error;
            }
            $stmt_start->close();
        }
    }
    $stmt_check_sub->close();
}

// --- 3. INCLUDE HEADER ---
include("../includes/header.php");

// --- 4. FETCH QUEST DETAILS AND USER STATUS ---
if ($is_db_connected && $quest_id) {
    try {
        $sql_quest = "
            SELECT q.Quest_id, q.Title, q.Description, q.Points_award, qc.Category_Name, q.Proof_type, q.Instructions 
            FROM Quest q
            LEFT JOIN Quest_Categories qc ON q.CategoryID = qc.CategoryID
            WHERE q.Quest_id = ?
        ";
        
        if ($stmt_quest = $conn->prepare($sql_quest)) {
            $stmt_quest->bind_param("i", $quest_id);
            $stmt_quest->execute();
            $quest = $stmt_quest->get_result()->fetch_assoc();
            $stmt_quest->close();
        }

        if ($quest) {
            $sql_status_sub = "
                SELECT s.Status FROM Student_Quest_Submissions s
                JOIN Quest_Calendar qc ON s.Quest_id = qc.Quest_id
                WHERE s.Student_id = ? AND s.Quest_id = ?
                AND s.Submission_date BETWEEN qc.Start_Date AND qc.End_Date
                AND NOW() BETWEEN qc.Start_Date AND qc.End_Date
            ";
            $stmt_status_sub = $conn->prepare($sql_status_sub);
            $stmt_status_sub->bind_param("ii", $student_id, $quest_id);
            $stmt_status_sub->execute();
            $data_sub = $stmt_status_sub->get_result()->fetch_assoc();
            $stmt_status_sub->close();

            if ($data_sub) {
                $user_quest_status = strtolower($data_sub['Status']);
                if ($user_quest_status == 'approved') $user_quest_status = 'completed';
            } else {
                $sql_status_prog = "SELECT Status FROM Quest_Progress WHERE Student_id = ? AND Quest_id = ?";
                $stmt_status_prog = $conn->prepare($sql_status_prog);
                $stmt_status_prog->bind_param("ii", $student_id, $quest_id);
                $stmt_status_prog->execute();
                $data_prog = $stmt_status_prog->get_result()->fetch_assoc();
                $stmt_status_prog->close();

                if ($data_prog) {
                    $user_quest_status = strtolower($data_prog['Status']);
                }
            }
        }
    } catch (Exception $e) {
        $db_error = 'Error fetching data.';
    }
}
?>

<main class="quest-detail-page">
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="message success-message"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if ($quest): ?>
            <div class="quest-summary-card">
                <a href="quests.php" class="back-link">&laquo; Back to All Quests</a>
                <span class="quest-category-tag"><?php echo htmlspecialchars($quest['Category_Name'] ?? 'General'); ?></span>
                <h1 class="quest-title"><?php echo htmlspecialchars($quest['Title']); ?></h1>
                <div class="quest-metrics">
                    <span class="points-badge">💰 +<?php echo number_format($quest['Points_award']); ?> PTS</span>
                    <span class="proof-badge">📷 Proof Type: <?php echo htmlspecialchars(ucfirst($quest['Proof_type'])); ?></span>
                </div>
                <p class="quest-description"><?php echo htmlspecialchars($quest['Description']); ?></p>
            </div>

            <div class="quest-content-grid">
                <div class="quest-instructions-guide">
                    <h2>Mission Instructions</h2>
                    <div class="instructions-box">
                        <?php echo nl2br(htmlspecialchars($quest['Instructions'])); ?>
                    </div>
                </div>

                <div class="quest-submission-area">
                    <?php if ($user_quest_status === 'completed'): ?>
                        <div class="status-box completed-box">
                            <h2>Status: Completed! 🎉</h2>
                            <p>You have successfully completed this quest. Keep up the great work!</p>
                            <a href="quests.php" class="btn-secondary">Find New Quests</a>
                        </div>

                    <?php elseif ($user_quest_status === 'pending'): ?>
                        <div class="status-box pending-box">
                            <h2>Status: Pending Review 🧐</h2>
                            <p>Your submission is waiting for a Moderator to approve. Check back later!</p>
                            <a href="quests.php" class="btn-secondary">Back to Quests</a>
                        </div>

                    <?php elseif ($user_quest_status === 'New' || $user_quest_status === 'rejected'): ?>
                        <div class="status-box new-box">
                            <?php if ($user_quest_status === 'rejected'): ?>
                                <h2 style="color: var(--color-error);">Status: Rejected 🚫</h2>
                                <p>Your last submission was rejected. Press 'Start Over' to clear it and try again.</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="start_quest">
                                    <button type="submit" class="btn-primary" style="background-color: var(--color-error);">Start Over</button>
                                </form>
                            <?php else: ?>
                                <h2>Ready to Commit?</h2>
                                <p>Press 'Start Quest' to accept this mission. You can submit your proof after starting.</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="start_quest">
                                    <button type="submit" class="btn-primary">Start Quest! 🚀</button>
                                </form>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($user_quest_status === 'active'): ?>
                        <div class="status-box active-box">
                            <h2>Submit Proof Now</h2>
                            <p>You have started this quest! Once you finish the mission, head to the submission page.</p>
                            <a href="student/validate.php" class="btn-primary">Submit Quest Proof</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>