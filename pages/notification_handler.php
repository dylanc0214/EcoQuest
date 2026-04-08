<?php
// pages/notification_handler.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Clean Output Buffer (Prevents whitespace errors)
ob_start(); 

// 2. Disable on-screen errors (So they don't break JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once(__DIR__ . "/../config/db.php");

// 3. Set Header
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
$student_id = $_SESSION['student_id'] ?? null;

if (!$user_id) {
    ob_end_clean();
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$notifications = [];
$unread_count = 0;
$debug_error = "";

try {
    // =========================================================
    // SCENARIO A: MODERATOR & ADMIN (Staff View)
    // =========================================================
    if ($user_role === 'moderator' || $user_role === 'admin') {
        
        // 1. PENDING QUEST SUBMISSIONS
        // FIX: Changed table names to lowercase (e.g. student_quest_submissions)
        $sql_sub = "
            SELECT s.Student_quest_submission_id, s.Submission_date, u.Username, q.Title 
            FROM student_quest_submissions s
            JOIN student st ON s.Student_id = st.Student_id
            JOIN user u ON st.User_id = u.User_id
            JOIN quest q ON s.Quest_id = q.Quest_id
            WHERE s.Status = 'pending'
        ";
        $res_sub = $conn->query($sql_sub);
        if (!$res_sub) throw new Exception("SQL Error (Submissions): " . $conn->error);

        while ($row = $res_sub->fetch_assoc()) {
            $notifications[] = [
                'id' => 'sub_' . $row['Student_quest_submission_id'],
                'title' => 'New Quest Submission 📥',
                'message' => "<strong>{$row['Username']}</strong> submitted proof for <em>{$row['Title']}</em>.",
                'time' => time_elapsed_string($row['Submission_date']),
                'link' => ($user_role === 'admin' ? '/Group7_EcoQuest/pages/admin/' : '/Group7_EcoQuest/pages/moderator/') . "review_submission.php?id=" . $row['Student_quest_submission_id'],
                'sort_time' => strtotime($row['Submission_date'])
            ];
        }

        // 2. PENDING REPORTS (Post & Comment)
        // Fetch Post Reports
        $sql_rep_p = "
            SELECT r.Post_report_id, r.Report_time, r.Reason, u.Username 
            FROM post_report r
            JOIN user u ON r.Reported_by = u.User_id
            WHERE LOWER(r.Status) = 'pending'
        ";
        $res_rep_p = $conn->query($sql_rep_p);
        if (!$res_rep_p) throw new Exception("SQL Error (Post Reports): " . $conn->error);

        while ($row = $res_rep_p->fetch_assoc()) {
            $notifications[] = [
                'id' => 'rep_p_' . $row['Post_report_id'],
                'title' => 'Post Reported 🚩',
                'message' => "<strong>{$row['Username']}</strong> reported a post. Reason: {$row['Reason']}",
                'time' => time_elapsed_string($row['Report_time']),
                'link' => '/Group7_EcoQuest/pages/moderator/review_report.php?type=post&id=' . $row['Post_report_id'],
                'sort_time' => strtotime($row['Report_time'])
            ];
        }

        // Fetch Comment Reports
        $sql_rep_c = "
            SELECT r.Comment_report_id, r.Report_time, r.Reason, u.Username 
            FROM comment_report r
            JOIN user u ON r.Reported_by = u.User_id
            WHERE LOWER(r.Status) = 'pending'
        ";
        $res_rep_c = $conn->query($sql_rep_c);
        if (!$res_rep_c) throw new Exception("SQL Error (Comment Reports): " . $conn->error);
        
        while ($row = $res_rep_c->fetch_assoc()) {
            $notifications[] = [
                'id' => 'rep_c_' . $row['Comment_report_id'],
                'title' => 'Comment Reported 🚩',
                'message' => "<strong>{$row['Username']}</strong> reported a comment. Reason: {$row['Reason']}",
                'time' => time_elapsed_string($row['Report_time']),
                'link' => '/Group7_EcoQuest/pages/moderator/review_report.php?type=comment&id=' . $row['Comment_report_id'],
                'sort_time' => strtotime($row['Report_time'])
            ];
        }

        // 3. STUDENT FEEDBACK (Admin Only)
        if ($user_role === 'admin') {
            $sql_feed = "
                SELECT f.Title, f.Description, f.Date_time, u.Username 
                FROM student_feedback f
                JOIN student s ON f.Student_id = s.Student_id
                JOIN user u ON s.User_id = u.User_id
                WHERE f.Date_time > DATE_SUB(NOW(), INTERVAL 3 DAY)
            ";
            $res_feed = $conn->query($sql_feed);
            if (!$res_feed) throw new Exception("SQL Error (Feedback): " . $conn->error);
            
            while ($row = $res_feed->fetch_assoc()) {
                $notifications[] = [
                    'id' => 'feed_' . uniqid(),
                    'title' => 'New Student Feedback 💬',
                    'message' => "<strong>{$row['Username']}</strong>: " . substr($row['Title'], 0, 30) . "...",
                    'time' => time_elapsed_string($row['Date_time']),
                    'link' => '/Group7_EcoQuest/pages/admin/view_feedback.php',
                    'sort_time' => strtotime($row['Date_time'])
                ];
            }
        }

        // Sort by newest first
        usort($notifications, function($a, $b) {
            return $b['sort_time'] - $a['sort_time'];
        });

        $unread_count = count($notifications);
    }

    // =========================================================
    // SCENARIO B: STUDENT (User View)
    // =========================================================
    elseif ($user_role === 'student' && $student_id) {
        // Quest Reviews (Completed/Rejected)
        $sql = "
            SELECT s.Status, s.Review_date, q.Title, q.Points_award 
            FROM student_quest_submissions s
            JOIN quest q ON s.Quest_id = q.Quest_id
            WHERE s.Student_id = ? 
            AND s.Status IN ('completed', 'rejected', 'approved')
            ORDER BY s.Review_date DESC 
            LIMIT 5
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $raw_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($raw_data as $row) {
            $status = ucfirst($row['Status']);
            $icon = ($status == 'Completed' || $status == 'Approved') ? '✅' : '❌';
            $msg = ($status == 'Completed' || $status == 'Approved') 
                ? "Quest <em>{$row['Title']}</em> Approved! +<strong>{$row['Points_award']} PTS</strong>." 
                : "Submission for <em>{$row['Title']}</em> Rejected. Check details.";

            $notifications[] = [
                'id' => 'hist_' . uniqid(),
                'title' => "Update: $status $icon",
                'message' => $msg,
                'time' => time_elapsed_string($row['Review_date']),
                'link' => '/Group7_EcoQuest/pages/student/validate.php'
            ];
            
            // "Fake" unread for 24 hours
            if (strtotime($row['Review_date']) > strtotime('-24 hours')) {
                $unread_count++;
            }
        }
    }

} catch (Exception $e) {
    // Catch the error and send it to JS for debugging
    $debug_error = $e->getMessage();
}

// Helper: Time Ago
function time_elapsed_string($datetime, $full = false) {
    try {
        if (!$datetime) return 'Recently';
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'min',
            's' => 'sec',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    } catch (Exception $e) {
        return 'Unknown time';
    }
}

// 4. Send JSON (and clean buffer again just in case)
ob_end_clean();
echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count,
    'debug_error'   => $debug_error
]);
?>