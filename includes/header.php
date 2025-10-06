<?php
// includes/header.php
// This is the starting point for every page. It handles crucial setup tasks.

// 1. Start Session FIRST (MUST be before any HTML output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database Connection (assuming all pages need it)
// It uses the connection parameters defined in 'config/db.php'
// Since this file is in 'includes/' and 'db.php' is in 'config/', we need to step out and go to 'config'.
// But wait, the pages that include header.php (like index.php) are in the root or a folder like 'pages/'.
// Let's assume the calling script (like validate.php) is in 'pages/' and header.php is in 'includes/'.
// Path from header.php to config/db.php is: ../config/db.php

// CRITICAL FIX: The database connection file is correctly located at 'config/db.php' from the root.
// When included by a page in 'pages/', we need to step back. The safest way is to include from the context of the calling page.
// Since header.php is usually included by the root page or a page in 'pages/', let's assume we need to go up one folder.
// A simpler solution is to include it using the path relative to the root, but since header is included, let's stick to simple relative paths.

// Since ALL pages that use header.php (like pages/validate.php) are the ones calling it:
// Let's make sure the path is correct from the perspective of the main file calling header.php.

// pages/validate.php includes ../includes/header.php
// Inside header.php, we need to find config/db.php.
// Path from pages/ to config/ is: ../config/db.php.

// BUT header.php is in 'includes'. If header.php is called, it might look for db.php in 'includes/db.php'.
// Let's modify header.php to reference the database connection file correctly.

// FIX: Change to use the correct file path and file name for the database connection.
require_once '../config/db.php'; 

// 3. User Authentication Check (Logic that was previously in navigation.php)
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
    <!-- NOTE: Adjust the path to style.css if needed, based on where the main page is located -->
    <link rel="stylesheet" href="../assets/css/style.css"> 
</head>
<body>
    <?php 
    // 4. Include the navigation bar immediately after the opening <body> tag
    require_once 'navigation.php'; 
    ?>
    <!-- The content of the specific page (e.g., index.php, dashboard.php) will start here -->
