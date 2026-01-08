<?php
// pages/profile.php
session_start();

include("../config/db.php");
include("../includes/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: sign_up.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'];
$db_error = '';
$user_data = null;
$user_badges = []; // For student badges

if (!isset($conn) || $conn->connect_error) {
    $db_error = 'Error: Database connection failed.';
} else {
    try {
        // --- Fetch user data based on their role ---
        $sql = '';

        switch ($current_user_role) {
            case 'student':
                $sql = "SELECT 
                            u.User_id, u.Username, u.Email, u.Role, u.Created_at,
                            s.Student_id, s.Total_point, s.Total_Exp_Point
                        FROM User u
                        JOIN Student s ON u.User_id = s.User_id
                        WHERE u.User_id = ?";
                break;
            case 'moderator':
                $sql = "SELECT 
                            u.User_id, u.Username, u.Email, u.Role, u.Created_at,
                            m.Moderator_id
                        FROM User u
                        JOIN Moderator m ON u.User_id = m.User_id
                        WHERE u.User_id = ?";
                break;
            case 'admin':
                $sql = "SELECT 
                            u.User_id, u.Username, u.Email, u.Role, u.Created_at,
                            a.Admin_id
                        FROM User u
                        JOIN Admin a ON u.User_id = a.User_id
                        WHERE u.User_id = ?";
                break;
            default:
                $db_error = 'Invalid user role found in session.';
                break;
        }

        if (empty($db_error) && !empty($sql)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
            } else {
                $db_error = 'User data not found. Please try logging in again.';
            }
            $stmt->close();
        }

        // --- Fetch badges ONLY if the user is a student ---
        if ($current_user_role === 'student' && $user_data) {
            $current_student_id = $_SESSION['student_id'];

            // Get Badges (from Badge table)
            $sql_badges = "
                SELECT b.Badge_Name, b.Badge_image, b.Require_Exp_Points
                FROM Student_Badge sb
                JOIN Badge b ON sb.Badge_id = b.Badge_id
                WHERE sb.Student_id = ?";
            
            if ($stmt_badge = $conn->prepare($sql_badges)) {
                $stmt_badge->bind_param("i", $current_student_id);
                $stmt_badge->execute();
                $result_badge = $stmt_badge->get_result();
                while ($row = $result_badge->fetch_assoc()) {
                    $user_badges[] = $row;
                }
                $stmt_badge->close();
            }
        }

    } catch (Exception $e) {
        $db_error = 'Query execution failed: ' . $e->getMessage();
    }
}
?>

<main class="profile-page">
    <div class="container">
        <h1 class="page-title">My EcoQuest Profile 👤</h1>
        <p class="page-subtitle">Your stats, your impact, your badges. Keep up the great work!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <div class="profile-card-simple">
                <div class="profile-grid">
                    <section class="profile-left">
                        <div class="profile-header-simple">
                            <div class="profile-avatar-simple">
                                <?php
                                    $role_icon = 'fa-user-circle'; // Default
                                    if ($user_data['Role'] === 'admin') $role_icon = 'fa-user-shield';
                                    if ($user_data['Role'] === 'moderator') $role_icon = 'fa-user-cog';
                                ?>
                                <i class="fas <?php echo $role_icon; ?>"></i>
                            </div>
                            <h2 class="profile-username-simple"><?php echo htmlspecialchars($user_data['Username']); ?></h2>
                            <span class="profile-role-simple role-<?php echo strtolower($user_data['Role']); ?>">
                                <?php echo ucfirst($user_data['Role']); ?>
                            </span>
                        </div>
                        <?php if ($current_user_role === 'student'): ?>
                            <div class="points-highlight">
                                <h4>Total Points (Leaderboard)</h4>
                                <p class="points-value-large"><?php echo number_format($user_data['Total_point']); ?></p>
                                <p class="points-label">PTS</p>
                            </div>
                            <div class="points-highlight" style="border-color: #f6ad55;">
                                <h4><i class="fas fa-medal"></i> Total EXP (Badges)</h4>
                                <p class="points-value-large" style="color: #f6ad55;"><?php echo number_format($user_data['Total_Exp_Point']); ?></p>
                                <p class="points-label">EXP</p>
                            </div>
                        <?php endif; ?>
                    </section>
                    <aside class="profile-right">
                        <div class="profile-details-list">
                            <div class="detail-item-simple">
                                <i class="fas fa-envelope"></i>
                                <h4>Email:</h4>
                                <p><?php echo htmlspecialchars($user_data['Email']); ?></p>
                            </div>
                            <div class="detail-item-simple">
                                <i class="fas fa-calendar-alt"></i>
                                <h4>Member Since:</h4>
                                <p><?php echo date('j F Y', strtotime($user_data['Created_at'])); ?></p>
                            </div>
                        </div>
                    </aside>
                </div>

                <?php if ($current_user_role === 'student'): ?>
                <div class="badges-section">
                    <h3 class="badges-title">My Badges (from EXP)</h3>
                    <?php if (empty($user_badges)): ?>
                        <p class="no-badges-msg">You haven't earned any EXP badges yet.</p>
                    <?php else: ?>
                        <div class="badges-container">
                            <?php foreach ($user_badges as $badge): ?>
                                <div class="badge-item" title="<?php echo htmlspecialchars($badge['Badge_Name']); ?> (<?php echo $badge['Require_Exp_Points']; ?> EXP)">
                                    <i class="<?php echo htmlspecialchars($badge['Badge_image'] ?? 'fas fa-shield-alt'); ?> badge-icon"></i>
                                    <span class="badge-name"><?php echo htmlspecialchars($badge['Badge_Name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="profile-actions-footer-simple">
                    <a href="edit_profile.php" class="btn-primary"><i class="fas fa-edit"></i> Edit Profile</a>
                    <a href="logout.php" class="btn-secondary"><i class="fas fa-sign-out-alt"></i> Log Out</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>