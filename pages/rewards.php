<?php
// pages/rewards.php
session_start();

include("../includes/header.php");
include("../includes/navigation.php");

// Determine if the user is a logged-in student (only students can redeem)
$is_student = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student');
$user_points = $is_student ? 850 : 0; // Simulated points for testing

// --- DATABASE SIMULATION: Placeholder Rewards Data ---
// In a real application, this array will be fetched from a 'rewards' database table.
$rewards = [
        [
                'id' => 1,
                'name' => 'bilabila Mart Voucher (RM10)',
                'points_cost' => 500,
                'category' => 'Food & Drink',
                'stock' => 50,
                'image_url' => 'https://placehold.co/400x250/1D4C43/FAFAF0?text=Voucher', // Placeholder image
                'desc' => 'Your daily dose of snack, confirm can get energy to study!',
        ],
        [
                'id' => 2,
                'name' => 'EcoQuest Tumbler',
                'points_cost' => 1200,
                'category' => 'Merchandise',
                'stock' => 15,
                'image_url' => 'https://placehold.co/400x250/71B48D/1D4C43?text=Tumbler',
                'desc' => 'The official tumbler for plastic-free heroes. Looks cool, seriously.',
        ],
        [
                'id' => 3,
                'name' => 'ICN Ticket (10%)',
                'points_cost' => 200,
                'category' => 'Experience',
                'stock' => 100,
                'image_url' => 'https://placehold.co/400x250/2C3E50/FAFAF0?text=Event',
                'desc' => 'Get a 10% discount of ICN ticket',
        ],
        [
                'id' => 4,
                'name' => 'Plant A Tree',
                'points_cost' => 5000,
                'category' => 'Plant',
                'stock' => 5,
                'image_url' => 'https://placehold.co/400x250/FF9900/FAFAF0?text=Tree',
                'desc' => 'For those who really commit to saving the planet. Big, expensive reward.',
        ]
];
?>

    <main class="rewards-page">
        <div class="container">
            <h1 class="page-title">Rewards Marketplace! 🎁</h1>
            <p class="page-subtitle">Spend your hard-earned points on these cool rewards. Better be fast, limited stock only!</p>

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
                    ?>
                    <div class="reward-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>">
                        <div class="reward-image-container">
                            <img src="<?php echo $reward['image_url']; ?>" alt="<?php echo $reward['name']; ?>"
                                 onerror="this.onerror=null;this.src='https://placehold.co/400x250/2C3E50/FAFAF0?text=Reward';" class="reward-image">
                            <?php if ($is_out_of_stock): ?>
                                <span class="stock-overlay">SOLD OUT</span>
                            <?php endif; ?>
                        </div>
                        <div class="reward-content">
                            <h3 class="reward-title"><?php echo $reward['name']; ?></h3>
                            <p class="reward-desc"><?php echo $reward['desc']; ?></p>
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

                <?php if (empty($rewards)): ?>
                    <p class="no-rewards">Aiyo, no rewards available right now. Tell the admin to add some!</p>
                <?php endif; ?>
            </div>

        </div>
    </main>

<?php include("../includes/footer.php"); ?>