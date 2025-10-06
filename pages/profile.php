<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php
    // pages/profile.php
    session_start();

    // --- DB Connection and Dependencies ---
    include("../config/db.php"); // Assuming this file establishes $conn
    include("../includes/header.php");

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if not logged in
        header("Location: login.php");
        exit();
    }

    $current_user_id = $_SESSION['user_id'];
    $db_error = '';
    $user_data = null;
    $is_db_connected = isset($conn) && !$conn->connect_error;

    if (!$is_db_connected) {
        $db_error = 'Error: Database connection failed. Cannot load profile data.';
    } else {
        // Fetch user details from the database
        $sql = "SELECT user_id, username, email, user_role, total_points 
                FROM users 
                WHERE user_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $current_user_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user_data = $result->fetch_assoc();
                } else {
                    $db_error = 'User data not found. Please try logging in again.';
                    session_unset();
                    session_destroy();
                }
            } else {
                $db_error = 'Query execution failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $db_error = 'Database query preparation failed: ' . $conn->error;
        }
    }
    ?>

    <main class="profile-page">
        <div class="container">
            <h1 class="page-title">My EcoQuest Profile 👤</h1>
            <p class="page-subtitle">Check your stats and see how you're helping the planet!</p>

            <?php if ($db_error): ?>
                <div class="message error-message"><?php echo $db_error; ?></div>
            <?php endif; ?>

            <?php if ($user_data): ?>
                <div class="profile-card-simple">
                    
                    <div class="profile-header-simple">
                        <div class="profile-avatar-simple">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h2 class="profile-username-simple"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                        <span class="profile-role-simple role-<?php echo strtolower($user_data['user_role']); ?>">
                            <i class="fas fa-leaf"></i> <?php echo ucfirst($user_data['user_role']); ?>
                        </span>
                    </div>

                    <div class="points-highlight">
                        <h4><i class="fas fa-star"></i> Total EcoPoints Earned</h4>
                        <p class="points-value-large"><?php echo number_format($user_data['total_points']); ?></p>
                        <p class="points-label">PTS</p>
                    </div>

                    <div class="profile-details-list">
                        
                        <div class="detail-item-simple">
                            <i class="fas fa-envelope"></i>
                            <h4>Email Address:</h4>
                            <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                        </div>
                        
                        <div class="detail-item-simple">
                            <i class="fas fa-calendar-alt"></i>
                            <h4>Member Since:</h4>
                            <p>Not implemented yet 🚧</p>
                        </div>

                        <div class="detail-item-simple">
                            <i class="fas fa-map-marker-alt"></i>
                            <h4>Location:</h4>
                            <p>Kuala Lumpur</p>
                        </div>

                    </div>

                    <div class="profile-actions-footer-simple">
                        <a href="edit_profile.php" class="btn-primary"><i class="fas fa-edit"></i> Edit Profile</a>
                        <a href="logout.php" class="btn-secondary"><i class="fas fa-sign-out-alt"></i> Log Out</a>
                    </div>
                </div>
            <?php elseif (!$db_error): ?>
                <div class="message error-message">Cannot display profile. User session may be invalid.</div>
            <?php endif; ?>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>