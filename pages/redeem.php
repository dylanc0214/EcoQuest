<?php
// pages/redeem.php
session_start();
include("../config/db.php");

// 1. Authorization: Make sure the user is a logged-in student.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student' || !isset($_SESSION['student_id'])) {
    header("Location: sign_up.php?error=unauthorized");
    exit();
}

$student_id = $_SESSION['student_id'];
$reward_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// 2. Validation: Check for valid reward ID and DB connection.
if (!$reward_id || !isset($conn) || $conn->connect_error) {
    header("Location: rewards.php?error=invalid_request");
    exit();
}

// 3. Start a Database Transaction for safety.
$conn->begin_transaction();

try {
    // 4. Get User's Points from the 'Student' table and lock the row.
    $stmt_user = $conn->prepare("SELECT Total_point FROM Student WHERE Student_id = ? FOR UPDATE");
    $stmt_user->bind_param("i", $student_id);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    // 5. Get Reward Details and lock the row.
    $stmt_reward = $conn->prepare("SELECT Points_cost, Stock FROM Reward WHERE Reward_id = ? FOR UPDATE");
    $stmt_reward->bind_param("i", $reward_id);
    $stmt_reward->execute();
    $reward = $stmt_reward->get_result()->fetch_assoc();
    $stmt_reward->close();

    // 6. Perform Checks: User exists, has enough points, and item is in stock.
    if (!$user || !$reward) throw new Exception("Invalid user or reward.");
    if ($user['Total_point'] < $reward['Points_cost']) throw new Exception("insufficient_points");
    if ($reward['Stock'] == 0) throw new Exception("out_of_stock");

    // 7. Execute the Redemption.
    // a. Subtract points from the student in the 'Student' table.
    $new_points = $user['Total_point'] - $reward['Points_cost'];
    $stmt_update_user = $conn->prepare("UPDATE Student SET Total_point = ? WHERE Student_id = ?");
    $stmt_update_user->bind_param("ii", $new_points, $student_id);
    $stmt_update_user->execute();
    $stmt_update_user->close();

    // b. Decrement stock (if it's not unlimited).
    if ($reward['Stock'] != -1) {
        $stmt_update_reward = $conn->prepare("UPDATE Reward SET Stock = Stock - 1 WHERE Reward_id = ?");
        $stmt_update_reward->bind_param("i", $reward_id);
        $stmt_update_reward->execute();
        $stmt_update_reward->close();
    }
    
    // c. Log the transaction in the 'Redemption_History' table.
    $stmt_log = $conn->prepare("INSERT INTO Redemption_History (Student_id, Reward_id, Points_used, Redemption_date) VALUES (?, ?, ?, NOW())");
    $stmt_log->bind_param("iii", $student_id, $reward_id, $reward['Points_cost']);
    $stmt_log->execute();
    $stmt_log->close();

    // 8. If all steps succeed, commit the changes.
    $conn->commit();
    header("Location: rewards.php?status=redeemed_successfully");
    exit();

} catch (Exception $e) {
    // 9. If any step fails, cancel everything.
    $conn->rollback();
    header("Location: rewards.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>