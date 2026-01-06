-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 06, 2026 at 11:31 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecoquest`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievement`
--

DROP TABLE IF EXISTS `achievement`;
CREATE TABLE IF NOT EXISTS `achievement` (
  `Achievement_id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Exp_point` int DEFAULT NULL,
  PRIMARY KEY (`Achievement_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `achievement`
--

INSERT INTO `achievement` (`Achievement_id`, `Title`, `Description`, `Exp_point`) VALUES
(1, 'Green Beginner', 'Complete your first quest.', NULL),
(2, 'Eco Warrior', 'Complete 5 quests.', NULL),
(3, 'Planet Savior', 'Complete 10 quests.', NULL),
(4, 'Voice of Change', 'Create your first forum post.', NULL),
(5, 'Community Leader', 'Create 5 forum posts.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `Admin_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  PRIMARY KEY (`Admin_id`),
  KEY `Admin_fk_User_id` (`User_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin_id`, `User_id`) VALUES
(1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `badge`
--

DROP TABLE IF EXISTS `badge`;
CREATE TABLE IF NOT EXISTS `badge` (
  `Badge_id` int NOT NULL AUTO_INCREMENT,
  `Badge_Name` varchar(255) DEFAULT NULL,
  `Badge_image` varchar(255) DEFAULT NULL,
  `Require_Exp_Points` int DEFAULT NULL,
  `Is_active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`Badge_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `badge`
--

INSERT INTO `badge` (`Badge_id`, `Badge_Name`, `Badge_image`, `Require_Exp_Points`, `Is_active`) VALUES
(1, 'Bronze Scavenger', '🥉', 100, 1),
(2, 'Silver Guardian', '🥈', 500, 1),
(3, 'Gold Hero', '🥇', 1000, 1),
(4, 'Platinum Legend', '💎', 2500, 1),
(5, 'Diamond Master', '👑', 5000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
CREATE TABLE IF NOT EXISTS `comment` (
  `Comment_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  `Post_id` int NOT NULL,
  `Comment` text,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Comment_id`),
  KEY `Comment_fk_Student_id` (`User_id`),
  KEY `Comment_fk_Post_id` (`Post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`Comment_id`, `User_id`, `Post_id`, `Comment`, `Created_at`) VALUES
(1, 1, 2, 'Hope everyone will like it.', '2025-12-11 06:42:39'),
(4, 3, 6, 'test', '2026-01-05 13:32:51'),
(5, 4, 8, 'asd', '2026-01-06 05:17:56');

-- --------------------------------------------------------

--
-- Table structure for table `comment_report`
--

DROP TABLE IF EXISTS `comment_report`;
CREATE TABLE IF NOT EXISTS `comment_report` (
  `Comment_report_id` int NOT NULL AUTO_INCREMENT,
  `Comment_id` int NOT NULL,
  `Reason` text,
  `Report_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(50) DEFAULT NULL,
  `Reported_by` int NOT NULL,
  PRIMARY KEY (`Comment_report_id`),
  KEY `CommentReport_fk_Comment_id` (`Comment_id`),
  KEY `CommentReport_fk_Reported_by` (`Reported_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comment_report`
--

INSERT INTO `comment_report` (`Comment_report_id`, `Comment_id`, `Reason`, `Report_time`, `Status`, `Reported_by`) VALUES
(1, 1, 'Violent Content', '2026-01-05 13:06:10', 'Completed', 1),
(2, 5, 'Spam', '2026-01-06 06:25:46', 'Pending', 1);

-- --------------------------------------------------------

--
-- Table structure for table `moderator`
--

DROP TABLE IF EXISTS `moderator`;
CREATE TABLE IF NOT EXISTS `moderator` (
  `Moderator_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  PRIMARY KEY (`Moderator_id`),
  KEY `Moderator_fk_User_id` (`User_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `moderator`
--

INSERT INTO `moderator` (`Moderator_id`, `User_id`) VALUES
(1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

DROP TABLE IF EXISTS `post`;
CREATE TABLE IF NOT EXISTS `post` (
  `Post_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Content` text,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Post_id`),
  KEY `Post_fk_Student_id` (`User_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `post`
--

INSERT INTO `post` (`Post_id`, `User_id`, `Image`, `Title`, `Content`, `Created_at`) VALUES
(2, 1, NULL, 'Recycling', 'I Love Doing Recycle', '2025-11-14 17:23:22'),
(3, 2, 'uploads/forum/post_695a43564219f3.32796086.png', 'I plant a tree.', 'I don\'t believe i made it!', '2026-01-04 10:39:18'),
(6, 3, NULL, 'test', 'test', '2026-01-05 13:32:39'),
(7, 4, NULL, '67', '67', '2026-01-05 15:08:48'),
(8, 4, NULL, 'test mute', 'a', '2026-01-06 05:17:34'),
(9, 3, NULL, 'te', 't', '2026-01-06 05:37:02');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

DROP TABLE IF EXISTS `post_likes`;
CREATE TABLE IF NOT EXISTS `post_likes` (
  `Like_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  `Post_id` int NOT NULL,
  PRIMARY KEY (`Like_id`),
  UNIQUE KEY `student_post_like` (`User_id`,`Post_id`),
  KEY `Like_fk_Post_id` (`Post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`Like_id`, `User_id`, `Post_id`) VALUES
(7, 1, 2),
(12, 4, 8);

-- --------------------------------------------------------

--
-- Table structure for table `post_report`
--

DROP TABLE IF EXISTS `post_report`;
CREATE TABLE IF NOT EXISTS `post_report` (
  `Post_report_id` int NOT NULL AUTO_INCREMENT,
  `Post_id` int NOT NULL,
  `Reason` text,
  `Report_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(50) DEFAULT NULL,
  `Reported_by` int NOT NULL,
  PRIMARY KEY (`Post_report_id`),
  KEY `PostReport_fk_Post_id` (`Post_id`),
  KEY `PostReport_fk_Reported_by` (`Reported_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `post_report`
--

INSERT INTO `post_report` (`Post_report_id`, `Post_id`, `Reason`, `Report_time`, `Status`, `Reported_by`) VALUES
(1, 2, 'Sexual Content', '2025-12-11 06:43:24', 'Pending', 1),
(2, 3, 'Spam', '2026-01-05 13:05:25', 'Pending', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quest`
--

DROP TABLE IF EXISTS `quest`;
CREATE TABLE IF NOT EXISTS `quest` (
  `Quest_id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Points_award` int DEFAULT NULL,
  `Proof_type` varchar(50) DEFAULT NULL,
  `Instructions` text,
  `Is_active` tinyint(1) DEFAULT '1',
  `Created_by` int DEFAULT NULL,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `CategoryID` int DEFAULT NULL,
  PRIMARY KEY (`Quest_id`),
  KEY `Quest_fk_Created_by` (`Created_by`),
  KEY `fk_quest_category` (`CategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quest`
--

INSERT INTO `quest` (`Quest_id`, `Title`, `Description`, `Points_award`, `Proof_type`, `Instructions`, `Is_active`, `Created_by`, `Created_at`, `CategoryID`) VALUES
(1, 'Zero Sachet Hero', 'Reduce your daily single-use plastic waste by replacing small plastic items (like straws, plastic cutlery, snack bags, or sachets) with reusable options for one week.', 150, 'Image', 'Take a &#39;Before&#39; photo of your typical packed lunch/snack setup with single-use plastic items. After 7 days, submit a &#39;Reflection Text&#39; (min 100 words) describing the challenges and successes, and an &#39;After&#39; photo showing your reusable alternatives (like a container/straw set).', 1, 1, '2025-11-10 04:14:37', 1),
(2, 'Power Down Detective', 'Conduct a 2-day audit of electricity wastage in a common area (like the classroom, library, or common hall) by identifying and documenting lights or devices left on when not in use.', 200, 'Image', 'Use a template to record at least 5 specific instances of wastage (Text Proof: time, location, device, estimated duration of waste). Submit one Image Proof of a &#39;Wasted Energy&#39; moment (e.g., a light on in an empty room) before taking action to turn it off.', 1, 1, '2025-11-10 04:15:02', 2),
(3, 'The Leak Hunter', 'Identify and report a leaking tap, hose, or running toilet at school or home, then track the progress of its repair or fix it yourself (if minor/safe).', 180, 'Image', 'Identify and report a leaking tap, hose, or running toilet at school or home, then track the progress of its repair or fix it yourself (if minor/safe).', 1, 1, '2025-11-10 04:15:39', 3),
(4, 'Local Ride Challenge', 'Swap out a motorized trip (car/bus/motorcycle) for a sustainable transport method (walking, cycling, public transport) for a set journey, and calculate the estimated CO2 saving.', 250, 'Image', 'Submit an Image Proof of you using the sustainable transport (e.g., locking your bike, standing on the bus). Submit a Text Proof detailing the original journey distance/mode and the estimated CO2 saved (use a simple online calculator for estimation, state the number in kg).', 1, 1, '2025-11-10 04:16:08', 4),
(5, 'Trash to Treasure Transformation', 'Transform a piece of discarded material (cardboard, old clothes, plastic bottles) into a useful or decorative item.', 220, 'Image', 'Submit a &#39;Before&#39; Image of the waste material. Submit a final &#39;Product Image&#39; of your upcycled creation, alongside a &#39;Description Text&#39; (min 70 words) explaining what you made and how it is useful.', 1, 1, '2025-11-10 04:16:51', 6);

-- --------------------------------------------------------

--
-- Table structure for table `quest_calendar`
--

DROP TABLE IF EXISTS `quest_calendar`;
CREATE TABLE IF NOT EXISTS `quest_calendar` (
  `Calendar_id` int NOT NULL AUTO_INCREMENT,
  `Quest_id` int NOT NULL,
  `Start_date` date NOT NULL,
  `End_date` date NOT NULL,
  PRIMARY KEY (`Calendar_id`),
  KEY `Quest_id` (`Quest_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quest_categories`
--

DROP TABLE IF EXISTS `quest_categories`;
CREATE TABLE IF NOT EXISTS `quest_categories` (
  `CategoryID` int NOT NULL AUTO_INCREMENT,
  `Category_Name` varchar(100) NOT NULL,
  PRIMARY KEY (`CategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quest_categories`
--

INSERT INTO `quest_categories` (`CategoryID`, `Category_Name`) VALUES
(1, 'Plastic Use'),
(2, 'Energy Use'),
(3, 'Water Conservation'),
(4, 'Carbon Footprint'),
(5, 'Resource Consumption'),
(6, 'Upcycling');

-- --------------------------------------------------------

--
-- Table structure for table `quest_progress`
--

DROP TABLE IF EXISTS `quest_progress`;
CREATE TABLE IF NOT EXISTS `quest_progress` (
  `Quest_Progress_id` int NOT NULL AUTO_INCREMENT,
  `Quest_id` int NOT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Completed_at` datetime DEFAULT NULL,
  `Student_id` int NOT NULL,
  PRIMARY KEY (`Quest_Progress_id`),
  KEY `QuestProgress_fk_Quest_id` (`Quest_id`),
  KEY `QuestProgress_fk_Student_id` (`Student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quest_progress`
--

INSERT INTO `quest_progress` (`Quest_Progress_id`, `Quest_id`, `Status`, `Completed_at`, `Student_id`) VALUES
(2, 4, 'completed', NULL, 1),
(3, 5, 'pending', NULL, 1),
(4, 2, 'active', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `redemption_history`
--

DROP TABLE IF EXISTS `redemption_history`;
CREATE TABLE IF NOT EXISTS `redemption_history` (
  `Redemption_History_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Reward_id` int NOT NULL,
  `Points_used` int DEFAULT NULL,
  `Redemption_date` datetime DEFAULT NULL,
  PRIMARY KEY (`Redemption_History_id`),
  KEY `Redemption_fk_Student_id` (`Student_id`),
  KEY `Redemption_fk_Reward_id` (`Reward_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `redemption_history`
--

INSERT INTO `redemption_history` (`Redemption_History_id`, `Student_id`, `Reward_id`, `Points_used`, `Redemption_date`) VALUES
(1, 1, 1, 250, '2025-11-17 13:33:46');

-- --------------------------------------------------------

--
-- Table structure for table `reward`
--

DROP TABLE IF EXISTS `reward`;
CREATE TABLE IF NOT EXISTS `reward` (
  `Reward_id` int NOT NULL AUTO_INCREMENT,
  `Reward_name` varchar(255) DEFAULT NULL,
  `Description` text,
  `Points_cost` int DEFAULT NULL,
  `Stock` int DEFAULT NULL,
  `Image_url` varchar(255) DEFAULT NULL,
  `Is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`Reward_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reward`
--

INSERT INTO `reward` (`Reward_id`, `Reward_name`, `Description`, `Points_cost`, `Stock`, `Image_url`, `Is_active`) VALUES
(1, 'Cuppa Sustainability', 'A voucher for one free premium drink from the school canteen or a partnering local cafe (like a fancy frappe or latte). Perfect fuel after a long quest!', 260, 29, NULL, 1),
(2, 'Eco-Planner Pack', 'A sustainable stationary bundle: one recycled paper notebook and a bamboo pen. Keep those assignment notes eco-friendly!', 350, 50, NULL, 1),
(3, 'Early Access Pass', 'Get priority entry to the next major school event (e.g., school carnival, sports day entrance, or lecture hall seating). Skip the queue, you earned it!', 400, 20, NULL, 1),
(4, 'Powerbank', 'A high-quality mini power bank to keep your devices charged, essential for any IT student or quest winner. Named after your pet, lah!', 550, 17, NULL, 1),
(5, 'Retreat Day', 'A voucher for a \"No Homework Pass\" for one subject or a \"Uniform Exemption Day\" (wear casual clothes). A day off is priceless!', 700, 11, NULL, 1),
(6, 'Master of Sustainability Badge', 'An exclusive physical badge/medal awarded at the next school assembly, plus a mention on the school&#39;s social media/newsletter. Flex on your friends.', 1000, 5, NULL, 1),
(11, 'tree', 'tree', 5000, 10, '../../assets/uploads/rewards/reward_1767498877_846.webp', 1);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `Student_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  `Ban_time` datetime DEFAULT NULL,
  `Mute_comment` datetime DEFAULT NULL,
  `Mute_post` datetime DEFAULT NULL,
  `Total_Exp_Point` int DEFAULT '0',
  `Total_point` int DEFAULT '0',
  `Quest_Progress_id` int DEFAULT NULL,
  PRIMARY KEY (`Student_id`),
  KEY `Student_fk_User_id` (`User_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`Student_id`, `User_id`, `Ban_time`, `Mute_comment`, `Mute_post`, `Total_Exp_Point`, `Total_point`, `Quest_Progress_id`) VALUES
(1, 1, NULL, NULL, NULL, 0, 0, NULL),
(2, 4, '2026-01-01 05:06:47', '2026-01-01 05:59:04', '2026-01-07 05:16:28', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_achievement`
--

DROP TABLE IF EXISTS `student_achievement`;
CREATE TABLE IF NOT EXISTS `student_achievement` (
  `Student_Achievement_id` int NOT NULL AUTO_INCREMENT,
  `Achievement_id` int NOT NULL,
  `Student_id` int NOT NULL,
  `Status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Student_Achievement_id`),
  KEY `StudentAchievement_fk_Achievement_id` (`Achievement_id`),
  KEY `StudentAchievement_fk_Student_id` (`Student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_achievement`
--

INSERT INTO `student_achievement` (`Student_Achievement_id`, `Achievement_id`, `Student_id`, `Status`) VALUES
(1, 1, 1, 'Unlocked'),
(2, 4, 1, 'Unlocked');

-- --------------------------------------------------------

--
-- Table structure for table `student_badge`
--

DROP TABLE IF EXISTS `student_badge`;
CREATE TABLE IF NOT EXISTS `student_badge` (
  `Student_Badge_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Badge_id` int NOT NULL,
  `Earned_Date` datetime DEFAULT NULL,
  PRIMARY KEY (`Student_Badge_id`),
  KEY `StudentBadge_fk_Student_id` (`Student_id`),
  KEY `StudentBadge_fk_Badge_id` (`Badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_feedback`
--

DROP TABLE IF EXISTS `student_feedback`;
CREATE TABLE IF NOT EXISTS `student_feedback` (
  `Student_feedback_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Date_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Student_feedback_id`),
  KEY `Feedback_fk_Student_id` (`Student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_feedback`
--

INSERT INTO `student_feedback` (`Student_feedback_id`, `Student_id`, `Title`, `Description`, `Date_time`) VALUES
(1, 1, 'test', 'test123', '2026-01-04 03:55:35'),
(2, 1, 'test', 'testttt', '2026-01-06 05:12:51');

-- --------------------------------------------------------

--
-- Table structure for table `student_moderation_records`
--

DROP TABLE IF EXISTS `student_moderation_records`;
CREATE TABLE IF NOT EXISTS `student_moderation_records` (
  `Student_moderation_records_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `User_id` int NOT NULL,
  `Reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Description` text,
  `Duration` varchar(100) DEFAULT NULL,
  `Date_Time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Student_moderation_records_id`),
  KEY `ModRecord_fk_Student_id` (`Student_id`),
  KEY `ModRecord_fk_User_id` (`User_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_moderation_records`
--

INSERT INTO `student_moderation_records` (`Student_moderation_records_id`, `Student_id`, `User_id`, `Reason`, `Description`, `Duration`, `Date_Time`) VALUES
(18, 2, 3, 'Unmute Comment', 'Unmute_comment action', '0', '2026-01-06 13:58:58'),
(19, 2, 3, 'Mute Comment', 'bad', '1', '2026-01-06 13:59:04');

-- --------------------------------------------------------

--
-- Table structure for table `student_quest_submissions`
--

DROP TABLE IF EXISTS `student_quest_submissions`;
CREATE TABLE IF NOT EXISTS `student_quest_submissions` (
  `Student_quest_submission_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Quest_id` int NOT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Submission_date` datetime DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Moderator_id` int DEFAULT NULL,
  `Review_date` datetime DEFAULT NULL,
  `Review_feedback` text,
  PRIMARY KEY (`Student_quest_submission_id`),
  KEY `Submission_fk_Student_id` (`Student_id`),
  KEY `Submission_fk_Quest_id` (`Quest_id`),
  KEY `Submission_fk_Moderator_id` (`Moderator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_quest_submissions`
--

INSERT INTO `student_quest_submissions` (`Student_quest_submission_id`, `Student_id`, `Quest_id`, `Image`, `Submission_date`, `Status`, `Moderator_id`, `Review_date`, `Review_feedback`) VALUES
(3, 1, 4, 'uploads/activities/proof_691ab23db7bc56.77390705_website-export-1763324521010.png', '2025-11-17 13:27:25', 'completed', NULL, '2025-11-17 13:33:16', 'Good'),
(4, 1, 5, 'uploads/activities/proof_691eaefcef9359.86533957_Screenshot 2025-11-17 191945.png', '2025-11-20 14:02:36', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_report`
--

DROP TABLE IF EXISTS `student_report`;
CREATE TABLE IF NOT EXISTS `student_report` (
  `Student_report_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Report_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(50) DEFAULT NULL,
  `Review_time` datetime DEFAULT NULL,
  `Moderator_id` int DEFAULT NULL,
  PRIMARY KEY (`Student_report_id`),
  KEY `Report_fk_Student_id` (`Student_id`),
  KEY `Report_fk_Moderator_id` (`Moderator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `User_id` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Role` enum('student','moderator','admin') NOT NULL,
  `Password_hash` varchar(255) NOT NULL,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`User_id`),
  UNIQUE KEY `Username_UNIQUE` (`Username`),
  UNIQUE KEY `Email_UNIQUE` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_id`, `Username`, `Email`, `Role`, `Password_hash`, `Created_at`) VALUES
(1, 'TP123456', 'TP123456@mail.apu.edu.my', 'student', '$2y$10$fCrzR7hnNzYhwyMmawTuk.fL7G1/jUXDyjNCPbfYQHSkVbU2Gs1ue', '2025-11-03 18:19:23'),
(2, 'AD123456', 'admin@mail.apu.edu.my', 'admin', '$2y$10$fCrzR7hnNzYhwyMmawTuk.fL7G1/jUXDyjNCPbfYQHSkVbU2Gs1ue', '2025-11-04 01:32:20'),
(3, 'MD123456', 'mod@mail.apu.edu.my', 'moderator', '$2y$10$u3QRPAFVpUX3ymkI6/2l0OKgvmJV30HSwoPxc0cODPdZDsm92q6Y.', '2025-11-04 01:32:55'),
(4, 'tp67', '67@gmail.com', 'student', '$2y$10$SdQYVFRPoF4OoLMLzCnCsOrlZEq/Ubx.OIcE6Z9zV/3sGWclezyJC', '2026-01-05 13:08:10');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `Admin_fk_User_id` FOREIGN KEY (`User_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `Comment_fk_Post_id` FOREIGN KEY (`Post_id`) REFERENCES `post` (`Post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `Comment_fk_Student_id` FOREIGN KEY (`User_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Constraints for table `comment_report`
--
ALTER TABLE `comment_report`
  ADD CONSTRAINT `CommentReport_fk_Comment_id` FOREIGN KEY (`Comment_id`) REFERENCES `comment` (`Comment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `CommentReport_fk_Reported_by` FOREIGN KEY (`Reported_by`) REFERENCES `user` (`User_id`) ON DELETE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `Post_fk_Student_id` FOREIGN KEY (`User_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
