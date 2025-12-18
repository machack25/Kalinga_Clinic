-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 21, 2025 at 07:55 AM
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
-- Database: `u587983768_kalinga_clinic`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('admin','super_admin') NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `full_name`, `email`, `password`, `created_at`, `role`) VALUES
(1, 'admin1', 'Kalinga Clinic', 'admin@example.com', '$2y$10$JtdnS9DslLhNOPGBgmvX0eRHl4Tvx6hmdLsTaANa04RxF1Z2S8gNu', '2025-09-20 16:00:58', 'admin'),
(2, 'superadmin1', 'Kalinga Clinic Admin', 'superadmin@example.com', '$2y$10$wqfGDSoBMzNNIlWNZ0YwWu63ZCl9bRBBORvvmTo1N.AygJhnhe.0K', '2025-09-20 18:02:59', 'super_admin');

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `queue_number` int(11) DEFAULT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `queue_number`, `status`, `created_at`, `updated_at`) VALUES
(35, 13, 3, '2025-10-02', '13:30:00', 'Check Up', NULL, 'Completed', '2025-10-01 16:44:24', '2025-10-01 17:05:03'),
(46, 19, 2, '2025-10-15', '11:00:00', 'i have dysmenorrhea', NULL, 'Completed', '2025-10-12 05:43:30', '2025-10-15 09:46:47'),
(52, 45, 1, '2025-10-14', '12:00:00', 'Headache, Dizziness, Vomiting', NULL, 'Completed', '2025-10-14 02:49:55', '2025-10-14 02:52:17'),
(53, 45, 1, '2025-10-15', '13:30:00', 'Headache, Shortness of Breath, Nosebleeds', NULL, '', '2025-10-14 02:51:25', '2025-10-16 05:27:07'),
(55, 48, 3, '2025-10-16', '09:00:00', 'Flu', NULL, '', '2025-10-15 16:22:07', '2025-10-17 05:13:15'),
(59, 13, 1, '2025-10-17', '15:10:00', 'Checkup', NULL, 'Completed', '2025-10-17 07:04:33', '2025-10-17 07:04:41'),
(60, 58, 3, '2025-10-18', '10:00:00', 'Chest Pain', NULL, 'Completed', '2025-10-18 01:46:19', '2025-10-18 02:06:23'),
(61, 58, 2, '2025-10-19', '10:00:00', 'Chest Pain', NULL, 'Completed', '2025-10-18 01:48:08', '2025-10-18 02:01:19'),
(63, 13, 1, '2025-10-20', '12:00:00', 'Check Up', 1, '', '2025-10-19 14:54:55', '2025-10-20 13:38:54'),
(64, 13, 1, '2025-10-21', '10:00:00', 'Test', 1, 'Cancelled', '2025-10-20 13:54:41', '2025-10-21 03:52:43'),
(65, 13, 1, '2025-10-25', '15:00:00', 'Appointment', NULL, 'Scheduled', '2025-10-21 04:09:02', '2025-10-21 04:09:02'),
(66, 16, 1, '2025-10-21', '16:00:00', 'Checkup', NULL, 'Scheduled', '2025-10-21 05:02:39', '2025-10-21 05:02:39'),
(67, 16, 1, '2025-10-21', '16:10:00', 'test', NULL, 'Cancelled', '2025-10-21 05:04:34', '2025-10-21 05:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `chat_logs`
--

CREATE TABLE `chat_logs` (
  `log_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `user_message` text NOT NULL,
  `bot_response` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation`
--

CREATE TABLE `consultation` (
  `consultation_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `consultation_date` date NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consultation`
--

INSERT INTO `consultation` (`consultation_id`, `appointment_id`, `consultation_date`, `diagnosis`, `prescription`, `notes`, `created_at`, `patient_id`, `doctor_id`) VALUES
(46, 35, '2025-10-02', 'Cleared', 'Monitor Health', 'Take prescribed medications at the right time', '2025-10-01 17:05:03', 13, 3),
(47, 52, '2025-10-14', 'Consultation completed - diagnosis pending', 'Prescription to be added', 'Consultation completed. Please update diagnosis and prescription details.', '2025-10-14 02:52:17', 45, 1),
(48, 46, '2025-10-15', 'Consultation completed - diagnosis pending', 'Take Medicines', 'Don\'t overdo', '2025-10-15 09:46:47', 19, 2),
(49, 59, '2025-10-17', 'Cleared', 'Paracetamol\r\n', 'Take the Right Prescription', '2025-10-17 07:04:41', 13, 1),
(50, 61, '2025-10-19', 'Consultation completed - diagnosis pending', 'Biogesic 3x A day', 'Heart Burn: \r\nECG\r\nBlood Chem', '2025-10-18 02:01:19', 58, 2),
(51, 60, '2025-10-18', 'Consultation completed - diagnosis pending', 'Biogesic 10x a day every 2hrs\r\n\r\nAspirin', 'Xray \r\n', '2025-10-18 02:06:23', 58, 3),
(52, NULL, '2025-10-21', 'test', 'test', '0', '2025-10-21 04:46:22', 16, 1),
(53, NULL, '2025-10-21', 'test', 'test', '0', '2025-10-21 04:47:07', 18, 1);

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doctor_id` int(11) NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`doctor_id`, `doctor_name`, `specialization`, `contact_number`, `email`, `created_at`) VALUES
(1, 'Dr. Jerick G. Linga', 'PHD', NULL, NULL, '2025-09-18 19:54:08'),
(2, 'Dr. Reyes', 'PHD', NULL, NULL, '2025-09-18 19:54:08'),
(3, 'Dr. Dela Cruz', 'PHD', NULL, NULL, '2025-09-18 19:54:08');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

CREATE TABLE `doctor_availability` (
  `availability_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL COMMENT '1=Monday, 2=Tuesday, ..., 7=Sunday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_availability`
--

INSERT INTO `doctor_availability` (`availability_id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(21, 1, 1, '09:00:00', '18:00:00'),
(11, 1, 2, '09:00:00', '18:00:00'),
(12, 1, 3, '09:00:00', '18:00:00'),
(22, 1, 4, '09:00:00', '18:00:00'),
(13, 1, 5, '09:00:00', '18:00:00'),
(26, 1, 6, '09:00:00', '18:00:00'),
(4, 2, 2, '09:00:00', '17:00:00'),
(5, 2, 3, '09:00:00', '17:00:00'),
(6, 2, 5, '09:00:00', '17:00:00'),
(23, 2, 7, '09:00:00', '17:00:00'),
(25, 3, 1, '12:00:00', '18:00:00'),
(8, 3, 2, '09:00:00', '18:00:00'),
(9, 3, 4, '09:00:00', '18:00:00'),
(10, 3, 5, '10:00:00', '18:00:00'),
(24, 3, 6, '09:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `availability` enum('Yes','No') NOT NULL DEFAULT 'No',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `medicine_name`, `availability`, `last_updated`) VALUES
(1, 'Paracetamol', 'Yes', '2025-10-01 16:03:25'),
(3, 'Ibuprofen', 'Yes', '2025-10-01 16:03:25'),
(4, 'Amlodipine', 'Yes', '2025-10-04 18:19:18'),
(5, 'amoxicillin', 'No', '2025-10-15 09:46:34'),
(6, 'Ciprofloxacin', 'Yes', '2025-10-01 16:33:14'),
(7, 'Ampicillin', 'No', '2025-10-04 21:33:51'),
(8, 'Metformin', 'Yes', '2025-10-06 03:05:23'),
(9, 'Lisinopril', 'Yes', '2025-10-06 03:05:23'),
(10, 'Atorvastatin', 'No', '2025-10-06 03:05:23'),
(11, 'Levothyroxine', 'Yes', '2025-10-06 03:05:23'),
(12, 'Albuterol', 'Yes', '2025-10-06 03:12:13'),
(13, 'Omeprazole', 'Yes', '2025-10-06 03:05:23'),
(14, 'Losartan', 'Yes', '2025-10-06 03:05:23'),
(15, 'Gabapentin', 'No', '2025-10-06 03:05:23'),
(16, 'Hydrochlorothiazide', 'Yes', '2025-10-06 03:05:23'),
(17, 'Sertraline', 'Yes', '2025-10-06 03:05:23'),
(18, 'Acetaminophen', 'No', '2025-10-17 07:06:11'),
(19, 'Cetirizine', 'Yes', '2025-10-06 03:05:23'),
(20, 'Diphenhydramine', 'No', '2025-10-06 03:05:23'),
(21, 'Naproxen', 'Yes', '2025-10-06 03:05:23'),
(22, 'Dextromethorphan', 'Yes', '2025-10-06 03:05:23'),
(23, 'Montelukast', 'Yes', '2025-10-06 03:06:29'),
(24, 'Furosemide', 'No', '2025-10-06 03:06:29'),
(25, 'Pantoprazole', 'Yes', '2025-10-06 03:06:29'),
(26, 'Escitalopram', 'Yes', '2025-10-06 03:06:29'),
(27, 'Tamsulosin', 'No', '2025-10-06 03:06:29'),
(28, 'Prednisone', 'Yes', '2025-10-06 03:06:29'),
(29, 'Clopidogrel', 'Yes', '2025-10-06 03:06:29'),
(30, 'Warfarin', 'Yes', '2025-10-06 03:06:29'),
(31, 'Meloxicam', 'No', '2025-10-06 03:06:29'),
(32, 'Rosuvastatin', 'Yes', '2025-10-06 03:06:29'),
(33, 'Tramadol', 'Yes', '2025-10-06 03:06:29'),
(34, 'Insulin Glargine', 'No', '2025-10-06 03:06:29'),
(35, 'Guaifenesin', 'Yes', '2025-10-06 03:06:29'),
(36, 'Mupirocin Ointment', 'Yes', '2025-10-06 03:06:29'),
(37, 'Hydrocortisone Cream', 'Yes', '2025-10-06 03:06:29'),
(38, 'Azithromycin', 'Yes', '2025-10-06 03:10:13'),
(39, 'Doxycycline', 'Yes', '2025-10-06 03:10:13'),
(40, 'Fluconazole', 'No', '2025-10-06 03:10:13'),
(41, 'Acyclovir', 'Yes', '2025-10-17 05:37:07'),
(42, 'Ondansetron', 'Yes', '2025-10-06 03:10:13'),
(43, 'Allopurinol', 'Yes', '2025-10-10 18:43:28'),
(44, 'Cyclobenzaprine', 'Yes', '2025-10-06 03:10:13'),
(45, 'Methylphenidate', 'No', '2025-10-06 03:10:13'),
(46, 'Bupropion', 'Yes', '2025-10-06 03:10:13'),
(47, 'Spironolactone', 'Yes', '2025-10-06 03:10:13'),
(48, 'Finasteride', 'No', '2025-10-06 03:10:13'),
(49, 'Latanoprost Eye Drops', 'Yes', '2025-10-06 03:10:13'),
(50, 'Olopatadine Eye Drops', 'Yes', '2025-10-06 03:10:13'),
(51, 'Fluticasone Nasal Spray', 'Yes', '2025-10-06 03:10:13'),
(52, 'Trazodone', 'No', '2025-10-06 03:10:13');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `full_name`, `email`, `phone`, `position`, `created_at`, `updated_at`) VALUES
(5, 'Nurse Alice', 'alice@example.com', NULL, 'Nurse', '2025-09-19 22:16:51', '2025-09-19 22:16:51'),
(6, 'Nurse Charlie', 'charlie@example.com', NULL, 'Nurse', '2025-09-19 22:16:51', '2025-09-19 22:16:51'),
(7, 'Receptionist David', 'david@example.com', NULL, 'Receptionist', '2025-09-19 22:16:51', '2025-09-19 22:16:51'),
(8, 'Technician Eve', 'eve@example.com', NULL, 'Technician', '2025-09-19 22:16:51', '2025-09-19 22:16:51');

-- --------------------------------------------------------

--
-- Table structure for table `staff_availability`
--

CREATE TABLE `staff_availability` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL COMMENT '1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 7=Sun',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_availability`
--

INSERT INTO `staff_availability` (`id`, `staff_id`, `day_of_week`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(20, 6, 2, '09:00:00', '17:00:00', '2025-09-19 22:33:03', '2025-10-15 23:54:57'),
(21, 6, 4, '09:00:00', '18:00:00', '2025-09-19 22:33:03', '2025-10-08 13:01:37'),
(22, 7, 1, '09:00:00', '17:00:00', '2025-09-19 22:33:03', '2025-10-08 13:01:51'),
(23, 7, 2, '09:00:00', '17:00:00', '2025-09-19 22:33:03', '2025-10-08 13:01:51'),
(24, 7, 3, '09:00:00', '17:00:00', '2025-09-19 22:33:03', '2025-10-08 13:01:51'),
(25, 7, 4, '09:00:00', '17:00:00', '2025-09-19 22:33:03', '2025-10-08 13:01:51'),
(26, 8, 3, '09:00:00', '18:00:00', '2025-09-19 22:33:03', '2025-10-08 13:02:04'),
(27, 8, 4, '09:00:00', '18:00:00', '2025-09-19 22:33:03', '2025-10-08 13:02:04'),
(28, 8, 5, '09:00:00', '18:00:00', '2025-09-19 22:33:03', '2025-10-08 13:02:04'),
(33, 6, 7, '09:00:00', '18:00:00', '2025-10-04 20:41:28', '2025-10-08 13:01:37'),
(48, 5, 1, '09:00:00', '18:00:00', '2025-10-16 01:37:44', '2025-10-16 01:37:44'),
(55, 5, 3, '09:00:00', '18:00:00', '2025-10-17 05:25:31', '2025-10-17 05:25:31'),
(57, 5, 5, '09:30:00', '17:00:00', '2025-10-17 05:25:31', '2025-10-17 05:25:31'),
(58, 5, 6, '09:30:00', '18:00:00', '2025-10-17 05:25:31', '2025-10-17 05:25:31'),
(59, 7, 5, '09:30:00', '18:00:00', '2025-10-17 05:27:03', '2025-10-17 05:27:03'),
(60, 7, 6, '09:30:00', '18:00:00', '2025-10-17 05:27:03', '2025-10-17 05:27:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin','super_admin') NOT NULL DEFAULT 'user',
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `phone`, `details`, `full_name`, `birthdate`, `age`, `email`, `password`, `created_at`, `role`, `reset_token`, `reset_token_expires`) VALUES
(13, 'RomarMacay', '+639286360210', 'High Blood pressure,\r\nhave history of Typhoid fever.', 'Romar Vincent Macay', '2004-04-25', 21, 'romarmacay04@gmail.com', '$2y$10$a.Iy5ffdjEWIqnZC.tral.EU.L7iSSKZwYcRIt43co4EVKKy01qNq', '2025-10-01 15:24:00', 'user', NULL, NULL),
(15, 'marcolrx', '+639764558870', NULL, 'Marco Lorenzo', '1998-02-04', 27, 'immrclrnz@gmail.com', '$2y$10$wJgF6YWDaghrEXDE.L4B/OgbjVj/gE0E9AliW2uni0oQqn307DcIW', '2025-10-10 18:59:30', 'user', NULL, NULL),
(16, 'larseu_', '+639369523417', NULL, 'Lara Maricor Brito', '1999-05-18', 26, 'britolaramari@gmail.com', '$2y$10$6ibzPWLRc.zT9nTqqnjeO.fA6iG5mt5MAtNZjWULlAbw2Mopb8Oau', '2025-10-11 03:03:59', 'user', NULL, NULL),
(18, 'clauel_', '+639916678201', NULL, 'Reign Clauel', '1990-10-18', 35, 'reignllaneza16@gmail.com', '$2y$10$mSCy19OBfs.FE59qBo6GR.1I2v0VcdmqDuZQx5.oWvNfXyGyRhHB.', '2025-10-12 04:39:58', 'user', NULL, NULL),
(19, 'rhemnel', '+639215194390', NULL, 'Rhemnel Cecilio', '1999-01-21', 26, 'rhemneljeon@gmail.com', '$2y$10$prxZP0SDV2yqrJXsI/uoxuPRBFphHeTodHAGF3ixhG26.Z25GQslC', '2025-10-12 05:41:45', 'user', NULL, NULL),
(20, 'juswa', '+639984464222', NULL, 'Joshua Sayson', '1964-02-03', 61, 'saysonjoshua1717@gmail.com', '$2y$10$GXLwwoJJiIWXp2mZyA3HXu2LgMn0i2KKK7fZeaeJIIDNeMNJxIS9W', '2025-10-12 06:21:03', 'user', NULL, NULL),
(22, 'haydeee0', '+639663291273', NULL, 'Hide Orera', '1973-06-23', 52, 'hideorera143@gmail.com', '$2y$10$m8/PYafaJMjbHs44Yb9i4ORGacgEgPpElxYhvqmEZHUzqUHqNt/ji', '2025-10-12 06:21:40', 'user', NULL, NULL),
(27, 'PlagueRounds', '+639762967969', NULL, 'Johndel L Narvaez', '1966-04-24', 59, 'johndelnarvaez06@gmail.com', '$2y$10$5F7rFtSZHRqtcYlIO.ahVeHqthWHGD8RgQ/Mbp514dyKtLAKulnQe', '2025-10-12 06:30:04', 'user', NULL, NULL),
(28, 'Romelitob', '+639157777436', NULL, 'Romel Bautista', '2005-05-03', 20, 'romelbautista222@gmail.com', '$2y$10$1GFnjK9UXZpYvayXQ7VZWOlnvEilHEmbpaq5jGcsdivgu/TBgFR.S', '2025-10-12 08:21:15', 'user', NULL, NULL),
(29, 'janna', '+639064992696', NULL, 'Jan Nathanielle Dadulla', '1964-12-08', 60, 'jannathanielledadulla@gmail.com', '$2y$10$XHydafNU4/n5bGx9ZNJeEOb1juumFy2D8fpaVl1mDfQ3fm6VbQJYG', '2025-10-12 08:27:50', 'user', NULL, NULL),
(30, 'FranzJacela', '+639617488738', NULL, 'Franz Jacela', '1956-11-16', 68, 'rominous75@gmail.com', '$2y$10$KWxeByq6fkWJfGpNkcn1Hu81LeSXW5FfREA3sh.7VhhH8VmOuGpYK', '2025-10-12 10:13:21', 'user', NULL, NULL),
(31, 'notdamielle', '+639214054641', NULL, 'Princess P. Soliven', '1988-10-07', 37, 'solivendanielle@gmail.com', '$2y$10$26L6ZQNc0iwOt7/WDlRAE.nBMORO6/2SRzkzG/J78kTxd1Xp4Nz0u', '2025-10-13 05:04:09', 'user', NULL, NULL),
(35, 'rhonnyrhonny', '+639515987945', NULL, 'Rhon Angelo', '1959-05-29', 66, 'rhon.angelo08@gmail.com', '$2y$10$4kHQh2TIvGeglkOmXxrCFOWRxTppZ5WlNLPESSA1PvYbfIk2MM7kW', '2025-10-13 07:05:03', 'user', NULL, NULL),
(37, 'Jairus Maligaya', '+639948696360', NULL, 'Jairus Harold M Maligaya', '1977-12-09', 47, 'maligayajairusharold55@gmail.com', '$2y$10$8E2.6nYm33.kcz.57MEVY..8wdkYR42XUuzZ3GAdIMor3JceRy4.a', '2025-10-13 09:31:30', 'user', NULL, NULL),
(38, 'crlo', '+639513570223', NULL, 'carlo erit', '2004-08-31', 21, 'carlo.erit123@gmail.com', '$2y$10$AM91/wzeVXZBCQpLB.E0OeOpK0UHxeNH3tBZ2UuDZvbqShrRabr96', '2025-10-13 09:43:13', 'user', NULL, NULL),
(40, 'Keniqt', '+639603323668', NULL, 'Kenneth Oblino', '1977-09-16', 48, 'oblino.kenneth@gmail.com', '$2y$10$Km5cor73rGgGg3xYGfVp/.y4RJwWH4ewX6EKX/irp/mfW6sha2/Va', '2025-10-13 11:21:11', 'user', NULL, NULL),
(41, 'abierowd', '+639916075693', NULL, 'Wyatt Abierowd Omnos', '1967-10-01', 58, 'wyatt.omnoss12@gmail.com', '$2y$10$8d06MO6hMbYLAOlk19uFPO5dyqOmcmf1fFtWzOuQ1jfmDWPTR0xtW', '2025-10-13 11:24:36', 'user', NULL, NULL),
(42, 'Naruto', '+630999999999', NULL, 'Naruto Uzumaki', '2001-10-26', 23, '4sythg@gmail.com', '$2y$10$WfUOuP81E5XcZmzR8e2zJOcf4Xl5SuvOHV6NRaQMEl0bu.Yctb/Zu', '2025-10-13 11:25:21', 'user', NULL, NULL),
(43, 'ira17', '+639669812525', NULL, 'Ira Dejumo', '1998-01-16', 27, 'iradejumo17@gmail.com', '$2y$10$HHU/uP4v4wQgUtHl8Mni6u8t2qvb2VYOgUyvqdb6i5QQ75g2Ruphy', '2025-10-13 11:31:35', 'user', NULL, NULL),
(44, 'mrbread', '+639999999999', NULL, 'Mister Bread', '1974-12-16', 50, 'mrbreadph@gmail.com', '$2y$10$i5V90oxyhphwlXniSSB2zOQeVw9HVJm1Y.djejvpoDpHnjxAp9AgG', '2025-10-13 11:54:58', 'user', NULL, NULL),
(45, 'test1', '', NULL, 'test123', '1975-11-10', 49, 'test1@gmail.com', '$2y$10$7qnUywUBIoPLjZsy1GBlKOyioxI5aiw1MTrlXlEz.iVQECN6.eqyu', '2025-10-13 13:34:38', 'user', NULL, NULL),
(47, 'Menchie', '+639274088327', NULL, 'Jan Patrick Villarin', '2002-08-12', 23, 'jeypimalupet@gmail.com', '$2y$10$DaA5D9b2u8sz1WFvK3lr4./nPwYGbhkKG651BvLWCbUBVcCv6yhim', '2025-10-15 09:54:27', 'user', NULL, NULL),
(48, 'Kitkat', '+639954647358', NULL, 'Kitkat Llaneza', '1969-09-12', 56, 'llaneza_kitkat@yahoo.com', '$2y$10$8AHpzwjH51b8J.tU.6Bo7OIF.RNHON2XQ91EJMpIOEl53VK/NNXBm', '2025-10-15 16:19:15', 'user', NULL, NULL),
(56, 'Reign12', '+639916678201', NULL, 'Reign Clauel', '1985-11-05', 39, 'llanezareign16@gmail.com', '$2y$10$lT3v0t1rs7A5nJFxVoE.qOni.AX3vdL2D0IQYvfUKacQvSNiqu9Ma', '2025-10-18 01:36:02', 'user', NULL, NULL),
(58, 'lalaine', '+631111111111', NULL, 'Lalaine Maganda', '1965-05-02', 60, 'lalaine2mars@gmail.com', '$2y$10$AA/CXeP4/djyoAvGuFQ2i.8gs02sUVkAY5buVpyw8kZEDbbgRSK2i', '2025-10-18 01:38:51', 'user', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`appointment_id`),
  ADD UNIQUE KEY `unique_doctor_schedule` (`doctor_id`,`appointment_date`,`appointment_time`),
  ADD UNIQUE KEY `unique_doctor_slot` (`doctor_id`,`appointment_date`,`appointment_time`),
  ADD UNIQUE KEY `unique_patient_slot` (`patient_id`,`appointment_date`,`appointment_time`);

--
-- Indexes for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `consultation`
--
ALTER TABLE `consultation`
  ADD PRIMARY KEY (`consultation_id`),
  ADD KEY `fk_consultation_patient` (`patient_id`),
  ADD KEY `fk_consultation_doctor` (`doctor_id`),
  ADD KEY `fk_consultation_appointment` (`appointment_id`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`doctor_id`);

--
-- Indexes for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD UNIQUE KEY `doctor_id` (`doctor_id`,`day_of_week`,`start_time`,`end_time`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_staff_availability_staff` (`staff_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token` (`reset_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `chat_logs`
--
ALTER TABLE `chat_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `consultation`
--
ALTER TABLE `consultation`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `staff_availability`
--
ALTER TABLE `staff_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `fk_appointment_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointment_user` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`),
  ADD CONSTRAINT `fk_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_patient_user` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD CONSTRAINT `fk_chat_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `consultation`
--
ALTER TABLE `consultation`
  ADD CONSTRAINT `fk_consultation_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointment` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_consultation_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_consultation_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD CONSTRAINT `doctor_availability_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD CONSTRAINT `fk_staff_availability_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `staff_availability_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
