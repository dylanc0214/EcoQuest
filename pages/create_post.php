<?php
// pages/create_post.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . "/../config/db.php");

// --- 1. AUTHORIZATION CHECK (MUST BE BEFORE HEADER.PHP) ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_path = null;

    if (empty($title) || empty($content)) {
        $error_message = 'Aiyo! Both title and content cannot be empty.';
    } else {
        // --- HANDLE IMAGE UPLOAD ---
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $max_size = 5 * 1024 * 1024;

            if (!in_array($_FILES['post_image']['type'], $allowed_types)) {
                $error_message = "Only JPG, PNG, and GIF images are allowed.";
            } elseif ($_FILES['post_image']['size'] > $max_size) {
                $error_message = "File size is too large (Max 5MB).";
            } else {
                $upload_dir = "../uploads/forum/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_ext = pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('post_', true) . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_path)) {
                    $image_path = "uploads/forum/" . $file_name;
                } else {
                    $error_message = "Failed to upload image.";
                }
            }
        }

                // --- CHECK IF USER IS MUTED FOR POSTING ---
        if (empty($error_message)) {
            $mute_check = $conn->prepare("SELECT Mute_post FROM Student WHERE User_id = ?");
            $mute_check->bind_param("i", $user_id);
            $mute_check->execute();
            $mute_result = $mute_check->get_result()->fetch_assoc();
            $mute_check->close();

            if ($mute_result && !empty($mute_result['Mute_post']) && $mute_result['Mute_post'] !== '0000-00-00 00:00:00') {
                $mute_expiry = new DateTime($mute_result['Mute_post'], new DateTimeZone('UTC'));
                $now = new DateTime('now', new DateTimeZone('UTC'));
                
                if ($mute_expiry > $now) {
                    $error_message = 'Your posting privileges are muted until ' . $mute_result['Mute_post'] . '. Please contact support for more information.';
                }
            }
        }

        // --- INSERT INTO DATABASE ---
        if (empty($error_message)) {
            if (isset($conn) && !$conn->connect_error) {
                try {
                    $sql = "INSERT INTO Post (User_id, Title, Content, Image, Created_at) VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isss", $user_id, $title, $content, $image_path);

                    if ($stmt->execute()) {
                        $success_message = 'Your post has been published successfully!';
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
}

include("../includes/header.php");
?>

<main class="forum-page">
    <div class="container" style="max-width: 1000px;">
        <header class="forum-header">
            <h1 class="page-title">Create a New Post ✍️</h1>
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
                <script>setTimeout(function(){ window.location.href = "forum.php"; }, 2000);</script>
            <?php endif; ?>

            <form action="create_post.php" method="POST" enctype="multipart/form-data" class="post-form-split">
                
                <div class="form-left-upload">
                    <label for="post_image" class="image-upload-label" id="imagePreviewContainer">
                        <div class="upload-placeholder">
                            <i class="fas fa-image" style="font-size: 3rem; color: #ccc;"></i>
                            <p>Click to select a photo</p>
                        </div>
                        <img id="preview" src="#" alt="Preview" style="display: none;">
                        <input type="file" id="post_image" name="post_image" accept="image/*" hidden onchange="previewImage(this)">
                    </label>
                </div>

                <div class="form-right-content">
                    <div class="form-group">
                        <label for="title">Post Title</label>
                        <input type="text" id="title" name="title" required placeholder="Add a catchy title...">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Description</label>
                        <textarea id="content" name="content" rows="8" required placeholder="Write a caption..."></textarea>
                    </div>

                    <div class="form-actions-bottom">
                        <a href="forum.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Share</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
    /* Split Layout based on your reference image */
    .post-form-split {
        display: flex;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        min-height: 500px;
    }

    .form-left-upload {
        flex: 1.2;
        background: #fafafa;
        border-right: 1px solid #efefef;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .form-right-content {
        flex: 1;
        padding: 20px;
        display: flex;
        flex-direction: column;
    }

    /* Clickable area styling */
    .image-upload-label {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
    }

    .upload-placeholder {
        text-align: center;
        color: #8e8e8e;
    }

    #preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .form-group { margin-bottom: 20px; }
    .form-group label { font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; display: block; }
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #dbdbdb;
        border-radius: 8px;
        font-family: inherit;
    }

    .form-actions-bottom {
        margin-top: auto;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding-top: 15px;
        border-top: 1px solid #efefef;
    }

    /* Adjust for mobile */
    @media (max-width: 768px) {
        .post-form-split { flex-direction: column; }
        .form-left-upload { min-height: 300px; }
    }
</style>

<script>
// Image Preview Script
function previewImage(input) {
    const preview = document.getElementById('preview');
    const placeholder = document.querySelector('.upload-placeholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include("../includes/footer.php"); ?>