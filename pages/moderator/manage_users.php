<?php
// pages/moderator/manage_users.php
require_once '../../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['moderator', 'admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$students = [];
if ($conn) {
    // Fetch Students
    $query = "
        SELECT s.Student_id, u.Username, u.Email, s.Total_point, u.Created_at
        FROM student s
        JOIN user u ON s.User_id = u.User_id
        ORDER BY u.Created_at DESC
    ";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}
?>

<main class="page-content manage-students-page">
    <div class="container" style="max-width: 1000px;">
        <header class="students-header">
            <div class="header-content">
                <h1 class="page-title"><i class="fas fa-users"></i> View Students</h1>
                <p class="page-subtitle">A list of all registered students on the EcoQuest platform.</p>
            </div>
        </header>
        
        <section class="students-section">
            <div class="students-card">
                <div class="card-header">
                    <h3 class="card-title">All Students (<?php echo count($students); ?>)</h3>
                </div>

                <?php if (empty($students)): ?>
                    <div class="no-students">
                        <i class="fas fa-inbox"></i>
                        <p>No students found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>STUDENT ID</th>
                                    <th>USERNAME</th>
                                    <th>EMAIL</th>
                                    <th>POINTS</th>
                                    <th>JOINED ON</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['Student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['Username']); ?></td>
                                        <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                        <td><?php echo number_format($student['Total_point']); ?> Pts</td>
                                        <td><?php echo date('d M Y', strtotime($student['Created_at'])); ?></td>
                                        <td>
                                            <div class="action-menu-container">
                                                <button class="menu-btn" onclick="toggleMenu(this)">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="menu-dropdown">
                                                    <a href="../view_student.php?student_id=<?php echo $student['Student_id']; ?>" class="menu-item">
                                                        <i class="fas fa-eye"></i> View Profile
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<style>
    .manage-students-page {
        padding: 40px 20px;
        background-color: #f5f7fa;
        min-height: calc(100vh - 250px);
    }

    .students-header {
        margin-bottom: 30px;
    }

    .header-content {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .page-title {
        color: #1D4C43;
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .page-subtitle {
        color: #666;
        font-size: 0.95rem;
        margin: 0;
    }

    .students-section {
        margin-bottom: 30px;
    }

    .students-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .card-header {
        padding: 20px 25px;
        background-color: #f9f9f9;
        border-bottom: 1px solid #eee;
    }

    .card-title {
        color: #1D4C43;
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
    }

    .no-students {
        text-align: center;
        padding: 80px 30px;
        color: #999;
    }

    .no-students i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 15px;
        display: block;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .students-table {
        width: 100%;
        border-collapse: collapse;
    }

    .students-table thead {
        background-color: #f9f9f9;
    }

    .students-table th {
        padding: 15px 20px;
        text-align: left;
        font-weight: 700;
        color: #1D4C43;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #1D4C43;
    }

    .students-table td {
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
        color: #333;
        font-size: 0.9rem;
    }

    .students-table tbody tr:hover {
        background-color: #f9f9f9;
    }

    .students-table tbody tr:last-child td {
        border-bottom: none;
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

    @media (max-width: 768px) {
        .manage-students-page {
            padding: 20px 10px;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .students-table thead {
            display: none;
        }

        .students-table tbody,
        .students-table tr,
        .students-table td {
            display: block;
            width: 100%;
        }

        .students-table tr {
            margin-bottom: 20px;
            border: 1px solid #DCDCDC;
            border-radius: 12px;
            padding: 12px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .students-table td {
            padding: 10px 0 10px 0;
            text-align: left;
            position: relative;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .students-table td:last-child {
            border-bottom: none;
            margin-top: 8px;
        }

        .students-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #4A5568;
            font-size: 0.8rem;
            min-width: 80px;
            text-transform: uppercase;
        }

        .students-table td:last-child::before {
            display: none;
        }

        .header-content {
            padding: 15px;
        }

        .card-header {
            padding: 15px;
        }

        .action-menu-container {
            width: 100%;
        }

        .menu-btn {
            width: 100%;
        }
    }

    @media (max-width: 600px) {
        .page-title {
            font-size: 1.3rem;
        }

        .students-table tr {
            padding: 10px;
        }

        .students-table td {
            flex-direction: column;
            align-items: flex-start;
            padding: 8px 0;
        }

        .students-table td::before {
            display: block;
            margin-bottom: 4px;
        }

        .menu-item {
            font-size: 0.85rem;
            padding: 8px 12px;
        }
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