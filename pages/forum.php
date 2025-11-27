<?php
// pages/forum.php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Authorization: Must be logged in to view.
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
if (!in_array($user_role, ['student', 'moderator', 'admin'])) {
    header("Location: sign_up.php");
    exit();
}

$student_id = $_SESSION['student_id'] ?? null; // For students

// --- HANDLE POST DELETION (for Mods/Admins) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (in_array($user_role, ['admin', 'moderator'])) {
        $post_id_to_delete = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

        if ($post_id_to_delete && isset($conn)) {
            $conn->begin_transaction();
            try {
                // 1. Get all Comment_ids from the post
                $comment_ids = [];
                $result = $conn->query("SELECT Comment_id FROM Comment WHERE Post_id = $post_id_to_delete");
                while ($row = $result->fetch_assoc()) {
                    $comment_ids[] = $row['Comment_id'];
                }
                
                // 2. Delete from Comment_report (child of Comment)
                if (!empty($comment_ids)) {
                    $conn->query("DELETE FROM Comment_report WHERE Comment_id IN (" . implode(',', $comment_ids) . ")");
                }
                
                // 3. Delete from Comment (child of Post)
                $conn->query("DELETE FROM Comment WHERE Post_id = $post_id_to_delete");
                
                // 4. Delete from Post_Likes (child of Post)
                $conn->query("DELETE FROM Post_Likes WHERE Post_id = $post_id_to_delete");
                
                // 5. Delete from Post_report (child of Post)
                $conn->query("DELETE FROM Post_report WHERE Post_id = $post_id_to_delete");
                
                // 6. Delete the Post itself
                $conn->query("DELETE FROM Post WHERE Post_id = $post_id_to_delete");
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
            }
            header("Location: forum.php"); // Refresh the page
            exit();
        }
    }
}


$posts = [];
$db_error = '';

if (isset($conn) && !$conn->connect_error) {
    // --- UPDATED SQL QUERY (joins User->Student->Post) ---
    $sql = "
        SELECT
            p.Post_id,
            p.Title,
            p.Content,
            p.Created_at,
            u.Username,
            (SELECT COUNT(*) FROM Comment c WHERE c.Post_id = p.Post_id) AS comment_count,
            (SELECT COUNT(*) FROM Post_Likes pl WHERE pl.Post_id = p.Post_id) AS like_count
        FROM Post p
        JOIN Student s ON p.Student_id = s.Student_id
        JOIN User u ON s.User_id = u.User_id
        ORDER BY p.Created_at DESC
    ";

    try {
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
    } catch (Exception $e) {
        $db_error = "Failed to load forum posts: " . $e->getMessage();
    }
} else {
    $db_error = "Database connection failed.";
}
?>

<main class="forum-page">
    <div class="container">
        <header class="forum-header">
            <h1 class="page-title">EcoQuest Community Forum 💬</h1>
            <p class="page-subtitle">Share your progress, ask questions, and motivate other students!</p>
            <?php if ($user_role === 'student'): // Only students can create posts ?>
                <a href="create_post.php" class="btn-primary create-post-btn">
                    <i class="fas fa-plus-circle"></i> Create New Post
                </a>
            <?php endif; ?>
        </header>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="post-list">
            <?php if (empty($posts) && !$db_error): ?>
                <div class="empty-state">
                    <h3>Be the First to Post!</h3>
                    <p>The forum is empty right now. Start a new conversation!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <span class="post-author">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($post['Username']); ?>
                            </span>
                            <span class="post-date">
                                <?php echo date('d M Y, h:i A', strtotime($post['Created_at'])); ?>
                            </span>
                        </div>
                        <div class="post-body">
                            <h2 class="post-title">
                                <a href="post_detail.php?id=<?php echo $post['Post_id']; ?>">
                                    <?php echo htmlspecialchars($post['Title']); ?>
                                </a>
                            </h2>
                            <p class="post-content-preview">
                                <?php echo htmlspecialchars(substr($post['Content'], 0, 150)); ?>...
                            </p>
                        </div>
                        <div class="post-footer">
                            <span class="post-stat">
                                <i class="fas fa-heart"></i> <?php echo $post['like_count']; ?> Likes
                            </span>
                            <span class="post-stat">
                                <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?> Comments
                            </span>

                            <?php if (in_array($user_role, ['admin', 'moderator'])): ?>
                                <form action="forum.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this post? This cannot be undone.');" style="margin-left: auto;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['Post_id']; ?>">
                                    <button type="submit" name="delete_post" class="btn-delete">
                                        <i class="fas fa-trash"></i> Delete Post
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="post_detail.php?id=<?php echo $post['Post_id']; ?>" class="view-post-link">
                                    Read More &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php include("../includes/footer.php"); ?>