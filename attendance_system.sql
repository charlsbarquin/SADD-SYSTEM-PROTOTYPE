-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Apr 05, 2025 at 04:34 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `login_attempts` int(11) DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `created_at`, `login_attempts`, `last_attempt`) VALUES
(3, 'admin', '$2y$10$jl/sAqn1Faery8LpnSYWqerEP76ah6/YZiBl2uTSva1LZfP99AgrG', 'admin@example.com', '2025-03-31 08:09:18', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `target_id`, `created_at`) VALUES
(1, 3, 'Approved professor ID 1', 1, '2025-04-01 07:55:30'),
(2, 3, 'Approved professor ID 2', 2, '2025-04-01 10:20:07'),
(3, 3, 'Approved professor ID 3', 3, '2025-04-01 10:38:31'),
(4, 3, 'Approved professor ID 4', 4, '2025-04-03 06:10:31'),
(5, 3, 'Approved professor ID 5', 5, '2025-04-03 06:41:12');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `professor_id` int(11) NOT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `auto_logout_time` datetime DEFAULT NULL,
  `work_duration` varchar(10) DEFAULT '0 hrs',
  `status` enum('Present','Absent','On Leave','Late') NOT NULL,
  `notes` text DEFAULT NULL,
  `face_scan_image` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `checkin_date` date NOT NULL DEFAULT curdate(),
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `auto_timeout` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `professor_id`, `check_in`, `check_out`, `auto_logout_time`, `work_duration`, `status`, `notes`, `face_scan_image`, `recorded_at`, `checkin_date`, `latitude`, `longitude`, `auto_timeout`) VALUES
(373, 1, '2025-04-05 01:15:12', NULL, NULL, '0 hrs', 'Present', NULL, 'checkin_1743786912.jpg', '2025-04-04 17:15:12', '2025-04-05', '14.099578', '122.9550349', 0),
(374, 4, '2025-04-05 09:52:54', NULL, NULL, '0 hrs', 'Present', NULL, 'checkin_1743817974.jpg', '2025-04-05 01:52:54', '2025-04-05', '14.099578', '122.9550349', 0),
(375, 3, '2025-04-05 09:55:52', NULL, NULL, '0 hrs', 'Present', NULL, 'checkin_1743818152.jpg', '2025-04-05 01:55:52', '2025-04-05', '13.3486731', '123.7069813', 0),
(376, 6, '2025-04-05 09:56:08', '2025-04-05 10:28:18', NULL, '00:32:10', 'Present', NULL, 'checkin_1743818168.jpg', '2025-04-05 01:56:08', '2025-04-05', '14.099578', '122.9550349', 0),
(377, 12, '2025-04-05 10:28:43', NULL, NULL, '0 hrs', 'Present', NULL, 'checkin_1743820123.jpg', '2025-04-05 02:28:43', '2025-04-05', '13.3487794', '123.7069381', 0);

--
-- Triggers `attendance`
--
DELIMITER $$
CREATE TRIGGER `after_attendance_checkin` AFTER INSERT ON `attendance` FOR EACH ROW BEGIN
    DECLARE prof_name VARCHAR(255);
    SELECT name INTO prof_name FROM professors WHERE id = NEW.professor_id;
    
    INSERT INTO logs (action, user, timestamp)
    VALUES (CONCAT('Check-in: ', prof_name), 'system', NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_attendance_checkout` AFTER UPDATE ON `attendance` FOR EACH ROW BEGIN
    IF OLD.check_out IS NULL AND NEW.check_out IS NOT NULL THEN
        INSERT INTO logs (action, user, timestamp)
        SELECT CONCAT('Check-out: ', name), 'system', NOW()
        FROM professors 
        WHERE id = NEW.professor_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_attendance_insert` BEFORE INSERT ON `attendance` FOR EACH ROW BEGIN
    DECLARE cutoff TIME;
    SELECT late_cutoff INTO cutoff FROM settings LIMIT 1;
    
    IF TIME(NEW.check_in) >= cutoff THEN
        SET NEW.status = 'Late';
    ELSE
        SET NEW.status = 'Present';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `user` varchar(100) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `action`, `user`, `timestamp`, `is_read`) VALUES
(1, 'Check-in: Arnold B. Platon', 'system', '2025-04-01 09:15:14', 0),
(2, 'Check-in: Paulo LL. Perete', 'system', '2025-04-01 09:31:08', 0),
(3, 'Check-in: Khristine A. Botin', 'system', '2025-04-01 09:32:54', 0),
(4, 'Check-in: Suzanne S. Causapin', 'system', '2025-04-01 09:52:39', 0),
(5, 'Check-out: Paulo LL. Perete', 'system', '2025-04-01 09:56:08', 0),
(6, 'Professor timed out', 'Paulo LL. Perete', '2025-04-01 09:56:08', 0),
(7, 'Check-in: Arnold B. Platon', 'system', '2025-04-01 10:37:50', 0),
(8, 'Check-out: Arnold B. Platon', 'system', '2025-04-01 10:38:06', 0),
(9, 'Professor timed out', 'Arnold B. Platon', '2025-04-01 10:38:06', 0),
(10, 'Check-in: Vince Angelo E. Naz ', 'system', '2025-04-01 11:07:00', 0),
(11, 'Check-in: Khristine A. Botin', 'system', '2025-04-01 11:57:20', 0),
(12, 'Check-out: Khristine A. Botin', 'system', '2025-04-01 11:58:38', 0),
(13, 'Professor timed out', 'Khristine A. Botin', '2025-04-01 11:58:38', 0),
(14, 'Check-out: Vince Angelo E. Naz ', 'system', '2025-04-01 11:58:54', 0),
(15, 'Professor timed out', 'Vince Angelo E. Naz ', '2025-04-01 11:58:54', 0),
(16, 'Check-in: Blessica B. Dorosan', 'system', '2025-04-01 16:55:36', 0),
(17, 'New professor registered: Charls Barquin', 'system', '2025-04-01 17:38:36', 0),
(18, 'New professor registered: Jemiel Honradez', 'system', '2025-04-01 18:15:00', 0),
(19, 'New professor registered: EJ Balaguer', 'system', '2025-04-01 18:19:24', 0),
(20, 'New professor registered: hello', 'system', '2025-04-01 18:42:54', 0),
(21, 'Check-out: Blessica B. Dorosan', 'system', '2025-04-01 21:05:54', 0),
(22, 'Check-in: Arnold B. Platon', 'system', '2025-04-01 22:19:24', 0),
(23, 'Check-in: Arnold B. Platon', 'system', '2025-04-01 22:21:50', 0),
(24, 'Check-in: Arnold B. Platon', 'system', '2025-04-01 22:23:04', 0),
(25, 'Check-in: Jerry B. Agsunod', 'system', '2025-04-01 22:38:54', 0),
(26, 'Check-in: Jerry B. Agsunod', 'system', '2025-04-01 22:39:38', 0),
(27, 'New professor registered: charls', 'system', '2025-04-01 23:18:30', 0),
(28, 'Check-in: Arnold B. Platon', 'system', '2025-04-01 23:20:27', 0),
(29, 'Check-out: Arnold B. Platon', 'system', '2025-04-01 23:26:43', 0),
(30, 'Check-in: Guillermo V. Red, Jr.', 'system', '2025-04-01 23:33:52', 0),
(31, 'Check-out: Guillermo V. Red, Jr.', 'system', '2025-04-01 23:59:03', 0),
(32, 'Check-in: Guillermo V. Red, Jr.', 'system', '2025-04-02 00:02:04', 0),
(33, 'Check-out: Guillermo V. Red, Jr.', 'system', '2025-04-02 00:07:31', 0),
(34, 'Check-in: Arnold B. Platon', 'system', '2025-04-02 00:10:29', 0),
(35, 'Check-in: Blessica B. Dorosan', 'system', '2025-04-02 00:10:51', 0),
(36, 'Check-out: Blessica B. Dorosan', 'system', '2025-04-02 00:11:49', 0),
(37, 'Check-in: Jerry B. Agsunod', 'system', '2025-04-02 00:12:32', 0),
(38, 'Check-out: Jerry B. Agsunod', 'system', '2025-04-02 09:49:54', 0),
(39, 'Check-in: Maria Charmy A. Arispe', 'system', '2025-04-02 11:55:47', 0),
(40, 'Check-in: Arnold B. Platon', 'system', '2025-04-02 12:07:03', 0),
(41, 'Check-in: Jerry B. Agsunod', 'system', '2025-04-02 12:08:00', 0),
(42, 'Check-in: Arnold B. Platon', 'system', '2025-04-02 12:50:01', 0),
(43, 'Check-in: Arnold B. Platon', 'system', '2025-04-02 13:39:06', 0),
(44, 'Check-in: Vince Angelo E. Naz ', 'system', '2025-04-02 13:42:33', 0),
(45, 'Check-in: Arnold B. Platon', 'system', '2025-04-02 13:44:00', 0),
(46, 'Check-in: Guillermo V. Red, Jr.', 'system', '2025-04-02 13:44:45', 0),
(47, 'Check-in: Suzanne S. Causapin', 'system', '2025-04-02 13:45:07', 0),
(48, 'Check-in: Khristine A. Botin', 'system', '2025-04-02 15:08:48', 0),
(49, 'Check-out: Arnold B. Platon', 'system', '2025-04-02 15:09:04', 0),
(50, 'Professor timed out', 'Arnold B. Platon', '2025-04-02 15:09:04', 0),
(51, 'Check-in: Mary Antoniette S. Ariño', 'system', '2025-04-02 20:44:53', 0),
(52, 'Check-in: Jorge Sulipicio S. Aganan', 'system', '2025-04-02 20:46:21', 0),
(53, 'Check-in: Maria Charmy A. Arispe', 'system', '2025-04-02 21:08:58', 0),
(54, 'Check-in: Paulo LL. Perete', 'system', '2025-04-03 11:57:39', 0),
(55, 'Check-in: Arnold B. Platon', 'system', '2025-04-03 14:36:24', 0),
(56, 'New professor registered: Charls barquin', 'system', '2025-04-03 14:36:56', 0),
(57, 'New professor registered: charls', 'system', '2025-04-03 14:39:22', 0),
(58, 'Check-in: Guillermo V. Red, Jr.', 'system', '2025-04-03 14:39:59', 0),
(59, 'Check-out: Guillermo V. Red, Jr.', 'system', '2025-04-03 14:40:38', 0),
(60, 'Check-out: Arnold B. Platon', 'system', '2025-04-03 14:43:22', 0),
(61, 'Check-in: Maria Charmy A. Arispe', 'system', '2025-04-03 14:44:03', 0),
(62, 'Check-out: Maria Charmy A. Arispe', 'system', '2025-04-03 14:44:09', 0),
(63, 'Check-in: Vince Angelo E. Naz ', 'system', '2025-04-03 14:45:08', 0),
(64, 'Check-in: Mary Antoniette S. Ariño', 'system', '2025-04-03 14:45:33', 0),
(65, 'Check-out: Mary Antoniette S. Ariño', 'system', '2025-04-03 14:58:08', 0),
(66, 'Check-in: Jorge Sulipicio S. Aganan', 'system', '2025-04-03 14:58:37', 0),
(67, 'Check-out: Jorge Sulipicio S. Aganan', 'system', '2025-04-03 14:58:46', 0),
(68, 'Check-in: Vince Angelo E. Naz ', 'system', '2025-04-03 18:33:15', 0),
(69, 'Check-in: Jerry B. Agsunod', 'system', '2025-04-03 18:33:15', 0),
(70, 'Check-out: Vince Angelo E. Naz ', 'system', '2025-04-03 18:33:31', 0),
(71, 'Professor timed out', 'Vince Angelo E. Naz ', '2025-04-03 18:33:31', 0),
(72, 'Check-in: Joseph L. Carinan', 'system', '2025-04-03 18:36:14', 0),
(73, 'Check-out: Joseph L. Carinan', 'system', '2025-04-03 18:36:24', 0),
(74, 'Professor timed out', 'Joseph L. Carinan', '2025-04-03 18:36:24', 0),
(75, 'Check-in: Maria Charmy A. Arispe', 'system', '2025-04-03 18:41:30', 0),
(76, 'Check-out: Jerry B. Agsunod', 'system', '2025-04-03 18:41:37', 0),
(77, 'Professor timed out', 'Jerry B. Agsunod', '2025-04-03 18:41:37', 0),
(78, 'Check-in: Mary Antoniette S. Ariño', 'system', '2025-04-03 18:52:54', 0),
(79, 'Check-out: Mary Antoniette S. Ariño', 'system', '2025-04-03 18:53:03', 0),
(80, 'Professor timed out', 'Mary Antoniette S. Ariño', '2025-04-03 18:53:03', 0),
(81, 'Check-in: Paulo LL. Perete', 'system', '2025-04-03 18:56:01', 0),
(82, 'Check-in: Guillermo V. Red, Jr.', 'system', '2025-04-03 19:14:34', 0),
(83, 'Check-out: Guillermo V. Red, Jr.', 'system', '2025-04-03 19:14:42', 0),
(84, 'Professor timed out', 'Guillermo V. Red, Jr.', '2025-04-03 19:14:42', 0),
(85, 'Check-in: Paulo LL. Perete', 'system', '2025-04-03 20:34:10', 0),
(86, 'Check-out: Paulo LL. Perete', 'system', '2025-04-03 20:34:21', 0),
(87, 'Professor timed out', 'Paulo LL. Perete', '2025-04-03 20:34:21', 0),
(88, 'Check-in: Arnold B. Platon', 'system', '2025-04-04 09:49:54', 0),
(89, 'Check-out: Arnold B. Platon', 'system', '2025-04-04 09:50:02', 0),
(90, 'Professor timed out', 'Arnold B. Platon', '2025-04-04 09:50:02', 0),
(91, 'Check-in: Guillermo V. Red, Jr.', 'system', '2025-04-04 09:54:35', 0),
(92, 'Check-out: Guillermo V. Red, Jr.', 'system', '2025-04-04 09:54:50', 0),
(93, 'Professor timed out', 'Guillermo V. Red, Jr.', '2025-04-04 09:54:50', 0),
(94, 'Check-in: Paulo LL. Perete', 'system', '2025-04-04 10:28:32', 0),
(95, 'Check-out: Paulo LL. Perete', 'system', '2025-04-04 10:28:37', 0),
(96, 'Professor timed out', 'Paulo LL. Perete', '2025-04-04 10:28:37', 0),
(97, 'Check-in: Joseph L. Carinan', 'system', '2025-04-04 10:53:23', 0),
(98, 'Check-out: Joseph L. Carinan', 'system', '2025-04-04 10:53:30', 0),
(99, 'Professor timed out', 'Joseph L. Carinan', '2025-04-04 10:53:30', 0),
(100, 'Check-in: Mary Antoniette S. Ariño', 'system', '2025-04-04 10:57:28', 0),
(101, 'Check-out: Mary Antoniette S. Ariño', 'system', '2025-04-04 10:57:34', 0),
(102, 'Professor timed out', 'Mary Antoniette S. Ariño', '2025-04-04 10:57:34', 0),
(103, 'Check-in: Paulo LL. Perete', 'system', '2025-04-04 12:25:53', 0),
(107, 'Check-out: Paulo LL. Perete', 'system', '2025-04-04 12:30:44', 0),
(108, 'Professor timed out', 'Paulo LL. Perete', '2025-04-04 12:30:44', 0),
(109, 'Check-in: Guillermo V. Red, Jr.', 'system', '2025-04-04 12:31:00', 0),
(110, 'Check-out: Guillermo V. Red, Jr.', 'system', '2025-04-04 12:31:08', 0),
(111, 'Professor timed out', 'Guillermo V. Red, Jr.', '2025-04-04 12:31:08', 0),
(112, 'Check-in: Vince Angelo E. Naz ', 'system', '2025-04-04 12:34:43', 0),
(113, 'Check-out: Vince Angelo E. Naz ', 'system', '2025-04-04 12:34:51', 0),
(114, 'Professor timed out', 'Vince Angelo E. Naz ', '2025-04-04 12:34:51', 0),
(115, 'Check-in: Mary Antoniette S. Ariño', 'system', '2025-04-04 12:40:46', 0),
(116, 'Check-out: Mary Antoniette S. Ariño', 'system', '2025-04-04 12:40:53', 0),
(117, 'Professor timed out', 'Mary Antoniette S. Ariño', '2025-04-04 12:40:53', 0),
(118, 'Check-in: Suzanne S. Causapin', 'system', '2025-04-04 13:26:47', 0),
(119, 'Check-out: Suzanne S. Causapin', 'system', '2025-04-04 13:26:56', 0),
(120, 'Professor timed out', 'Suzanne S. Causapin', '2025-04-04 13:26:56', 0),
(121, 'Check-in: Maria Charmy A. Arispe', 'system', '2025-04-04 13:49:39', 0),
(122, 'Check-in: Arnold B. Platon', 'system', '2025-04-04 13:50:50', 0),
(123, 'Check-out: Arnold B. Platon', 'system', '2025-04-04 13:50:57', 0),
(124, 'Professor timed out', 'Arnold B. Platon', '2025-04-04 13:50:57', 0),
(125, 'Check-in: Jerry B. Agsunod', 'system', '2025-04-04 13:56:10', 0),
(126, 'Check-in: Joseph L. Carinan', 'system', '2025-04-04 14:08:18', 0),
(127, 'Check-out: Jerry B. Agsunod', 'system', '2025-04-04 14:09:40', 0),
(128, 'Professor timed out', 'Jerry B. Agsunod', '2025-04-04 14:09:40', 0),
(129, 'Check-out: Joseph L. Carinan', 'system', '2025-04-04 14:10:24', 0),
(130, 'Professor timed out', 'Joseph L. Carinan', '2025-04-04 14:10:24', 0),
(131, 'Check-in: Mary Antoniette S. Ariño', 'system', '2025-04-04 14:13:32', 0),
(132, 'Check-out: Mary Antoniette S. Ariño', 'system', '2025-04-04 14:13:36', 0),
(133, 'Professor timed out', 'Mary Antoniette S. Ariño', '2025-04-04 14:13:36', 0),
(134, 'Check-in: Vince Angelo E. Naz ', 'system', '2025-04-04 22:22:08', 0),
(135, 'Check-in: Khristine A. Botin', 'system', '2025-04-04 22:25:44', 0),
(136, 'Check-in: Blessica B. Dorosan', 'system', '2025-04-04 22:33:51', 0),
(137, 'Check-in: Maria Charmy A. Arispe', 'system', '2025-04-04 22:35:03', 0),
(138, 'Check-in: Arnold B. Platon', 'system', '2025-04-04 22:37:59', 0),
(139, 'Check-out: Arnold B. Platon', 'system', '2025-04-04 22:38:16', 0),
(140, 'Professor timed out', 'Arnold B. Platon', '2025-04-04 22:38:16', 0),
(141, 'Check-in: Guillermo V. Red, Jr.', 'system', '2025-04-04 22:39:04', 0),
(142, 'Check-in: Vince Angelo E. Naz ', 'system', '2025-04-04 22:43:54', 0),
(143, 'Check-out: Guillermo V. Red, Jr.', 'system', '2025-04-04 22:44:26', 0),
(144, 'Professor timed out', 'Guillermo V. Red, Jr.', '2025-04-04 22:44:26', 0),
(145, 'Check-in: Paulo LL. Perete', 'system', '2025-04-04 22:49:54', 0),
(146, 'Check-out: Vince Angelo E. Naz ', 'system', '2025-04-04 22:50:02', 0),
(147, 'Professor timed out', 'Vince Angelo E. Naz ', '2025-04-04 22:50:02', 0),
(148, 'Check-out: Paulo LL. Perete', 'system', '2025-04-04 22:50:10', 0),
(149, 'Professor timed out', 'Paulo LL. Perete', '2025-04-04 22:50:10', 0),
(150, 'Check-in: Maria Charmy A. Arispe', 'system', '2025-04-04 22:50:44', 0),
(151, 'Check-out: Maria Charmy A. Arispe', 'system', '2025-04-04 22:50:49', 0),
(152, 'Professor timed out', 'Maria Charmy A. Arispe', '2025-04-04 22:50:49', 0),
(153, 'Check-in: Suzanne S. Causapin', 'system', '2025-04-04 23:12:25', 0),
(154, 'Check-in: Joseph L. Carinan', 'system', '2025-04-05 00:43:58', 0),
(155, 'Check-out: Joseph L. Carinan', 'system', '2025-04-05 00:44:12', 0),
(156, 'Professor timed out', 'Joseph L. Carinan', '2025-04-05 00:44:12', 0),
(157, 'Check-in: Mary Antoniette S. Ariño', 'system', '2025-04-05 00:44:52', 0),
(158, 'Check-out: Mary Antoniette S. Ariño', 'system', '2025-04-05 00:44:58', 0),
(159, 'Professor timed out', 'Mary Antoniette S. Ariño', '2025-04-05 00:44:58', 0),
(160, 'Check-in: Arnold B. Platon', 'system', '2025-04-05 01:15:12', 0),
(161, 'Check-in: Paulo LL. Perete', 'system', '2025-04-05 09:52:54', 0),
(162, 'Check-in: Jerry B. Agsunod', 'system', '2025-04-05 09:55:52', 0),
(163, 'Check-in: Maria Charmy A. Arispe', 'system', '2025-04-05 09:56:08', 0),
(164, 'Check-out: Maria Charmy A. Arispe', 'system', '2025-04-05 10:28:18', 0),
(165, 'Professor timed out', 'Maria Charmy A. Arispe', '2025-04-05 10:28:18', 0),
(166, 'Check-in: Mary Antoniette S. Ariño', 'system', '2025-04-05 10:28:43', 0);

-- --------------------------------------------------------

--
-- Table structure for table `professors`
--

CREATE TABLE `professors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','pending') DEFAULT 'pending',
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professors`
--

INSERT INTO `professors` (`id`, `name`, `email`, `designation`, `profile_image`, `created_at`, `status`, `approved_at`, `approved_by`, `department`, `phone`) VALUES
(1, 'Arnold B. Platon', 'johndoe@bup.edu.ph', 'Department Head', '', '2025-02-08 05:22:23', 'active', '2025-04-01 15:55:30', 3, 'Computer Studies Department', '0917283016'),
(2, 'Vince Angelo E. Naz ', 'janesmith@bup.edu.ph', 'BSIT Program Coordinator', '', '2025-02-08 05:22:23', 'active', '2025-04-01 18:20:07', 3, 'Computer Studies Department', '0950475140'),
(3, 'Jerry B. Agsunod', 'markdelacruz@bup.edu.ph', 'BSCS Program Coordinator', '', '2025-02-08 05:22:23', 'active', '2025-04-01 18:38:31', 3, 'Computer Studies Department', '0900526661'),
(4, 'Paulo LL. Perete', 'mariasantos@bup.edu.ph', 'BSIT-Animation Program Coordinator', '', '2025-02-08 06:17:30', 'active', '2025-04-03 14:10:31', 3, 'Computer Studies Department', '0951207890'),
(5, 'Guillermo V. Red, Jr.', 'rafaelcruz@bup.edu.ph', 'BSIS Program Coordinator', '', '2025-02-08 06:17:30', 'active', '2025-04-03 14:41:12', 3, 'Computer Studies Department', '0954459468'),
(6, 'Maria Charmy A. Arispe', 'angelareyes@bup.edu.ph', 'College IMO Coordinator', '', '2025-02-08 06:17:30', 'pending', NULL, NULL, 'Computer Studies Department', '0918673672'),
(7, 'Blessica B. Dorosan', 'michaeltan@bup.edu.ph', 'Professor', '', '2025-02-08 06:17:30', 'pending', NULL, NULL, 'Computer Studies Department', '0929989963'),
(8, 'Suzanne S. Causapin', 'sophiagomez@bup.edu.ph', 'Professor', '', '2025-02-08 06:17:30', 'pending', NULL, NULL, 'Computer Studies Department', '0993928801'),
(9, 'Khristine A. Botin', 'carlosvillanueva@bup.edu.ph', 'Professor', '', '2025-02-08 06:17:30', 'pending', NULL, NULL, 'Computer Studies Department', '0979674122'),
(10, 'Jorge Sulipicio S. Aganan', 'jessicalim@bup.edu.ph', 'Professor', '', '2025-02-08 06:17:30', 'pending', NULL, NULL, 'Computer Studies Department', '0916584207'),
(11, 'Joseph L. Carinan', 'benedictchua@bup.edu.ph', 'College Document Custodian', '', '2025-02-08 06:17:30', 'pending', NULL, NULL, 'Computer Studies Department', '0943898675'),
(12, 'Mary Antoniette S. Ariño', 'oliviamendoza@bup.edu.ph', 'College SIP Coordinator', '', '2025-02-08 06:17:30', 'pending', NULL, NULL, 'Computer Studies Department', '0969740758');

--
-- Triggers `professors`
--
DELIMITER $$
CREATE TRIGGER `after_professor_insert` AFTER INSERT ON `professors` FOR EACH ROW BEGIN
    INSERT INTO logs (action, user, timestamp)
    VALUES (CONCAT('New professor registered: ', NEW.name), 'system', NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `late_cutoff` time NOT NULL DEFAULT '08:00:00',
  `timezone` varchar(50) NOT NULL DEFAULT 'Asia/Manila',
  `allow_auto_timeout` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `late_cutoff`, `timezone`, `allow_auto_timeout`) VALUES
(1, '22:00:00', 'Asia/Manila', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_checkin` (`professor_id`,`checkin_date`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `professors`
--
ALTER TABLE `professors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_professors_approved_by` (`approved_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=378;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `professors`
--
ALTER TABLE `professors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_professor_attendance` FOREIGN KEY (`professor_id`) REFERENCES `professors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `professors`
--
ALTER TABLE `professors`
  ADD CONSTRAINT `fk_professors_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
