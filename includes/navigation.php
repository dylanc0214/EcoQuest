<?php
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['user_role'] : 'guest';
$app_title = "EcoQuest";

// Define the base path for your project
$base_path = '/'; // Update this if your project folder is different
?>

<nav class="main-nav">
    <div class="nav-brand">
        <?php if ($user_role == 'guest'): ?>
            <a href="<?php echo $base_path; ?>pages/index.php"> 
            <img src="<?php echo $base_path; ?>assets/images/logo.PNG" alt="EcoQuest Logo" class="logo">
            <span class="app-title"><?php echo $app_title; ?></span>
            </a>
        <?php else: ?>
            <a href="<?php echo $base_path; ?>pages/<?php echo $user_role; ?>/dashboard.php">
            <img src="<?php echo $base_path; ?>assets/images/logo.PNG" alt="EcoQuest Logo" class="logo">
            <span class="app-title"><?php echo $app_title; ?></span>
        </a>
        <?php endif; ?>
    </div>

    <div class="nav-links" id="navLinks">
        <?php if ($user_role == 'guest'): ?>
            <li class="dropdown-wrapper"><a href="<?php echo $base_path; ?>pages/about.php" class="dropdown-title">About</a></li>
            <li class="dropdown-wrapper"><a href="<?php echo $base_path; ?>pages/leaderboard.php" class="dropdown-title">Leaderboard</a></li>
            <li class="dropdown-wrapper"><a href="<?php echo $base_path; ?>pages/rewards.php" class="dropdown-title">Rewards</a></li>

            <li class="dropdown-wrapper" id = "signup-li"><a href="<?php echo $base_path; ?>pages/sign_up.php">Sign Up</a></li>
        <?php elseif ($user_role == 'student'): ?>
            <li class="dropdown-wrapper"><a href="<?php echo $base_path; ?>pages/student/dashboard.php" class="dropdown-title">Dashboard</a></li>
            <li class="dropdown-wrapper">
                <a href="#" class="dropdown-title">Activity ▼</a>
                <ul class="dropdown-content">
                    <li><a href="<?php echo $base_path; ?>pages/quests.php"><img src="<?php echo $base_path; ?>assets/images/icons/quest.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Quests</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/leaderboard.php"><img src="<?php echo $base_path; ?>assets/images/icons/leaderboard.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Leaderboard</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/student/validate.php"><img src="<?php echo $base_path; ?>assets/images/icons/submission.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Submissions</strong></p></a></li>
                </ul>
            </li>
            <li class="dropdown-wrapper">
                <a href="#" class="dropdown-title">Rewards ▼</a>
                <ul class="dropdown-content">
                    <li><a href="<?php echo $base_path; ?>pages/rewards.php"><img src="<?php echo $base_path; ?>assets/images/icons/reward.png" alt="reward image" class="dropdown-img"><p><strong class="dropdown-title-text">Rewards</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/student/my_rewards.php"><img src="<?php echo $base_path; ?>assets/images/icons/claim.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Claimed</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/student/achievements.php"><img src="<?php echo $base_path; ?>assets/images/icons/achievement.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Achievements</strong></p></a></li>
                </ul>
            </li>
            <li class="dropdown-wrapper">
                <a href="#" class="dropdown-title">Community ▼</a>
                <ul class="dropdown-content"> 
                    <li><a href="<?php echo $base_path; ?>pages/forum.php"><img src="<?php echo $base_path; ?>assets/images/icons/forum.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Forum</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/student/feedback.php"><img src="<?php echo $base_path; ?>assets/images/icons/feedback.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Feedback</strong></p></a></li>
                </ul>
            </li>

            <li class="dropdown-wrapper" id = "profile-li"><a href="<?php echo $base_path; ?>pages/profile.php">Profile</a></li>
            <li class="dropdown-wrapper" id = "logout-li"><a href="<?php echo $base_path; ?>pages/logout.php" class="logout-link">Logout</a></li>

        <?php elseif ($user_role == 'moderator'): ?>
            <li class="dropdown-wrapper"><a href="<?php echo $base_path; ?>pages/moderator/dashboard.php" class="dropdown-title">Dashboard</a></li>
            <li class="dropdown-wrapper">
                <a href="" class="dropdown-title">Manage ▼</a>
                <ul class="dropdown-content">
                    <li><a href="<?php echo $base_path; ?>pages/moderator/manage_users.php"><img src="<?php echo $base_path; ?>assets/images/icons/user.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Users</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/moderator/manage_reports.php"><img src="<?php echo $base_path; ?>assets/images/icons/report.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Reports</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/moderator/manage_submissions.php"><img src="<?php echo $base_path; ?>assets/images/icons/submission.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Submissions</strong></p></a></li>
                </ul>
            </li>
            <li class="dropdown-wrapper">
                <a href="#" class="dropdown-title">Review ▼</a>
                <ul class="dropdown-content">
                    <li><a href="<?php echo $base_path; ?>pages/moderator/manage_quests.php"><img src="<?php echo $base_path; ?>assets/images/icons/quest.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Quests</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/moderator/manage_rewards.php"><img src="<?php echo $base_path; ?>assets/images/icons/reward.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Reward</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/forum.php"><img src="<?php echo $base_path; ?>assets/images/icons/forum.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Forum</strong></p></a></li>
                </ul>
            </li>
            <li class="dropdown-wrapper" id = "profile-li"><a href="<?php echo $base_path; ?>pages/profile.php">Profile</a></li>
            <li class="dropdown-wrapper" id = "logout-li"><a href="<?php echo $base_path; ?>pages/logout.php" class="logout-link">Logout</a></li>
        <?php elseif ($user_role == 'admin'): ?>
            <li class="dropdown-wrapper"><a href="<?php echo $base_path; ?>pages/admin/dashboard.php" class="dropdown-title">Dashboard</a></li>
            <li class="dropdown-wrapper">
                <a href="#" class="dropdown-title">Manage ▼</a>
                <ul class="dropdown-content">
                    <li><a href="<?php echo $base_path; ?>pages/admin/manage_users.php"><img src="<?php echo $base_path; ?>assets/images/icons/quest.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Users</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/admin/manage_submissions.php"><img src="<?php echo $base_path; ?>assets/images/icons/submission.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Submissions</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/admin/manage_quests.php"><img src="<?php echo $base_path; ?>assets/images/icons/quest.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Quests</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/admin/manage_badges.php"><img src="<?php echo $base_path; ?>assets/images/icons/submission.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Badge</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/admin/manage_rewards.php"><img src="<?php echo $base_path; ?>assets/images/icons/reward.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Rewards</strong></p></a></li>
                </ul>
            </li>
            <li class="dropdown-wrapper">
                <a href="#" class="dropdown-title">Moderate ▼</a>
                <ul class="dropdown-content">
                    <li><a href="<?php echo $base_path; ?>pages/admin/moderation_records.php"><img src="<?php echo $base_path; ?>assets/images/icons/log.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Moderation Log</strong></p></a></li>
                </ul>
            </li>
            <li class="dropdown-wrapper">
                <a href="#" class="dropdown-title">Review ▼</a>
                <ul class="dropdown-content">
                    <li><a href="<?php echo $base_path; ?>pages/admin/view_feedback.php"><img src="<?php echo $base_path; ?>assets/images/icons/reward.png" alt="reward image" class="dropdown-img"><p><strong class="dropdown-title-text">Feedback</strong></p></a></li>
                    <li><a href="<?php echo $base_path; ?>pages/forum.php"><img src="<?php echo $base_path; ?>assets/images/icons/achievement.png" alt="" class="dropdown-img"><p><strong class="dropdown-title-text">Forum</strong></p></a></li>
                </ul>
            </li>

            <li class="dropdown-wrapper" id = "profile-li"><a href="<?php echo $base_path; ?>pages/profile.php">Profile</a></li>
            <li class="dropdown-wrapper" id = "logout-li"><a href="<?php echo $base_path; ?>pages/logout.php" class="logout-link">Logout</a></li>
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

            <div class="profile-container">
                <li><a href="<?php echo $base_path; ?>pages/profile.php" class="nav-btn-profile">
                    <i class="fas fa-user-circle"></i> Profile
                </a></li>
            </div>
            <div class="logout-container">
                <li><a href="<?php echo $base_path; ?>pages/logout.php" class="nav-btn-signup">Logout</a></li>
            </div>
                
            <!-- <li><a href="<?php echo $base_path; ?>pages/profile.php" class="nav-btn-profile">
                <i class="fas fa-user-circle"></i> Profile
            </a></li>
            <li><a href="<?php echo $base_path; ?>pages/logout.php" class="nav-btn-signup">Logout</a></li> -->
        <?php else: ?>
            <div class="signup-container">
                <li><a href="<?php echo $base_path; ?>pages/sign_up.php" class="nav-btn-signup">Sign Up</a></li>
            </div>
        <?php endif; ?>
    </div>

    <button class="nav-toggle" id="mobile-menu-btn" aria-label="Toggle navigation">&#9776;</button>
</nav>