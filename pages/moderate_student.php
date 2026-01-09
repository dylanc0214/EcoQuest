<?php
// pages/moderate_student.php
require_once '../includes/header.php';

$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

// Only admin and moderator can access
if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../index.php?error=unauthorized');
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$error_message = null;
$success_message = null;
$student = null;
$current_user_id = $_SESSION['user_id'] ?? null;

if (!$conn) {
    $error_message = 'Database connection failed.';
} else {
    // Fetch student info
    $sql = "SELECT u.User_id, u.Username, s.Student_id FROM User u 
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

    // Handle moderation submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moderate_submit']) && $student) {
        $category = $_POST['category'] ?? '';
        $duration = intval($_POST['duration'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!$category || !in_array($category, ['ban', 'mute_post', 'mute_comment']) || $duration <= 0) {
            $error_message = 'Invalid category or duration.';
        } elseif (empty($description)) {
            $error_message = 'Description is required.';
        } else {
            try {
                $conn->begin_transaction();

                // Calculate expiration time (UTC)
                $expiration_time = date('Y-m-d H:i:s', strtotime("+$duration days"));
                $student_user_id = $student['User_id'];

                // Update Student table based on category
                if ($category === 'ban') {
                    $update_sql = "UPDATE Student SET Ban_time = UTC_TIMESTAMP() + INTERVAL ? DAY WHERE Student_id = ?";
                } elseif ($category === 'mute_post') {
                    $update_sql = "UPDATE Student SET Mute_post = UTC_TIMESTAMP() + INTERVAL ? DAY WHERE Student_id = ?";
                } else { // mute_comment
                    $update_sql = "UPDATE Student SET Mute_comment = UTC_TIMESTAMP() + INTERVAL ? DAY WHERE Student_id = ?";
                }

                $update_stmt = $conn->prepare($update_sql);
                // For UTC_TIMESTAMP() + INTERVAL ? DAY, we bind the duration as an integer
                $update_stmt->bind_param('ii', $duration, $student_id);
                $update_stmt->execute();
                $update_stmt->close();

                // Record in student_moderation_records
                $record_sql = "

                INSERT INTO student_moderation_records
                (Student_id, User_id, Reason, Description, Duration, Date_Time) 
                VALUES (?, ?, ?, ?, ?, NOW())
                
                ";

                $record_stmt = $conn->prepare($record_sql);
                
                // Map category to title
                $title_map = [
                    'ban' => 'Temporary Ban',
                    'mute_post' => 'Mute Post',
                    'mute_comment' => 'Mute Comment'
                ];
                $title = $title_map[$category];
                
                $record_stmt->bind_param('iissi', $student_id, $current_user_id, $title, $description, $duration);
                $record_stmt->execute();
                $record_stmt->close();

                $conn->commit();
                $success_message = "Student moderation applied successfully!";
                $redirect_url = "view_student.php?student_id=$student_id";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = 'Error applying moderation: ' . $e->getMessage();
            }
        }
    }
}
?>

<main class="page-content moderate-student">
    <div class="container">
        <a href="view_student.php?student_id=<?php echo $student_id; ?>" class="btn-link">← Back</a>
        <h1 class="page-title">Student Moderation</h1>
        <p class="subtitle">Apply appropriate punishments for this student!</p>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($student): ?>
            <div class="moderation-card">
                <form method="POST" action="moderate_student.php?student_id=<?php echo $student_id; ?>">
                    
                    <!-- Student Info -->
                    <div class="moderation-field">
                        <label class="field-label">Student:</label>
                        <div class="field-value"><?php echo htmlspecialchars($student['Username']); ?></div>
                    </div>

                    <!-- Category Selection -->
                    <div class="moderation-field">
                        <label class="field-label">Category:</label>
                        <div class="category-buttons">
                            <label class="category-option">
                                <input type="radio" name="category" value="ban" required>
                                <span class="category-label">Temporary Ban</span>
                            </label>
                            <label class="category-option">
                                <input type="radio" name="category" value="mute_post" required>
                                <span class="category-label">Mute Post</span>
                            </label>
                            <label class="category-option">
                                <input type="radio" name="category" value="mute_comment" required>
                                <span class="category-label">Mute Comment</span>
                            </label>
                        </div>
                    </div>

                    <!-- Duration Selection -->
                    <div class="moderation-field">
                        <label class="field-label">Duration:</label>
                        <div class="duration-buttons">
                            <label class="duration-option">
                                <input type="radio" name="duration" value="1" required>
                                <span class="duration-label">1 Day</span>
                            </label>
                            <label class="duration-option">
                                <input type="radio" name="duration" value="3" required>
                                <span class="duration-label">3 Day</span>
                            </label>
                            <label class="duration-option">
                                <input type="radio" name="duration" value="7" required>
                                <span class="duration-label">7 Day</span>
                            </label>
                            <span class="duration-divider">or</span>
                            <input type="number" name="duration_custom" min="1" placeholder="Enter Day" class="duration-input">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="moderation-field">
                        <label class="field-label">Description:</label>
                        <textarea name="description" rows="8" placeholder="Write your reason..." class="description-textarea" required></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="moderation-actions">
                        <a href="view_student.php?student_id=<?php echo $student_id; ?>" class="btn-back">Back</a>
                        <button type="submit" name="moderate_submit" class="btn-moderate">Moderate</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
    </div>
</main>

<style>
    .moderate-student {
        padding: 40px 0;
    }

    .btn-link {
        display: inline-block;
        color: #1D4C43;
        text-decoration: none;
        font-weight: 500;
        margin-bottom: 20px;
        transition: color 0.3s;
    }

    .btn-link:hover {
        color: #71B48D;
    }

    .page-title {
        color: #1D4C43;
        font-size: 2.5rem;
        margin: 20px 0 10px;
    }

    .subtitle {
        color: #666;
        margin-bottom: 30px;
    }

    .moderation-card {
        background: white;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .moderation-field {
        margin-bottom: 30px;
    }

    .field-label {
        display: block;
        font-weight: 600;
        color: #1D4C43;
        margin-bottom: 12px;
        font-size: 1rem;
    }

    .field-value {
        padding: 12px;
        background: #f5f5f5;
        border-radius: 6px;
        color: #333;
    }

    .category-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .category-option,
    .duration-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .category-option input[type="radio"],
    .duration-option input[type="radio"] {
        cursor: pointer;
        display: none;
    }

    .category-label,
    .duration-label {
        padding: 10px 16px;
        border: 2px solid #ddd;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        background: white;
    }

    .category-option input[type="radio"]:checked + .category-label,
    .duration-option input[type="radio"]:checked + .duration-label {
        background-color: #71B48D;
        color: white;
        border-color: #71B48D;
    }

    .duration-buttons {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .duration-divider {
        color: #999;
        font-weight: 500;
    }

    .duration-input {
        padding: 10px 12px;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        width: 120px;
    }

    .duration-input:focus {
        outline: none;
        border-color: #71B48D;
        background-color: #f9f9f9;
    }

    .description-textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        font-family: inherit;
        resize: vertical;
    }

    .description-textarea:focus {
        outline: none;
        border-color: #71B48D;
        box-shadow: 0 0 5px rgba(113, 180, 141, 0.3);
    }

    .moderation-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-back {
        padding: 12px 24px;
        background: #555;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s;
    }

    .btn-back:hover {
        background: #333;
    }

    .btn-moderate {
        padding: 12px 24px;
        background: #E53E3E;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
    }

    .btn-moderate:hover {
        background: #c92a2a;
    }

    .alert {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-error {
        background-color: #ffe0e0;
        color: #c92a2a;
        border-left: 4px solid #c92a2a;
    }

    .alert-success {
        background-color: #e6f5eb;
        color: #1D4C43;
        border-left: 4px solid #71B48D;
    }
</style>

<script>
    // Handle custom duration input
    document.addEventListener('DOMContentLoaded', function() {
        const durationRadios = document.querySelectorAll('input[name="duration"]');
        const customInput = document.querySelector('input[name="duration_custom"]');

        // Clear custom input when preset is selected
        durationRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value !== 'custom') {
                    customInput.value = '';
                }
            });
        });

        // Handle form submission with custom duration
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const selectedDuration = document.querySelector('input[name="duration"]:checked');
            const customDuration = customInput.value.trim();

            // If no preset selected and custom input has value, validate
            if (!selectedDuration && customDuration) {
                customInput.name = 'duration';
                return true;
            }

            // If custom input has value but no preset selected, use custom
            if (customDuration && !selectedDuration.checked) {
                const customRadio = document.createElement('input');
                customRadio.type = 'hidden';
                customRadio.name = 'duration';
                customRadio.value = customDuration;
                form.appendChild(customRadio);
            }
        });
    });

    // Auto-redirect after success
    <?php if (isset($redirect_url)): ?>
        setTimeout(function() {
            window.location.href = '<?php echo $redirect_url; ?>';
        }, 2000);
    <?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>
