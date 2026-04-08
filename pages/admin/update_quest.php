<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . "/../../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['Quest_id'];
    $title = $_POST['Title'];
    $points = $_POST['Points_award'];
    $desc = $_POST['Description'];
    $cat = $_POST['CategoryID'];
    $active = $_POST['Is_active'];

    $sql = "UPDATE Quest SET Title=?, Points_award=?, Description=?, CategoryID=?, Is_active=? WHERE Quest_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisiii", $title, $points, $desc, $cat, $active, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>