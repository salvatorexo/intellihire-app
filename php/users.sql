-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 12, 2026 at 03:11 AM
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
-- Database: `intellihire`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `account_type` enum('applicant','recruiter','admin') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT DELAYED IGNORE INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `account_type`, `created_at`, `failed_attempts`, `locked_until`) VALUES
(1, 'System Admin', 'admin@intellihire.com', '+254700000000', '$2y$10$Y/JEXoLp0jACxeY7nZDwVO8HQhI3YkYleQEfKXokj.CrvtCDFXgfK', 'admin', '2026-10-06 23:06:54', 0, NULL),
(3, 'kiboen kemboi', 'kiboenkemboi840@gmail.com', '+254724044635', '$2y$10$3CJev07nl5vP5wf4O5FtVe1.gT2tTdKKmC7H9K3AdOKFx.J8p0Qhm', 'applicant', '2026-10-07 10:11:07', 0, NULL),
(4, 'kiboen kemboi', 'kiboenkemboi005@gmail.com', '+254740610732', '$2y$10$s8H8Mv0HH8Q6fnttGi5G0uBh38FMA8epyG1B8EMx/u3wSz1OrwUmq', 'recruiter', '2026-10-07 11:14:05', 0, NULL),
(7, 'salvatorexo', 'patricknyerere085@gmail.com', '+254724044635', '$2y$10$2ctUtqfX2v27.lbLv4aVOu5E9M9Vb1obRx0Layv4mQKfye850/x8O', 'applicant', '2026-10-07 19:19:14', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
