-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2025 at 12:00 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gcl`
--

-- --------------------------------------------------------

--
-- Table structure for table `alarms`
--

CREATE TABLE `alarms` (
  `id` int(11) NOT NULL,
  `Source` varchar(255) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Value` float DEFAULT NULL,
  `Time` timestamp NOT NULL DEFAULT current_timestamp(),
  `db_value` float DEFAULT NULL,
  `url_value` float DEFAULT NULL,
  `status1` varchar(255) DEFAULT NULL,
  `alarm_count` int(11) DEFAULT 1,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `meterdata`
--

CREATE TABLE `meterdata` (
  `id` int(11) NOT NULL,
  `Source` varchar(255) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Value` decimal(10,2) DEFAULT NULL,
  `Time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `meterdata`
--

INSERT INTO `meterdata` (`id`, `Source`, `Status`, `Value`, `Time`) VALUES
(1, ' Tranformer 1', 'Low Voltage', '380.00', '2025-03-05 05:59:13'),
(2, ' Tranformer 1', 'High Voltage', '430.00', '2025-03-05 05:59:28');

-- --------------------------------------------------------

--
-- Table structure for table `production`
--

CREATE TABLE `production` (
  `id` int(11) NOT NULL,
  `GWP` int(11) NOT NULL,
  `Airjet` int(11) NOT NULL,
  `Sewing2` int(11) NOT NULL,
  `Textile` int(11) NOT NULL,
  `Sewing1` int(11) NOT NULL,
  `PG` int(11) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `production`
--

INSERT INTO `production` (`id`, `GWP`, `Airjet`, `Sewing2`, `Textile`, `Sewing1`, `PG`, `date`) VALUES
(66, 32125, 15276, 13697, 53340, 21391, 86710, '2025-01-01'),
(67, 30007, 15297, 14067, 56035, 23161, 84400, '2025-01-02'),
(68, 22504, 15316, 14648, 48208, 22049, 83620, '2025-01-03'),
(69, 30566, 15666, 14677, 53350, 21652, 72560, '2025-01-04'),
(70, 30058, 15740, 0, 51460, 0, 80202, '2025-01-05'),
(71, 27470, 15922, 13603, 53505, 21982, 84033, '2025-01-06'),
(72, 31175, 16082, 14848, 52169, 22659, 73790, '2025-01-07'),
(73, 30306, 15957, 15048, 25355, 21965, 75180, '2025-01-08'),
(74, 32141, 16059, 15123, 52240, 22131, 74152, '2025-01-09'),
(75, 33081, 16094, 14532, 64124, 21992, 74578, '2025-01-10'),
(76, 32137, 15979, 14181, 56870, 21974, 83260, '2025-01-11'),
(77, 32218, 16098, 0, 58900, 0, 65820, '2025-01-12'),
(78, 32037, 15881, 14489, 50230, 20673, 72315, '2025-01-13'),
(79, 29247, 15850, 14274, 56125, 22043, 77475, '2025-01-14'),
(80, 32682, 15654, 13559, 55025, 21877, 71523, '2025-01-15'),
(81, 33171, 15775, 15570, 51470, 22613, 73977, '2025-01-16'),
(82, 32646, 15455, 14756, 42749, 22053, 83028, '2025-01-17'),
(83, 31176, 15784, 14770, 45245, 22239, 75052, '2025-01-18'),
(84, 31235, 15950, 0, 57005, 0, 69536, '2025-01-19'),
(85, 31461, 15718, 14002, 56854, 22602, 72574, '2025-01-20'),
(86, 30019, 15839, 14957, 50135, 22178, 75333, '2025-01-21'),
(87, 32631, 15885, 13286, 82748, 22431, 76743, '2025-01-22'),
(88, 33236, 16217, 14873, 54225, 22533, 71883, '2025-01-23'),
(89, 33162, 16255, 14780, 49388, 22474, 71841, '2025-01-24'),
(90, 33041, 16345, 14116, 51610, 22427, 74919, '2025-01-25'),
(91, 33058, 16103, 0, 51370, 0, 66690, '2025-01-26'),
(92, 33253, 16177, 12474, 53280, 21541, 74584, '2025-01-27'),
(93, 34462, 16007, 114458, 53890, 22864, 74233, '2025-01-28'),
(94, 34370, 16275, 15491, 49345, 23094, 86404, '2025-01-29'),
(95, 32337, 16335, 14301, 47690, 23457, 77148, '2025-01-30'),
(96, 31433, 15997, 12388, 47660, 23088, 73907, '2025-01-31');

-- --------------------------------------------------------

--
-- Table structure for table `recentalarms`
--

CREATE TABLE `recentalarms` (
  `id` int(11) NOT NULL,
  `meter` varchar(50) DEFAULT NULL,
  `option_selected` varchar(50) DEFAULT NULL,
  `db_value` float DEFAULT NULL,
  `url_value` float DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `total_duration` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`) VALUES
(1, 'demo', 'demo'),
(2, 'test@gmail.com', 'test'),
(3, 'testuser', 'testpassword'),
(4, 'automation@jiotp.com', 'sahamid');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alarms`
--
ALTER TABLE `alarms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meterdata`
--
ALTER TABLE `meterdata`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production`
--
ALTER TABLE `production`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recentalarms`
--
ALTER TABLE `recentalarms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alarms`
--
ALTER TABLE `alarms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meterdata`
--
ALTER TABLE `meterdata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `production`
--
ALTER TABLE `production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `recentalarms`
--
ALTER TABLE `recentalarms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
