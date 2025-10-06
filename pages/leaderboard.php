<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoQuest Leaderboard</title>
</head>
<body>
    <?php
    // pages/leaderboard.php
    session_start();

    // --- DB Connection and Dependencies ---
    include("../config/db.php"); // Assuming this file establishes $conn
    include("../includes/header.php");

    // Get the current logged-in user ID
    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    $users = [];
    $db_error = '';
    $is_db_connected = isset($conn) && !$conn->connect_error;

    if (!$is_db_connected) {
        $db_error = 'Error: Database connection failed. Leaderboard cannot load data.';
    } else {
        // SQL to fetch users (students only), ordered by points (highest first)
        $sql = "SELECT user_id, username, user_role, total_points 
                FROM users 
                WHERE user_role = 'student'
                ORDER BY total_points DESC";
        
        if ($result = $conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                // Ensure user_id is named 'id' for compatibility with existing loop logic
                $row['id'] = $row['user_id'];
                $users[] = $row;
            }
            $result->free();
        } else {
            $db_error = 'Query failed: ' . $conn->error;
        }

        // Close the connection explicitly if it was successfully established
        // $conn->close(); // Optional, but usually closed by PHP automatically
    }

    // --- RANKING LOGIC (Applied to the fetched $users array) ---
    // The query already sorted them, so we just need to assign the rank.
    $rank = 0;
    $prev_points = -1;
    foreach ($users as $key => $user) {
        // Use user_id for the conditional in the table below
        $users[$key]['id'] = $user['user_id'];

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

            <?php if ($db_error): ?>
                <div class="message error-message"><?php echo $db_error; ?></div>
            <?php endif; ?>

            <!-- Controls (Filter is now mostly decorative since we only fetch students) -->
            <div class="quest-controls leaderboard-controls">
                <div class="filter-dropdown form-group">
                    <select id="rank-filter">
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
                        $role_class = strtolower($user['user_role']);
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
                                <span class="role-tag role-<?php echo $role_class; ?>"><?php echo ucfirst($user['user_role']); ?></span>
                            </td>
                            <td data-label="Total Points" class="points-cell">
                                <span class="total-points"><?php echo number_format($user['total_points']); ?></span> PTS
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($users) && !$db_error): ?>
                <p class="no-data">Aiyo, no one is on the leaderboard yet! Be the first one!</p>
            <?php endif; ?>

        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>
