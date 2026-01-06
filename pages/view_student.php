<?php
// pages/view_student.php
require_once '../includes/header.php';

$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;
$current_user_id = $_SESSION['user_id'] ?? null;

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../index.php?error=unauthorized');
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$error_message = null;
$success_message = null;
$student = null;
$student_achievements = [];
$student_badges = [];

if (!$conn) {
    $error_message = 'Database connection failed.';
} else {
    // Handle unmute/unban actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unmoderate_action'])) {
        $action = $_POST['unmoderate_action'];
        
        try {
            $conn->begin_transaction();
            
            $sql = "UPDATE Student SET ";
            if ($action === 'unban') {
                $sql .= "ban_time = NULL";
            } elseif ($action === 'unmute_post') {
                $sql .= "mute_post = NULL";
            } elseif ($action === 'unmute_comment') {
                $sql .= "mute_comment = NULL";
            }
            $sql .= " WHERE Student_id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $stmt->close();
            
            // Record in student_moderation_records
            $title_map = [
                'unban' => 'Unban',
                'unmute_post' => 'Unmute Post',
                'unmute_comment' => 'Unmute Comment'
            ];
            
            $record_sql = "INSERT INTO student_moderation_records (Student_id, User_id, Title, Description, Duration, Date_Time) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
            $record_stmt = $conn->prepare($record_sql);
            
            $title = $title_map[$action] ?? 'Unknown';
            $description = ucfirst(str_replace('_', ' ', $action)) . " action";
            $duration = 0;
            
            $record_stmt->bind_param('iissi', $student_id, $current_user_id, $title, $description, $duration);
            $record_stmt->execute();
            $record_stmt->close();
            
            $conn->commit();
            $success_message = ucfirst(str_replace('_', ' ', $action)) . " applied successfully!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = 'Error processing action: ' . $e->getMessage();
        }
    }

    try {
        $sql = "SELECT u.User_id, u.Username, u.Email, u.Role, u.Created_at, s.Student_id, s.Total_point, s.Total_Exp_Point,
                        s.ban_time, s.mute_post, s.mute_comment
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

// Check if all moderation actions are active
$all_mods_active = false;
if ($student) {
    $now = date('Y-m-d H:i:s');
    $ban_active = $student['ban_time'] && strtotime($student['ban_time']) > strtotime($now);
    $mute_post_active = $student['mute_post'] && strtotime($student['mute_post']) > strtotime($now);
    $mute_comment_active = $student['mute_comment'] && strtotime($student['mute_comment']) > strtotime($now);
    $all_mods_active = $ban_active && $mute_post_active && $mute_comment_active;
}
?>

<main class="page-content view-student">
    <div class="container">
        <a href="forum.php" class="btn-link">← Back</a>
        <h1 class="page-title">View Student</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($student): ?>
            <div class="profile-card-simple" style="position: relative;">
                <!-- 3-Dot Menu -->
                <div class="moderation-menu-container">
                    <button class="moderation-menu-btn" onclick="toggleModerationMenu(this)">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="moderation-menu-dropdown">
                        <a href="view_moderation_history.php?student_id=<?php echo $student_id; ?>">
                            <i class="fas fa-history"></i> Moderation History
                        </a>
                        
                        <?php
                        $now = date('Y-m-d H:i:s');
                        $ban_active = $student['ban_time'] && strtotime($student['ban_time']) > strtotime($now);
                        $mute_post_active = $student['mute_post'] && strtotime($student['mute_post']) > strtotime($now);
                        $mute_comment_active = $student['mute_comment'] && strtotime($student['mute_comment']) > strtotime($now);
                        ?>
                        
                        <?php if ($ban_active): ?>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="unmoderate_action" value="unban" class="unmoderate-btn">
                                    <i class="fas fa-lock-open"></i> Unban
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($mute_post_active): ?>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="unmoderate_action" value="unmute_post" class="unmoderate-btn">
                                    <i class="fas fa-comment"></i> Unmute Post
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($mute_comment_active): ?>
                            <form method="POST" style="display: inline;">
                                <button type="submit" name="unmoderate_action" value="unmute_comment" class="unmoderate-btn">
                                    <i class="fas fa-reply"></i> Unmute Comment
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if (!$all_mods_active): ?>
                            <a href="moderate_student.php?student_id=<?php echo $student_id; ?>">
                                <i class="fas fa-gavel"></i> Moderate
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-grid">
                    <section class="profile-left">
                        <div class="profile-header-simple">
                            <div class="profile-avatar-simple">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h2 class="profile-username-simple"><?php echo htmlspecialchars($student['Username']); ?></h2>
                            <span class="profile-role-simple">Student</span>
                        </div>

                        <!-- Moderation Status (Red Text) -->
                        <?php if ($ban_active || $mute_post_active || $mute_comment_active): ?>
                            <div class="moderation-status">
                                <?php if ($ban_active): ?>
                                    <p class="status-text">
                                        <i class="fas fa-ban"></i> Temporary Banned until <?php echo date('h:i A m/d/Y', strtotime($student['ban_time'])); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($mute_post_active): ?>
                                    <p class="status-text">
                                        <i class="fas fa-comment-slash"></i> Muted Post until <?php echo date('h:i A m/d/Y', strtotime($student['mute_post'])); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($mute_comment_active): ?>
                                    <p class="status-text">
                                        <i class="fas fa-reply-all"></i> Muted Comment until <?php echo date('h:i A m/d/Y', strtotime($student['mute_comment'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

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

<style>
    .moderation-menu-container {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 20;
    }

    .moderation-menu-btn {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        font-size: 1.5rem;
        padding: 5px 8px;
        transition: color 0.3s;
    }

    .moderation-menu-btn:hover {
        color: #333;
    }

    .moderation-menu-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        min-width: 180px;
        margin-top: 8px;
        z-index: 100;
    }

    .moderation-menu-dropdown.active {
        display: block;
    }

    .moderation-menu-dropdown a,
    .moderation-menu-dropdown button {
        display: block;
        width: 100%;
        padding: 12px 16px;
        border: none;
        background: none;
        text-align: left;
        color: #333;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s;
        font-size: 0.95rem;
    }

    .moderation-menu-dropdown a:hover,
    .moderation-menu-dropdown button:hover {
        background-color: #f5f5f5;
    }

    .moderation-menu-dropdown a:first-child,
    .moderation-menu-dropdown button:first-child {
        border-radius: 8px 8px 0 0;
    }

    .moderation-menu-dropdown a:last-child,
    .moderation-menu-dropdown button:last-child {
        border-radius: 0 0 8px 8px;
    }

    .unmoderate-btn {
        color: #E53E3E !important;
    }

    .unmoderate-btn:hover {
        background-color: #ffe0e0 !important;
    }

    .moderation-menu-dropdown i {
        margin-right: 10px;
        width: 16px;
    }

    .moderation-status {
        background: #ffe0e0;
        border-left: 4px solid #E53E3E;
        padding: 12px;
        border-radius: 6px;
        margin: 15px 0;
    }

    .status-text {
        color: #E53E3E;
        margin: 6px 0;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .status-text i {
        margin-right: 8px;
    }
</style>

<script>
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.moderation-menu-container')) {
            document.querySelectorAll('.moderation-menu-dropdown.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    function toggleModerationMenu(button) {
        const menu = button.nextElementSibling;
        menu.classList.toggle('active');
        event.stopPropagation();
    }
</script>

<?php require_once '../includes/footer.php'; ?>
