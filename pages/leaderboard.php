<?php
// pages/leaderboard.php
session_start();

include("../includes/header.php");
include("../includes/navigation.php");

// Set simulated logged-in user ID for highlighting
// Assuming a student with user_id = 3 is currently logged in
// In a real app, $_SESSION['user_id'] would be used.
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 3;

// --- DATABASE SIMULATION: Placeholder Leaderboard Data ---
// In a real application, this array will be fetched from a 'users' database table, 
// joining or calculating total points.
$users = [
        ['id' => 1, 'username' => 'SuperSaver', 'total_points' => 9500, 'role' => 'student'],
        ['id' => 2, 'username' => 'GreenChampion', 'total_points' => 7800, 'role' => 'student'],
        ['id' => 3, 'username' => 'AliBinStudent', 'total_points' => 7800, 'role' => 'student'], // Current User
        ['id' => 4, 'username' => 'EcoWarrior', 'total_points' => 6100, 'role' => 'student'],
        ['id' => 5, 'username' => 'ModBoss', 'total_points' => 500, 'role' => 'moderator'],
        ['id' => 6, 'username' => 'AdminHq', 'total_points' => 100, 'role' => 'admin'],
        ['id' => 7, 'username' => 'PlantLover', 'total_points' => 5900, 'role' => 'student'],
        ['id' => 8, 'username' => 'RecycleKing', 'total_points' => 4500, 'role' => 'student'],
];

// 1. Sort users by points (Highest to Lowest)
// PHP's usort function is used to sort the array
usort($users, function($a, $b) {
    return $b['total_points'] <=> $a['total_points'];
});

// 2. Assign Rank (handling ties)
$rank = 0;
$prev_points = -1;
foreach ($users as $key => $user) {
    // If points are different from the previous user, update the rank
    if ($user['total_points'] !== $prev_points) {
        $rank = $key + 1;
    }
    $users[$key]['rank'] = $rank;
    $prev_points = $user['total_points'];
}
?>

<main class="leaderboard-page">
    <div class="container">
        <h1 class="page-title">EcoQuest Leaderboard 🏆</h1>
        <p class="page-subtitle">Who's the ultimate planet champion? See the top users and their total impact points here!</p>

        <!-- Controls (Filter for roles) -->
        <div class="quest-controls leaderboard-controls">
            <div class="filter-dropdown form-group">
                <select id="rank-filter">
                    <option value="all">View: All Users</option>
                    <option value="student">View: Students Only</option>
                </select>
            </div>
        </div>

        <!-- Leaderboard Table Container -->
        <div class="leaderboard-table-container">
            <table class="leaderboard-table">
                <thead>
                <tr>
                    <th>Rank</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Total Points</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    // Check if this row is the current logged-in user
                    $is_current_user = $user['id'] == $current_user_id;
                    $row_class = $is_current_user ? 'current-user' : '';
                    $role_class = strtolower($user['role']);
                    ?>
                    <tr class="<?php echo $row_class; ?> <?php echo $role_class; ?>">
                        <td data-label="Rank" class="rank-cell">
                            <?php if ($user['rank'] <= 3): ?>
                                <!-- Display a medal icon for top 3 -->
                                <span class="medal medal-<?php echo $user['rank']; ?>">
                                    <?php
                                    if ($user['rank'] == 1) echo '🥇'; // Gold Medal
                                    if ($user['rank'] == 2) echo '🥈'; // Silver Medal
                                    if ($user['rank'] == 3) echo '🥉'; // Bronze Medal
                                    ?>
                                </span>
                            <?php else: ?>
                                <?php echo $user['rank']; ?>
                            <?php endif; ?>
                        </td>
                        <td data-label="User" class="user-cell">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td data-label="Role" class="role-cell">
                            <span class="role-tag role-<?php echo $role_class; ?>"><?php echo ucfirst($user['role']); ?></span>
                        </td>
                        <td data-label="Total Points" class="points-cell">
                            <span class="total-points"><?php echo number_format($user['total_points']); ?></span> PTS
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($users)): ?>
            <p class="no-data">Aiyo, no one is on the leaderboard yet! Be the first one!</p>
        <?php endif; ?>

    </div>
</main>

<?php include("../includes/footer.php"); ?>
