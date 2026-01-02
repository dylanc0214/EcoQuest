<?php
// pages/admin/manage_rewards.php
ob_start(); // 必须在最顶端开启，防止任何 HTML 输出破坏 AJAX 的 JSON 格式
require_once '../../includes/header.php'; 

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

$success_message = filter_input(INPUT_GET, 'success', FILTER_SANITIZE_SPECIAL_CHARS);
$error_message = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS);
$rewards = [];

// 2. HANDLE DELETE ACTION
$delete_id = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
if ($delete_id && isset($conn)) {
    try {
        $stmt_check = $conn->prepare("SELECT Redemption_History_id FROM Redemption_History WHERE Reward_id = ? LIMIT 1");
        $stmt_check->bind_param("i", $delete_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
             throw new Exception("Cannot delete reward: It has already been redeemed. Please set it to 'Inactive' instead.");
        }
        $stmt_check->close();
        
        $sql_delete = "DELETE FROM Reward WHERE Reward_id = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $delete_id);

        if ($stmt->execute()) {
            $success_message = "Reward deleted successfully.";
        } else {
            throw new Exception("Execution Failed: " . $stmt->error);
        }
        $stmt->close();
        header('Location: manage_rewards.php?success=' . urlencode($success_message));
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 3. FETCH ALL REWARDS
if (isset($conn)) {
    try {
        $sql_fetch_all = "SELECT Reward_id, Reward_name, Description, Points_cost, Stock, Is_active, Image_url FROM Reward ORDER BY Points_cost ASC";
        $result = $conn->query($sql_fetch_all);
        if ($result) { $rewards = $result->fetch_all(MYSQLI_ASSOC); }
    } catch (Exception $e) {
        $error_message = "Failed to load rewards. DB Error: " . $e->getMessage();
    }
}

// --- AJAX UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_reward') {
    if (ob_get_length()) ob_clean(); 
    header('Content-Type: application/json');
    
    $r_id = filter_input(INPUT_POST, 'Reward_id', FILTER_VALIDATE_INT);
    $name = $_POST['Reward_name'] ?? '';
    $points = filter_input(INPUT_POST, 'Points_cost', FILTER_VALIDATE_INT);
    $stock = filter_input(INPUT_POST, 'Stock', FILTER_VALIDATE_INT);
    $desc = $_POST['Description'] ?? '';
    $active = isset($_POST['Is_active']) ? (int)$_POST['Is_active'] : 0;
    
    if ($r_id && isset($conn)) {
        try {
            $new_image_url = null;
            if (isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/uploads/rewards/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = pathinfo($_FILES['reward_image']['name'], PATHINFO_EXTENSION);
                $file_name = 'reward_' . $r_id . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['reward_image']['tmp_name'], $target_file)) {
                    $new_image_url = '../../assets/uploads/rewards/' . $file_name;
                }
            }

            if ($new_image_url) {
                $stmt = $conn->prepare("UPDATE Reward SET Reward_name = ?, Points_cost = ?, Stock = ?, Description = ?, Is_active = ?, Image_url = ? WHERE Reward_id = ?");
                $stmt->bind_param("siisisi", $name, $points, $stock, $desc, $active, $new_image_url, $r_id);
            } else {
                $stmt = $conn->prepare("UPDATE Reward SET Reward_name = ?, Points_cost = ?, Stock = ?, Description = ?, Is_active = ? WHERE Reward_id = ?");
                $stmt->bind_param("siisii", $name, $points, $stock, $desc, $active, $r_id);
            }

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Reward updated successfully!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $stmt->error]);
            }
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Request Data']);
    }
    exit;
}
?>

<main class="page-content admin-page">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-gift"></i> Reward & Badge Management</h1>
            <p class="subtitle">Create, set requirement and manage all reward & badge.</p>
        </header>

        <div class="search-container">
            <input type="text" id="rewardSearch" placeholder="Search rewards..." onkeyup="filterRewards()">
            <i class="fas fa-search search-icon"></i>
        </div>
                
        <div class="rewards-grid" id="rewardsGrid">
            <?php foreach ($rewards as $reward): 
                $image_path = !empty($reward['Image_url']) ? htmlspecialchars($reward['Image_url']) : 'https://placehold.co/400x250/2C3E50/FAFAF0?text=Reward';
                $isActive = (int)$reward['Is_active'] === 1;
                $statusText = $isActive ? 'Active' : 'Inactive';
                $statusClass = $isActive ? 'status-active' : 'status-inactive';
            ?>
            <div class="reward-card <?= $isActive ? '' : 'inactive-card' ?>">
                <div class="card-options">
                    <button class="options-trigger"><i class="fas fa-ellipsis-v"></i></button>
                    <div class="options-dropdown">
                        <button type="button" onclick="openEditModal(<?= $reward['Reward_id'] ?>, '<?= addslashes($reward['Reward_name']) ?>', '<?= addslashes($reward['Description']) ?>', <?= $reward['Points_cost'] ?>, <?= $reward['Stock'] ?>, '<?= $image_path ?>', <?= $reward['Is_active'] ?>)"><i class="fas fa-edit"></i> Edit Reward</button>
                        <button type="button" class="btn-delete-opt" onclick="confirmDelete(<?= $reward['Reward_id'] ?>, '<?= addslashes($reward['Reward_name']) ?>')"><i class="fas fa-trash-alt"></i> Delete Reward</button>
                    </div>
                </div>
            
                <div class="card-header" style="background-image: url('<?php echo $image_path; ?>'); background-size: cover; background-position: center;"></div>

                <div class="card-body">
                    <span class="status-tag <?php echo $statusClass; ?>">
                        <?php echo $statusText; ?>
                    </span>
                    <p class="reward-name"><?php echo htmlspecialchars($reward['Reward_name']); ?></p>
                    <p class="reward-desc"><?php echo htmlspecialchars($reward['Description']); ?></p>
                </div>

                <div class="card-footer-stats">
                    <div class="stat-header-row">
                        <div class="stat-box-label left"><strong>Point</strong></div>
                        <div class="stat-box-label right"><strong>Stock</strong></div>
                    </div>
                    <div class="stat-value-row">
                        <div class="stat-box-value left highlight-points"><?php echo number_format($reward['Points_cost']); ?></div>
                        <div class="stat-box-value right">
                            <?php echo ($reward['Stock'] == -1) ? '∞' : number_format($reward['Stock']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <a href="create_reward.php" class="add-new-card">
                <div class="add-circle"><i class="fas fa-plus"></i></div>
                <p>Add New Reward</p>
            </a>
        </div>
    </div>

    <div id="editModal" class="modal-overlay" style="display: none;">
        <div class="modal-content redesigned-modal">
            <form id="editRewardForm" enctype="multipart/form-data">
                <input type="hidden" name="Reward_id" id="modal-id">
                <div class="modal-top-row">
                    <div class="image-upload-container" onclick="document.getElementById('imageInput').click()">
                        <img id="modal-img-preview" src="" alt="Preview">
                        <input type="file" id="imageInput" name="reward_image" style="display:none" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div class="top-info-block">
                        <div class="flex-header-row">
                            <input type="text" name="Reward_name" id="modal-name" class="input-name-field" placeholder="Name">
                            <div class="toggle-track" id="modal-status-toggle" onclick="toggleModalStatus()">
                                <span id="modal-status-text">Active</span>
                            </div>
                            <input type="hidden" name="Is_active" id="modal-status-val">
                        </div>
                        <div class="stats-wrapper">
                            <div class="input-group-row">
                                <span class="field-label-box">Point :</span>
                                <div class="counter-control-wrap">
                                    <button type="button" class="btn-math" onclick="adjustModalValue('modal-points', -10)">-</button>
                                    <div class="pill-display"><input type="number" name="Points_cost" id="modal-points" value="0"></div>
                                    <button type="button" class="btn-math" onclick="adjustModalValue('modal-points', 10)">+</button>
                                </div>
                            </div>
                            <div class="input-group-row">
                                <span class="field-label-box">Quantity :</span>
                                <div class="counter-control-wrap">
                                    <button type="button" class="btn-math" onclick="adjustModalValue('modal-stock', -1)">-</button>
                                    <div class="pill-display"><input type="number" name="Stock" id="modal-stock" value="0"></div>
                                    <button type="button" class="btn-math" onclick="adjustModalValue('modal-stock', 1)">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="description-area-box">
                    <textarea name="Description" id="modal-desc" class="description-field-flat" placeholder="Reward description..."></textarea>
                </div>
                <div class="modal-footer-action-btns">
                    <button type="button" class="btn-cancel-flat" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-confirm-flat">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</main>
 
<script>
    function confirmDelete(id, name) {
        if (confirm(`Permanently delete "${name}"?`)) window.location.href = `manage_rewards.php?delete_id=${id}`;
    }

    function filterRewards() {
        let input = document.getElementById('rewardSearch').value.toLowerCase();
        let cards = document.getElementsByClassName('reward-card');
        for (let card of cards) {
            let name = card.querySelector('.reward-name').innerText.toLowerCase();
            card.style.display = name.includes(input) ? "flex" : "none";
        }
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('.options-trigger')) {
            const dropdown = e.target.closest('.card-options').querySelector('.options-dropdown');
            dropdown.classList.toggle('show');
        } else {
            document.querySelectorAll('.options-dropdown').forEach(d => d.classList.remove('show'));
        }
    });

    function openEditModal(id, name, desc, points, stock, imageUrl, isActive) {
        document.getElementById('modal-id').value = id;
        document.getElementById('modal-name').value = name;
        document.getElementById('modal-desc').value = desc;
        document.getElementById('modal-points').value = points;
        document.getElementById('modal-stock').value = stock;
        document.getElementById('modal-status-val').value = isActive;
        document.getElementById('modal-img-preview').src = imageUrl || "https://placehold.co/400x250/2C3E50/FAFAF0?text=Reward";
        updateModalStatusUI(isActive);
        document.getElementById('editModal').style.display = 'flex';
    }

    function updateModalStatusUI(val) {
        const toggle = document.getElementById('modal-status-toggle');
        const text = document.getElementById('modal-status-text');
        const isActive = parseInt(val) === 1;
        
        text.innerText = isActive ? "Active" : "Inactive";
        
        if(isActive) {
            toggle.style.backgroundColor = "#2ecc71";
            toggle.style.color = "#fff";
        } else {
            toggle.style.backgroundColor = "#e74c3c";
            toggle.style.color = "#fff";
        }
    }

    function toggleModalStatus() {
        const input = document.getElementById('modal-status-val');
        input.value = parseInt(input.value) === 1 ? 0 : 1;
        updateModalStatusUI(input.value);
    }

    function adjustModalValue(inputId, amount) {
        const input = document.getElementById(inputId);
        input.value = Math.max(0, (parseInt(input.value) || 0) + amount);
    }

    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => document.getElementById('modal-img-preview').src = e.target.result;
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('editRewardForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_reward');
        
        fetch('manage_rewards.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(r => {
            if (!r.ok) return r.text().then(text => { throw new Error(text) });
            return r.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            alert("A system error occurred. Please check database connectivity or file permissions.");
        });
    };
</script>

<style>
    :root {
        --eco-dark: #1D4C43; --eco-green: #71B48D; --eco-bg: #FAFAF0;
        --eco-gray: #D9D9D9; --eco-border: #BCBCBC; --eco-body-bg: #E8E8E8;
    }

    .admin-page { background-color: var(--eco-bg); min-height: 100vh; padding: 40px 0; font-family: sans-serif; }
    .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .dashboard-header { margin-bottom: 30px; }
    .page-title { font-size: 2.2rem; font-weight: 800; color: #000; margin-bottom: 10px; }
    .subtitle { color: #666; font-size: 1.1rem; }

    .search-container { position: relative; width: 100%; margin-bottom: 40px; }
    .search-container input { width: 100%; padding: 12px 50px 12px 20px; background: #E0EAE3; border: 1px solid #71B48D; border-radius: 25px; }
    .search-icon { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); }

    .rewards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; align-items: stretch; }
    
    /* Reward Card Design - Blending with BG but Standing Out */
    .reward-card { 
        background-color: var(--eco-bg); 
        border-radius: 25px; 
        overflow: hidden; 
        display: flex; 
        flex-direction: column; 
        position: relative; 
        border: 2px solid rgba(29, 76, 67, 0.2); /* Thicker border to stand out */
        box-shadow: 10px 10px 30px rgba(0, 0, 0, 0.1), -5px -5px 20px rgba(255, 255, 255, 0.9); /* Neumorphic deep shadow */
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .reward-card:hover {
        transform: translateY(-8px);
        box-shadow: 15px 15px 35px rgba(0, 0, 0, 0.15);
    }
    .inactive-card { opacity: 0.75; filter: grayscale(0.2); }

    .card-header { height: 180px; background-size: cover; background-position: center; border-bottom: 1.5px solid rgba(29, 76, 67, 0.1); }
    .card-body { position: relative; padding: 20px; flex-grow: 1; }
    .reward-name { font-size: 1.3rem; color: var(--eco-dark); margin: 10px 0; font-weight: bold; }
    .reward-desc { font-size: 0.9rem; color: #666; overflow: hidden; }

    .card-footer-stats { display: flex; flex-direction: column; border-top: 1.5px solid rgba(29, 76, 67, 0.1); background: rgba(29, 76, 67, 0.03); }
    .stat-header-row, .stat-value-row { display: flex; width: 100%; }
    .stat-box-label { flex: 1; padding: 5px 0; text-align: center; font-size: 1rem; font-weight: bold; border-bottom: 1px solid rgba(0,0,0,0.05); color: var(--eco-dark); }
    .stat-box-value { flex: 1; padding: 10px 0; text-align: center; font-size: 1.1rem; font-weight: 800; }
    .left { border-right: 1.5px solid rgba(29, 76, 67, 0.1); }
    .highlight-points { color: #f39c12 !important; }

    .status-tag { position: absolute; top: 0; right: 10px; background: rgba(255, 255, 255, 0.95); padding: 3px 15px; border-radius: 0 0 12px 12px; font-weight: bold; font-size: 0.8rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .status-active { color: #2ecc71; border-bottom: 3px solid #2ecc71; }
    .status-inactive { color: #e74c3c; border-bottom: 3px solid #e74c3c; }

    .add-new-card { 
        background-color: var(--eco-bg);
        border: 3px dashed #bbb;
        border-radius: 25px; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        text-decoration: none; 
        color: #666; 
        height: 100%; 
        box-shadow: 4px 4px 15px rgba(0, 0, 0, 0.05);
        transition: background 0.3s ease;
    }
    .add-new-card:hover { background: rgba(113, 180, 141, 0.05); }
    .add-circle { width: 80px; height: 80px; border: 4px solid currentColor; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin-bottom: 15px; }

    /* Updated Larger Options Dropdown */
    .card-options { position: absolute; top: 15px; right: 15px; z-index: 10; }
    .options-trigger { background: rgba(0,0,0,0.25); border: none; color: #fff; width: 38px; height: 38px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; }
    
    .options-dropdown { 
        position: absolute; 
        right: 0; 
        top: 45px; 
        background: #fff; 
        border: 1px solid var(--eco-border); 
        border-radius: 15px; 
        display: none; 
        min-width: 220px; 
        padding: 10px 0; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
        z-index: 100;
    }
    .options-dropdown.show { display: block; }
    
    .options-dropdown button { 
        display: flex; 
        align-items: center; 
        width: 100%; 
        padding: 18px 25px; /* Larger Padding */
        border: none; 
        background: none; 
        cursor: pointer; 
        font-size: 1.1rem; /* Larger Font */
        font-weight: 600;
        color: var(--eco-dark);
        text-align: left;
        transition: background 0.2s;
    }
    .options-dropdown button i { font-size: 1.3rem; margin-right: 15px; width: 25px; text-align: center; }
    .options-dropdown button:hover { background: #f0f7f4; color: var(--eco-green); }
    .options-dropdown button.btn-delete-opt { color: #e74c3c; }
    .options-dropdown button.btn-delete-opt:hover { background: #fff5f5; color: #c0392b; }

    /* Modal Styling */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .redesigned-modal { background: #fff; border-radius: 20px; border: 2px solid var(--eco-dark); padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .modal-top-row { display: flex; gap: 25px; margin-bottom: 20px; }
    
    .image-upload-container { width: 160px; height: 160px; min-width: 160px; background: #E0E0E0; display: flex; align-items: center; justify-content: center; cursor: pointer; border-radius: 15px; border: 2px solid var(--eco-border); overflow: hidden; }
    .image-upload-container img { width: 100%; height: 100%; object-fit: cover; }
    
    .top-info-block { flex: 1; display: flex; flex-direction: column; gap: 15px; overflow: hidden; }
    .flex-header-row { display: flex; align-items: center; gap: 15px; width: 100%; }
    .input-name-field { flex: 1; background: #F0F0F0; border: none; padding: 10px; border-radius: 8px; font-size: 1.3rem; font-weight: bold; color: var(--eco-dark); }
    
    .toggle-track { width: 100px; min-width: 100px; padding: 8px 0; border-radius: 25px; cursor: pointer; font-size: 0.85rem; font-weight: 800; text-align: center; transition: all 0.3s ease; }

    .stats-wrapper { display: flex; flex-direction: column; gap: 12px; width: 100%; }
    .input-group-row { display: flex; align-items: center; gap: 10px; width: 100%; }
    .field-label-box { background: #F0F0F0; padding: 8px 15px; border-radius: 8px; width: 110px; font-weight: bold; color: #555; text-align: left; box-sizing: border-box; }
    
    .counter-control-wrap { display: flex; align-items: center; gap: 10px; flex: 1; width: 100%; }
    .pill-display { background: #F0F0F0; border-radius: 25px; flex: 1; padding: 6px 15px; text-align: center; border: 1px solid transparent; }
    .pill-display input { width: 100%; background: transparent; border: none; text-align: center; outline: none; font-size: 1.1rem; font-weight: bold; color: var(--eco-dark); }
    
    .btn-math { background: #71B48D; color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 1.2rem; transition: transform 0.1s; flex-shrink: 0; }
    .btn-math:active { transform: scale(0.9); }

    .description-area-box { background: #F0F0F0; border-radius: 12px; padding: 15px; border: 1px solid var(--eco-border); }
    .description-field-flat { width: 100%; background: transparent; border: none; resize: none; height: 100px; outline: none; font-size: 0.95rem; line-height: 1.5; color: #444; }
    
    .modal-footer-action-btns { display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; }
    .btn-cancel-flat { background: #eee; border: 1px solid #ccc; padding: 10px 30px; border-radius: 10px; cursor: pointer; font-weight: 600; color: #666; transition: background 0.2s; }
    .btn-confirm-flat { background: var(--eco-dark); color: #fff; border: none; padding: 10px 40px; border-radius: 10px; cursor: pointer; font-weight: 600; transition: opacity 0.2s; }
    .btn-confirm-flat:hover { opacity: 0.9; }
</style>

<?php 
require_once '../../includes/footer.php'; 
ob_end_flush();
?>