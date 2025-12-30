<?php
// pages/admin/manage_rewards.php
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
        $sql_fetch_all = "SELECT Reward_id, Reward_name, Description, Points_cost, Stock, Is_active, image_url FROM Reward ORDER BY Points_cost ASC";
        $result = $conn->query($sql_fetch_all);
        if ($result) { $rewards = $result->fetch_all(MYSQLI_ASSOC); }
    } catch (Exception $e) {
        $error_message = "Failed to load rewards. DB Error: " . $e->getMessage();
    }
}

// --- AJAX UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_reward') {
    ob_clean();
    header('Content-Type: application/json');
    $r_id = filter_input(INPUT_POST, 'Reward_id', FILTER_VALIDATE_INT);
    $name = $_POST['Reward_name'] ?? '';
    $points = filter_input(INPUT_POST, 'Points_cost', FILTER_VALIDATE_INT);
    $stock = filter_input(INPUT_POST, 'Stock', FILTER_VALIDATE_INT);
    $desc = $_POST['Description'] ?? '';
    $active = isset($_POST['Is_active']) ? (int)$_POST['Is_active'] : 0;

    if ($r_id && isset($conn)) {
        $stmt = $conn->prepare("UPDATE Reward SET Reward_name = ?, Points_cost = ?, Stock = ?, Description = ?, Is_active = ? WHERE Reward_id = ?");
        $stmt->bind_param("siisii", $name, $points, $stock, $desc, $active, $r_id);
        if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Updated successfully']);
        else echo json_encode(['status' => 'error', 'message' => 'Error updating']);
    }
    exit;
}
?>

<main class="page-content admin-page">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title">Reward & Badge Management</h1>
            <p class="subtitle">Create, set requirement and manage all reward & badge.</p>
        </header>

        <div class="management-tabs">
            <a href="manage_rewards.php" class="tab-btn active"><i class="fas fa-gift"></i> Reward</a>
            <a href="manage_badges.php" class="tab-btn "><i class="fas fa-award"></i> Badge</a>
        </div>

        <div class="search-container">
            <input type="text" id="rewardSearch" placeholder="Search rewards..." onkeyup="filterRewards()">
            <i class="fas fa-search search-icon"></i>
        </div>

        <?php if ($success_message): ?>
            <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
                
        <div class="rewards-grid" id="rewardsGrid">
            <?php foreach ($rewards as $reward): 
                $image_path = !empty($reward['image_url']) ? htmlspecialchars($reward['image_url']) : '../../assets/images/placeholder-reward.png';
                $isActive = (int)$reward['Is_active'] === 1;
                $statusText = $isActive ? 'Active' : 'Inactive';
                $statusClass = $isActive ? 'status-active' : 'status-inactive';
            ?>
            <div class="reward-card <?= $isActive ? '' : 'inactive-card' ?>">
                <div class="card-options">
                    <button class="options-trigger"><i class="fas fa-ellipsis-v"></i></button>
                    <div class="options-dropdown">
                        <button type="button" onclick="openEditModal(<?= $reward['Reward_id'] ?>, '<?= addslashes($reward['Reward_name']) ?>', '<?= addslashes($reward['Description']) ?>', <?= $reward['Points_cost'] ?>, <?= $reward['Stock'] ?>, '<?= $image_path ?>', <?= $reward['Is_active'] ?>)"><i class="fas fa-edit"></i> Edit</button>
                        <button type="button" onclick="confirmDelete(<?= $reward['Reward_id'] ?>, '<?= addslashes($reward['Reward_name']) ?>')"><i class="fas fa-trash-alt"></i> Delete</button>
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
            <form id="editRewardForm">
                <input type="hidden" name="Reward_id" id="modal-id">
                <div class="modal-top-row">
                    <div class="image-upload-container" onclick="document.getElementById('imageInput').click()">
                        <img id="modal-img-preview" src="" alt="Preview">
                        <input type="file" id="imageInput" name="reward_image" style="display:none" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <div class="top-info-block">
                        <div class="flex-header-row">
                            <input type="text" name="Reward_name" id="modal-name" class="input-name-field" placeholder="Name">
                            <div class="toggle-track" onclick="toggleModalStatus()">
                                <span id="modal-status-text">Active</span>
                            </div>
                            <input type="hidden" name="Is_active" id="modal-status-val">
                        </div>
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
        document.getElementById('modal-img-preview').src = imageUrl || "https://placehold.co/400x250/E8E8E8/666?text=Reward";
        updateModalStatusUI(isActive);
        document.getElementById('editModal').style.display = 'flex';
    }

    function updateModalStatusUI(val) {
        document.getElementById('modal-status-text').innerText = parseInt(val) === 1 ? "Active" : "Inactive";
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
        fetch('manage_rewards.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(data => {
            alert(data.message);
            if (data.status === 'success') window.location.reload();
        }).catch(err => console.error(err));
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
    .management-tabs { display: flex; gap: 10px; margin-bottom: 30px; }
    .tab-btn { padding: 8px 30px; background-color: #E2E8F0; color: #4A5568; border: 1px solid #CBD5E0; text-decoration: none; border-radius: 8px; font-weight: 600; }
    .tab-btn.active { background-color: #808080; color: #FFFFFF; font-weight: 800; }

    .search-container { position: relative; width: 100%; margin-bottom: 40px; }
    .search-container input { width: 100%; padding: 12px 50px 12px 20px; background: #E0EAE3; border: 1px solid #71B48D; border-radius: 25px; }
    .search-icon { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); }

    .rewards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; align-items: stretch; }
    .reward-card { background: var(--eco-gray); border-radius: 25px; overflow: hidden; display: flex; flex-direction: column; position: relative; border: 2px solid var(--eco-border); }
    .card-header { height: 180px; background-size: cover; background-position: center; border-bottom: 2px solid var(--eco-border); }
    .card-body { position: relative; padding: 20px; flex-grow: 1; }
    .reward-name { font-size: 1.3rem; color: var(--eco-dark); margin: 10px 0; font-weight: bold; }
    .reward-desc { font-size: 0.9rem; color: #666; height: 3em; overflow: hidden; }

    .card-footer-stats { display: flex; flex-direction: column; border-top: 2px solid var(--eco-border); background: var(--eco-body-bg); }
    .stat-header-row, .stat-value-row { display: flex; width: 100%; }
    .stat-box-label { flex: 1; padding: 5px 0; text-align: center; font-size: 1.2rem; font-weight: bold; border-bottom: 1px solid var(--eco-border); }
    .stat-box-value { flex: 1; padding: 10px 0; text-align: center; font-size: 1.1rem; font-weight: 800; }
    .left { border-right: 2px solid var(--eco-border); }
    .highlight-points { color: #f39c12 !important; }

    .status-tag { position: absolute; top: 0; right: 10px; background: rgba(255, 255, 255, 0.95); padding: 3px 15px; border-radius: 0 0 12px 12px; font-weight: bold; }
    .status-active { color: #2ecc71; border-bottom: 3px solid #2ecc71; }
    .status-inactive { color: #e74c3c; border-bottom: 3px solid #e74c3c; }

    .add-new-card { border: 3px dashed #bbb; border-radius: 25px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: #666; height: 100%; background: var(--eco-gray); }
    .add-circle { width: 80px; height: 80px; border: 4px solid currentColor; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin-bottom: 15px; }

    .card-options { position: absolute; top: 15px; right: 15px; z-index: 10; }
    .options-trigger { background: rgba(255,255,255,0.2); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; }
    .options-dropdown { position: absolute; right: 0; top: 35px; background: #fff; border: 1px solid #ccc; border-radius: 8px; display: none; min-width: 160px; padding: 8px 0; }
    .options-dropdown.show { display: block; }
    .options-dropdown button { display: flex; align-items: center; width: 100%; padding: 12px 20px; border: none; background: none; cursor: pointer; }
    .options-dropdown button:hover { background: #f5f5f5; color: var(--eco-green); }

    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .redesigned-modal { width: 560px; background: #fff; border-radius: 15px; border: 1.5px solid #333; padding: 25px; }
    .modal-top-row { display: flex; gap: 20px; margin-bottom: 15px; }
    .image-upload-container { width: 150px; height: 150px; background: #E0E0E0; display: flex; align-items: center; justify-content: center; cursor: pointer; border-radius: 4px; border: 1px solid #ccc; overflow: hidden; }
    .image-upload-container img { width: 100%; height: 100%; object-fit: cover; }
    .top-info-block { flex: 1; display: flex; flex-direction: column; gap: 12px; }
    .flex-header-row { display: flex; align-items: center; gap: 10px; justify-content: space-between; }
    .input-name-field { flex: 1; background: #EEEEEE; border: none; padding: 8px; border-radius: 4px; font-size: 1.4rem; text-align: center; }
    .toggle-track { background: #D9D9D9; padding: 5px 15px; border-radius: 20px; cursor: pointer; font-size: 0.9rem; }
    .input-group-row { display: flex; align-items: center; gap: 10px; }
    .field-label-box { background: #EEEEEE; padding: 6px 15px; border-radius: 4px; min-width: 90px; }
    .counter-control-wrap { display: flex; align-items: center; gap: 8px; flex: 1; }
    .pill-display { background: #EEEEEE; border-radius: 25px; flex: 1; padding: 4px 10px; text-align: center; }
    .pill-display input { width: 100%; background: transparent; border: none; text-align: center; outline: none; font-size: 1.1rem; }
    .btn-math { background: #E0E0E0; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 1.2rem; }
    .description-area-box { background: #EEEEEE; border-radius: 8px; padding: 12px; margin-top: 5px; }
    .description-field-flat { width: 100%; background: transparent; border: none; resize: none; height: 100px; outline: none; }
    .modal-footer-action-btns { display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px; }
    .btn-cancel-flat, .btn-confirm-flat { background: #D9D9D9; border: none; padding: 8px 30px; border-radius: 10px; cursor: pointer; font-weight: 600; }
</style>

<?php require_once '../../includes/footer.php'; ?>