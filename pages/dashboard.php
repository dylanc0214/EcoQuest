<?php
// pages/dashboard.php
session_start();

include("../includes/header.php");
include("../includes/navigation.php");

// Check if user is logged in and is a student
// Redirect non-students or guests to the login page for security
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// --- DATABASE SIMULATION: Placeholder User Data ---
// In a real application, this data is fetched based on $_SESSION['user_id']
$username = $_SESSION['username'] ?? 'Student Buddy';
$user_id = $_SESSION['user_id'] ?? 3;

// Simulated Metrics
$user_metrics = [
        'total_points' => 7800,
        'global_rank' => 3,
        'quests_completed' => 25,
        'rewards_redeemed' => 2,
        'pending_submissions' => 4, // Proofs waiting for mod review
];

// Simulated Recent Activity (Last 3 quests)
$recent_activity = [
        ['quest' => 'The Carpool Crew', 'points' => 500, 'date' => 'Oct 2, 2025'],
        ['quest' => 'Reusable Container Champion', 'points' => 300, 'date' => 'Sep 30, 2025'],
        ['quest' => 'No Plastic Bag Challenge', 'points' => 100, 'date' => 'Sep 28, 2025'],
];
?>

<main class="dashboard-page">
    <div class="container">

        <!-- Welcome Banner -->
        <div class="welcome-card">
            <h1>Hey there, <?php echo htmlspecialchars($username); ?>! Confirm ready to save the planet? 🌎</h1>
            <p class="welcome-text">You're making a real impact! Check out your stats and latest activities below.</p>
            <div class="current-rank">
                <span class="rank-label">Your Global Rank:</span>
                <!-- Highlighting top rank with color and large font -->
                <span class="rank-number"><?php echo $user_metrics['global_rank']; ?></span>
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

            <!-- Rewards Redeemed Card -->
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
                                <span class="activity-quest"><?php echo htmlspecialchars($activity['quest']); ?></span>
                                <span class="activity-points">+<?php echo number_format($activity['points']); ?> PTS</span>
                                <span class="activity-date"><?php echo $activity['date']; ?></span>
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
