-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2025 at 10:04 AM
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
-- Database: `bh`
--

-- --------------------------------------------------------

--
-- Table structure for table `boarders`
--

CREATE TABLE `boarders` (
  `id` int(11) NOT NULL,
  `boarder_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `move_in_date` date NOT NULL,
  `monthly_rate` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `appliances` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boarders`
--

INSERT INTO `boarders` (`id`, `boarder_id`, `user_id`, `firstname`, `lastname`, `email`, `phone`, `address`, `move_in_date`, `monthly_rate`, `deposit_amount`, `room`, `status`, `appliances`) VALUES
(3, 1, 2, 'Boarder 1', 'Surname 1', 'boarder1@gmail.com', '12345678987', 'Kisolon Sumilao Bukidnon', '2025-05-04', 1000.00, 1000.00, 'B1', 'active', 0),
(4, 2, 2, 'Stephanie Jane', 'Eleccion', 'stephaniejaneeleccion6@gmail.com', '09123456788', 'Poblacion Impasug-ong Bukidnon', '2025-05-04', 1000.00, 1000.00, 'B1', 'active', 0),
(5, 3, 2, 'Rechael Antonette', 'Salise', 'jimfind01@gmail.com', '09123456788', 'Kalasungay Bukidnon', '2024-08-09', 1000.00, 1000.00, 'B2', 'active', 0),
(6, 4, 2, 'Alriesh', 'Oximer', 'Alriesh@gmail.com', '12345678987', 'Impasug-ong Bukidnon', '2025-05-09', 1000.00, 1000.00, 'B2', 'active', 0),
(7, 5, 2, 'Anamie', 'Soterio', 'Anamie@gmail.com', '09823758901', 'Kibawe Bukidnon', '2025-05-04', 1000.00, 1000.00, 'B3', 'active', 0),
(9, 6, 2, 'Hanni', 'Pham', 'jadeunders@gmail.com', '09382835828', 'Maramag Bukidnon', '2025-05-17', 1000.00, 1000.00, 'B4', 'active', 0),
(10, 7, 2, 'Jasmine', 'Devera', '2301105251@student.buksu.edu.ph', '09824721512', 'Kisolon Sumilao Bukidnon', '2025-05-19', 1000.00, 1000.00, 'B4', 'active', 0),
(11, 8, 2, 'Sammy Jane', 'Amposta', 'eleccion.stephaniejane6@gmail.com', '09756234123', 'Poblacion Impasug-ong Bukidnon', '2025-05-19', 1000.00, 1000.00, 'B5', 'active', 0),
(12, 9, 2, 'Jeremy', 'Eleccion', 'jeromeeleccion2@gmail.com', '09564732152', 'Poblacion Impasug-ong Bukidnon', '2025-05-19', 1000.00, 1000.00, 'B6', 'active', 0),
(13, 10, 2, 'Marry Jane', 'Ceris', '2301103230@student.buksu.edu.ph', '09234563456', 'Valencia Bukidnon', '2025-05-19', 1000.00, 1000.00, 'B3', 'active', 0),
(14, 11, 2, 'Marivel', 'Jude', 'marveljude@gmail.com', '09979077226', 'Butuan City', '2024-08-14', 1000.00, 1000.00, 'B3', 'active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('New','Read') DEFAULT 'New',
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `status`, `created_at`) VALUES
(4, 2, 'Rent is due on the 10th. Prepare to engage boarders with payment reminders.', 'New', '2025-05-04');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `boarder_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('paid','partial','pending','overdue') DEFAULT 'pending',
  `payment_type` enum('rent','visitor') DEFAULT 'rent',
  `mode_of_payment` varchar(50) DEFAULT NULL,
  `appliances` int(11) DEFAULT 0,
  `days` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `boarder_id`, `user_id`, `amount`, `payment_date`, `status`, `payment_type`, `mode_of_payment`, `appliances`, `days`) VALUES
(17, 3, 2, 1100.00, '2025-05-04', 'paid', 'rent', NULL, 1, 0),
(19, 4, 2, 1100.00, '2025-05-06', 'paid', 'rent', NULL, 1, 0),
(20, 5, 2, 500.00, '2025-05-06', 'partial', 'rent', NULL, 0, 0),
(21, 7, 2, 700.00, '2025-05-06', 'partial', 'rent', NULL, 1, 0),
(24, 5, 2, 100.00, '2025-05-17', 'paid', 'visitor', NULL, 0, 1),
(25, 6, 2, 1020.00, '2025-05-17', 'overdue', 'rent', NULL, 0, 0),
(26, 5, 2, 200.00, '2025-05-19', 'paid', 'visitor', NULL, 0, 2),
(27, 6, 2, 100.00, '2025-05-19', 'paid', 'visitor', NULL, 0, 1),
(28, 7, 2, 300.00, '2025-05-19', 'paid', 'visitor', NULL, 0, 3),
(29, 10, 2, 1020.00, '2025-05-19', 'paid', 'rent', NULL, 0, 0),
(30, 11, 2, 1020.00, '2025-05-19', 'paid', 'rent', NULL, 0, 0),
(31, 12, 2, 1020.00, '2025-05-19', 'paid', 'rent', NULL, 0, 0),
(32, 13, 2, 1020.00, '2025-05-19', 'paid', 'rent', NULL, 0, 0),
(33, 9, 2, 1020.00, '2025-05-19', 'paid', 'rent', NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `rates`
--

CREATE TABLE `rates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `monthly_rate` decimal(10,2) DEFAULT NULL,
  `appliances_rate` decimal(10,2) DEFAULT NULL,
  `late_fee` decimal(10,2) DEFAULT NULL,
  `visitor_daily_rate` decimal(10,2) DEFAULT 166.67
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rates`
--

INSERT INTO `rates` (`id`, `user_id`, `due_date`, `monthly_rate`, `appliances_rate`, `late_fee`, `visitor_daily_rate`) VALUES
(4, 2, '2025-05-10', 1000.00, 100.00, 20.00, 166.67);

-- --------------------------------------------------------

--
-- Table structure for table `sent_emails`
--

CREATE TABLE `sent_emails` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `boarder_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sent_emails`
--

INSERT INTO `sent_emails` (`id`, `user_id`, `boarder_id`, `subject`, `body`, `sent_at`) VALUES
(10, 2, 6, 'Payment Due Reminder', '\r\nDear marivel gales,\r\n\r\nThis is a friendly reminder that your rent payment of ₱1,000.00 for B2 is due on the 10th of May 2025. Please ensure timely payment to avoid a late fee of ₱20.00.\r\n\r\nPayment can be made via cash. Contact us at prixanejadegales@gmail.com for any questions.\r\n\r\nThank you,\r\nKring-Kring Ladies Boarding House\r\n', '2025-05-06 20:37:40'),
(11, 2, 8, 'PHPMailer', 'Download ni gang dayon e compress didto sa xampp > htdocs > BHBMS > sidebar > phpmailer < mao na kibali ibutang sulod sa sidebar\r\n', '2025-05-06 20:39:13'),
(12, 2, 8, 'Payment', '\r\nDear prixane Doe,\r\n\r\nThis is a friendly reminder that your rent payment of ₱1,000.00 for B3 is due on the 10th of May 2025. Please ensure timely payment to avoid a late fee of ₱20.00.\r\n\r\nPayment can be made via cash. Contact us at prixanejadegales@gmail.com for any questions.\r\n\r\nThank you,\r\nKring Kring Ladies Boarding House\r\n', '2025-05-08 00:05:32'),
(13, 2, 4, 'payment', '\r\nDear Stephanie Jane Eleccion,\r\n\r\nThis is a friendly reminder that your rent payment of ₱1,000.00 for B1 is due on the 10th of May 2025. Please ensure timely payment to avoid a late fee of ₱20.00.\r\n\r\nPayment can be made via cash. Contact us at prixanejadegales@gmail.com for any questions.\r\n\r\nThank you,\r\nKring Kring Ladies Boarding House\r\n', '2025-05-14 12:36:03'),
(14, 2, 4, 'payment', '\r\nDear Stephanie Jane Eleccion,\r\n\r\nThis is a friendly reminder that your rent payment of ₱1,000.00 for B1 is due on the 10th of May 2025. Please ensure timely payment to avoid a late fee of ₱20.00.\r\n\r\nPayment can be made via cash. Contact us at prixanejadegales@gmail.com for any questions.\r\n\r\nThank you,\r\nKring Kring Ladies Boarding House\r\n', '2025-05-14 12:42:31');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `default_language` varchar(50) DEFAULT 'English',
  `timezone` varchar(50) DEFAULT 'Asia/Manila'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `user_id`, `email_notifications`, `sms_notifications`, `default_language`, `timezone`) VALUES
(1, 2, 1, 0, 'English', 'Asia/Manila'),
(2, 1, 1, 0, 'English', 'Asia/Manila');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `government_id` varchar(100) DEFAULT NULL,
  `tin_num` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `boardinghousename` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `reset_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `contact`, `government_id`, `tin_num`, `address`, `boardinghousename`, `password`, `reset_code`) VALUES
(2, 'Prixane Jade', 'Gales', 'prixanejadegales@gmail.com', '09206062132', '13145335553', '14144121414141', 'Zone 3 kisolon', 'Kring Kring Ladies', '$2y$10$ZuufqbYHdfsvyFpiXqKwW.lfcx2IXKgizBzFBez5p0MKlCdXok2Bi', '210262'),
(4, 'Stephanie  Jane', 'Eleccion', 'stephaniejaneeleccion6@gmail.com', '09206062132', '12345344645643', '2435365675', 'Poblacion Impasug-ong Bukidnon', 'Stephanie BH', '$2y$10$l4gy5dogOLEqNiNS37dJhuUffAUw70FMtF7UcsQaYHqxip0PD4V8S', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

CREATE TABLE `visitors` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `boarder_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitors`
--

INSERT INTO `visitors` (`id`, `name`, `boarder_id`, `user_id`) VALUES
('1', 'Jane Cols', 5, 2),
('2', 'Lexie Martines', 4, 2),
('3', 'Alexa', 6, 2),
('4', 'Jane Foster', 7, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `boarders`
--
ALTER TABLE `boarders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `boarder_id` (`boarder_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boarder_id` (`boarder_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rates`
--
ALTER TABLE `rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sent_emails`
--
ALTER TABLE `sent_emails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boarder_id` (`boarder_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `boarders`
--
ALTER TABLE `boarders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `rates`
--
ALTER TABLE `rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sent_emails`
--
ALTER TABLE `sent_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `boarders`
--
ALTER TABLE `boarders`
  ADD CONSTRAINT `boarders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
