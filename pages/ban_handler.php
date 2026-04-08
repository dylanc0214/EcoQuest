<?php
// pages/ban_handler.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . "/../config/db.php");

// 1. Authorization: Only Admin or Moderator can ban
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'moderator'])) {
    header("Location: ../index.php");
    exit();
}

// 2. Get Params
$student_id = $_GET['student_id'] ?? null;
$action = $_GET['action'] ?? null; // 'ban' or 'unban'
$redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php'; // Go back to where we came from

if ($student_id && $action) {
    if ($action === 'ban') {
        // Ban until year 9999 (Permaban) 🛑
        $sql = "UPDATE student SET Ban_time = '9999-12-31 23:59:59' WHERE Student_id = ?";
    } else {
        // Unban (Set to NULL) 🟢
        $sql = "UPDATE student SET Ban_time = NULL WHERE Student_id = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "User has been " . ($action === 'ban' ? "banned" : "unbanned") . ".";
    } else {
        $_SESSION['flash_error'] = "Database error.";
    }
    $stmt->close();
}

header("Location: " . $redirect);
exit();
?>