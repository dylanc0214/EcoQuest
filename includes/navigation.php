<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>
<body>
    <nav class="main-nav">
    <div class="nav-brand">
        <a href="index.php">
            <img src="../assets/images/logo.PNG" alt="EcoQuest Logo" class="logo">
            <span class="app-title">EcoQuest</span>
        </a>
    </div>

    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="quests.php">Quests</a></li>
        <li><a href="leaderboard.php">Leaderboard</a></li>
        <li><a href="rewards.php">Rewards</a></li>
        <li class="admin-link"><a href="admin/manage_quests.php">Admin Panel</a></li>
        <li class="moderator-link"><a href="moderator/validate_submissions.php">Validate</a></li>
    </ul>

    <div class="nav-auth">
        <a href="login.php" class="nav-btn-login">Login</a>
        <a href="register.php" class="nav-btn-signup">Register</a>
    </div>

    <button class="nav-toggle" aria-label="Toggle navigation">
        <span class="burger-line"></span>
        <span class="burger-line"></span>
        <span class="burger-line"></span>
    </button>
</nav>
</body>
</html>