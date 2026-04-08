<?php
// includes/header.php

// 1. Start Session FIRST (MUST be before any HTML output)
// THIS IS THE ONLY PLACE THE SESSION SHOULD BE STARTED.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database Connection
// This path is relative to this file's directory.
require_once __DIR__ . '/../config/db.php';
$base_path = '/';

// 3. User Authentication Check
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? ($_SESSION['user_role'] ?? 'guest') : 'guest';
$app_title = "EcoQuest";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>EcoQuest: Go Green, Earn Rewards 🌱 | <?php echo basename($_SERVER['PHP_SELF'], '.php'); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    
    <?php 
    // Check if the current script is in an 'admin' or 'moderator' subfolder
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || strpos($_SERVER['REQUEST_URI'], '/moderator/') !== false) : 
    ?>
        <link rel="stylesheet" href="/assets/css/style.css">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php 
    // 4. Include the navigation bar
    // This file (navigation.php) MUST NOT have session_start()
    require_once __DIR__ . '/navigation.php'; 
    ?>