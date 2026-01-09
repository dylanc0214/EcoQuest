<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoQuest</title>

    <style>
        /* ===== FOOTER STYLES ===== */
        .site-footer{
            background:#1D4C43;
            color:#FAFAF0;
            padding:30px 15px;
            text-align:center;
        }

        .footer-logo{
            max-width:120px;
            margin-bottom:20px;
        }

        .footer-links{
            display:flex;
            flex-wrap:wrap;
            justify-content:center;
            gap:15px;
            margin-bottom:20px;
        }

        .footer-links a{
            color:white;
            text-decoration:none;
            font-size:16px;
        }

        .footer-copy{
            font-size:14px;
            line-height:1.5;
        }

        /* MOBILE */
        @media (max-width:768px){
            .footer-links{
                flex-direction:column;
                gap:10px;
            }
        }
    </style>
</head>

<body>

<footer class="site-footer">

    <img src="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/images/logo.PNG"
         alt="EcoQuest Logo"
         class="footer-logo">

    <div class="footer-links">
        <?php
        $bp   = isset($base_path) ? $base_path : '/Group7_EcoQuest/';
        $role = $_SESSION['user_role'] ?? 'guest';

        if ($role === 'guest') {
            echo "<a href='{$bp}pages/about.php'>About</a>";
            echo "<a href='{$bp}pages/leaderboard.php'>Leaderboard</a>";
            echo "<a href='{$bp}pages/rewards.php'>Rewards</a>";

        } elseif ($role === 'student') {
            echo "<a href='{$bp}pages/student/dashboard.php'>Dashboard</a>";
            echo "<a href='{$bp}pages/quests.php'>Quests</a>";
            echo "<a href='{$bp}pages/leaderboard.php'>Leaderboard</a>";
            echo "<a href='{$bp}pages/rewards.php'>Rewards</a>";
            echo "<a href='{$bp}pages/student/my_rewards.php'>Claimed</a>";
            echo "<a href='{$bp}pages/student/achievements.php'>Achievements</a>";
            echo "<a href='{$bp}pages/student/validate.php'>Submissions</a>";
            echo "<a href='{$bp}pages/forum.php'>Forum</a>";
            echo "<a href='{$bp}pages/student/feedback.php'>Feedback</a>";

        } elseif ($role === 'moderator') {
            echo "<a href='{$bp}pages/moderator/dashboard.php'>Dashboard</a>";
            echo "<a href='{$bp}pages/moderator/manage_submissions.php'>Submissions</a>";
            echo "<a href='{$bp}pages/moderator/manage_reports.php'>Reports</a>";
            echo "<a href='{$bp}pages/moderator/manage_users.php'>Users</a>";
            echo "<a href='{$bp}pages/moderator/manage_quests.php'>Quests</a>";
            echo "<a href='{$bp}pages/moderator/manage_rewards.php'>Rewards</a>";
            echo "<a href='{$bp}pages/forum.php'>Forum</a>";

        } elseif ($role === 'admin') {
            echo "<a href='{$bp}pages/admin/dashboard.php'>Dashboard</a>";
            echo "<a href='{$bp}pages/admin/manage_submissions.php'>Validate</a>";
            echo "<a href='{$bp}pages/admin/manage_users.php'>Users</a>";
            echo "<a href='{$bp}pages/admin/moderation_records.php'>Mod Log</a>";
            echo "<a href='{$bp}pages/admin/manage_quests.php'>Quests</a>";
            echo "<a href='{$bp}pages/admin/manage_badges.php'>Badges</a>";
            echo "<a href='{$bp}pages/admin/manage_rewards.php'>Rewards</a>";
            echo "<a href='{$bp}pages/forum.php'>Forum</a>";
            echo "<a href='{$bp}pages/admin/view_feedback.php'>Feedback</a>";
        }
        ?>
    </div>

    <p class="footer-copy">
        © <?php echo date("Y"); ?> EcoQuest.<br>
        A project for APU Responsive Web Design & Development.<br>
        APU Community: Go Green. Earn Rewards. Plant Trees.
    </p>

</footer>

<?php
// FAQ Chat (Student & Guest only)
if ($role === 'student' || $role === 'guest'):
?>

<button class="chat-toggle-button" id="faq-toggle-button">
    <i class="fas fa-question"></i>
</button>

<div class="chat-popup" id="faq-popup">
    <div class="chat-header">
        <h3>Frequently Asked Questions</h3>
        <button class="chat-close-btn" id="faq-close-btn">&times;</button>
    </div>
    <div class="chat-messages" id="faq-list"></div>
</div>

<?php endif; ?>

<script src="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/js/main.js"></script>

</body>
</html>
