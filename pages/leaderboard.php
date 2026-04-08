<?php
// pages/leaderboard.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once(__DIR__ . "/../config/db.php");
include("../includes/header.php");

// Get the current logged-in student ID
$current_student_id = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'student' && isset($_SESSION['student_id'])) {
    $current_student_id = $_SESSION['student_id'];
}

$users = [];
$db_error = '';

if (!isset($conn) || $conn->connect_error) {
    $db_error = 'Error: Database connection failed. Leaderboard cannot load data.';
} else {
    // --- UPDATED SQL QUERY (joins User and Student) ---
    $sql = "SELECT 
                s.Student_id, 
                u.Username, 
                s.Total_point,
                u.Role
            FROM student s
            JOIN user u ON s.User_id = u.User_id
            ORDER BY s.Total_point DESC";

    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $result->free();
    } else {
        $db_error = 'Query failed: ' . $conn->error;
    }
}

// --- RANKING LOGIC (Updated column name) ---
$rank = 0;
$prev_points = -1;
foreach ($users as $key => $user) {
    if ($user['Total_point'] !== $prev_points) {
        $rank = $key + 1;
    }
    $users[$key]['rank'] = $rank;
    $prev_points = $user['Total_point'];
}
?>

<main class="leaderboard-page">
    <div class="container">
        <h1 class="page-title">EcoQuest Leaderboard 🏆</h1>
        <p class="page-subtitle">Who's the ultimate planet champion? See the top students and their total impact points here!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo $db_error; ?></div>
        <?php endif; ?>

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
                    <?php if (empty($users) && !$db_error): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">Aiyo, no one is on the leaderboard yet!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                            // Check against 'Student_id'
                            $is_current_user = ($current_student_id !== null && $user['Student_id'] == $current_student_id);
                            $row_class = $is_current_user ? 'current-user' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td data-label="Rank" class="rank-cell">
                                    <?php if ($user['rank'] <= 3): ?>
                                        <span class="medal medal-<?php echo $user['rank']; ?>">
                                            <?php
                                            if ($user['rank'] == 1) echo '🥇';
                                            if ($user['rank'] == 2) echo '🥈';
                                            if ($user['rank'] == 3) echo '🥉';
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo $user['rank']; ?>
                                    <?php endif; ?>
                                </td>
                                <td data-label="User" class="user-cell">
                                    <?php echo htmlspecialchars($user['Username']); ?>
                                </td>
                                <td data-label="Role" class="role-cell">
                                    <span class="role-tag role-student"><?php echo ucfirst($user['Role']); ?></span>
                                </td>
                                <td data-label="Total Points" class="points-cell">
                                    <span class="total-points"><?php echo number_format($user['Total_point']); ?></span> PTS
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>