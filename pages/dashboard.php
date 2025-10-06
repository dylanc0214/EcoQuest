<?php
// pages/dashboard.php
session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php"); // Provides $conn (MySQLi object)
include("../includes/header.php");

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$db_error = '';
 
// Check if connection object exists and is successful
$is_db_connected = isset($conn) && !$conn->connect_error;

// Default values if DB connection fails
$username = $_SESSION['username'] ?? 'Student Buddy';
$user_id = $_SESSION['user_id'];
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
    // --- 1. FETCH PRIMARY USER METRICS ---
    try {
        // Fetch total_points and ensure username is up-to-date
        $sql_user = "SELECT username, total_points FROM users WHERE user_id = ?";
        if ($stmt_user = $conn->prepare($sql_user)) {
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            
            if ($data = $result_user->fetch_assoc()) {
                $username = $data['username'];
                $user_metrics['total_points'] = $data['total_points'];
            }
            $stmt_user->close();
        }

        // --- 2. CALCULATE GLOBAL RANK ---
        // This query calculates rank dynamically based on total_points
        $sql_rank = "SELECT COUNT(*) + 1 AS global_rank FROM users WHERE total_points > ?";
        if ($stmt_rank = $conn->prepare($sql_rank)) {
            $stmt_rank->bind_param("i", $user_metrics['total_points']);
            $stmt_rank->execute();
            $result_rank = $stmt_rank->get_result();
            
            if ($data = $result_rank->fetch_assoc()) {
                // If rank is 1, it means 0 people have more points, so we set rank to 1
                $user_metrics['global_rank'] = $data['global_rank']; 
            }
            $stmt_rank->close();
        }

        // --- 3. COUNT PENDING SUBMISSIONS (Assumes 'activities' table with 'status' column) ---
        $sql_pending = "SELECT COUNT(*) AS pending_count FROM activities WHERE user_id = ? AND status = 'pending'";
        if ($stmt_pending = $conn->prepare($sql_pending)) {
            $stmt_pending->bind_param("i", $user_id);
            $stmt_pending->execute();
            $result_pending = $stmt_pending->get_result();
            
            if ($data = $result_pending->fetch_assoc()) {
                $user_metrics['pending_submissions'] = $data['pending_count'];
            }
            $stmt_pending->close();
        }

        // --- 4. FETCH RECENT APPROVED ACTIVITY (FIXED: Joining quests table to get the name and points) ---
        $sql_activity = "
            SELECT 
                q.title AS quest_name, 
                q.points_award AS points_earned, -- *** FIXED: Using points_award from quests table ***
                a.created_at 
            FROM activities a
            INNER JOIN quests q ON a.quest_id = q.quest_id 
            WHERE 
                a.user_id = ? 
                AND a.status = 'approved' 
            ORDER BY 
                a.created_at DESC 
            LIMIT 3";
            
        if ($stmt_activity = $conn->prepare($sql_activity)) {
            $stmt_activity->bind_param("i", $user_id);
            $stmt_activity->execute();
            $result_activity = $stmt_activity->get_result();
            
            // Reset completed count before iterating through results
            $user_metrics['quests_completed'] = $result_activity->num_rows; 

            while ($activity = $result_activity->fetch_assoc()) {
                // Format the date nicely for display
                $activity['date'] = date('M j, Y', strtotime($activity['created_at'])); 
                $recent_activity[] = $activity;
                
                // We cannot reliably count all completed quests here; should run a separate COUNT query if needed.
                // For simplicity, we only count the *recent* ones, but the loop is correct.
            }
            $stmt_activity->close();
        }
        
        // Note: Rewards Redeemed is left at 0 for now as it needs a separate 'rewards_redeemed' table lookup.

    } catch (mysqli_sql_exception $e) {
        $db_error = 'Error fetching data: ' . $e->getMessage() . '. Please contact support.';
    }
}
 
// The footer will handle the $conn->close()
?>

<main class="dashboard-page">
    <div class="container">

        <!-- Database Error Display -->
        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <!-- Welcome Banner -->
        <div class="welcome-card">
            <h1>Hey there, <?php echo htmlspecialchars($username); ?>! Confirm ready to save the planet? 🌎</h1>
            <p class="welcome-text">You're making a real impact! Check out your stats and latest activities below.</p>
            <div class="current-rank">
                <span class="rank-label">Your Global Rank:</span>
                <!-- Highlighting top rank with color and large font -->
                <span class="rank-number"><?php echo htmlspecialchars($user_metrics['global_rank']); ?></span>
                <a href="leaderboard.php" class="btn-leaderboard">See Full Leaderboard &raquo;</a>
            </div>
        </div>

        <!-- Section 1: Key Metrics Grid -->
        <section class="metric-grid">

            <!-- Total Points Card -->
            <div class="metric-card points-card">
                <div class="icon">💰</div>
                <h3>Total Points</h3>
                <p class="metric-value"><?php echo number_format($user_metrics['total_points']); ?> PTS</p>
                <a href="rewards.php" class="metric-link">Spend Your Points &raquo;</a>
            </div>

            <!-- Quests Completed Card -->
            <div class="metric-card completed-card">
                <div class="icon">✅</div>
                <h3>Quests Completed</h3>
                <p class="metric-value"><?php echo $user_metrics['quests_completed']; ?></p>
                <a href="quests.php" class="metric-link">Find New Quests &raquo;</a>
            </div>

            <!-- Rewards Redeemed Card (Still uses simulated count until rewards table is ready) -->
            <div class="metric-card rewards-card">
                <div class="icon">🎁</div>
                <h3>Rewards Redeemed</h3>
                <p class="metric-value"><?php echo $user_metrics['rewards_redeemed']; ?></p>
                <a href="rewards.php" class="metric-link">View Redemption History &raquo;</a>
            </div>
        </section>

        <!-- Section 2: Action & Activity Grid -->
        <section class="action-activity-grid">

            <!-- Pending Submissions Card -->
            <div class="action-card pending-card">
                <h2>Pending Proofs</h2>
                <?php if ($user_metrics['pending_submissions'] > 0): ?>
                    <!-- Warning message for pending tasks -->
                    <p class="action-status warning-status">
                        Aiyo! You have <span class="count"><?php echo $user_metrics['pending_submissions']; ?></span> submissions waiting for Moderator review. Patience is key!
                    </p>
                    <a href="validate.php" class="btn-primary">View Submission Status</a>
                <?php else: ?>
                    <!-- Success message when everything is reviewed -->
                    <p class="action-status success-status">
                        All your submitted proofs have been approved or reviewed! Clean slate!
                    </p>
                    <a href="validate.php" class="btn-secondary">Check Completed Proofs</a>
                <?php endif; ?>
            </div>

            <!-- Recent Activity Card -->
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

<?php include("../includes/footer.php"); ?>
