<?php

ob_start();

require_once '../../includes/header.php'; 

// 权限检查逻辑移至顶部
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? 'guest';
$admin_id = $_SESSION['admin_id'] ?? null;

if (!$is_logged_in || $user_role !== 'admin' || !$admin_id) {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

$success_message = null;
$error_message = null;
$form_data = [
    'title' => '',
    'description' => '',
    'points_award' => '',
    'CategoryID' => '',
    'proof_type' => 'Image',
    'instructions' => '',
    'is_active' => 1,
];

// --- 加载分类数据 ---
$categories_from_db = [];
if (isset($conn) && $conn) {
    try {
        $result = $conn->query("SELECT CategoryID, Category_Name FROM Quest_Categories ORDER BY Category_Name ASC");
        while ($row = $result->fetch_assoc()) {
            $categories_from_db[] = $row;
        }
    } catch (Exception $e) {
        $error_message = "Failed to load categories: " . $e->getMessage();
    }
}

// 2. 处理表单提交 (必须在 require header.php 之前，因为这里有跳转指令)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['title'] = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['description'] = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['points_award'] = filter_input(INPUT_POST, 'points_award', FILTER_VALIDATE_INT);
    $form_data['CategoryID'] = filter_input(INPUT_POST, 'CategoryID', FILTER_VALIDATE_INT);
    $form_data['proof_type'] = 'Image';
    $form_data['instructions'] = filter_input(INPUT_POST, 'instructions', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['is_active'] = isset($_POST['is_active']) ? 1 : 0; 

    if (empty($form_data['title']) || $form_data['points_award'] === false || empty($form_data['instructions']) || empty($form_data['CategoryID'])) {
        $error_message = "Please fill in all required fields correctly.";
    } elseif ($form_data['points_award'] <= 0) {
        $error_message = "Points Award must be a positive number.";
    } else {
        if (isset($conn) && $conn) {
            try {
                $sql_insert = "INSERT INTO Quest (Title, Description, Points_award, CategoryID, Proof_type, Instructions, Is_active, Created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_insert);
                $stmt->bind_param("ssiisssi", 
                    $form_data['title'], $form_data['description'], $form_data['points_award'], 
                    $form_data['CategoryID'], $form_data['proof_type'], $form_data['instructions'], 
                    $form_data['is_active'], $admin_id 
                );

                if ($stmt->execute()) {
                    $success_message = "Quest created successfully! Redirecting...";
                    // 现在 header() 将正常工作，因为还没有 HTML 输出
                    header("Refresh: 2; URL=manage_quests.php?success=" . urlencode("Quest created!"));
                } else {
                    throw new Exception("Execution Failed: " . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $error_message = "Failed to save quest. Error: " . $e->getMessage();
            }
        }
    }
}

// --- 逻辑处理完毕后，头部 HTML 已在上方引入 ---
// (header.php already included above via require_once)
?>

<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Create New Quest</h1>
        <p class="admin-subtitle">Fill out the details below to add a new environmental challenge.</p>

        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="create_quest.php" class="quest-form">
            <div class="form-group">
                <label for="title">Quest Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title']); ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="description">Short Description (Optional)</label>
                <textarea id="description" name="description" rows="2"><?php echo htmlspecialchars($form_data['description']); ?></textarea>
            </div>

            <input type="hidden" name="proof_type" value="Image">

            <div class="form-row">
                <div class="form-group w-full md:w-1/2">
                    <label for="points_award">Points Award <span class="required">*</span></label>
                    <input type="number" id="points_award" name="points_award" value="<?php echo htmlspecialchars($form_data['points_award']); ?>" required min="1">
                </div>

                <div class="form-group w-full md:w-1/2">
                    <label for="CategoryID">Category <span class="required">*</span></label>
                    <select id="CategoryID" name="CategoryID" required>
                        <option value="">-- Select a Category --</option>
                        <?php foreach ($categories_from_db as $cat): ?>
                            <option value="<?php echo $cat['CategoryID']; ?>" <?php echo ($form_data['CategoryID'] == $cat['CategoryID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['Category_Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="instructions">Detailed Instructions <span class="required">*</span></label>
                <textarea id="instructions" name="instructions" rows="4" required><?php echo htmlspecialchars($form_data['instructions']); ?></textarea>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active" class="inline-label">Is Active (Make quest available to users)</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-submit">
                    <i class="fas fa-save mr-2"></i> Save Quest
                </button>
                <a href="manage_quests.php" class="btn-secondary-cancel">
                    Cancel and Back to List
                </a>
            </div>
        </form>
    </div>
</main>

<style>
    .admin-page { padding: 30px 20px; background-color: #FAFAF0; min-height: 90vh; }
    .admin-content-card { max-width: 900px; margin: 0 auto; background: #FFFFFF; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
    .admin-title { font-size: 2rem; font-weight: 700; color: #1D4C43; margin-bottom: 5px; border-bottom: 2px solid #DDEEE5; padding-bottom: 10px; }
    .admin-subtitle { font-size: 1rem; color: #5A7F7C; margin-bottom: 25px; }
    .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; font-weight: 500; }
    .error-message { background-color: #FADBD8; border: 1px solid #E74C3C; color: #C0392B; }
    .success-message { background-color: #DDEEE5; border: 1px solid #71B48D; color: #1D4C43; }
    .mr-2 { margin-right: 0.5rem; }
    .quest-form { display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { font-weight: 600; color: #1D4C43; margin-bottom: 8px; font-size: 0.95rem; }
    .required { color: #E74C3C; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group select, .form-group textarea { padding: 10px 15px; border: 1px solid #DDEEE5; border-radius: 6px; font-size: 1rem; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #71B48D; outline: none; box-shadow: 0 0 0 2px rgba(113, 180, 141, 0.2); }
    .checkbox-group { flex-direction: row; align-items: center; margin-top: 10px; }
    .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; margin-right: 10px; }
    .checkbox-group .inline-label { margin-bottom: 0; cursor: pointer; }
    .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
    .w-full { width: 100%; }
    .md\:w-1\/2 { flex-basis: calc(50% - 10px); }
    .form-actions { display: flex; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
    .btn-primary-submit, .btn-secondary-cancel { padding: 12px 20px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; transition: background 0.3s; text-decoration: none; }
    .btn-primary-submit { background: #71B48D; color: #1D4C43; }
    .btn-secondary-cancel { background: #F4F7F6; color: #5A7F7C; border: 1px solid #DDEEE5; }
    @media (max-width: 768px) {
        .form-row { flex-direction: column; gap: 15px; }
        .md\:w-1\/2 { flex-basis: 100%; }
    }
</style>

<?php 
require_once '../../includes/footer.php'; 
ob_end_flush(); // 结束缓冲
?>