<?php
// pages/achievements.php
session_start();

include("../config/db.php");
include("../includes/header.php");

// Only students can view this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$achievements = [];
$db_error = '';

if (isset($conn) && !$conn->connect_error) {
    // This query gets ALL achievements and checks which ones the user has earned
    // (Based on Student_Achievement table)
    $sql = "
        SELECT
            a.Achievement_id,
            a.Title,
            a.Description,
            a.Exp_point,
            sa.Status
        FROM Achievement a
        LEFT JOIN Student_Achievement sa ON a.Achievement_id = sa.Achievement_id AND sa.Student_id = ?
        ORDER BY a.Exp_point ASC
    ";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $achievements[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $db_error = "Failed to load achievements: " . $e->getMessage();
    }
} else {
    $db_error = "Database connection failed.";
}
?>

<main class="rewards-page" style="padding: 40px 8%;">
    <div class="container">
        <h1 class="page-title">My Achievements 🏆</h1>
        <p class="page-subtitle">Completing quests can also unlock achievements, which give you bonus EXP for badges!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="achievement-grid">
            <?php foreach ($achievements as $ach): ?>
                <?php
                    // Check if the achievement has been earned
                    $is_earned = (isset($ach['Status']) && $ach['Status'] === 'Completed');
                    $card_class = $is_earned ? 'earned' : 'unearned';
                ?>
                <div class="ach-card <?php echo $card_class; ?>">
                    <div class="ach-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="ach-content">
                        <h3 class="ach-name"><?php echo htmlspecialchars($ach['Title']); ?></h3>
                        <p class="ach-desc"><?php echo htmlspecialchars($ach['Description']); ?></p>
                        <p class="ach-date" style="font-weight: bold; color: #f6ad55;">
                            +<?php echo htmlspecialchars($ach['Exp_point']); ?> EXP
                        </p>
                        <?php if (!$is_earned): ?>
                            <p class="ach-date">Status: Locked</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<style>
    .achievement-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
    .ach-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }
    .ach-card.unearned {
        opacity: 0.6;
        filter: grayscale(80%);
    }
    .ach-card.earned {
        border-left: 5px solid #FFC107; /* Gold border for earned badges */
    }
    .ach-icon {
        font-size: 2.5rem;
        flex-shrink: 0;
    }
    .ach-card.earned .ach-icon {
        color: #FFC107;
    }
    .ach-name {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: var(--color-primary);
    }
    .ach-desc {
        font-size: 0.9rem;
        color: var(--color-light-text);
        margin: 0;
    }
    .ach-date {
        font-size: 0.8rem;
        color: #aaa;
        font-style: italic;
        margin-top: 8px;
    }
</style>

<?php include("../includes/footer.php"); ?>