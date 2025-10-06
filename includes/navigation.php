<?php
// includes/navigation.php
// Start the session if it hasn't been started (though it should be in header.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- SIMULATION OF USER AUTHENTICATION AND ROLE ---
// In a real app, this data comes from the database after login.
// For testing, uncomment and change the role below:
// $_SESSION['user_id'] = 1;
// $_SESSION['username'] = 'AliBinStudent';
// $_SESSION['user_role'] = 'student'; // 'student', 'moderator', 'admin', or not set (guest)

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['user_role'] : 'guest';
$app_title = "EcoQuest";
?>

<nav class="main-nav">
    <div class="nav-brand">
        <a href="index.php">
            <!-- Replace with your actual logo image -->
            <img src="../assets/images/logo.png" alt="EcoQuest Logo" class="logo">
            <span class="app-title"><?php echo $app_title; ?></span>
        </a>
    </div>

    <!-- Main Navigation Links -->
    <ul class="nav-links" id="navLinks">
        <?php if ($user_role == 'guest'): ?>
            <!-- Guest Links -->
            <li><a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a></li>
            <li><a href="quests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'quests.php' ? 'active' : ''; ?>">Quests</a></li>
            <li><a href="leaderboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : ''; ?>">Leaderboard</a></li>
            <li><a href="rewards.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'rewards.php' ? 'active' : ''; ?>">Rewards</a></li>

        <?php elseif ($user_role == 'student'): ?>
            <!-- Student Links -->
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="quests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'quests.php' ? 'active' : ''; ?>">Quests</a></li>
            <li><a href="leaderboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : ''; ?>">Leaderboard</a></li>
            <li><a href="rewards.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'rewards.php' ? 'active' : ''; ?>">Rewards</a></li>
            <li><a href="validate.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'validate.php' ? 'active' : ''; ?>">My Submissions</a></li>

        <?php elseif ($user_role == 'moderator'): ?>
            <!-- Moderator Links (Main job is to approve student proofs) -->
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="quests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'quests.php' ? 'active' : ''; ?>">Quests</a></li>
            <li><a href="mod_review.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mod_review.php' ? 'active' : ''; ?>">Review Proofs (Mod)</a></li>
            <li><a href="leaderboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : ''; ?>">Leaderboard</a></li>

        <?php elseif ($user_role == 'admin'): ?>
            <!-- Admin Links (Full control over users, quests, rewards) -->
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="admin_panel.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : ''; ?>">Admin Panel</a></li>
            <li><a href="admin_users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">Manage Users</a></li>
            <li><a href="admin_quests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_quests.php' ? 'active' : ''; ?>">Manage Quests</a></li>

        <?php endif; ?>
    </ul>

    <!-- Auth/Logout Buttons -->
    <div class="nav-auth">
        <?php if ($is_logged_in): ?>
            <!-- Logout Button for all logged-in roles -->
            <span class="user-info">Hello, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</span>
            <a href="index.php" class="nav-btn-signup">Logout</a>
        <?php else: ?>
            <!-- Login/Register for Guests -->
            <a href="login.php" class="nav-btn-login">Login</a>
            <a href="register.php" class="nav-btn-signup">Register</a>
        <?php endif; ?>
    </div>

    <!-- Mobile Menu Toggle (Needs JS to function) -->
    <button class="nav-toggle" aria-label="Toggle navigation" onclick="toggleMenu()">
        &#9776;
    </button>
</nav>

<script>
    // Simple JavaScript for mobile menu toggling (based on previous CSS)
    function toggleMenu() {
        const navLinks = document.getElementById('navLinks');
        navLinks.classList.toggle('is-open');
    }
</script>
