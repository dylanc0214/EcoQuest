<?php
// pages/post_detail.php
session_start();
include("../config/db.php");

// --- 1. INITIAL DATA & AJAX LIKE LOGIC (MUST BE AT TOP) ---
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// AJAX LIKE HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    $p_id = (int)$_POST['post_id'];

    if ($user_id && $p_id && isset($conn)) {
        $stmt = $conn->prepare("SELECT Like_id FROM post_likes WHERE User_id = ? AND Post_id = ?");
        $stmt->bind_param("ii", $user_id, $p_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM post_likes WHERE User_id = ? AND Post_id = ?");
            $stmt->bind_param("ii", $user_id, $p_id);
            $stmt->execute();
            $status = 'unliked';
        } else {
            $stmt = $conn->prepare("INSERT INTO post_likes (User_id, Post_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $p_id);
            $stmt->execute();
            $status = 'liked';
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM post_likes WHERE Post_id = ?");
        $stmt->bind_param("i", $p_id);
        $stmt->execute();
        echo json_encode(['status' => $status, 'new_count' => $stmt->get_result()->fetch_assoc()['count']]);
    }
    exit(); 
}

if (!$post_id) { header("Location: forum.php"); exit(); }

// --- 2. HANDLE OTHER POST ACTIONS (BEFORE HEADER) ---
$success_msg = null;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. REPORT SUBMISSION
    if (isset($_POST['submit_report'])) {
        $report_type = $_POST['report_type']; 
        $content_id = (int)$_POST['content_id'];
        $reason = $_POST['reason'];
        if ($content_id && $reason && $user_id) {
            try {
                if ($report_type === 'post') {
                    $check = $conn->prepare("SELECT Post_report_id FROM Post_report WHERE Post_id = ? AND Reported_by = ?");
                    $insert = $conn->prepare("INSERT INTO Post_report (Post_id, Reason, Report_time, Status, Reported_by) VALUES (?, ?, NOW(), 'Pending', ?)");
                } else {
                    $check = $conn->prepare("SELECT Comment_report_id FROM Comment_report WHERE Comment_id = ? AND Reported_by = ?");
                    $insert = $conn->prepare("INSERT INTO Comment_report (Comment_id, Reason, Report_time, Status, Reported_by) VALUES (?, ?, NOW(), 'Pending', ?)");
                }
                $check->bind_param("ii", $content_id, $user_id);
                $check->execute();
                if ($check->get_result()->num_rows == 0) {
                    $insert->bind_param("isi", $content_id, $reason, $user_id);
                    if ($insert->execute()) $success_msg = ucfirst($report_type) . " reported successfully.";
                    $insert->close();
                } else { $error_msg = "You have already reported this."; }
                $check->close();
            } catch (Exception $e) { $error_msg = "Error processing report."; }
        }
    }

    // B. ADD COMMENT - UPDATED: Removed student-only restriction
    if (isset($_POST['add_comment']) && $user_id) {
        $comment = trim($_POST['comment_content']);
        if($comment) {
            $stmt = $conn->prepare("INSERT INTO Comment (Post_id, User_id, Comment, Created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $post_id, $user_id, $comment);
            $stmt->execute();
            header("Location: post_detail.php?id=$post_id"); exit();
        }
    }

    // C. DELETE COMMENT
    if (isset($_POST['delete_comment']) && in_array($user_role, ['admin', 'moderator'])) {
        $cid = (int)$_POST['comment_id'];
        $conn->query("DELETE FROM Comment_report WHERE Comment_id = $cid"); 
        $conn->query("DELETE FROM Comment WHERE Comment_id = $cid");
        header("Location: post_detail.php?id=$post_id"); exit();
    }
}

include("../includes/header.php");

// --- 3. FETCH DATA ---
$post = null; $comments = [];
if (isset($conn)) {
    $stmt = $conn->prepare("
        SELECT p.*, u.Username,
        (SELECT COUNT(*) FROM post_likes WHERE Post_id = p.Post_id AND User_id = ?) AS user_liked
        FROM Post p JOIN User u ON p.User_id = u.User_id WHERE p.Post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id); $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($post) {
        $res = $conn->query("SELECT c.*, u.Username FROM Comment c JOIN User u ON c.User_id = u.User_id WHERE c.Post_id = $post_id ORDER BY c.Created_at ASC");
        while ($row = $res->fetch_assoc()) $comments[] = $row;
        $post['like_count'] = $conn->query("SELECT COUNT(*) FROM post_likes WHERE Post_id = $post_id")->fetch_row()[0];
    }
}
?>

<main class="forum-page">
    <div class="container" style="max-width: 900px;">
        <a href="forum.php" class="back-link">&laquo; Back to Forum</a>
        
        <?php if ($success_msg): ?><div class="message success-message"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="message error-message"><?php echo $error_msg; ?></div><?php endif; ?>

        <?php if ($post): ?>
            <div class="post-full-content" style="position: relative;">
                <?php if ($user_role === 'student'): ?>
                    <button class="report-btn-corner" onclick="openReportModal('post', <?php echo $post['Post_id']; ?>)" title="Report Post">
                        <i class="fas fa-flag"></i>
                    </button>
                <?php endif; ?>

                <h1 class="post-full-title"><?php echo htmlspecialchars($post['Title']); ?></h1>
                <div class="post-meta"><span>By: <strong><?php echo htmlspecialchars($post['Username']); ?></strong></span></div>
                
                <?php if (!empty($post['Image'])): ?>
                    <div class="post-full-image" style="margin-bottom: 20px; text-align: center;">
                        <img src="../<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    </div>
                <?php endif; ?>

                <div class="post-full-body"><?php echo nl2br(htmlspecialchars($post['Content'])); ?></div>
                
                <div class="post-actions">
                    <span class="post-stat like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                          onclick="toggleLike(this, <?php echo $post['Post_id']; ?>)" style="cursor: pointer;">
                        <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart"></i> 
                        <span class="count"><?php echo $post['like_count']; ?></span>
                    </span>
                </div>
            </div>

            <div class="comments-section">
                <h2 class="comments-title">Comments (<?php echo count($comments); ?>)</h2>
                <div class="comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item" style="position: relative;">
                            <?php if ($user_role === 'student'): ?>
                                <button class="report-btn-corner-sm" onclick="openReportModal('comment', <?php echo $comment['Comment_id']; ?>)" title="Report Comment">
                                    <i class="fas fa-flag"></i>
                                </button>
                            <?php endif; ?>
                            <div class="comment-header">
                                <span class="comment-author"><?php echo htmlspecialchars($comment['Username']); ?></span>
                                <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                                    <form method="POST" onsubmit="return confirm('Delete comment?');" style="display:inline;">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['Comment_id']; ?>">
                                        <button type="submit" name="delete_comment" class="btn-delete-comment">&times;</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <p class="comment-body"><?php echo nl2br(htmlspecialchars($comment['Comment'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="add-comment-form">
                    <h3>Leave a Reply</h3>
                    <form method="POST">
                        <textarea name="comment_content" rows="4" required placeholder="Write your comment..."></textarea>
                        <button type="submit" name="add_comment" class="btn-primary">Post Comment</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="message error-message">Post not found.</div>
        <?php endif; ?>
    </div>

    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeReportModal()">&times;</span>
            <h2 class="modal-title">Report Content 🚩</h2>
            <form method="POST">
                <input type="hidden" name="submit_report" value="1">
                <input type="hidden" id="report_type" name="report_type" value=""> 
                <input type="hidden" id="report_content_id" name="content_id" value="">
                <div class="radio-group">
                    <label class="radio-option"><input type="radio" name="reason" value="Sexual Content" required> <span>Sexual Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Violent Content"> <span>Violent Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Hateful Content"> <span>Hateful Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Spam"> <span>Spam</span></label>
                </div>
                <button type="submit" class="btn-submit-report">Submit Report</button>
            </form>
        </div>
    </div>
</main>

<style>
    .report-btn-corner { position: absolute; top: 20px; right: 20px; background: none; border: none; color: #ccc; cursor: pointer; font-size: 1.2rem; transition: 0.2s; z-index: 10; }
    .report-btn-corner:hover { color: #E53E3E; }
    .report-btn-corner-sm { position: absolute; top: 10px; right: 10px; background: none; border: none; color: #ddd; cursor: pointer; font-size: 0.9rem; transition: 0.2s; z-index: 10; }
    .report-btn-corner-sm:hover { color: #E53E3E; }
    
    /* MODIFIED: Removed background color and border from like button */
    .post-stat.like-btn { 
        color: #555; 
        transition: all 0.2s ease; 
        display: flex; 
        align-items: center; 
        gap: 5px; 
        background: transparent !important; 
        border: none !important; 
        padding: 0;
        box-shadow: none !important;
    }
    .post-stat.like-btn i { transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-size: 1.3rem; }
    .post-stat.like-btn.liked { color: #ff4d4d !important; }
    .post-stat.like-btn:active i { transform: scale(1.4); }
    
    .btn-delete-comment { background: none; border: none; color: #cc0000; font-size: 1.2rem; font-weight: bold; cursor: pointer; float:right; margin-right: 30px; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s; }
    .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border-radius: 12px; width: 90%; max-width: 450px; position: relative; }
    .radio-group { display: flex; flex-direction: column; gap: 12px; margin: 20px 0; }
    .radio-option { display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #eee; border-radius: 8px; cursor: pointer; transition: 0.2s; }
    .radio-option:hover { background-color: #f9f9f9; border-color: #71B48D; }
    .btn-submit-report { width: 100%; padding: 12px; background-color: #E53E3E; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
</style>

<script>
    function toggleLike(element, postId) {
        const icon = element.querySelector('i');
        const countSpan = element.querySelector('.count');
        let currentCount = parseInt(countSpan.textContent);
        const isLiked = element.classList.contains('liked');
        if (isLiked) {
            element.classList.remove('liked');
            icon.classList.replace('fas', 'far');
            countSpan.textContent = currentCount - 1;
        } else {
            element.classList.add('liked');
            icon.classList.replace('far', 'fas');
            countSpan.textContent = currentCount + 1;
        }
        const formData = new FormData();
        formData.append('toggle_like', '1');
        formData.append('post_id', postId);
        fetch('post_detail.php?id=' + postId, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if (data.new_count !== undefined) countSpan.textContent = data.new_count; })
        .catch(error => { window.location.reload(); });
    }
    function openReportModal(type, id) {
        document.getElementById('reportModal').style.display = "block";
        document.getElementById('report_type').value = type;
        document.getElementById('report_content_id').value = id;
    }
    function closeReportModal() { document.getElementById('reportModal').style.display = "none"; }
</script>

<?php include("../includes/footer.php"); ?>