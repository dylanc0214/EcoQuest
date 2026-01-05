<?php
// pages/view_student.php
require_once '../includes/header.php';

$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../index.php?error=unauthorized');
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$error_message = null;
$student = null;
$student_achievements = [];
$student_badges = [];

if (!$conn) {
    $error_message = 'Database connection failed.';
} else {
    try {
        $sql = "SELECT u.User_id, u.Username, u.Email, u.Role, u.Created_at, s.Student_id, s.Total_point, s.Total_Exp_Point
                FROM User u
                JOIN Student s ON u.User_id = s.User_id
                WHERE s.Student_id = ? LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $student = $res->fetch_assoc();
            } else {
                $error_message = 'Student not found.';
            }
            $stmt->close();
        }

        if ($student) {
            // Achievements
            $sqlAch = "SELECT a.Title, a.Description, a.Exp_point
                       FROM Student_Achievement sa
                       JOIN Achievement a ON sa.Achievement_id = a.Achievement_id
                       WHERE sa.Student_id = ? AND sa.Status = 'Completed'";
            if ($s2 = $conn->prepare($sqlAch)) {
                $s2->bind_param('i', $student_id);
                $s2->execute();
                $r2 = $s2->get_result();
                while ($row = $r2->fetch_assoc()) $student_achievements[] = $row;
                $s2->close();
            }

            // Badges
            $sqlBadge = "SELECT b.Badge_Name, b.Badge_image, b.Require_Exp_Points
                         FROM Student_Badge sb
                         JOIN Badge b ON sb.Badge_id = b.Badge_id
                         WHERE sb.Student_id = ?";
            if ($s3 = $conn->prepare($sqlBadge)) {
                $s3->bind_param('i', $student_id);
                $s3->execute();
                $r3 = $s3->get_result();
                while ($row = $r3->fetch_assoc()) $student_badges[] = $row;
                $s3->close();
            }
        }

    } catch (Exception $e) {
        $error_message = 'Query error: ' . $e->getMessage();
    }
}
?>

<main class="page-content view-student">
    <div class="container">
        <a href="forum.php" class="btn-link">← Back</a>
        <h1 class="page-title">View Student</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($student): ?>
            <div class="profile-card-simple">
                <div class="profile-grid">
                    <section class="profile-left">
                        <div class="profile-header-simple">
                            <div class="profile-avatar-simple">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h2 class="profile-username-simple"><?php echo htmlspecialchars($student['Username']); ?></h2>
                            <span class="profile-role-simple">Student</span>
                        </div>

                        <div class="points-highlight">
                            <h4><i class="fas fa-star"></i> Total Points</h4>
                            <p class="points-value-large"><?php echo number_format($student['Total_point']); ?></p>
                            <p class="points-label">PTS</p>
                        </div>
                        <div class="points-highlight" style="border-color:#f6ad55;">
                            <h4><i class="fas fa-medal"></i> Total EXP</h4>
                            <p class="points-value-large" style="color:#f6ad55"><?php echo number_format($student['Total_Exp_Point']); ?></p>
                            <p class="points-label">EXP</p>
                        </div>
                    </section>

                    <aside class="profile-right">
                        <div class="profile-details-list">
                            <div class="detail-item-simple">
                                <i class="fas fa-envelope"></i>
                                <h4>Email:</h4>
                                <p><?php echo htmlspecialchars($student['Email']); ?></p>
                            </div>
                            <div class="detail-item-simple">
                                <i class="fas fa-calendar-alt"></i>
                                <h4>Member Since:</h4>
                                <p><?php echo date('j F Y', strtotime($student['Created_at'])); ?></p>
                            </div>
                        </div>
                    </aside>
                </div>

                <div class="badges-section">
                    <h3 class="badges-title">Badges</h3>
                    <?php if (empty($student_badges)): ?>
                        <p class="no-badges-msg">No badges earned yet.</p>
                    <?php else: ?>
                        <div class="badges-container">
                            <?php foreach ($student_badges as $b): ?>
                                <div class="badge-item" title="<?php echo htmlspecialchars($b['Badge_Name']); ?>">
                                    <i class="<?php echo htmlspecialchars($b['Badge_image'] ?? 'fas fa-shield-alt'); ?> badge-icon"></i>
                                    <span class="badge-name"><?php echo htmlspecialchars($b['Badge_Name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="badges-section" style="border-top:1px dashed #eee;margin-top:15px;padding-top:15px;">
                    <h3 class="badges-title">Achievements</h3>
                    <?php if (empty($student_achievements)): ?>
                        <p class="no-badges-msg">No achievements yet.</p>
                    <?php else: ?>
                        <div class="badges-container">
                            <?php foreach ($student_achievements as $ach): ?>
                                <div class="badge-item" title="<?php echo htmlspecialchars($ach['Description']); ?> (+<?php echo $ach['Exp_point']; ?> EXP)">
                                    <i class="fas fa-star badge-icon"></i>
                                    <span class="badge-name"><?php echo htmlspecialchars($ach['Title']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
