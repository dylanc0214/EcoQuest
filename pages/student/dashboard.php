<?php
// pages/dashboard.php
session_start();

// --- DB Connection and Dependencies ---
include("../../config/db.php"); // Provides $conn (MySQLi object)
include("../../includes/header.php");

// Check if user is logged in and is a student
// We now check for student_id, which is set during login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php");
    exit();
}

$db_error = '';
 
// Check if connection object exists and is successful
$is_db_connected = isset($conn) && !$conn->connect_error;

// Get the correct IDs from the session
$username = $_SESSION['username'] ?? 'Student Buddy'; // Get Username from User table session
$student_id = $_SESSION['student_id']; // Get Student_id from session

$user_metrics = [
    'total_points' => 0,
    'global_rank' => 'N/A',
    'quests_completed' => 0,
    'rewards_redeemed' => 0, 
    'pending_submissions' => 0, 
];
$recent_activity = [];

if (!$is_db_connected) {
    $db_error = 'Warning: Database connection failed. Data displayed may be incomplete or default.';
} else {
    // --- 1. FETCH PRIMARY USER METRICS (from Student table) ---
    try {
        $sql_user = "SELECT Total_point FROM Student WHERE Student_id = ?";
        if ($stmt_user = $conn->prepare($sql_user)) {
            $stmt_user->bind_param("i", $student_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            
            if ($data = $result_user->fetch_assoc()) {
                $user_metrics['total_points'] = $data['Total_point'];
            }
            $stmt_user->close();
        }

        // --- 2. CALCULATE GLOBAL RANK (from Student table) ---
        $sql_rank = "SELECT COUNT(*) + 1 AS global_rank FROM Student WHERE Total_point > ?";
        if ($stmt_rank = $conn->prepare($sql_rank)) {
            $stmt_rank->bind_param("i", $user_metrics['total_points']);
            $stmt_rank->execute();
            $result_rank = $stmt_rank->get_result();
            
            if ($data = $result_rank->fetch_assoc()) {
                $user_metrics['global_rank'] = $data['global_rank']; 
            }
            $stmt_rank->close();
        }
        
        // --- 3. COUNT TOTAL COMPLETED QUESTS (from Student_Quest_Submissions) ---
        $sql_completed = "SELECT COUNT(*) AS completed_count FROM Student_Quest_Submissions WHERE Student_id = ? AND Status = 'completed'";
        if ($stmt_completed = $conn->prepare($sql_completed)) {
            $stmt_completed->bind_param("i", $student_id);
            $stmt_completed->execute();
            $result_completed = $stmt_completed->get_result();
            if ($data = $result_completed->fetch_assoc()) {
                $user_metrics['quests_completed'] = $data['completed_count'];
            }
            $stmt_completed->close();
        }

        // --- 4. COUNT PENDING SUBMISSIONS (from Student_Quest_Submissions) ---
        $sql_pending = "SELECT COUNT(*) AS pending_count FROM Student_Quest_Submissions WHERE Student_id = ? AND Status = 'pending'";
        if ($stmt_pending = $conn->prepare($sql_pending)) {
            $stmt_pending->bind_param("i", $student_id);
            $stmt_pending->execute();
            $result_pending = $stmt_pending->get_result();
            
            if ($data = $result_pending->fetch_assoc()) {
                $user_metrics['pending_submissions'] = $data['pending_count'];
            }
            $stmt_pending->close();
        }
        
        // --- 5. COUNT REWARDS REDEEMED (from Redemption_History) ---
        $sql_rewards = "SELECT COUNT(*) AS rewards_count FROM Redemption_History WHERE Student_id = ?";
        if ($stmt_rewards = $conn->prepare($sql_rewards)) {
            $stmt_rewards->bind_param("i", $student_id);
            $stmt_rewards->execute();
            $result_rewards = $stmt_rewards->get_result();
            if ($data = $result_rewards->fetch_assoc()) {
                $user_metrics['rewards_redeemed'] = $data['rewards_count'];
            }
            $stmt_rewards->close();
        }

        // --- 6. FETCH RECENT APPROVED ACTIVITY (from Student_Quest_Submissions) ---
        $sql_activity = "
            SELECT 
                q.Title AS quest_name, 
                q.Points_award AS points_earned,
                s.Review_date 
            FROM Student_Quest_Submissions s
            INNER JOIN Quest q ON s.Quest_id = q.Quest_id 
            WHERE 
                s.Student_id = ? 
                AND s.Status = 'completed' 
            ORDER BY 
                s.Review_date DESC 
            LIMIT 3";
            
        if ($stmt_activity = $conn->prepare($sql_activity)) {
            $stmt_activity->bind_param("i", $student_id);
            $stmt_activity->execute();
            $result_activity = $stmt_activity->get_result();
            
            while ($activity = $result_activity->fetch_assoc()) {
                $activity['date'] = $activity['Review_date'] ? date('M j, Y', strtotime($activity['Review_date'])) : 'N/A'; 
                $recent_activity[] = $activity;
            }
            $stmt_activity->close();
        }
        
    } catch (mysqli_sql_exception $e) {
        $db_error = 'Error fetching data: ' . $e->getMessage() . '. Please contact support.';
    }
}
 
// The footer will handle the $conn->close()
?>

<main class="dashboard-page">
    <div class="container">

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="welcome-card">
            <h1>Hey there, <?php echo htmlspecialchars($username); ?>! Confirm ready to save the planet? 🌎</h1>
            <p class="welcome-text">You're making a real impact! Check out your stats and latest activities below.</p>
            <div class="current-rank">
                <span class="rank-label">Your Global Rank:</span>
                <span class="rank-number"><?php echo htmlspecialchars($user_metrics['global_rank']); ?></span>
<<<<<<< HEAD
                <a href="../leaderboard.php" class="btn-leaderboard">See Full Leaderboard &raquo;</a>
=======
                <a href="<?php echo $base_path; ?>pages/leaderboard.php" class="btn-leaderboard">See Full Leaderboard &raquo;</a>
>>>>>>> b29e19fdabbe73a6c323d7d9d82111f3fc01cd3e
            </div>
        </div>

        <section class="metric-grid">
            <div class="metric-card points-card">
                <div class="icon">💰</div>
                <h3>Total Points</h3>
                <p class="metric-value"><?php echo number_format($user_metrics['total_points']); ?> PTS</p>
<<<<<<< HEAD
                <a href="../rewards.php" class="metric-link">Spend Your Points &raquo;</a>
=======
                <a href="<?php echo $base_path; ?>pages/rewards.php" class="metric-link">Spend Your Points &raquo;</a>
>>>>>>> b29e19fdabbe73a6c323d7d9d82111f3fc01cd3e
            </div>
            <div class="metric-card completed-card">
                <div class="icon">✅</div>
                <h3>Quests Completed</h3>
                <p class="metric-value"><?php echo $user_metrics['quests_completed']; ?></p>
<<<<<<< HEAD
                <a href="../quests.php" class="metric-link">Find New Quests &raquo;</a>
=======
                <a href="<?php echo $base_path; ?>pages/quests.php" class="metric-link">Find New Quests &raquo;</a>
>>>>>>> b29e19fdabbe73a6c323d7d9d82111f3fc01cd3e
            </div>
            <div class="metric-card rewards-card">
                <div class="icon">🎁</div>
                <h3>Rewards Redeemed</h3>
                <p class="metric-value"><?php echo $user_metrics['rewards_redeemed']; ?></p>
<<<<<<< HEAD
                <a href="../my_rewards.php" class="metric-link">View Redemption History &raquo;</a>
=======
                <a href="<?php echo $base_path; ?>pages/student/my_rewards.php" class="metric-link">View Redemption History &raquo;</a>
>>>>>>> b29e19fdabbe73a6c323d7d9d82111f3fc01cd3e
            </div>
        </section>

        <section class="action-activity-grid">
            <div class="action-card pending-card">
                <h2>Pending Proofs</h2>
                <?php if ($user_metrics['pending_submissions'] > 0): ?>
                    <p class="action-status warning-status">
                        Aiyo! You have <span class="count"><?php echo $user_metrics['pending_submissions']; ?></span> submissions waiting for Moderator review. Patience is key!
                    </p>
<<<<<<< HEAD
                    <a href="../student/validate.php" class="btn-primary">View Submission Status</a>
=======
                    <a href="<?php echo $base_path; ?>pages/student/validate.php" class="btn-primary">View Submission Status</a>
>>>>>>> b29e19fdabbe73a6c323d7d9d82111f3fc01cd3e
                <?php else: ?>
                    <p class="action-status success-status">
                        All your submitted proofs have been reviewed! Clean slate!
                    </p>
                    <a href="<?php echo $base_path; ?>pages/student/validate.php" class="btn-secondary">Check Completed Proofs</a>
                <?php endif; ?>
            </div>
            <div class="action-card activity-card">
                <h2>Recent Activity</h2>
                <ul class="activity-list">
                    <?php if (!empty($recent_activity)): ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <li>
                                <span class="activity-quest"><?php echo htmlspecialchars($activity['quest_name']); ?></span>
                                <span class="activity-points">+<?php echo number_format($activity['points_earned']); ?> PTS</span>
                                <span class="activity-date"><?php echo htmlspecialchars($activity['date']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="no-activity">No recent quests yet. Go finish some quests lah!</li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>
    </div>
</main>

<?php include("../../includes/footer.php"); ?>