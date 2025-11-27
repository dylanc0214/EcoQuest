<?php
// pages/my_rewards.php
session_start();

include("../config/db.php");
include("../includes/header.php");

// Authorization: Only logged-in students can see this page.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$redeemed_rewards = [];
$db_error = '';

if (isset($conn) && !$conn->connect_error) {
    // UPDATED: Query Redemption_History and join Reward
    $sql = "
        SELECT
            r.Reward_name,
            r.Description,
            rd.Points_used,
            rd.Redemption_date
        FROM Redemption_History rd
        JOIN Reward r ON rd.Reward_id = r.Reward_id
        WHERE rd.Student_id = ?
        ORDER BY rd.Redemption_date DESC
    ";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $redeemed_rewards[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $db_error = "Failed to load your rewards history: " . $e->getMessage();
    }
} else {
    $db_error = "Database connection failed.";
}
?>

<main class="rewards-page" style="padding: 40px 8%;">
    <div class="container">
        <h1 class="page-title">My Claimed Rewards 🎟️</h1>
        <p class="page-subtitle">This is your collection of redeemed items. Show this page to the staff to claim your vouchers or merch!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if (empty($redeemed_rewards) && !$db_error): ?>
            <div class="empty-state" style="text-align: center; padding: 40px; background: #fff; border-radius: 12px;">
                <h3>Aiyo, no rewards redeemed yet!</h3>
                <p>Go earn some more points and check out the marketplace.</p>
                <a href="rewards.php" class="btn-primary" style="margin-top: 20px;">Go to Marketplace</a>
            </div>
        <?php else: ?>
            <div class="reward-grid">
                <?php foreach ($redeemed_rewards as $reward): ?>
                    <div class="reward-card" style="opacity: 1; border-left: 5px solid var(--color-accent);">
                        <div class="reward-content">
                            <h3 class="reward-title"><?php echo htmlspecialchars($reward['Reward_name']); ?></h3>
                            <p class="reward-desc" style="margin-bottom: 10px;"><?php echo htmlspecialchars($reward['Description']); ?></p>
                            <div class="reward-footer" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                                <span class="reward-cost" style="font-size: 1.2rem; color: var(--color-error);">- <?php echo number_format($reward['Points_used']); ?> PTS</span>
                                <span style="font-size: 0.9rem; color: #555;">
                                    <strong>Claimed On:</strong> <?php echo date('d M Y, h:i A', strtotime($reward['Redemption_date'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>