-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2025 at 03:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `orchidfy_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'paid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_items`
--

CREATE TABLE `booking_items` (
  `booking_item_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `ticket_quantity` int(11) NOT NULL,
  `price_at_booking` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_item`
--

CREATE TABLE `cart_item` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `full_name`, `email_address`, `subject`, `message`, `ip_address`, `sent_at`) VALUES
(1, 'matthew meercy', 'matthew@gmail.com', 'CEO Connect Forum Nigeria', 'CEO Connect Forum Nigeria', NULL, '2025-06-18 13:26:28');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `available_tickets` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `description`, `date`, `time`, `location`, `category`, `price`, `image_url`, `available_tickets`) VALUES
(1, 'Afrobeats Music Festival 2025', 'Experience the rhythm of Africa! A spectacular evening featuring top Afrobeats artists, vibrant performances, and an unforgettable celebration of music and culture.', '2025-07-15', '10:00:00', 'Abuja', 'Music', 15000.00, '/orchid/img/Music Fes.jpg', 0),
(2, 'Garden City Marathon', 'Lace up your running shoes for the annual Garden City Marathon! A challenging yet rewarding race through the scenic routes of Port Harcourt, with categories for all ages and fitness levels', '0000-00-00', '10:00:00', 'Port Harcourt', 'Sports', 5000.00, '/orchid/img/Garden .jpg', 0),
(3, 'Tech Innovators Summit 2025', 'Join leading tech minds for two days of groundbreaking discussions, workshops, and networking. Discover the future of AI, blockchain, and sustainable technology.', '2025-06-26', '09:30:00', 'Lagos', 'Tech', 25000.00, '/orchid/img/tech_summit.jpeg', 0),
(4, 'Afrobeats Legends Festival\r\n', 'This Lagos event, organized by Kennis Music, will feature 2Baba (2Face Idibia) and DJ Jimmy Jatt as headliners, celebrating the legacy of Afrobeats pioneers', '2025-08-10', '10:00:00', '', 'Music ', 25000.00, '/orchid/img/download.jpeg', 0),
(5, 'Access Bank Lagos City Marathon', 'Access Bank Lagos City Marathon, held annually in Lagos, Nigeria. It\'s a major event with a 42km full marathon and a 10km fun run, attracting over 100,000 runners. The race typically starts at the National Stadium in Surulere and finishes at Eko Atlantic City in Victoria Island. \r\n', '2025-08-15', '06:00:00', 'Lagos', '', 0.00, '/orchid/img/Marathon.jpg', 0),
(6, 'Afrobeats Music Festival 2025', 'Experience the rhythm of Africa! A spectacular evening featuring top Afrobeats artists, vibrant performances, and an unforgettable celebration of music and culture.', '2025-07-15', '10:00:00', 'Abuja', 'Music', 15000.00, '/orchid/img/Music Fes.jpg', 0),
(7, 'Garden City Marathon', 'Lace up your running shoes for the annual Garden City Marathon! A challenging yet rewarding race through the scenic routes of Port Harcourt, with categories for all ages and fitness levels', '0000-00-00', '10:00:00', 'Abuja', 'Sports', 5000.00, '/orchid/img/Garden .jpg', 0),
(8, 'Tech Innovators Summit 2025', 'Join leading tech minds for two days of groundbreaking discussions, workshops, and networking. Discover the future of AI, blockchain, and sustainable technology.', '2025-06-26', '09:30:00', 'Abuja\n', 'Tech', 25000.00, '/orchid/img/tech_summit.jpeg', 0),
(9, 'Afrobeats Legends Festival\r\n', 'This Abuja event, organized by Kennis Music, will feature 2Baba (2Face Idibia) and DJ Jimmy Jatt as headliners, celebrating the legacy of Afrobeats pioneers', '2025-08-10', '10:00:00', 'Abuja', 'Music ', 25000.00, '/orchid/img/download.jpeg', 0),
(10, 'Access Bank Abuja City Marathon', 'Access Bank Abuja City Marathon, held annually in Abuja, Nigeria. It\'s a major event with a 42km full marathon and a 10km fun run, attracting over 100,000 runners. The race typically starts at the National Stadium in Abuja and finishes at a Aso Rock city gate . \r\n', '2025-08-15', '06:00:00', 'Abuja\r\n\r\n', 'Sport', 1500.00, '/orchid/img/Marathon.jpg', 0),
(11, 'Afrobeats Music Festival 2025', 'Experience the rhythm of Africa! A spectacular evening featuring top Afrobeats artists, vibrant performances, and an unforgettable celebration of music and culture.', '2025-07-15', '10:00:00', 'Port Harcourt ', 'Music', 15000.00, '/orchid/img/Music Fes.jpg', 0),
(12, 'Garden City Marathon', 'Lace up your running shoes for the annual Garden City Marathon! A challenging yet rewarding race through the scenic routes of Port Harcourt, with categories for all ages and fitness levels', '0000-00-00', '10:00:00', 'Lagos', 'Sports', 5000.00, '/orchid/img/Garden .jpg', 0),
(13, 'Tech Innovators Summit 2025', 'Join leading tech minds for two days of groundbreaking discussions, workshops, and networking. Discover the future of AI, blockchain, and sustainable technology.', '2025-06-26', '09:30:00', 'Port Harcourt', 'Tech', 25000.00, '/orchid/img/tech_summit.jpeg', 0),
(14, 'Afrobeats Legends Festival\r\n', 'This Lagos event, organized by Kennis Music, will feature 2Baba (2Face Idibia) and DJ Jimmy Jatt as headliners, celebrating the legacy of Afrobeats pioneers', '2025-08-10', '10:00:00', 'Lagos', 'Music ', 25000.00, '/orchid/img/download.jpeg', 0),
(15, 'Access Bank Lagos City Marathon', 'Access Bank Lagos City Marathon, held annually in Lagos, Nigeria. It\'s a major event with a 42km full marathon and a 10km fun run, attracting over 100,000 runners. The race typically starts at the National Stadium in Surulere and finishes at Eko Atlantic City in Victoria Island. \r\n', '2025-08-15', '06:00:00', 'Lagos', 'Sport', 2500.00, '/orchid/img/Marathon.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `created_at`, `updated_at`) VALUES
(3, 'ezejoy@gmail.com', 'ezejoy@gmail.com', '$2y$10$aQC0fHvFemwkUna.xntC2OX2saFvnnK2TKAGvN4KBEdANsya.J5SO', 'eze joy', '2025-06-20 11:42:38', '2025-06-20 11:42:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD PRIMARY KEY (`booking_item_id`),
  ADD KEY `fk_booking_id` (`booking_id`),
  ADD KEY `fk_event_id` (`event_id`);

--
-- Indexes for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`event_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `id_2` (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_email` (`email`(191));

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_items`
--
ALTER TABLE `booking_items`
  MODIFY `booking_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_item`
--
ALTER TABLE `cart_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD CONSTRAINT `fk_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_id` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
