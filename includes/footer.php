<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="../assets/css/style.css">
<body>
    <footer>
        <div class="footer-content" style="text-align: center;padding: 25px; background-color: #1D4C43; color: #FAFAF0; font-size: 0.9rem;">
            <img src="<?php echo $base_path; ?>assets/images/logo.PNG" alt="EcoQuest Logo" class="logo">
            <div class="footer-link" style="margin: 2% 0 3% 8%;">
                <a href="index.php" style="color: white;text-decoration: none;font-size: 18px;margin-right: 5%;">Home</a>
                <a href="about.php" style="color: white;text-decoration: none;font-size: 18px;margin-right: 5%;">About</a>
                <a href="quests.php" style="color: white;text-decoration: none;font-size: 18px;margin-right: 5%;">Quest</a>
                <a href="leaderboard.php" style="color: white;text-decoration: none;font-size: 18px;margin-right: 5%;">Leaderboard</a>
                <a href="rewards.php" style="color: white;text-decoration: none;font-size: 18px;margin-right: 5%;">Reward</a>
            </div>
            <div class="footer-copyright" style="font-size: 15px;">
                <p>&copy; <?php echo date("Y"); ?> EcoQuest. A project for APU's Responsive Web Design & Development.<br> APU Community: Go Green. Earn Rewards. Plant Trees.</p>
            </div>
        </div>
    </footer>

    <?php
    // Check if the user is a student or a guest
    // The $user_role variable is available from 'includes/header.php'
    if (isset($user_role) && ($user_role === 'student' || $user_role === 'guest')):
    ?>

    <button class="chat-toggle-button" id="faq-toggle-button">
        <i class="fas fa-question"></i>
    </button>

    <div class="chat-popup" id="faq-popup">
        <div class="chat-header">
            <h3>Frequently Asked Questions (FAQ)</h3>
            <button class="chat-close-btn" id="faq-close-btn">&times;</button>
        </div>
        
        <div class="chat-messages" id="faq-list"></div>
    </div>
    <?php
    endif; // End check for student or guest
    ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>