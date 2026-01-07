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
        // Student info
        $sql = "SELECT u.User_id, u.Username, s.Student_id
                FROM User u
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

        // Moderation history
        if ($student) {
            $history_sql = "SELECT smr.*, u.Username AS moderator_name
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
        $error_message = 'Query error.';
    }
}
?>

<main class="page-content moderation-logs-page">
    <div class="container">

        <h1 class="logs-title">
            <?php echo htmlspecialchars($student['Username'] ?? 'Student'); ?>'s Moderation Logs
        </h1>
        <p class="logs-subtitle">Record of student behaviour and actions taken</p>
        <div class="header-divider"></div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($student): ?>
            <div class="logs-card">

                <?php if (empty($moderation_records)): ?>
                    <div class="no-records-message">
                        <p>No moderation records found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="logs-table data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moderation_records as $record): ?>
                                    <tr>
                                        <td data-label="Date">
                                            <?php echo date('d/m/Y', strtotime($record['Date_Time'])); ?>
                                        </td>
                                        <td data-label="Action">
                                            <span class="title-badge">
                                                <?php echo htmlspecialchars($record['Reason']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Description">
                                            <?php echo htmlspecialchars($record['Description']); ?>
                                        </td>
                                        <td data-label="Duration">
                                            <?php
                                                echo (!empty($record['Duration']) && $record['Duration'] !== '0')
                                                    ? htmlspecialchars($record['Duration']) . ' Day'
                                                    : 'N/A';
                                            ?>
                                        </td>
                                        <td data-label="By">
                                            <?php echo htmlspecialchars($record['moderator_name']); ?>
                                        </td>
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
/* ===== GLOBAL SAFETY ===== */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

.container {
    width: 100%;
    max-width: 900px;
    padding: 0 12px;
}

/* ===== PAGE ===== */
.moderation-logs-page {
    padding: 30px 10px;
    background: #f5f7fa;
}

.logs-title {
    color: #1D4C43;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 6px;
}

.logs-subtitle {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 12px;
}

.header-divider {
    height: 2px;
    background: linear-gradient(90deg, #1D4C43 30%, transparent);
    margin-bottom: 20px;
}

/* ===== CARD ===== */
.logs-card {
    background: #fff;
    border-radius: 10px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    margin-bottom: 20px;
}

.no-records-message {
    text-align: center;
    color: #999;
    padding: 40px 10px;
}

/* ===== TABLE ===== */
.logs-table {
    width: 100%;
    border-collapse: collapse;
}

.logs-table th,
.logs-table td {
    padding: 14px;
    text-align: left;
    font-size: 0.9rem;
}

.logs-table thead {
    background: #f9f9f9;
    border-top: 2px solid #1D4C43;
    border-bottom: 2px solid #1D4C43;
}

.title-badge {
    display: inline-block;
    padding: 6px 10px;
    background: #ffe0e0;
    color: #E53E3E;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* ===== FOOTER ===== */
.logs-footer {
    margin-top: 10px;
}

.back-btn {
    padding: 10px 22px;
    background: #1D4C43;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
}

/* ===== MOBILE CARD VIEW ===== */
@media (max-width: 768px) {

    .logs-title {
        font-size: 1.5rem;
    }

    .logs-card {
        padding: 18px;
    }

    .logs-table thead {
        display: none;
    }

    .logs-table,
    .logs-table tbody,
    .logs-table tr,
    .logs-table td {
        display: block;
        width: 100%;
    }

    .logs-table tr {
        margin-bottom: 14px;
        padding: 14px;
        border: 1px solid #ddd;
        border-radius: 12px;
        background: #fff;
    }

    .logs-table td {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .logs-table td:last-child {
        border-bottom: none;
    }

    .logs-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.75rem;
        color: #555;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
