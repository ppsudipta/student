-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 29, 2025 at 11:05 AM
-- Server version: 5.7.23-23
-- PHP Version: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sstechno_bstud`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `course` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `rmonth` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `fees` varchar(255) NOT NULL,
  `otp` varchar(11) NOT NULL,
  `board` text NOT NULL,
  `subject` text NOT NULL,
  `level` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `phone`, `email`, `address`, `fname`, `course`, `password`, `date`, `status`, `rmonth`, `img`, `fees`, `otp`, `board`, `subject`, `level`) VALUES
(42, 'sumanta', '7439366097', 'impulsiitjee@gmail.com', '', '', 'Accounting', '123456', '31-08-2024', 'Pending', 'Aug', '4jpg', '900', '90038', '', '', ''),
(43, 'sumant', '9035986565', 'lilima.parida@gmail.com', '', '', 'Accounting', '123456', '03-09-2024', 'Pending', 'Sep', '3jpg', '900', '', '', '', ''),
(44, '$name', '$phone', '$email', '$address', '$fname', '$course', '$password', '$date', 'Approved', '$month', '$path1', '$fees', '', '', '', ''),
(45, 'Sumanta Malik', '9038591598', 'sumantamalik06@gmail.com', '', '', 'Accounting', '123456', '20-09-2024', 'Pending', 'Sep', '3jpg', '500', '', '', '', ''),
(46, 'Rupam Roy', '06291555729', 'rupamroy7555@gmail.com', '143/E Nursery Road', 'demo father', 'English', '12', '28-04-2025', 'Pending', 'Apr', '', '', '', '', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
