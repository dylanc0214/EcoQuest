<?php
// pages/moderator/manage_rewards.php
require_once '../../includes/header.php';

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$error_message = null;
$rewards = [];

// 2. FETCH ALL REWARDS (READ-ONLY)
if (isset($conn) && $conn) {
    try {
        $sql_fetch = "SELECT Reward_id, Reward_name, Description, Points_cost, Stock, Is_active FROM Reward ORDER BY Points_cost ASC";
        $result = $conn->query($sql_fetch);

        if ($result) {
            $rewards = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            throw new Exception("SQL Error: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = "Failed to load rewards. DB Error: " . $e->getMessage();
    }
} else {
    $error_message = "Database connection unavailable.";
}
?>

<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title"><i class="fas fa-gift"></i> View Rewards Catalogue</h1>
        <p class="admin-subtitle">Browse and review all available rewards. (Read-only access)</p>

        <?php if ($error_message): ?>
            <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (empty($rewards) && !$error_message): ?>
            <div class="no-data-message"><i class="fas fa-box-open"></i><p>No rewards found.</p></div>
        <?php else: ?>
            <div class="rewards-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Cost (Points)</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rewards as $reward): ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($reward['Reward_id']); ?></td>
                                <td data-label="Name"><?php echo htmlspecialchars($reward['Reward_name']); ?></td>
                                <td data-label="Cost" class="text-center points-cost">
                                    <?php echo number_format($reward['Points_cost']); ?>
                                </td>
                                <td data-label="Stock" class="text-center">
                                    <?php 
                                        if ($reward['Stock'] == -1) {
                                            echo '<span class="status-unlimited"><i class="fas fa-infinity mr-1"></i> Unlimited</span>';
                                        } elseif ($reward['Stock'] > 0) {
                                            echo '<span class="status-in-stock">' . number_format($reward['Stock']) . ' left</span>';
                                        } else {
                                            echo '<span class="status-out-stock">Out of Stock</span>';
                                        }
                                    ?>
                                </td>
                                <td data-label="Status" class="text-center">
                                    <?php if ($reward['Is_active']): ?>
                                        <span class="badge active-badge">Active</span>
                                    <?php else: ?>
                                        <span class="badge inactive-badge">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Description" class="small-text">
                                    <?php echo htmlspecialchars($reward['Description']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .admin-page { padding: 30px 20px; background-color: #FAFAF0; min-height: 90vh; }
    .admin-content-card { max-width: 1100px; margin: 0 auto; background: #FFFFFF; padding: 30px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); }
    .admin-title { font-size: 2rem; font-weight: 700; color: #1D4C43; margin-bottom: 5px; border-bottom: 2px solid #DDEEE5; padding-bottom: 10px; }
    .admin-subtitle { font-size: 1rem; color: #5A7F7C; margin-bottom: 25px; }
    .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .error-message { background-color: #FADBD8; border: 1px solid #E74C3C; color: #C0392B; }
    .no-data-message { text-align: center; padding: 50px 20px; background-color: #F8F8F8; border-radius: 8px; color: #5A7F7C; }
    .no-data-message i { font-size: 3rem; margin-bottom: 10px; color: #71B48D; }
    .rewards-table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead { background-color: #E6F3EE; color: #1D4C43; }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #F4F7F6; }
    .data-table th { font-weight: 700; font-size: 0.9rem; text-transform: uppercase; }
    .data-table tbody tr:hover { background-color: #f7fffb; }
    .text-center { text-align: center; }
    .points-cost { font-weight: 700; color: #C0392B; }
    .badge { display: inline-block; padding: 4px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 600; }
    .active-badge { background-color: #71B48D; color: #1D4C43; }
    .inactive-badge { background-color: #F4F7F6; color: #777; }
    .status-unlimited { color: #2980B9; font-weight: 500; font-size: 0.85rem; }
    .status-in-stock { color: #1D4C43; font-weight: 500; }
    .status-out-stock { color: #C0392B; font-weight: 500; }
    .small-text { font-size: 0.85rem; color: #374151; line-height: 1.4; }
    
    @media (max-width: 768px) {
        .admin-page {
            padding: 20px 10px;
        }

        .admin-content-card {
            padding: 20px;
            border-radius: 12px;
        }

        .admin-title {
            font-size: 1.5rem;
        }

        .data-table thead {
            display: none;
        }

        .data-table tbody,
        .data-table tr,
        .data-table td {
            display: block;
            width: 100%;
        }

        .data-table tr {
            margin-bottom: 20px;
            border: 1px solid #DDEEE5;
            border-radius: 12px;
            padding: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .data-table td {
            padding: 10px 0 10px 0;
            text-align: left;
            position: relative;
            border-bottom: 1px solid #f4f7f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .data-table td:last-child {
            border-bottom: none;
            margin-top: 8px;
        }

        .data-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #5A7F7C;
            font-size: 0.8rem;
            min-width: 80px;
            text-transform: uppercase;
        }

        .data-table td:last-child::before {
            display: none;
        }

        .status-unlimited,
        .status-in-stock,
        .status-out-stock {
            font-size: 0.8rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 3px 6px;
        }
    }

    @media (max-width: 600px) {
        .admin-title {
            font-size: 1.3rem;
        }

        .data-table tr {
            padding: 10px;
        }

        .data-table td {
            flex-direction: column;
            align-items: flex-start;
            padding: 8px 0;
        }

        .data-table td::before {
            display: block;
            margin-bottom: 4px;
        }

        .points-cost {
            font-weight: 600;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>