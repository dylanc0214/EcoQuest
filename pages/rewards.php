<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards Marketplace</title>
</head>
<body>
    <?php
    // pages/rewards.php
    session_start();

    // --- DB Connection and Dependencies ---
    // Ensure you have a working connection in db.php
    include("../config/db.php"); 
    include("../includes/header.php");

    $db_error = '';
    $user_points = 0;
    $rewards = [];
    $is_db_connected = isset($conn) && !$conn->connect_error;

    // Determine if the user is a logged-in student (only students can redeem)
    $is_student = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student' && isset($_SESSION['user_id']));
    $current_user_id = $_SESSION['user_id'] ?? null;

    if (!$is_db_connected) {
        $db_error = 'Error: Database connection failed. Cannot load data.';
    } else {
        
        // 1. FETCH USER POINTS (if logged in as student)
        if ($is_student) {
            // Using prepared statement for security
            $stmt = $conn->prepare("SELECT total_points FROM users WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $current_user_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($user_data = $result->fetch_assoc()) {
                        $user_points = (int) $user_data['total_points'];
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
        // FIX: Assumes 'reward_id', 'image_url', and 'description' columns now exist in the rewards table.
        $sql = "SELECT reward_id AS id, name, points_cost, category, stock, image_url, description 
                FROM rewards 
                ORDER BY points_cost ASC"; // Default sort low to high
        
        if ($result = $conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                // Rename 'description' to 'desc' for compatibility with existing HTML loop
                $row['desc'] = $row['description'];
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
                    <p class="points-tip">Want to redeem? <a href="login.php">Log in</a> as a Student to see your points and redeem rewards!</p>
                </div>
            <?php endif; ?>

            <!-- Filter Bar -->
            <div class="quest-controls reward-controls">
                <div class="filter-dropdown form-group">
                    <select id="reward-filter">
                        <option value="all">Filter by Category: All</option>
                        <option value="food">Food & Drink</option>
                        <option value="merch">Merchandise</option>
                        <option value="experience">Experience</option>
                        <option value="tech">Tech</option>
                    </select>
                </div>
                <div class="sort-dropdown form-group">
                    <select id="reward-sort">
                        <option value="low">Sort by Points (Low to High)</option>
                        <option value="high">Sort by Points (High to Low)</option>
                    </select>
                </div>
            </div>

            <!-- Rewards Grid -->
            <div class="reward-grid">
                <?php foreach ($rewards as $reward): ?>
                    <?php
                    $can_redeem = $is_student && ($user_points >= $reward['points_cost']) && ($reward['stock'] > 0);
                    $is_out_of_stock = $reward['stock'] == 0;
                    
                    // Fallback URL if image_url is empty
                    $image_url = !empty($reward['image_url']) ? htmlspecialchars($reward['image_url']) : 'https://placehold.co/400x250/2C3E50/FAFAF0?text=Reward';
                    ?>
                    <div class="reward-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                        <div class="reward-image-container">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($reward['name']); ?>"
                                onerror="this.onerror=null;this.src='https://placehold.co/400x250/2C3E50/FAFAF0?text=Reward';" class="reward-image">
                            <?php if ($is_out_of_stock): ?>
                                <span class="stock-overlay">SOLD OUT</span>
                            <?php endif; ?>
                        </div>
                        <div class="reward-content">
                            <h3 class="reward-title"><?php echo htmlspecialchars($reward['name']); ?></h3>
                            <p class="reward-desc"><?php echo htmlspecialchars($reward['desc']); ?></p>
                            <div class="reward-footer">
                                <span class="reward-cost"><?php echo number_format($reward['points_cost']); ?> PTS</span>

                                <?php if ($is_student): ?>
                                    <?php if ($is_out_of_stock): ?>
                                        <button class="btn-redeem btn-disabled" disabled>Out of Stock</button>
                                    <?php elseif ($can_redeem): ?>
                                        <!-- Link to redemption processing page (future file) -->
                                        <a href="redeem.php?id=<?php echo $reward['id']; ?>" class="btn-redeem btn-submit">Redeem Now</a>
                                    <?php else: ?>
                                        <button class="btn-redeem btn-disabled" disabled>Need <?php echo number_format($reward['points_cost'] - $user_points); ?> PTS More</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="btn-redeem btn-submit">Login to Redeem</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($rewards) && !$db_error): ?>
                    <p class="no-rewards">Aiyo, no rewards available right now. Tell the admin to add some!</p>
                <?php endif; ?>
            </div>

        </div>
    </main>

<?php include("../includes/footer.php"); ?>