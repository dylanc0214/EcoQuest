<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available EcoQuests</title>
</head>
<body>
    <?php
    // pages/quests.php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // --- DB Connection and Dependencies ---
    // Make sure these paths are correct relative to this file
    include("../config/db.php"); // Provides $conn (MySQLi object)
    include("../includes/header.php");

    $user_id = $_SESSION['user_id'];
    $db_error = '';
    $quests = [];
    
    // Check if connection object exists and is successful
    $is_db_connected = isset($conn) && $conn instanceof mysqli && !$conn->connect_error;

    if (!$is_db_connected) {
        $db_error = 'Warning: Database connection failed. Cannot load quest list.';
    } else {
        // --- FETCH ACTIVE QUESTS AND USER STATUS ---
        
        $sql = "
            SELECT 
                q.quest_id, 
                q.title, 
                q.description, 
                q.points_award, 
                q.category, 
                q.proof_type,
                COALESCE(uq.status, 'Available') AS status 
            FROM quests q
            LEFT JOIN user_quests uq 
                ON q.quest_id = uq.quest_id AND uq.user_id = ?
            WHERE 
                q.is_active = 1
            ORDER BY 
                q.points_award DESC, q.title ASC";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id); // Bind user_id for the LEFT JOIN filter
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($quest = $result->fetch_assoc()) {
                    // --- UPDATED STATUS MAPPING LOGIC ---
                    if ($quest['status'] === 'completed') {
                        $quest['display_status'] = 'Completed';
                    } elseif ($quest['status'] === 'pending') {
                        // This status is set by validate.php after submission
                        $quest['display_status'] = 'Pending Review';
                    } elseif ($quest['status'] === 'active') { 
                        // Status when quest is STARTED but not submitted
                        $quest['display_status'] = 'In Progress';
                    } else {
                        // This catches 'Available' (from COALESCE)
                        $quest['display_status'] = 'Available'; 
                    }
                    $quests[] = $quest;
                }
            } else {
                $db_error = 'Query execution failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $db_error = 'Database query preparation failed: ' . $conn->error;
        }
    }

    // Determine the user's role to adjust difficulty display (simulated, needs actual DB column)
    $user_role = $_SESSION['user_role'] ?? 'student'; // Fallback to 'student'
    ?>

    <main class="quests-page">
        <div class="container">
            <h1 class="page-title">Ready for the Next Challenge? 🚀</h1>
            <p class="page-subtitle">Pick a quest, submit your proof, and start earning points for real impact. Cepat, don't miss out!</p>
            
            <?php if ($db_error): ?>
                <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
            <?php endif; ?>

            <div class="quest-controls">
                <div class="search-bar form-group">
                    <input type="text" placeholder="Search by title or theme...">
                </div>
                <div class="filter-dropdown form-group">
                    <select id="quest-filter">
                        <option value="all">Filter by Status: All</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending Review</option>
                    </select>
                </div>
            </div>

            <div class="quest-grid">
                <?php foreach ($quests as $quest): ?>
                    <?php
                    // Simple formatting for the status class name
                    $status_class = strtolower(str_replace(' ', '-', $quest['display_status']));
                    
                    // Simple simulation of difficulty based on points (you need a real 'difficulty' column)
                    $difficulty = $quest['points_award'] > 300 ? 'Hard' : ($quest['points_award'] > 150 ? 'Medium' : 'Easy');
                    ?>

                    <div class="quest-card status-<?php echo $status_class; ?>">

                        <div class="quest-header">
                            <span class="quest-theme"><?php echo htmlspecialchars($quest['category']); ?></span>
                            <span class="quest-points">+<?php echo number_format($quest['points_award']); ?> PTS</span>
                        </div>

                        <h3 class="quest-title"><?php echo htmlspecialchars($quest['title']); ?></h3>
                        <p class="quest-desc"><?php echo htmlspecialchars($quest['description']); ?></p>

                        <div class="quest-footer">
                            <span class="quest-difficulty"><?php echo $difficulty; ?></span>

                            <?php if ($quest['display_status'] == 'Available'): ?>
                                <span class="quest-status"><?php echo $quest['display_status']; ?></span>
                                <a href="quest_detail.php?id=<?php echo $quest['quest_id']; ?>" class="btn-submit">Start Quest</a>
                            <?php elseif ($quest['display_status'] == 'In Progress'): ?>
                                <span class="quest-status in-progress"><?php echo $quest['display_status']; ?></span>
                                <!-- Link to the validation page to submit proof -->
                                <a href="validate.php" class="btn-submit btn-continue">Submit Proof</a>
                            <?php elseif ($quest['display_status'] == 'Pending Review'): ?>
                                <!-- This is the key update: Display pending and disable action -->
                                <span class="quest-status pending-review"><?php echo $quest['display_status']; ?></span>
                                <span class="btn-pending">Waiting...</span>
                            <?php else: /* Completed */ ?>
                                <span class="quest-status completed"><?php echo $quest['display_status']; ?></span>
                                <span class="btn-completed">Done! 🎉</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($quests) && !$db_error): ?>
                    <p class="no-quests">Aiyo, looks like no active quests right now! Check back soon or contact your admin.</p>
                <?php endif; ?>
            </div>

        </div>
    </main>

<?php include("../includes/footer.php"); ?>
