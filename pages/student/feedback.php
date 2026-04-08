<?php
// pages/feedback.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . "/../../config/db.php");
include("../../includes/header.php");

// 1. Authorization: Only logged-in students can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$message = [];

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($description)) {
        $message = ['type' => 'error', 'text' => 'Please fill in both the title and description.'];
    } else {
        if ($conn) {
            try {
                $sql = "INSERT INTO student_feedback (Student_id, Title, Description, Date_time) VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $student_id, $title, $description);

                if ($stmt->execute()) {
                    $message = ['type' => 'success', 'text' => 'Feedback submitted successfully! The Admin will review it soon.'];
                    // Clear fields after success
                    $title = '';
                    $description = '';
                } else {
                    throw new Exception("Execution failed: " . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $message = ['type' => 'error', 'text' => 'Error submitting feedback: ' . $e->getMessage()];
            }
        } else {
            $message = ['type' => 'error', 'text' => 'Database connection failed.'];
        }
    }
}
?>

<main class="feedback-page" style="padding: 40px 8%;">
    <div class="container" style="max-width: 700px; margin: 0 auto;">
        <h1 class="page-title">Student Feedback 📢</h1>
        <p class="page-subtitle">Have a suggestion, complaint, or idea for EcoQuest? Let the admins know directly!</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message['type']; ?>-message">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <div class="auth-card" style="padding: 30px; border-radius: 12px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <form action="feedback.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="title">Subject / Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required placeholder="e.g., Suggestion for a new Quest">
                </div>

                <div class="form-group">
                    <label for="description">Feedback Details</label>
                    <textarea id="description" name="description" rows="6" required placeholder="Write your message to the admin here..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>

                <div class="form-actions" style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="width: 100%;">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include("../../includes/footer.php"); ?>
