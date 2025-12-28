<?php
// includes/navigation.php
// --- NO session_start() HERE! ---
// The header.php file already handles it.

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['user_role'] : 'guest';
$app_title = "EcoQuest";

// Define the base path for your project
$base_path = '/Group7_EcoQuest/'; // Update this if your project folder is different
?>

<nav class="main-nav">
    <div class="nav-brand">
        <a href="<?php echo $base_path; ?>pages/index.php">
            <img src="<?php echo $base_path; ?>assets/images/logo.PNG" alt="EcoQuest Logo" class="logo">
            <span class="app-title"><?php echo $app_title; ?></span>
        </a>
    </div>

    <div class="nav-links" id="navLinks">
        <?php if ($user_role == 'guest'): ?>
            <li><a href="<?php echo $base_path; ?>pages/about.php">About</a></li>
            <li><a href="<?php echo $base_path; ?>pages/leaderboard.php">Leaderboard</a></li>
            <li><a href="<?php echo $base_path; ?>pages/rewards.php">Rewards</a></li>

        <?php elseif ($user_role == 'student'): ?>
            <li><a href="<?php echo $base_path; ?>pages/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_path; ?>pages/quests.php">Quests</a></li>
            <li><a href="<?php echo $base_path; ?>pages/leaderboard.php">Leaderboard</a></li>
            <li><a href="<?php echo $base_path; ?>pages/rewards.php">Rewards</a></li>
            <li><a href="<?php echo $base_path; ?>pages/my_rewards.php">Claimed</a></li>
            <li><a href="<?php echo $base_path; ?>pages/achievements.php">Achievements</a></li>
            <li><a href="<?php echo $base_path; ?>pages/validate.php">Submissions</a></li>
            <li><a href="<?php echo $base_path; ?>pages/forum.php">Forum</a></li>
            <li><a href="<?php echo $base_path; ?>pages/feedback.php">Feedback</a></li>

        <?php elseif ($user_role == 'moderator'): ?>
            <li><a href="<?php echo $base_path; ?>pages/moderator/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_path; ?>pages/moderator/manage_submissions.php">Submissions</a></li>
            <li><a href="<?php echo $base_path; ?>pages/moderator/manage_reports.php">Reports</a></li>
            <li><a href="<?php echo $base_path; ?>pages/moderator/manage_users.php">Users</a></li>
            <li><a href="<?php echo $base_path; ?>pages/moderator/manage_quests.php">Quests</a></li>
            <li><a href="<?php echo $base_path; ?>pages/moderator/manage_rewards.php">Rewards</a></li>
            <li><a href="<?php echo $base_path; ?>pages/forum.php">Forum</a></li>

        <?php elseif ($user_role == 'admin'): ?>
            <li><a href="<?php echo $base_path; ?>pages/admin/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo $base_path; ?>pages/admin/manage_submissions.php">Validate</a></li>
            <li><a href="<?php echo $base_path; ?>pages/admin/manage_users.php">Users</a></li>
            <li><a href="<?php echo $base_path; ?>pages/admin/moderation_records.php">Moderation Log</a></li>
            <li><a href="<?php echo $base_path; ?>pages/admin/manage_quests.php">Quests</a></li>
            <li><a href="<?php echo $base_path; ?>pages/admin/manage_badges.php">Badges</a></li>
            <li><a href="<?php echo $base_path; ?>pages/admin/manage_rewards.php">Rewards</a></li>
            <li><a href="<?php echo $base_path; ?>pages/forum.php">Forum</a></li>
            <li><a href="<?php echo $base_path; ?>pages/admin/view_feedback.php">Feedback</a></li>
        <?php endif; ?>
    </div>

    <div class="nav-auth">
        <?php if ($is_logged_in): ?>
            <div class="notif-dropdown-container">
                <button id="notif-bell" class="notif-btn" onclick="toggleNotifDropdown()">
                    <i class="fas fa-bell"></i>
                    <span id="notif-badge" class="notif-badge" style="display: none;">0</span>
                </button>
                <div id="notif-dropdown" class="notif-dropdown">
                    <div class="notif-header">
                        <span>Notifications</span>
                    </div>
                    <div id="notif-list" class="notif-list">
                        <div class="notif-empty">Loading...</div>
                    </div>
                </div>
            </div>
            <li><a href="<?php echo $base_path; ?>pages/profile.php" class="nav-btn-profile">
                <i class="fas fa-user-circle"></i> Profile
            </a></li>
            <li><a href="<?php echo $base_path; ?>pages/logout.php" class="nav-btn-signup">Logout</a></li>
        <?php else: ?>
            <li><a href="<?php echo $base_path; ?>pages/sign_up.php" class="nav-btn-signup">Sign Up</a></li>
        <?php endif; ?>
    </div>

    <button class="nav-toggle" id="mobile-menu-btn" aria-label="Toggle navigation">&#9776;</button>
</nav>