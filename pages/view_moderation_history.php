<?php
// pages/view_moderation_history.php
require_once '../includes/header.php';

$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../index.php?error=unauthorized');
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$error_message = null;
$student = null;
$moderation_records = [];

if (!$conn) {
    $error_message = 'Database connection failed.';
} else {
    try {
        // Fetch student info
        $sql = "SELECT u.User_id, u.Username, s.Student_id FROM User u 
                JOIN Student s ON u.User_id = s.User_id 
                WHERE s.Student_id = ? LIMIT 1";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $student = $res->fetch_assoc();
            } else {
                $error_message = 'Student not found.';
            }
            $stmt->close();
        }

        // Fetch moderation records
        if ($student) {
            $history_sql = "SELECT smr.*, u.Username as moderator_name
                            FROM student_moderation_records smr
                            JOIN User u ON smr.User_id = u.User_id
                            WHERE smr.Student_id = ?
                            ORDER BY smr.Date_Time DESC";
            
            if ($stmt = $conn->prepare($history_sql)) {
                $stmt->bind_param('i', $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $moderation_records[] = $row;
                }
                $stmt->close();
            }
        }

    } catch (Exception $e) {
        $error_message = 'Query error: ' . $e->getMessage();
    }
}
?>

<main class="page-content moderation-logs-page">
    <div class="container" style="max-width: 900px;">
        <h1 class="logs-title"><?php echo htmlspecialchars($student['Username'] ?? 'Student'); ?>'s Moderation Logs</h1>
        <p class="logs-subtitle">Record of student behaviour and actions taken!</p>
        <div class="header-divider"></div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($student): ?>
            <div class="logs-card">
                <?php if (empty($moderation_records)): ?>
                    <div class="no-records-message">
                        <i class="fas fa-info-circle"></i>
                        <p>No moderation records found for this student.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>DATE</th>
                                    <th>TITLE</th>
                                    <th>DESCRIPTION</th>
                                    <th>DURATION</th>
                                    <th>BY</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moderation_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($record['Date_Time'])); ?></td>
                                        <td>
                                            <span class="title-badge"><?php echo htmlspecialchars($record['Title']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['Description']); ?></td>
                                        <td><?php echo $record['Duration'] > 0 ? $record['Duration'] . ' Day' : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($record['moderator_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="logs-footer">
                <a href="view_student.php?student_id=<?php echo $student_id; ?>" class="back-btn">Back</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .moderation-logs-page {
        padding: 40px 20px;
        background-color: #f5f7fa;
        min-height: calc(100vh - 250px);
    }

    .logs-title {
        color: #1D4C43;
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 8px 0;
    }

    .logs-subtitle {
        color: #666;
        font-size: 0.95rem;
        margin: 0 0 15px 0;
    }

    .header-divider {
        height: 2px;
        background: linear-gradient(90deg, #1D4C43 30%, transparent);
        margin-bottom: 25px;
    }

    .logs-card {
        background: white;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 25px;
        min-height: 400px;
    }

    .no-records-message {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .no-records-message i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 15px;
        display: block;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .logs-table {
        width: 100%;
        border-collapse: collapse;
    }

    .logs-table thead {
        background-color: #f9f9f9;
        border-top: 2px solid #1D4C43;
        border-bottom: 2px solid #1D4C43;
    }

    .logs-table th {
        padding: 15px;
        text-align: left;
        font-weight: 700;
        color: #1D4C43;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .logs-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        color: #333;
    }

    .logs-table tbody tr:hover {
        background-color: #fafafa;
    }

    .logs-table tbody tr:last-child td {
        border-bottom: none;
    }

    .title-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.9rem;
        background-color: #ffe0e0;
        color: #E53E3E;
        white-space: nowrap;
    }

    .logs-footer {
        text-align: left;
    }

    .back-btn {
        display: inline-block;
        padding: 10px 24px;
        background-color: #1D4C43;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .back-btn:hover {
        background-color: #0f3028;
        box-shadow: 0 2px 8px rgba(29, 76, 67, 0.3);
    }

    .alert {
        padding: 15px 20px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-error {
        background-color: #ffe0e0;
        color: #c92a2a;
        border-left: 4px solid #c92a2a;
    }

    @media (max-width: 768px) {
        .logs-title {
            font-size: 1.5rem;
        }

        .logs-card {
            padding: 20px;
        }

        .logs-table th,
        .logs-table td {
            padding: 10px 8px;
            font-size: 0.85rem;
        }

        .title-badge {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
    }
</style>

<?php require_once '../includes/footer.php'; ?>
