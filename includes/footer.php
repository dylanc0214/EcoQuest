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
            <img src="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/images/logo.PNG" alt="EcoQuest Logo" class="logo">
            
            <div class="footer-link" style="margin: 2% 0 3% 8%;">
                <?php
                // Define style once to keep it clean ✨
                $s = 'color: white; text-decoration: none; font-size: 18px; margin-right: 5%; display: inline-block; margin-bottom: 10px;';
                
                // Ensure base_path is set (fallback to root if needed)
                $bp = isset($base_path) ? $base_path : '/Group7_EcoQuest/';
                
                // Ensure user_role is set
                $role = isset($user_role) ? $user_role : 'guest';

                // --- GUEST ---
                if ($role == 'guest') {
                    echo "<a href='{$bp}pages/about.php' style='$s'>About</a>";
                    echo "<a href='{$bp}pages/leaderboard.php' style='$s'>Leaderboard</a>";
                    echo "<a href='{$bp}pages/rewards.php' style='$s'>Rewards</a>";
                
                // --- STUDENT ---
                } elseif ($role == 'student') {
                    echo "<a href='{$bp}pages/dashboard.php' style='$s'>Dashboard</a>";
                    echo "<a href='{$bp}pages/quests.php' style='$s'>Quests</a>";
                    echo "<a href='{$bp}pages/leaderboard.php' style='$s'>Leaderboard</a>";
                    echo "<a href='{$bp}pages/rewards.php' style='$s'>Rewards</a>";
                    echo "<a href='{$bp}pages/my_rewards.php' style='$s'>Claimed</a>";
                    echo "<a href='{$bp}pages/achievements.php' style='$s'>Achievements</a>";
                    echo "<a href='{$bp}pages/validate.php' style='$s'>Submissions</a>";
                    echo "<a href='{$bp}pages/forum.php' style='$s'>Forum</a>";
                    echo "<a href='{$bp}pages/feedback.php' style='$s'>Feedback</a>";

                // --- MODERATOR ---
                } elseif ($role == 'moderator') {
                    echo "<a href='{$bp}pages/moderator/dashboard.php' style='$s'>Dashboard</a>";
                    echo "<a href='{$bp}pages/moderator/manage_submissions.php' style='$s'>Submissions</a>";
                    echo "<a href='{$bp}pages/moderator/manage_reports.php' style='$s'>Reports</a>";
                    echo "<a href='{$bp}pages/moderator/manage_users.php' style='$s'>Users</a>";
                    echo "<a href='{$bp}pages/moderator/manage_quests.php' style='$s'>Quests</a>";
                    echo "<a href='{$bp}pages/moderator/manage_rewards.php' style='$s'>Rewards</a>";
                    echo "<a href='{$bp}pages/forum.php' style='$s'>Forum</a>";

                // --- ADMIN ---
                } elseif ($role == 'admin') {
                    echo "<a href='{$bp}pages/admin/dashboard.php' style='$s'>Dashboard</a>";
                    echo "<a href='{$bp}pages/admin/manage_submissions.php' style='$s'>Validate</a>";
                    echo "<a href='{$bp}pages/admin/manage_users.php' style='$s'>Users</a>";
                    echo "<a href='{$bp}pages/admin/moderation_records.php' style='$s'>Mod Log</a>";
                    echo "<a href='{$bp}pages/admin/manage_quests.php' style='$s'>Quests</a>";
                    echo "<a href='{$bp}pages/admin/manage_rewards.php' style='$s'>Rewards</a>";
                    echo "<a href='{$bp}pages/forum.php' style='$s'>Forum</a>";
                    echo "<a href='{$bp}pages/admin/view_feedback.php' style='$s'>Feedback</a>";
                }
                ?>
            </div>

            <div class="footer-copyright" style="font-size: 15px;">
                <p>&copy; <?php echo date("Y"); ?> EcoQuest. A project for APU's Responsive Web Design & Development.<br> APU Community: Go Green. Earn Rewards. Plant Trees.</p>
            </div>
        </div>
    </footer>

    <?php
    // Chat Toggle Logic
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
        
        <div class="chat-messages" id="faq-list">
        </div>
    </div>
    <?php
    endif; 
    ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>