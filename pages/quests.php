<?php
// pages/quests.php
include("../config/db.php");
include("../includes/header.php");

// 2. Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$db_error = '';
$quests = [];
$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Warning: Database connection failed. Cannot load quest list.';
} else {
    // --- 5. Retrieve Quest List (Final Fix: Progress and Submissions must match the current week) ---
    $sql = "
        SELECT
            q.Quest_id,
            q.Title,
            q.Description,
            q.Points_award,
            qc.Category_Name,
            
            CASE
                -- 1. Prioritize checking submissions for the current week (pending/completed/rejected)
                WHEN s.Status IS NOT NULL THEN s.Status
                -- 2. Check progress: If the quest was submitted before the current week (determined via subquery), ignore the old progress
                WHEN p.Status IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM Student_Quest_Submissions old_s 
                    WHERE old_s.Quest_id = q.Quest_id 
                    AND old_s.Student_id = ? 
                    AND old_s.Submission_date < qc_cal.Start_Date
                ) THEN p.Status
                ELSE 'Available'
            END AS user_quest_status
            
        FROM Quest_Calendar qc_cal
        JOIN Quest q ON qc_cal.Quest_id = q.Quest_id
        LEFT JOIN Quest_Categories qc ON q.CategoryID = qc.CategoryID
        
        -- Link to progress table
        LEFT JOIN Quest_Progress p 
            ON q.Quest_id = p.Quest_id 
            AND p.Student_id = ?
            
        -- Core Logic: Link only to submission records for the current week
        LEFT JOIN Student_Quest_Submissions s
            ON q.Quest_id = s.Quest_id 
            AND s.Student_id = ?
            AND s.Submission_date BETWEEN qc_cal.Start_Date AND qc_cal.End_Date
            
        WHERE
            q.Is_active = 1
            AND NOW() BETWEEN qc_cal.Start_Date AND qc_cal.End_Date
        
        ORDER BY
            FIELD(user_quest_status, 'Available', 'active', 'pending', 'completed', 'rejected'), q.Points_award DESC";

    if ($stmt = $conn->prepare($sql)) {
        // Note: There are now three parameter placeholders requiring the student_id
        $stmt->bind_param("iii", $student_id, $student_id, $student_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($quest = $result->fetch_assoc()) {
                switch (strtolower($quest['user_quest_status'])) {
                    case 'completed':
                    case 'approved': 
                        $quest['display_status'] = 'Completed';
                        break;
                    case 'pending':
                        $quest['display_status'] = 'Pending Review';
                        break;
                    case 'active':
                        $quest['display_status'] = 'In Progress';
                        break;
                    case 'rejected':
                        $quest['display_status'] = 'Rejected';
                        break;
                    default:
                        $quest['display_status'] = 'Available';
                        break;
                }
                $quests[] = $quest;
            }
        } else {
            $db_error = 'Query execution failed: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $db_error = 'Database query preparation failed: ' . $conn->error;
    }
}
?>

<main class="quests-page">
    <div class="container">
        <h1 class="page-title">Ready for the Next Challenge? 🚀</h1>
        <p class="page-subtitle">Pick a quest, submit your proof, and start earning points for real impact. Hurry, don't miss out!</p>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="quest-grid">
            <?php if (empty($quests) && !$db_error): ?>
                <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <i class="fas fa-search large-icon" style="font-size: 3rem; color: #71B48D;"></i>
                    <h3>Oh no, no active quests right now!</h3>
                    <p>(No quests are scheduled for the current time.)</p>
                </div>
            <?php else: ?>
                <?php foreach ($quests as $quest):
                    $status_class = strtolower(str_replace(' ', '-', $quest['display_status']));
                    $difficulty = $quest['Points_award'] > 300 ? 'Hard' : ($quest['Points_award'] > 150 ? 'Medium' : 'Easy');
                ?>
                    <div class="quest-card status-<?php echo $status_class; ?>">
                        <div class="quest-header">
                            <span class="quest-theme"><?php echo htmlspecialchars($quest['Category_Name'] ?? 'General'); ?></span>
                            <span class="quest-points">+<?php echo number_format($quest['Points_award']); ?> PTS</span>
                        </div>

                        <h3 class="quest-title"><?php echo htmlspecialchars($quest['Title']); ?></h3>
                        <p class="quest-desc"><?php echo htmlspecialchars($quest['Description']); ?></p>

                        <div class="quest-footer">
                            <span class="quest-difficulty"><?php echo $difficulty; ?></span>
                            <span class="quest-status"><?php echo $quest['display_status']; ?></span>

                            <?php if ($quest['display_status'] === 'Available'): ?>
                                <a href="quest_detail.php?id=<?php echo $quest['Quest_id']; ?>" class="btn-primary" style="margin-left: auto;">Start Quest</a>
                            <?php elseif ($quest['display_status'] === 'In Progress'): ?>
                                <a href="student/validate.php?quest_id=<?php echo $quest['Quest_id']; ?>" class="btn-primary" style="margin-left: auto; background-color: var(--color-accent);">Submit Proof</a>
                            <?php elseif ($quest['display_status'] === 'Pending Review'): ?>
                                <span class="btn-disabled" style="margin-left: auto; cursor: default;">Waiting...</span>
                            <?php else: ?>
                                <?php if ($quest['display_status'] === 'Rejected'): ?>
                                     <a href="quest_detail.php?id=<?php echo $quest['Quest_id']; ?>" class="btn-primary" style="margin-left: auto; background-color: var(--color-error);">Try Again</a>
                                <?php else: ?>
                                    <span class="btn-disabled" style="margin-left: auto; cursor: default;">Done! 🎉</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>