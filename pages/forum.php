<?php
// pages/forum.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . "/../config/db.php");

// --- 1. HANDLE LIKE TOGGLE (AJAX) - MUST BE AT THE VERY TOP FOR CLEAN JSON RESPONSE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $current_session_user_id = $_SESSION['user_id'] ?? null;
    $post_id = (int)$_POST['post_id'];

    if ($current_session_user_id && $post_id && isset($conn)) {
        $stmt = $conn->prepare("SELECT Like_id FROM post_likes WHERE User_id = ? AND Post_id = ?");
        $stmt->bind_param("ii", $current_session_user_id, $post_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            // Unlike post
            $stmt = $conn->prepare("DELETE FROM post_likes WHERE User_id = ? AND Post_id = ?");
            $stmt->bind_param("ii", $current_session_user_id, $post_id);
            $stmt->execute();
            $status = 'unliked';
        } else {
            // Like post
            $stmt = $conn->prepare("INSERT INTO post_likes (User_id, Post_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $current_session_user_id, $post_id);
            $stmt->execute();
            $status = 'liked';
        }
        
        // Get updated count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM post_likes WHERE Post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $new_count = $stmt->get_result()->fetch_assoc()['count'];
        
        echo json_encode(['status' => $status, 'new_count' => $new_count]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized or invalid data']);
    }
    exit(); 
}

// --- 2. HANDLE DELETE LOGIC (MUST BE BEFORE HEADER INCLUDE) ---
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id_to_delete = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    if ($post_id_to_delete && isset($conn)) {
        // Get post author ID and role
        $stmt_check = $conn->prepare("SELECT p.User_id, u.Role FROM Post p JOIN User u ON p.User_id = u.User_id WHERE p.Post_id = ?");
        $stmt_check->bind_param("i", $post_id_to_delete);
        $stmt_check->execute();
        $post_check = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        
        // Final Authorization check for safety
        $can_delete = false;
        if ($user_role === 'admin') {
            $can_delete = true;
        } elseif ($user_role === 'moderator' && in_array($post_check['Role'], ['student', 'moderator'])) {
            $can_delete = true;
        } elseif ($user_role === 'student' && $post_check['User_id'] == $user_id) {
            $can_delete = true;
        }

        if ($post_check && $can_delete) {
            $conn->begin_transaction();
            try {
                // Delete associated data first due to foreign key constraints
                $conn->query("DELETE FROM Comment WHERE Post_id = $post_id_to_delete");
                $conn->query("DELETE FROM post_likes WHERE Post_id = $post_id_to_delete");
                $conn->query("DELETE FROM Post_report WHERE Post_id = $post_id_to_delete");
                $conn->query("DELETE FROM Post WHERE Post_id = $post_id_to_delete");
                $conn->commit();
                
                header("Location: forum.php");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
            }
        }
    }
}

// --- 3. HANDLE REPORT LOGIC (MUST BE BEFORE HEADER INCLUDE) ---
$success_msg = null;
$error_msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $report_type = $_POST['report_type']; 
    $content_id = (int)$_POST['content_id'];
    $reason = $_POST['reason'];
    
    if ($content_id && $reason && isset($conn)) {
        try {
            $stmt_check = $conn->prepare("SELECT Post_report_id FROM Post_report WHERE Post_id = ? AND Reported_by = ?");
            $stmt_check->bind_param("ii", $content_id, $user_id);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO Post_report (Post_id, Reason, Report_time, Status, Reported_by) VALUES (?, ?, NOW(), 'Pending', ?)");
                $stmt->bind_param("isi", $content_id, $reason, $user_id);
                $stmt->execute();
                $stmt->close();
                $success_msg = "Report submitted successfully.";
            } else {
                $error_msg = "You have already reported this post.";
            }
            $stmt_check->close();
        } catch (Exception $e) {
            $error_msg = "Error submitting report.";
        }
    }
}

// --- 4. INCLUDE PAGE HEADER (SAFE TO OUTPUT HTML NOW) ---
include("../includes/header.php");

// Authorization verification
if (!in_array($user_role, ['student', 'moderator', 'admin'])) {
    header("Location: login.php");
    exit();
}

// --- 5. FETCH POST LIST ---
$posts = [];
if (isset($conn) && !$conn->connect_error) {
    $sql = "
        SELECT p.Post_id, p.User_id, p.Title, p.Content, p.Image, p.Created_at, u.Username, u.Role,
        (SELECT s.Student_id FROM Student s WHERE s.User_id = p.User_id) AS student_id,
        (SELECT COUNT(*) FROM Comment c WHERE c.Post_id = p.Post_id) AS comment_count,
        (SELECT COUNT(*) FROM post_likes pl WHERE pl.Post_id = p.Post_id) AS like_count,
        (SELECT COUNT(*) FROM post_likes WHERE Post_id = p.Post_id AND User_id = ?) AS user_liked
        FROM Post p
        JOIN User u ON p.User_id = u.User_id
        ORDER BY p.Created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $posts[] = $row;
}
?>

<main class="forum-page">
    <div class="container">
        <header class="forum-header">
            <h1 class="page-title">EcoQuest Community Forum 💬</h1>
            <p class="subtitle">Share your progress, ask questions, and motivate others!</p>
            <a href="create_post.php" class="btn-primary create-post-btn">
                <i class="fas fa-plus-circle"></i> Create New Post
            </a>
        </header>
        
        <?php if ($success_msg): ?><div class="message success-message"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if ($error_msg): ?><div class="message error-message"><?php echo $error_msg; ?></div><?php endif; ?>

        <div class="post-list">
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <div class="post-menu-container">
                        <?php 
                        // logic for 3 dots visibility according to your role rules
                        $show_dots = false;
                        if ($user_role === 'admin') {
                            $show_dots = true; // Admin sees on ALL posts
                        } elseif ($user_role === 'moderator' && in_array($post['Role'], ['student', 'moderator'])) {
                            $show_dots = true; // Mod sees on Student and Mod posts
                        } elseif ($user_role === 'student' && $post['Role'] === 'student') {
                            $show_dots = true; // Student sees ONLY on student posts
                        }

                        if ($show_dots): ?>
                            <button class="post-menu-btn" onclick="togglePostMenu(this)">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="post-menu-dropdown">
                                <?php if ($post['student_id']): ?>
                                    <a href="view_student.php?student_id=<?php echo $post['student_id']; ?>">
                                        <i class="fas fa-user"></i> View Profile
                                    </a>
                                <?php endif; ?>

                                <?php 
                                $can_delete_this_item = false;
                                if ($user_role === 'admin') {
                                    $can_delete_this_item = true;
                                } elseif ($user_role === 'moderator' && in_array($post['Role'], ['student', 'moderator'])) {
                                    $can_delete_this_item = true;
                                } elseif ($user_role === 'student' && $post['User_id'] == $user_id) {
                                    $can_delete_this_item = true; // ONLY student owner
                                }

                                if ($can_delete_this_item): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="post_id" value="<?php echo $post['Post_id']; ?>">
                                        <button type="submit" name="delete_post" class="delete-menu-item" onclick="return confirm('Delete this post?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($post['User_id'] != $user_id && $user_role === 'student' && $post['Role'] === 'student'): ?>
                                    <a href="#" onclick="openReportModal('post', <?php echo $post['Post_id']; ?>); return false;">
                                        <i class="fas fa-flag"></i> Report
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="post-header">
                        <span class="post-author"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($post['Username']); ?></span>
                        <span class="post-date"><?php echo date('d M Y, h:i A', strtotime($post['Created_at'])); ?></span>
                    </div>
                    <div class="post-body">
                        <?php if (!empty($post['Image'])): ?>
                            <div class="post-image-preview" style="margin-bottom: 15px;">
                                <img src="../<?php echo htmlspecialchars($post['Image']); ?>" alt="Post Image" style="max-width: 100%; max-height: 400px; border-radius: 8px; object-fit: cover; display: block;">
                            </div>
                        <?php endif; ?>
                        <h2 class="post-title" style="cursor: default;"><?php echo htmlspecialchars($post['Title']); ?></h2>
                        <p class="post-content-preview"><?php echo htmlspecialchars(substr($post['Content'], 0, 150)); ?>...</p>
                    </div>
                    
                    <div class="post-footer">
                        <span class="post-stat like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                              onclick="toggleLike(this, <?php echo $post['Post_id']; ?>)" style="cursor: pointer; background: transparent; border: none; padding: 0;">
                            <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart"></i> 
                            <span class="count"><?php echo $post['like_count']; ?></span>
                        </span>

                        <span class="post-stat" style="cursor: pointer; background: transparent;" 
                              onclick="window.location.href='post_detail.php?id=<?php echo $post['Post_id']; ?>'">
                            <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?>
                        </span>
                        
                        <div style="margin-left: auto;">
                            <a href="post_detail.php?id=<?php echo $post['Post_id']; ?>" class="view-post-link">Read More &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeReportModal()">&times;</span>
            <h2 class="modal-title">Report Content 🚩</h2>
            <p>Please select a reason for reporting this content:</p>
            <form method="POST" action="forum.php">
                <input type="hidden" name="submit_report" value="1">
                <input type="hidden" id="report_type" name="report_type" value="">
                <input type="hidden" id="report_content_id" name="content_id" value="">
                <div class="radio-group">
                    <label class="radio-option"><input type="radio" name="reason" value="Sexual Content" required> <span>Sexual Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Violent Content"> <span>Violent Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Abusive Content"> <span>Abusive Content</span></label>
                    <label class="radio-option"><input type="radio" name="reason" value="Spam"> <span>Spam</span></label>
                </div>
                <button type="submit" class="btn-submit-report">Submit Report</button>
            </form>
        </div>
    </div>
</main>

<style>
    .post-card { position: relative; }
    .post-menu-container { position: absolute; top: 15px; right: 15px; z-index: 20; }
    .post-menu-btn { 
        background: none; 
        border: none; 
        color: #999; 
        cursor: pointer; 
        font-size: 1.5rem; 
        padding: 5px 8px;
        transition: color 0.3s;
    }
    .post-menu-btn:hover { color: #333; }
    .post-menu-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 180px;
        margin-top: 8px;
        z-index: 100;
    }
    .post-menu-dropdown.active { display: block; }
    .post-menu-dropdown a, .post-menu-dropdown button {
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
    .post-menu-dropdown a:hover, .post-menu-dropdown button:hover { background-color: #f5f5f5; }
    .post-menu-dropdown a:first-child, .post-menu-dropdown button:first-child { border-radius: 8px 8px 0 0; }
    .post-menu-dropdown a:last-child, .post-menu-dropdown button:last-child { border-radius: 0 0 8px 8px; }
    .delete-menu-item { color: #E53E3E !important; }
    .delete-menu-item:hover { background-color: #ffe0e0 !important; }
    .post-menu-dropdown i { margin-right: 10px; width: 16px; }
    .post-date { margin-right: 35px; }
    .post-stat.like-btn { 
        color: #555; 
        transition: all 0.2s ease; 
        background: transparent !important; 
        border: none !important;
        box-shadow: none !important;
    }
    .post-stat.like-btn i { transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-size: 1.2rem; margin-right: 5px; }
    .post-stat.like-btn.liked { color: #ff4d4d !important; }
    .post-stat.like-btn.liked i { color: #ff4d4d !important; }
    .post-stat.like-btn:active i { transform: scale(1.4); }
    .post-stat { background: transparent !important; border: none !important; color: #555; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s; }
    .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border-radius: 12px; width: 90%; max-width: 450px; position: relative; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
    .close-modal { position: absolute; top: 10px; right: 20px; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
    .modal-title { margin-top: 0; color: #1D4C43; }
    .radio-group { display: flex; flex-direction: column; gap: 12px; margin: 20px 0; }
    .radio-option { display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #eee; border-radius: 8px; cursor: pointer; transition: 0.2s; }
    .radio-option:hover { background-color: #f9f9f9; border-color: #71B48D; }
    .btn-submit-report { width: 100%; padding: 12px; background-color: #E53E3E; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
</style>

<script>
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.post-menu-container')) {
            document.querySelectorAll('.post-menu-dropdown.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    function togglePostMenu(button) {
        const menu = button.nextElementSibling;
        const isActive = menu.classList.contains('active');
        document.querySelectorAll('.post-menu-dropdown.active').forEach(m => m.classList.remove('active'));
        if (!isActive) {
            menu.classList.add('active');
        }
        event.stopPropagation();
    }

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

        fetch('forum.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.new_count !== undefined) {
                countSpan.textContent = data.new_count;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.reload(); 
        });
    }

    function openReportModal(type, id) {
        document.getElementById('reportModal').style.display = "block";
        document.getElementById('report_type').value = type;
        document.getElementById('report_content_id').value = id;
    }
    function closeReportModal() {
        document.getElementById('reportModal').style.display = "none";
    }
</script>

<?php include("../includes/footer.php"); ?>