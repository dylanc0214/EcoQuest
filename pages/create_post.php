<?php
// pages/create_post.php
session_start();
include("../config/db.php");
include("../includes/header.php");

// Only logged-in students can create posts
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Simple validation
    if (empty($title) || empty($content)) {
        $error_message = 'Aiyo! Both title and content cannot be empty.';
    } else {
        // Insert the new post into the database
        if (isset($conn) && !$conn->connect_error) {
            try {
                // UPDATED: Insert into Post table with Student_id
                $sql = "INSERT INTO Post (Student_id, Title, Content, Created_at) VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $student_id, $title, $content);

                if ($stmt->execute()) {
                    $success_message = 'Your post has been published successfully!';
                    // Redirect to the forum page after a short delay
                    header("Refresh: 2; URL=forum.php");
                } else {
                    $error_message = "Failed to create post. Please try again.";
                }
                $stmt->close();
            } catch (Exception $e) {
                $error_message = "A database error occurred: " . $e->getMessage();
            }
        } else {
            $error_message = "Database connection failed.";
        }
    }
}
?>

<main class="forum-page">
    <div class="container" style="max-width: 800px;">
        <header class="forum-header">
            <h1 class="page-title">Create a New Post ✍️</h1>
            <p class="page-subtitle">Share your thoughts, tips, or success stories with the community.</p>
        </header>

        <div class="create-post-card">
            <?php if ($error_message): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="message success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                    <p style="font-size: 0.9rem; margin-top: 5px;">Redirecting you to the forum now...</p>
                </div>
            <?php endif; ?>

            <form action="create_post.php" method="POST" class="post-form">
                <div class="form-group">
                    <label for="title">Post Title</label>
                    <input type="text" id="title" name="title" required placeholder="e.g., My experience completing the 'Plastic Fighter' quest!">
                </div>
                <div class="form-group">
                    <label for="content">Your Content</label>
                    <textarea id="content" name="content" rows="10" required placeholder="Share more details here..."></textarea>
                </div>
                <div class="form-actions">
                    <a href="forum.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Publish Post</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>