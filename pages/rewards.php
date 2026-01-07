<?php
// pages/rewards.php
session_start();

include("../config/db.php"); 
include("../includes/header.php");

$db_error = '';
$user_points = 0;
$rewards = [];
$is_db_connected = isset($conn) && !$conn->connect_error;

// Determine if the user is a logged-in student
$is_student = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student' && isset($_SESSION['student_id']));
$current_student_id = $_SESSION['student_id'] ?? null;

if (!$is_db_connected) {
    $db_error = 'Error: Database connection failed. Cannot load data.';
} else {
    // 1. FETCH USER POINTS
    if ($is_student) {
        $stmt = $conn->prepare("SELECT Total_point FROM Student WHERE Student_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $current_student_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($user_data = $result->fetch_assoc()) {
                    $user_points = (int) $user_data['Total_point'];
                }
            } else {
                $db_error = 'Could not fetch user points: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $db_error = 'User points query preparation failed: ' . $conn->error;
        }
    }
    
    // 2. FETCH ALL REWARDS
    $sql = "SELECT Reward_id, Reward_name, Points_cost, Stock, Image_url, Description 
            FROM Reward 
            WHERE Is_active = 1
            ORDER BY Points_cost ASC";
    
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $rewards[] = $row;
        }
        $result->free();
    } else {
        $db_error = 'Could not fetch rewards: ' . $conn->error;
    }
}
?>

<main class="rewards-page">
    <div class="container">
        <h1 class="page-title">Rewards Marketplace! 🎁</h1>
        <p class="page-subtitle">Spend your hard-earned points on these cool rewards. Better be fast, limited stock only!</p>

        <?php if ($db_error): ?>
            <div class="message error-error"><?php echo $db_error; ?></div>
        <?php endif; ?>

        <?php if ($is_student): ?>
            <div class="user-points-summary">
                <p>Your current balance: <span class="points-balance"><?php echo number_format($user_points); ?> PTS</span></p>
                <?php if ($user_points < 500): ?>
                    <p class="points-tip">A bit low, right? Go complete more <a href="quests.php">quests</a> to earn points!</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="user-points-summary">
                <p class="points-tip">Want to redeem? <a href="sign_up.php">Log in</a> as a Student to see your points and redeem rewards!</p>
            </div>
        <?php endif; ?>

        <div class="reward-grid">
            <?php foreach ($rewards as $reward): ?>
                <?php
                $can_redeem = $is_student && ($user_points >= $reward['Points_cost']) && ($reward['Stock'] > 0 || $reward['Stock'] == -1);
                $is_out_of_stock = $reward['Stock'] == 0;

                // FIXED PATH LOGIC
                if (!empty($reward['Image_url'])) {
                    // Replace '../../' with '../' to correctly point to the root assets folder from the pages/ folder
                    $image_url = str_replace('../../', '../', $reward['Image_url']);
                    $image_url = htmlspecialchars($image_url);
                } else {
                    $image_url = 'https://placehold.co/400x250/2C3E50/FAFAF0?text=Reward';
                }
                ?>
                <div class="reward-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                    <div class="reward-image-container">
                        <img src="<?php echo $image_url; ?>" 
                             alt="<?php echo htmlspecialchars($reward['Reward_name']); ?>" 
                             class="reward-image" 
                             onerror="this.src='https://placehold.co/400x250/2C3E50/FAFAF0?text=Image+Not+Found'">
                        
                        <?php if ($is_out_of_stock): ?>
                            <span class="stock-overlay">SOLD OUT</span>
                        <?php endif; ?>
                    </div>
                    <div class="reward-content">
                        <h3 class="reward-title"><?php echo htmlspecialchars($reward['Reward_name']); ?></h3>
                        <p class="reward-desc"><?php echo htmlspecialchars($reward['Description']); ?></p>
                        <div class="reward-footer">
                            <span class="reward-cost"><?php echo number_format($reward['Points_cost']); ?> PTS</span>
                            <?php if ($is_student): ?>
                                <?php if ($is_out_of_stock): ?>
                                    <button class="btn-redeem btn-disabled" disabled>Out of Stock</button>
                                <?php elseif ($can_redeem): ?>
                                    <a href="redeem.php?id=<?php echo $reward['Reward_id']; ?>" class="btn-redeem btn-submit">Redeem Now</a>
                                <?php else: ?>
                                    <button class="btn-redeem btn-disabled" disabled>Need <?php echo number_format($reward['Points_cost'] - $user_points); ?> PTS More</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="sign_up.php" class="btn-redeem btn-submit">Login to Redeem</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<style>
/* Ensuring images cover the area correctly like in image_b0e4c6.png */
.reward-image {
    width: 100%;
    height: 100%;
    object-fit: cover; 
    display: block;
}
.reward-image-container {
    width: 100%;
    height: 250px;
    overflow: hidden;
    position: relative;
    background-color: #f0f0f0;
}
.reward-cost {
    font-weight: bold;
    color: #F39C12; 
}
</style>

<?php include("../includes/footer.php"); ?>
</body>
</html>