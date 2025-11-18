<?php
// pages/admin/manage_users.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// =======================================================
// 2. FILTERING & DATA FETCHING (NEW ERD)
// =======================================================
$error_message = null;
$users = [];

$filter_role = $_GET['role'] ?? 'all';
$valid_roles = ['all', 'student', 'moderator', 'admin'];
if (!in_array($filter_role, $valid_roles)) {
    $filter_role = 'all';
}

$query = "SELECT User_id, Username, Email, Role, Created_at FROM User";
$params = [];
$types = '';

if ($filter_role !== 'all') {
    $query .= " WHERE Role = ?";
    $params[] = $filter_role;
    $types .= 's';
}

$query .= " ORDER BY Created_at DESC";

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
             // THIS IS THE FIXED LINE
             throw new Exception("SQL Prepare failed: " . $conn->error);
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            throw new Exception("SQL execution failed: ". $stmt->error);
        }
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "A database query error occurred: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-users">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-users-cog"></i> Manage Users</h1>
            <p class="subtitle">View, filter, and manage all student and staff accounts.</p>
        </header>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <nav class="user-filter-nav">
            <?php foreach ($valid_roles as $role): ?>
                <a href="?role=<?php echo htmlspecialchars($role); ?>" 
                   class="btn btn-filter <?php echo ($filter_role === $role) ? 'active btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-<?php echo ($role === 'admin') ? 'shield-alt' : (($role === 'moderator') ? 'user-shield' : (($role === 'student') ? 'user-graduate' : 'list-ul')); ?>"></i> 
                    <?php echo htmlspecialchars(ucfirst($role)); ?>
                </a>
            <?php endforeach; ?>
            
            <a href="create_user.php" class="btn-create-user">
                <i class="fas fa-user-plus mr-2"></i> Register New User
            </a>
        </nav>
        
        <section class="admin-data-section user-list">
            <header class="section-header">
                <h2><?php echo htmlspecialchars(ucfirst($filter_role)); ?> Accounts (<?php echo count($users); ?>)</h2>
            </header>

            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($user['User_id']); ?></td>
                                    <td data-label="Username">
                                        <i class="fas fa-user-circle user-icon"></i> 
                                        <?php echo htmlspecialchars($user['Username']); ?>
                                    </td>
                                    <td data-label="Email"><?php echo htmlspecialchars($user['Email']); ?></td>
                                    <td data-label="Role">
                                        <span class="status-badge status-<?php echo strtolower($user['Role']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($user['Role'])); ?>
                                        </span>
                                    </td>
                                    <td data-label="Joined"><?php echo date('d M Y', strtotime($user['Created_at'])); ?></td>
                                    <td data-label="Action">
                                        <div class="action-group">
                                            <a href="edit_user.php?id=<?php echo $user['User_id']; ?>" class="btn-action-icon btn-action-edit" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><h3>No Users Found</h3></div>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
    /* ---------------------------------------------------- */
    /* Filter Navigation Stacking (Re-confirming vertical flow) */
    /* ---------------------------------------------------- */
    .user-filter-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        background: #ffffff;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        align-items: center;
    }
    .user-filter-nav .btn-filter, .user-filter-nav .btn-create-user {
        padding: 10px 15px;
        font-size: 0.9rem;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .user-filter-nav .btn-create-user {
        background-color: #10b981; /* Emerald 500 */
        color: white;
        margin-left: auto;
    }


    /* ---------------------------------------------------- */
    /* Mobile Table Transformation (< 768px) - Highly Specific Override */
    /* ---------------------------------------------------- */
    @media (max-width: 768px) {
        /* 1. Filter Bar Stacking (Ensure full width) */
        .user-filter-nav {
            flex-direction: column;
            align-items: stretch;
        }
        .user-filter-nav a, .user-filter-nav button {
            width: 100%;
            margin-left: 0 !important; /* Override desktop margin-left: auto */
        }
        .user-filter-nav .btn-create-user {
            margin-top: 10px;
        }

        /* 2. Table Transformation (Targeting the user list table) */
        .admin-data-table {
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }
        .admin-data-table thead {
            display: none; /* Hide headers */
        }

        /* Force table elements to stack */
        .admin-data-table,
        .admin-data-table tbody,
        .admin-data-table tr,
        .admin-data-table td {
            display: block;
            width: 100% !important; /* CRITICAL: Force full width override */
            box-sizing: border-box;
        }

        /* Style each row as a card */
        .admin-data-table tr {
            margin-bottom: 20px;
            border: 1px solid #DCDCDC;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 10px 0; /* Vertical padding around content */
        }

        /* Style individual cells */
        .admin-data-table td {
            text-align: right;
            padding: 8px 15px;
            padding-left: 100px; /* Space for the data label */
            position: relative;
            border-bottom: 1px dashed #f0f0f0;
            line-height: 1.4;
        }
        .admin-data-table td:last-child {
            border-bottom: none;
            /* Center the action buttons horizontally */
            text-align: center;
            padding-top: 15px;
        }

        /* 3. Mobile Data Labels */
        .admin-data-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 15px;
            width: 80px; /* Fixed width for alignment */
            text-align: left;
            font-weight: 700;
            color: #4A5568;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        /* Action Group formatting */
        .action-group {
            display: flex;
            justify-content: center; /* Center the icons */
            gap: 15px;
            width: 100%;
        }

        /* Specific adjustment for Username/Email cell alignment */
        .admin-data-table td[data-label="USERNAME"],
        .admin-data-table td[data-label="EMAIL"],
        .admin-data-table td[data-label="ROLE"] {
            /* Align values to the right for clear separation */
            text-align: right;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>