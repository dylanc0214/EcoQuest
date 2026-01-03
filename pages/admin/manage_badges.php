<?php
// pages/admin/manage_badges.php
ob_start();
require_once '../../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

// --- HANDLE DELETE ACTION ---
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM badge WHERE Badge_id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        // Message removed
    }
    $stmt->close();
}

// --- AJAX UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_badge') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $b_id = filter_input(INPUT_POST, 'Badge_id', FILTER_VALIDATE_INT);
    $name = $_POST['Badge_Name'] ?? '';
    $xp = filter_input(INPUT_POST, 'Require_Exp_Points', FILTER_VALIDATE_INT);
    $icon = $_POST['Badge_image'] ?? ''; 
    $is_active = isset($_POST['Is_active']) ? (int)$_POST['Is_active'] : 1; 
    
    if ($b_id && isset($conn)) {
        $stmt = $conn->prepare("UPDATE badge SET Badge_Name = ?, Require_Exp_Points = ?, Badge_image = ?, Is_active = ? WHERE Badge_id = ?");
        $stmt->bind_param("sisii", $name, $xp, $icon, $is_active, $b_id);
        
        if ($stmt->execute()) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error']);
    }
    exit;
}

// --- AJAX CREATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_badge') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $name = $_POST['Badge_Name'] ?? '';
    $xp = filter_input(INPUT_POST, 'Require_Exp_Points', FILTER_VALIDATE_INT);
    $icon = $_POST['Badge_image'] ?? ''; 
    $is_active = isset($_POST['Is_active']) ? (int)$_POST['Is_active'] : 1;
    
    if (!empty($name) && isset($conn)) {
        $stmt = $conn->prepare("INSERT INTO badge (Badge_Name, Require_Exp_Points, Badge_image, Is_active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisi", $name, $xp, $icon, $is_active);
        
        if ($stmt->execute()) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error']);
    }
    exit;
}

// --- FETCH BADGES ---
$badges = [];
$result = $conn->query("SELECT * FROM badge ORDER BY Require_Exp_Points ASC");
if ($result) {
    $badges = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<main class="page-content admin-page">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-medal"></i> Reward & Badge Management</h1>
            <p class="subtitle">Set the ranks and XP milestones for students.</p>
        </header>

        <section class="admin-data-section">
            <div class="table-responsive">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Icon</th>
                            <th>Badge Name</th>
                            <th>XP Required</th>
                            <th class="text-right" style="padding-right: 25px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($badges)): ?>
                            <tr><td colspan="4" style="text-align:center; padding: 40px; color: #666;">No badges found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($badges as $b): 
                                $isInactive = isset($b['Is_active']) && (int)$b['Is_active'] === 0;
                            ?>
                                <tr class="badge-row <?php echo $isInactive ? 'inactive-card' : ''; ?>">
                                    <td class="badge-icon-cell"><?php echo $b['Badge_image']; ?></td>
                                    <td class="badge-name-cell"><strong><?php echo htmlspecialchars($b['Badge_Name']); ?></strong></td>
                                    <td>
                                        <span class="xp-badge">
                                            <?php echo number_format($b['Require_Exp_Points']); ?> XP
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="action-buttons">
                                            <button class="btn-icon edit" title="Edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($b)); ?>)">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>
                                            <button class="btn-icon delete" title="Delete" onclick="confirmDelete(<?php echo $b['Badge_id']; ?>, '<?php echo addslashes($b['Badge_Name']); ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <tr>
                            <td colspan="4" class="no-padding">
                                <div class="btn-add-inline" onclick="openCreateModal()">
                                    <i class="fas fa-plus-circle"></i> Create New Badge
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="createBadgeModal" class="modal-overlay" style="display: none;">
        <div class="modal-content redesigned-modal">
            <form id="createBadgeForm">
                <input type="hidden" name="Is_active" id="create-status-val" value="1">
                <div class="modal-top-row">
                    <div class="image-upload-container">
                        <div id="create-icon-preview" class="badge-preview-text">✨</div>
                    </div>
                    <div class="top-info-block">
                        <div class="flex-header-row">
                            <input type="text" name="Badge_Name" class="input-name-field" placeholder="New Badge Name" required>
                            <div class="toggle-track status-active" id="create-status-toggle" onclick="toggleStatus('create')">
                                <span id="create-status-text">Active</span>
                            </div>
                        </div>
                        <div class="stats-wrapper">
                            <div class="input-group-row">
                                <span class="field-label-box">XP Req :</span>
                                <div class="counter-control-wrap">
                                    <button type="button" class="btn-math" onclick="adjustValue('create-xp', -100)">-</button>
                                    <div class="pill-display">
                                        <input type="number" name="Require_Exp_Points" id="create-xp" value="0">
                                    </div>
                                    <button type="button" class="btn-math" onclick="adjustValue('create-xp', 100)">+</button>
                                </div>
                            </div>
                            <div class="input-group-row">
                                <span class="field-label-box">Icon:</span>
                                <div class="counter-control-wrap">
                                    <div class="pill-display full-width">
                                        <input type="text" name="Badge_image" placeholder="Emoji/Icon" oninput="document.getElementById('create-icon-preview').innerText = this.value" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-action-btns">
                    <button type="button" class="btn-cancel-flat" onclick="closeModal('createBadgeModal')">Cancel</button>
                    <button type="submit" class="btn-confirm-flat">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editBadgeModal" class="modal-overlay" style="display: none;">
        <div class="modal-content redesigned-modal">
            <form id="editBadgeForm">
                <input type="hidden" name="Badge_id" id="modal-id">
                <input type="hidden" name="Is_active" id="edit-status-val">
                <div class="modal-top-row">
                    <div class="image-upload-container">
                        <div id="modal-icon-preview" class="badge-preview-text">🏅</div>
                    </div>
                    <div class="top-info-block">
                        <div class="flex-header-row">
                            <input type="text" name="Badge_Name" id="modal-name" class="input-name-field" placeholder="Badge Name">
                            <div class="toggle-track" id="edit-status-toggle" onclick="toggleStatus('edit')">
                                <span id="edit-status-text">Active</span>
                            </div>
                        </div>
                        <div class="stats-wrapper">
                            <div class="input-group-row">
                                <span class="field-label-box">XP Req :</span>
                                <div class="counter-control-wrap">
                                    <button type="button" class="btn-math" onclick="adjustValue('modal-xp', -100)">-</button>
                                    <div class="pill-display">
                                        <input type="number" name="Require_Exp_Points" id="modal-xp" value="0">
                                    </div>
                                    <button type="button" class="btn-math" onclick="adjustValue('modal-xp', 100)">+</button>
                                </div>
                            </div>
                            <div class="input-group-row">
                                <span class="field-label-box">Icon:</span>
                                <div class="counter-control-wrap">
                                    <div class="pill-display full-width">
                                        <input type="text" name="Badge_image" id="modal-icon-input" oninput="document.getElementById('modal-icon-preview').innerText = this.value">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-action-btns">
                    <button type="button" class="btn-cancel-flat" onclick="closeModal('editBadgeModal')">Cancel</button>
                    <button type="submit" class="btn-confirm-flat">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function confirmDelete(id, name) {
        if (confirm(`Permanently delete "${name}" badge?`)) {
            window.location.href = `manage_badges.php?delete_id=${id}`;
        }
    }

    function openCreateModal() {
        document.getElementById('createBadgeForm').reset();
        document.getElementById('create-icon-preview').innerText = "✨";
        updateStatusUI('create', 1);
        document.getElementById('createBadgeModal').style.display = 'flex';
    }

    function openEditModal(badge) {
        document.getElementById('modal-id').value = badge.Badge_id;
        document.getElementById('modal-name').value = badge.Badge_Name;
        document.getElementById('modal-xp').value = badge.Require_Exp_Points;
        document.getElementById('modal-icon-input').value = badge.Badge_image;
        document.getElementById('modal-icon-preview').innerText = badge.Badge_image;
        updateStatusUI('edit', badge.Is_active !== undefined ? parseInt(badge.Is_active) : 1);
        document.getElementById('editBadgeModal').style.display = 'flex';
    }

    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function toggleStatus(prefix) {
        const valInput = document.getElementById(prefix + '-status-val');
        const newVal = parseInt(valInput.value) === 1 ? 0 : 1;
        updateStatusUI(prefix, newVal);
    }

    function updateStatusUI(prefix, isActive) {
        const valInput = document.getElementById(prefix + '-status-val');
        const toggle = document.getElementById(prefix + '-status-toggle');
        const text = document.getElementById(prefix + '-status-text');
        valInput.value = isActive;
        if (isActive === 1) {
            text.innerText = "Active";
            toggle.style.backgroundColor = "#2ecc71";
            toggle.style.color = "#fff";
        } else {
            text.innerText = "Inactive";
            toggle.style.backgroundColor = "#e74c3c";
            toggle.style.color = "#fff";
        }
    }

    function adjustValue(id, amount) {
        const input = document.getElementById(id);
        input.value = Math.max(0, (parseInt(input.value) || 0) + amount);
    }

    function handleForm(formId, actionName) {
        const form = document.getElementById(formId);
        if(!form) return;
        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', actionName);
            fetch('manage_badges.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                // Alert removed
                if (data.status === 'success') window.location.reload();
            }).catch(err => console.error(err));
        };
    }

    handleForm('editBadgeForm', 'update_badge');
    handleForm('createBadgeForm', 'create_badge');
</script>

<style>
    :root {
        --eco-dark: #1D4C43;
        --eco-green: #71B48D;
        --eco-bg: #FAFAF0;
        --eco-border: #BCBCBC;
    }

    .admin-page { background-color: var(--eco-bg); min-height: 90vh; padding: 40px 0; }
    .page-title { color: #000; font-weight: 800; display: flex; align-items: center; gap: 15px; }
    .page-title i { color: var(--eco-green); }

    .admin-data-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    .admin-data-table th { padding: 15px; color: #666; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; text-align: left; }
    .admin-data-table td { padding: 15px; background: white; border-top: 1px solid #eee; border-bottom: 1px solid #eee; vertical-align: middle; }
    .admin-data-table td:first-child { border-left: 1px solid #eee; border-radius: 15px 0 0 15px; }
    .admin-data-table td:last-child { border-right: 1px solid #eee; border-radius: 0 15px 15px 0; padding-right: 25px; }

    .badge-row.inactive-card { opacity: 0.6; filter: grayscale(0.5); }

    .no-padding { padding: 0 !important; }
    .btn-add-inline { display: flex; align-items: center; justify-content: center; width: 100%; padding: 20px !important; background: #fdfdfd !important; border: 2px dashed var(--eco-green) !important; border-radius: 15px; cursor: pointer; color: var(--eco-dark); font-weight: 800; font-size: 1.1rem; transition: all 0.3s ease; }
    .btn-add-inline:hover { background-color: #f0f7f4 !important; transform: scale(0.995); }
    .btn-add-inline i { margin-right: 10px; font-size: 1.3rem; color: var(--eco-green); }

    .badge-icon-cell { font-size: 2.5rem; text-align: center; }
    .xp-badge { background: #eef2ff; color: #4f46e5; padding: 6px 15px; border-radius: 20px; font-weight: 800; font-size: 0.9rem; }

    .action-buttons { display: flex; gap: 15px; justify-content: flex-end; align-items: center; height: 100%; }
    .btn-icon { background: none; border: none; cursor: pointer; font-size: 1.2rem; transition: 0.2s; padding: 5px; display: flex; align-items: center; }
    .btn-icon.edit { color: #3b82f6; }
    .btn-icon.delete { color: #ef4444; }
    .btn-icon:hover { transform: scale(1.15); }

    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .redesigned-modal { background: #fff; border-radius: 20px; border: 2px solid var(--eco-dark); padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 620px; box-sizing: border-box; overflow: hidden; }
    .modal-top-row { display: flex; gap: 25px; margin-bottom: 20px; width: 100%; }
    
    .image-upload-container { width: 160px; height: 160px; min-width: 160px; background: #F0F0F0; display: flex; align-items: center; justify-content: center; border-radius: 15px; border: 2px solid var(--eco-border); overflow: hidden; }
    .badge-preview-text { font-size: 5rem; }
    
    .top-info-block { flex: 1; display: flex; flex-direction: column; gap: 15px; min-width: 0; }
    .flex-header-row { display: flex; align-items: center; gap: 15px; width: 100%; }
    .input-name-field { flex: 1; background: #F0F0F0; border: none; padding: 10px; border-radius: 8px; font-size: 1.3rem; font-weight: bold; color: var(--eco-dark); width: 100%; box-sizing: border-box; }
    .toggle-track { width: 100px; min-width: 100px; padding: 8px 0; border-radius: 25px; font-size: 0.85rem; font-weight: 800; text-align: center; cursor: pointer; transition: all 0.3s ease; }

    .stats-wrapper { display: flex; flex-direction: column; gap: 12px; width: 100%; }
    .input-group-row { display: flex; align-items: center; gap: 10px; width: 100%; }
    .field-label-box { background: #F0F0F0; padding: 8px 15px; border-radius: 8px; width: 110px; font-weight: bold; color: #555; text-align: left; }
    .counter-control-wrap { display: flex; align-items: center; gap: 10px; flex: 1; }
    .pill-display { background: #F0F0F0; border-radius: 25px; flex: 1; padding: 6px 15px; text-align: center; }
    .pill-display input { width: 100%; background: transparent; border: none; text-align: center; outline: none; font-size: 1.1rem; font-weight: bold; color: var(--eco-dark); }
    .pill-display.full-width { border-radius: 8px; }
    .btn-math { background: var(--eco-green); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; }

    .modal-footer-action-btns { display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; }
    .btn-cancel-flat { background: #eee; padding: 10px 30px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; color: #666; }
    .btn-confirm-flat { background: var(--eco-dark); color: #fff; border: none; padding: 10px 40px; border-radius: 10px; cursor: pointer; font-weight: 600; }
    .text-right { text-align: right; }
</style>

<?php 
require_once '../../includes/footer.php'; 
ob_end_flush();
?>