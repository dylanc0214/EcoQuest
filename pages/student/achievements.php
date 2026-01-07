<?php
// pages/achievements.php
session_start();
include("../../config/db.php");
include("../../includes/header.php");

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$newly_unlocked = [];

// ==========================================
// 1. GET STUDENT STATS 📊
// ==========================================
// We still need to know how many quests/posts you have!
if ($conn) {
    $sql_stats = "
        SELECT 
            s.Total_Exp_Point,
            (SELECT COUNT(*) FROM student_quest_submissions WHERE Student_id = s.Student_id AND Status = 'completed') as quest_count,
            (SELECT COUNT(*) FROM post WHERE Student_id = s.Student_id) as post_count
        FROM student s
        WHERE s.Student_id = ?
    ";
    $stmt = $conn->prepare($sql_stats);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $xp = $stats['Total_Exp_Point'] ?? 0;
    $quests = $stats['quest_count'] ?? 0;
    $posts = $stats['post_count'] ?? 0;

    // ==========================================
    // 2. CHECK BADGES (XP Based)
    // ==========================================
    // Added WHERE Is_active = 1 to only process active badges
    $db_badges = $conn->query("SELECT * FROM badge WHERE Is_active = 1")->fetch_all(MYSQLI_ASSOC);
    foreach ($db_badges as $b) {
        if ($xp >= $b['Require_Exp_Points']) {
            unlock_item($conn, $student_id, 'badge', $b['Badge_id'], $b['Badge_Name'], $newly_unlocked);
        }
    }

    // ==========================================
    // 3. CHECK ACHIEVEMENTS (The Simple Way) 👇
    // ==========================================
    // No more "Brain" array. Just straight logic.
    // IDs match your ecoquest.sql

    // Quest Milestone 1: Green Beginner (1 Quest)
    if ($quests >= 1) {
        unlock_item($conn, $student_id, 'achievement', 1, 'Green Beginner', $newly_unlocked);
    }

    // Quest Milestone 2: Eco Warrior (5 Quests)
    if ($quests >= 5) {
        unlock_item($conn, $student_id, 'achievement', 2, 'Eco Warrior', $newly_unlocked);
    }

    // Quest Milestone 3: Planet Savior (10 Quests)
    if ($quests >= 10) {
        unlock_item($conn, $student_id, 'achievement', 3, 'Planet Savior', $newly_unlocked);
    }

    // Post Milestone 1: Voice of Change (1 Post)
    if ($posts >= 1) {
        unlock_item($conn, $student_id, 'achievement', 4, 'Voice of Change', $newly_unlocked);
    }

    // Post Milestone 2: Community Leader (5 Posts)
    if ($posts >= 5) {
        unlock_item($conn, $student_id, 'achievement', 5, 'Community Leader', $newly_unlocked);
    }
}

// ==========================================
// HELPER FUNCTION (To avoid repeating code)
// ==========================================
function unlock_item($conn, $sid, $type, $item_id, $name, &$unlocked_list) {
    if ($type == 'badge') {
        $check = $conn->query("SELECT 1 FROM student_badge WHERE Student_id = $sid AND Badge_id = $item_id");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO student_badge (Student_id, Badge_id, Earned_Date) VALUES ($sid, $item_id, NOW())");
            $unlocked_list[] = $name;
        }
    } else {
        $check = $conn->query("SELECT 1 FROM student_achievement WHERE Student_id = $sid AND Achievement_id = $item_id");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO student_achievement (Student_id, Achievement_id, Status) VALUES ($sid, $item_id, 'Unlocked')");
            $unlocked_list[] = $name;
        }
    }
}

// ==========================================
// 4. FETCH DATA FOR DISPLAY
// ==========================================
$my_badges = [];
$my_achievements = [];

if ($conn) {
    // Fetch Badges - Added WHERE Is_active = 1 to hide inactive badges from the UI
    $sql_b = "SELECT b.*, sb.Earned_Date 
              FROM badge b 
              LEFT JOIN student_badge sb ON b.Badge_id = sb.Badge_id AND sb.Student_id = $student_id 
              WHERE b.Is_active = 1
              ORDER BY b.Require_Exp_Points ASC";
    $my_badges = $conn->query($sql_b)->fetch_all(MYSQLI_ASSOC);

    // Fetch Achievements
    $sql_a = "SELECT a.*, sa.Status FROM achievement a LEFT JOIN student_achievement sa ON a.Achievement_id = sa.Achievement_id AND sa.Student_id = $student_id";
    $my_achievements = $conn->query($sql_a)->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="dashboard-page">
    <div class="container">
        
        <header class="page-header" style="text-align: center; margin-bottom: 40px;">
            <h1 class="page-title">Your Trophy Case 🏆</h1>
            <p class="page-subtitle">
                Current Stats: 
                <span class="stat-tag">⭐ <?php echo $xp; ?> XP</span>
                <span class="stat-tag">✅ <?php echo $quests; ?> Quests</span>
                <span class="stat-tag">📝 <?php echo $posts; ?> Posts</span>
            </p>
        </header>

        <?php if (!empty($newly_unlocked)): ?>
            <div class="message success-message" style="text-align: center; margin-bottom: 30px; animation: popIn 0.5s;">
                <h3>🎉 Awesome! New Unlocks!</h3>
                <p>You earned: <strong><?php echo implode(', ', $newly_unlocked); ?></strong></p>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Rank Badges</h2>
        <div class="achievement-grid">
            <?php foreach ($my_badges as $badge): ?>
                <?php $is_unlocked = !is_null($badge['Earned_Date']); ?>
                <div class="achievement-card <?php echo $is_unlocked ? 'unlocked' : 'locked'; ?>">
                    <div class="badge-icon"><?php echo $badge['Badge_image']; ?></div>
                    <div class="badge-info">
                        <h3 class="badge-title"><?php echo htmlspecialchars($badge['Badge_Name']); ?></h3>
                        <?php if ($is_unlocked): ?>
                            <span class="badge-date">Unlocked!</span>
                        <?php else: ?>
                            <div class="badge-req">Requires <?php echo $badge['Require_Exp_Points']; ?> XP</div>
                            <span class="badge-status-lock"><i class="fas fa-lock"></i> Locked</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 class="section-title" style="margin-top: 40px;">Activity Milestones</h2>
        <div class="achievement-grid">
            <?php foreach ($my_achievements as $ach): ?>
                <?php 
                    $is_unlocked = !empty($ach['Status']);
                    $id = $ach['Achievement_id'];
                    
                    // Simple Icon Switch - Hardcoded visual logic
                    $icon = '🏅'; // Default
                    switch($id) {
                        case 1: $icon = '🌱'; break; // Green Beginner
                        case 2: $icon = '⚔️'; break; // Eco Warrior
                        case 3: $icon = '🌍'; break; // Planet Savior
                        case 4: $icon = '📢'; break; // Voice of Change
                        case 5: $icon = '🤝'; break; // Community Leader
                    }
                ?>
                <div class="achievement-card <?php echo $is_unlocked ? 'unlocked' : 'locked'; ?>">
                    <div class="badge-icon"><?php echo $icon; ?></div>
                    <div class="badge-info">
                        <h3 class="badge-title"><?php echo htmlspecialchars($ach['Title']); ?></h3>
                        <p class="badge-desc"><?php echo htmlspecialchars($ach['Description']); ?></p>
                        <?php if ($is_unlocked): ?>
                            <span class="badge-date">Completed!</span>
                        <?php else: ?>
                            <span class="badge-status-lock"><i class="fas fa-lock"></i> Locked</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</main>

<style>
    /* Stats Tag */
    .stat-tag { background: #e0f2f1; color: #1D4C43; padding: 5px 10px; border-radius: 15px; font-weight: 600; margin: 0 5px; font-size: 0.9rem; }
    
    /* Section Title */
    .section-title { font-size: 1.5rem; color: #2C3E50; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
    
    /* Grid */
    .achievement-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }

    /* Card */
    .achievement-card {
        background: #fff; border-radius: 12px; padding: 25px 15px; text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: all 0.3s ease;
        border: 2px solid transparent; position: relative;
    }

    /* Unlocked State */
    .achievement-card.unlocked { border-color: #71B48D; background: linear-gradient(to bottom right, #ffffff, #f0fff4); }
    .achievement-card.unlocked:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(113, 180, 141, 0.2); }
    .achievement-card.unlocked .badge-icon { font-size: 3rem; margin-bottom: 10px; animation: float 3s infinite ease-in-out; }
    .achievement-card.unlocked .badge-title { color: #1D4C43; font-weight: 800; }

    /* Locked State */
    .achievement-card.locked { background: #f9f9f9; opacity: 0.8; filter: grayscale(90%); }
    .achievement-card.locked:hover { filter: grayscale(0%); opacity: 1; }
    .achievement-card.locked .badge-icon { font-size: 3rem; margin-bottom: 10px; opacity: 0.5; }
    .achievement-card.locked .badge-title { color: #888; font-weight: 600; }
    
    /* Text */
    .badge-desc { font-size: 0.85rem; color: #666; margin-bottom: 10px; min-height: 35px; }
    .badge-date { font-size: 0.8rem; color: #71B48D; font-weight: 700; display: block; background: #e6fffa; padding: 4px 10px; border-radius: 20px; margin: 5px auto 0; width: fit-content;}
    .badge-status-lock { font-size: 0.8rem; color: #999; font-weight: 600; display: block; }
    .badge-req { font-size: 0.75rem; color: #e53e3e; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }

    @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-5px); } 100% { transform: translateY(0px); } }
    @keyframes popIn { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
</style>

<?php include("../../includes/footer.php"); ?>