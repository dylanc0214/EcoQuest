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
// 2. FILTERING & DATA FETCHING
// =======================================================
$error_message = null;
$users = [];

$filter_role = $_GET['role'] ?? 'all';
$valid_roles = ['all', 'student', 'moderator', 'admin'];
if (!in_array($filter_role, $valid_roles)) {
    $filter_role = 'all';
}

// --- UPDATED QUERY ---
// Fetch users with Student_id for students
$query = "
    SELECT 
        u.User_id, 
        u.Username, 
        u.Email, 
        u.Role, 
        u.Created_at,
        s.Student_id
    FROM User u
    LEFT JOIN Student s ON u.User_id = s.User_id
";

$params = [];
$types = '';

if ($filter_role !== 'all') {
    $query .= " WHERE u.Role = ?";
    $params[] = $filter_role;
    $types .= 's';
}

$query .= " ORDER BY u.Created_at DESC";

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
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
                                <th>USER ID</th>
                                <th>USERNAME</th>
                                <th>EMAIL</th>
                                <th>ROLE</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php 
                                    $is_student = ($user['Role'] === 'student');
                                ?>
                                <tr>
                                    <td data-label="User ID"><?php echo htmlspecialchars($user['User_id']); ?></td>
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
                                    <td data-label="Action">
                                        <div class="action-menu-container">
                                            <button class="menu-btn" onclick="toggleMenu(this)">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="menu-dropdown">
                                                <a href="edit_user.php?id=<?php echo $user['User_id']; ?>" class="menu-item">
                                                    <i class="fas fa-edit"></i> Edit User
                                                </a>
                                                <?php if ($is_student): ?>
                                                    <a href="../view_student.php?student_id=<?php echo $user['Student_id']; ?>" class="menu-item">
                                                        <i class="fas fa-eye"></i> View Profile
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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
    /* Admin Users Page Styles */
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
        background-color: #10b981;
        color: white;
        margin-left: auto;
    }

    .action-menu-container {
        position: relative;
        display: inline-block;
    }

    .menu-btn {
        background: none;
        border: none;
        color: #1D4C43;
        font-size: 1rem;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .menu-btn:hover {
        background-color: #f0f0f0;
        color: #0f3028;
    }

    .menu-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        min-width: 150px;
        display: none;
        z-index: 1000;
    }

    .menu-dropdown.active {
        display: block;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        color: #1D4C43;
        text-decoration: none;
        transition: all 0.2s ease;
        border-bottom: 1px solid #f0f0f0;
    }

    .menu-item:last-child {
        border-bottom: none;
    }

    .menu-item:hover {
        background-color: #f9f9f9;
        color: #0f3028;
        padding-left: 20px;
    }

    .menu-item i {
        width: 16px;
    }

    /* Mobile Styles */
    @media (max-width: 768px) {
        .user-filter-nav { flex-direction: column; align-items: stretch; }
        .user-filter-nav a, .user-filter-nav button { width: 100%; margin-left: 0 !important; }
        .user-filter-nav .btn-create-user { margin-top: 10px; }
        
        .admin-data-table thead { display: none; }
        .admin-data-table, .admin-data-table tbody, .admin-data-table tr, .admin-data-table td { display: block; width: 100% !important; }
        .admin-data-table tr { margin-bottom: 20px; border: 1px solid #DCDCDC; border-radius: 8px; padding: 10px 0; }
        .admin-data-table td { text-align: right; padding: 8px 15px; padding-left: 100px; position: relative; border-bottom: 1px dashed #f0f0f0; }
        .admin-data-table td:last-child { border-bottom: none; text-align: center; padding-top: 15px; }
        .admin-data-table td::before { content: attr(data-label); position: absolute; left: 15px; width: 80px; text-align: left; font-weight: 700; color: #4A5568; font-size: 0.75rem; }
    }
</style>

<script>
    function toggleMenu(button) {
        const container = button.closest('.action-menu-container');
        const menu = container.querySelector('.menu-dropdown');
        const allMenus = document.querySelectorAll('.menu-dropdown');
        
        // Close all other menus
        allMenus.forEach(m => {
            if (m !== menu) {
                m.classList.remove('active');
            }
        });
        
        // Toggle current menu
        menu.classList.toggle('active');
    }

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.menu-dropdown').forEach(m => {
                m.classList.remove('active');
            });
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>