<?php
// pages/admin/create_reward.php

// 1. 开启输出缓冲，解决 "Headers already sent" 错误
ob_start();

require_once '../../includes/header.php'; 

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

$errors = [];
$form_data = [
    'Reward_name' => '',
    'Description' => '',
    'Points_cost' => '',
    'Stock' => '',
    'Image_url' => '',
    'Is_active' => 1
];

// 2. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['Reward_name'] = trim(filter_input(INPUT_POST, 'Reward_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $form_data['Description'] = trim(filter_input(INPUT_POST, 'Description', FILTER_SANITIZE_SPECIAL_CHARS));
    $form_data['Is_active'] = isset($_POST['Is_active']) ? 1 : 0;
    
    // Numeric Validation
    $points_cost = filter_input(INPUT_POST, 'Points_cost', FILTER_VALIDATE_INT);
    if ($points_cost === false || $points_cost < 1) {
        $errors['Points_cost'] = "Cost must be a positive whole number.";
    } else {
        $form_data['Points_cost'] = $points_cost;
    }

    $stock = filter_input(INPUT_POST, 'Stock', FILTER_VALIDATE_INT);
    if ($stock === false || $stock < 0) {
        $errors['Stock'] = "Stock must be a whole number (0 or higher).";
    } else {
        $form_data['Stock'] = $stock;
    }

    if (empty($form_data['Reward_name'])) {
        $errors['Reward_name'] = "Reward name is required.";
    }

    // 2.1 Handle File Upload
    $image_path = null;
    if (empty($errors) && isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/uploads/rewards/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['reward_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'reward_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['reward_image']['tmp_name'], $target_file)) {
            $image_path = '../../assets/uploads/rewards/' . $file_name;
        } else {
            $errors['Image'] = "Failed to upload image.";
        }
    }

    // 2.2 Execute Database INSERT
    if (empty($errors) && isset($conn) && $conn) {
        try {
            // 注意这里使用的是你的数据库字段名 Image_url (首字母大写)
            $sql = "INSERT INTO Reward (Reward_name, Description, Points_cost, Stock, Image_url, Is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssiisi",
                $form_data['Reward_name'],
                $form_data['Description'],
                $form_data['Points_cost'],
                $form_data['Stock'],
                $image_path,
                $form_data['Is_active']
            );

            if ($stmt->execute()) {
                // 清除之前的输出缓冲，确保跳转成功
                ob_clean();
                header('Location: manage_rewards.php?success=' . urlencode("Reward '{$form_data['Reward_name']}' created!"));
                exit; // 必须添加 exit，防止后续代码继续执行
            } else {
                $errors['db'] = "Failed to create reward: " . $stmt->error;
            }
            $stmt->close();

        } catch (Exception $e) {
            $errors['db'] = "A critical database error occurred: " . $e->getMessage();
        }
    }
}
?>

<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Create New Reward</h1>
        <p class="admin-subtitle">Fill in the details for a new item in your rewards catalogue.</p>

        <?php if (isset($errors['db'])): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($errors['db']); ?></div>
        <?php endif; ?>

        <form method="POST" action="create_reward.php" class="reward-form" enctype="multipart/form-data">
            <div class="form-top-layout">
                <div class="image-preview-container" onclick="document.getElementById('reward_image').click()">
                    <img id="img-placeholder" src="https://placehold.co/400x250/2C3E50/FAFAF0?text=Click+to+Upload" alt="Preview">
                    <input type="file" id="reward_image" name="reward_image" style="display:none" accept="image/*" onchange="previewImage(this)">
                    <div class="img-label">Click to Upload Image</div>
                </div>

                <div class="top-info-block">
                    <div class="form-group">
                        <label for="Reward_name">Reward Name <span class="required">*</span></label>
                        <input type="text" id="Reward_name" name="Reward_name" class="large-input" value="<?php echo htmlspecialchars($form_data['Reward_name']); ?>" required>
                        <?php if (isset($errors['Reward_name'])): ?><p class="error-text"><?php echo $errors['Reward_name']; ?></p><?php endif; ?>
                    </div>

                    <div class="stats-row">
                        <div class="form-group flex-1">
                            <label for="Points_cost">Points Cost</label>
                            <input type="number" id="Points_cost" name="Points_cost" value="<?php echo htmlspecialchars($form_data['Points_cost']); ?>" required min="1">
                        </div>
                        <div class="form-group flex-1">
                            <label for="Stock">Available Stock</label>
                            <input type="number" id="Stock" name="Stock" value="<?php echo htmlspecialchars($form_data['Stock']); ?>" required min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="Description">Detailed Description</label>
                <textarea id="Description" name="Description" rows="4"><?php echo htmlspecialchars($form_data['Description']); ?></textarea>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="Is_active" name="Is_active" value="1" <?php echo $form_data['Is_active'] ? 'checked' : ''; ?>>
                <label for="Is_active" class="inline-label">Set as Active (Available in Shop)</label>
            </div>

            <div class="button-bar">
                <a href="manage_rewards.php" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Save Reward</button>
            </div>
        </form>
    </div>
</main>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('img-placeholder').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<style>
    :root {
        --eco-dark: #1D4C43;
        --eco-green: #71B48D;
        --eco-bg: #FAFAF0;
        --eco-border: #DDEEE5;
    }

    .admin-page { padding: 30px 20px; background-color: #f4f7f6; min-height: 90vh; }
    .admin-content-card { max-width: 850px; margin: 0 auto; background: #FFFFFF; padding: 35px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
    .admin-title { font-size: 1.8rem; font-weight: 700; color: var(--eco-dark); margin-bottom: 5px; }
    .admin-subtitle { font-size: 0.95rem; color: #666; margin-bottom: 30px; border-bottom: 2px solid var(--eco-bg); padding-bottom: 15px; }
    
    .form-top-layout { display: flex; gap: 25px; margin-bottom: 20px; }
    
    .image-preview-container { 
        width: 180px; height: 180px; min-width: 180px; 
        background: #f0f0f0; border-radius: 15px; 
        border: 2px solid var(--eco-border); overflow: hidden;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        position: relative; cursor: pointer;
    }
    .image-preview-container:hover { border-color: var(--eco-green); }
    .image-preview-container img { width: 100%; height: 100%; object-fit: cover; }
    .img-label { position: absolute; bottom: 0; background: rgba(0,0,0,0.5); color: white; width: 100%; font-size: 0.7rem; text-align: center; padding: 4px 0; }

    .top-info-block { flex: 1; display: flex; flex-direction: column; gap: 15px; }
    .large-input { font-size: 1.2rem; font-weight: 600; padding: 15px !important; border-color: var(--eco-green) !important; }
    
    .stats-row { display: flex; gap: 15px; }
    .flex-1 { flex: 1; }

    .form-group { margin-bottom: 18px; }
    label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.9rem; }
    .required { color: #E74C3C; }

    input, textarea { 
        width: 100%; padding: 12px; border: 1.5px solid var(--eco-border); 
        border-radius: 10px; box-sizing: border-box; font-size: 1rem; 
        background-color: var(--eco-bg);
    }
    input:focus, textarea:focus { border-color: var(--eco-green); outline: none; background-color: #fff; }

    .checkbox-group { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
    .checkbox-group input { width: 18px; height: 18px; }

    .button-bar { display: flex; justify-content: flex-end; gap: 15px; padding-top: 20px; border-top: 1px solid #eee; }
    .btn-primary { background: var(--eco-dark); color: white; padding: 12px 30px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; }
    .btn-secondary { background: #eee; color: #666; padding: 12px 30px; border-radius: 10px; text-decoration: none; font-weight: 600; }
    .error-text { color: #E74C3C; font-size: 0.8rem; margin-top: 4px; }
    
    @media (max-width: 600px) {
        .form-top-layout { flex-direction: column; align-items: center; }
        .image-preview-container { width: 100%; }
    }
</style>

<?php 
require_once '../../includes/footer.php'; 
// 3. 刷新并发送缓冲区内容
ob_end_flush(); 
?>