<?php
// pages/quest_detail.php
session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php"); // Provides $conn (MySQLi object)
include("../includes/header.php");

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db_error = '';
$quest = null;
$user_quest_status = 'New'; // 'New', 'active', 'pending', 'completed'

// 1. Check for quest ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $db_error = 'Error: No valid quest ID provided. Please return to the Quests list.';
    goto display_page;
}

$quest_id = (int)$_GET['id'];
$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Warning: Database connection failed. Cannot load quest details.';
    goto display_page;
}


// --- POST HANDLER: START QUEST ACTION ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'start_quest') {
    
    // Use ON DUPLICATE KEY UPDATE to handle both INSERT (new) and UPDATE (if user was previously rejected)
    $sql_start = "INSERT INTO user_quests (user_id, quest_id, status) VALUES (?, ?, 'active') 
                  ON DUPLICATE KEY UPDATE status = 'active'";
    
    if ($stmt_start = $conn->prepare($sql_start)) {
        $stmt_start->bind_param("ii", $user_id, $quest_id);
        
        if ($stmt_start->execute()) {
            // Success! Redirect to self (GET request) to update the status display and show the form
            header("Location: quest_detail.php?id=$quest_id&msg=Quest started! Now submit your proof.");
            exit();
        } else {
            $db_error = 'Database error starting quest: ' . $stmt_start->error;
        }
        $stmt_start->close();
    } else {
        $db_error = 'Database query preparation failed for starting quest.';
    }
}


// --- 2. FETCH QUEST DETAILS AND USER STATUS ---
try {
    // A. Fetch Quest Details
    $sql_quest = "
        SELECT 
            quest_id, title, description, points_award, category, proof_type, instructions 
        FROM quests 
        WHERE quest_id = ?";
    
    if ($stmt_quest = $conn->prepare($sql_quest)) {
        $stmt_quest->bind_param("i", $quest_id);
        $stmt_quest->execute();
        $result_quest = $stmt_quest->get_result();
        
        if ($result_quest->num_rows > 0) {
            $quest = $result_quest->fetch_assoc();
        }
        $stmt_quest->close();
    }

    if ($quest) {
        // B. Check User Status for this Quest from user_quests table
        $sql_status = "
            SELECT status 
            FROM user_quests 
            WHERE user_id = ? AND quest_id = ?";
        
        if ($stmt_status = $conn->prepare($sql_status)) {
            $stmt_status->bind_param("ii", $user_id, $quest_id);
            $stmt_status->execute();
            $result_status = $stmt_status->get_result();
            
            if ($data = $result_status->fetch_assoc()) {
                // Status can be 'active', 'pending', or 'completed'
                $user_quest_status = $data['status']; 
            } else {
                // No entry in user_quests means user has not committed yet
                $user_quest_status = 'New';
            }
            $stmt_status->close();
        }

    } else {
        $db_error = 'Error: Quest not found or is inactive.';
    }

} catch (mysqli_sql_exception $e) {
    $db_error = 'Error fetching data: ' . $e->getMessage() . '. Please contact support.';
}

display_page: // Goto marker for cleaner error handling
?>

<main class="quest-detail-page">
    <div class="container">
        
        <!-- Messages from Start Action -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="message success-message"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <!-- Database Error Display -->
        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if ($quest): ?>
            
            <!-- Quest Header and Summary Card -->
            <div class="quest-summary-card">
                <a href="quests.php" class="back-link">&laquo; Back to All Quests</a>
                <span class="quest-category-tag"><?php echo htmlspecialchars($quest['category']); ?></span>
                
                <h1 class="quest-title"><?php echo htmlspecialchars($quest['title']); ?></h1>
                
                <div class="quest-metrics">
                    <span class="points-badge">💰 +<?php echo number_format($quest['points_award']); ?> PTS</span>
                    <span class="proof-badge">📋 Proof Type: <?php echo htmlspecialchars(ucfirst($quest['proof_type'])); ?></span>
                </div>
                
                <p class="quest-description"><?php echo htmlspecialchars($quest['description']); ?></p>
            </div>

            <div class="quest-content-grid">
                
                <!-- Instructions and Guide -->
                <div class="quest-instructions-guide">
                    <h2>Mission Instructions</h2>
                    <div class="instructions-box">
                        <?php echo nl2br(htmlspecialchars($quest['instructions'])); ?>
                    </div>
                    <p class="warning-note">⚠️ Note: Ensure your submission follows all instructions. Invalid proof will be rejected!</p>
                </div>

                <!-- Submission / Status Area -->
                <div class="quest-submission-area">
                    
                    <?php if ($user_quest_status === 'completed'): ?>
                        
                        <!-- State: Completed -->
                        <div class="status-box completed-box">
                            <h2>Status: Completed! 🎉</h2>
                            <p>You have successfully completed this quest and earned your points. Keep up the amazing work, EcoWarrior!</p>
                            <a href="quests.php" class="btn-secondary">Find New Quests</a>
                        </div>
                        
                    <?php elseif ($user_quest_status === 'pending'): ?>
                        
                        <!-- State: Pending Review -->
                        <div class="status-box pending-box">
                            <h2>Status: Pending Review... 🧐</h2>
                            <p>Your submission is currently waiting for a Moderator to approve. Don't worry, it usually takes up to 24 hours. Check back later!</p>
                            <a href="quests.php" class="btn-secondary">Back to Quests</a>
                        </div>
                        
                    <?php elseif ($user_quest_status === 'New'): ?>
                        
                        <!-- State: New - Must Start First -->
                        <div class="status-box new-box">
                            <h2>Ready to Commit?</h2>
                            <p>Press 'Start Quest' below to confirm you are taking on this mission. Once started, you can submit your proof!</p>
                            
                            <!-- Start Quest Form (POSTs back to this page) -->
                            <form method="POST" class="quest-proof-form">
                                <input type="hidden" name="action" value="start_quest">
                                <button type="submit" class="btn-primary">Start Quest! 🚀</button>
                            </form>
                        </div>

                    <?php elseif ($user_quest_status === 'active'): // Now the submission form appears only after "Start Quest" ?>
                        
                        <!-- State: Active - Ready to Submit Proof -->
                        <div class="status-box active-box">
                            <h2>Submit Proof Now</h2>
                            <p>You have started this quest! Once you finish the mission, upload your proof below. Confirm ready to earn those sweet points!</p>
                            
                            <!-- Submission Form -->
                            <form action="validate.php" method="POST" class="quest-proof-form">
                                <button type="submit" class="btn-primary">Submit Quest Proof</button>
                            </form>       
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php include("../includes/footer.php"); ?>
