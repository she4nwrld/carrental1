-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2026 at 04:38 AM
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
-- Database: `carrental`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `car_id` int(10) UNSIGNED NOT NULL,
  `pickup_location` varchar(60) NOT NULL,
  `return_location` varchar(60) NOT NULL,
  `pickup_date` date NOT NULL,
  `return_date` date NOT NULL,
  `driver_age` varchar(10) NOT NULL,
  `delivery` tinyint(1) NOT NULL DEFAULT 0,
  `days` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `subtotal` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `discount_code` varchar(45) DEFAULT NULL,
  `discount` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `car_id`, `pickup_location`, `return_location`, `pickup_date`, `return_date`, `driver_age`, `delivery`, `days`, `subtotal`, `discount_code`, `discount`, `total`, `status`, `created_at`) VALUES
(6, 3, 6, 'Rizal Boulevard, Dumaguete', 'Rizal Boulevard, Dumaguete', '2026-09-17', '2026-09-18', '25-29', 0, 1, 4800, 'LOCAL5', 240, 4560, 'pending', '2026-09-09 01:02:52');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
CREATE TABLE `cars` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` enum('Hatchback','Sedan','SUV','MPV') NOT NULL,
  `price` int(10) UNSIGNED NOT NULL,
  `gear` enum('Manual','Auto') NOT NULL DEFAULT 'Manual',
  `seats` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `doors` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `bag_large` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `bag_small` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `kids` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `aircon` tinyint(1) NOT NULL DEFAULT 1,
  `img` varchar(200) NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `name`, `type`, `price`, `gear`, `seats`, `doors`, `bag_large`, `bag_small`, `kids`, `aircon`, `img`, `available`) VALUES
(1, 'Toyota Avanza', 'MPV', 2800, 'Manual', 7, 5, 2, 3, 3, 1, 'images/toyota-avanza.png', 1),
(2, 'Mitsubishi Xpander', 'MPV', 3000, 'Auto', 7, 5, 3, 2, 3, 1, 'images/mitsubishi-xpander.png', 1),
(3, 'Toyota Innova', 'MPV', 3500, 'Manual', 8, 5, 3, 3, 3, 1, 'images/toyota-innova.png', 1),
(4, 'Toyota Fortuner', 'SUV', 4500, 'Auto', 7, 5, 3, 3, 3, 1, 'images/toyota-fortuner.png', 1),
(5, 'Mitsubishi Montero Sport', 'SUV', 4300, 'Auto', 7, 5, 3, 3, 3, 1, 'images/mitsubishi-montero.png', 1),
(6, 'Ford Everest', 'SUV', 4800, 'Auto', 7, 5, 4, 2, 3, 1, 'images/ford-everest.png', 1),
(7, 'Toyota Vios', 'Sedan', 2200, 'Manual', 5, 4, 2, 2, 2, 1, 'images/toyota-vios.png', 1),
(8, 'Honda City', 'Sedan', 2400, 'Auto', 5, 4, 2, 2, 2, 1, 'images/honda-city.png', 1),
(9, 'Suzuki Swift', 'Hatchback', 1900, 'Manual', 5, 5, 1, 2, 2, 1, 'images/suzuki-swift.png', 1),
(10, 'Toyota Wigo', 'Hatchback', 1600, 'Manual', 5, 5, 1, 2, 1, 1, 'images/toyota-wigo.png', 1),
(11, 'Toyota Corolla Altis', 'Sedan', 2600, 'Auto', 5, 4, 2, 2, 2, 1, 'images/toyota-corolla-altis.png', 1),
(12, 'Kia Picanto', 'Hatchback', 1500, 'Manual', 5, 5, 1, 2, 1, 1, 'images/kia-picanto.png', 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

DROP TABLE IF EXISTS `promos`;
CREATE TABLE `promos` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `tag` varchar(30) NOT NULL,
  `title` varchar(140) NOT NULL,
  `blurb` varchar(400) NOT NULL,
  `percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `free_days` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `min_days` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `min_advance_days` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `stackable` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` date DEFAULT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`id`, `code`, `tag`, `title`, `blurb`, `percent`, `free_days`, `min_days`, `min_advance_days`, `stackable`, `active`, `expires_at`, `sort_order`) VALUES
(1, 'EARLY10', 'Early Bird', 'Book 7 days ahead, save 10%', 'Reserve any unit at least one week before your pick-up date and we knock 10% off the daily rate. Applies to all categories.', 10, 0, 0, 7, 1, 1, NULL, 1),
(2, 'WEEKFREE', 'Long Trip', '7+ days: 1 day free', 'Rent for seven days or more and the seventh day is on us. Perfect for island loops down to Apo Island jump-offs and up to Twin Lakes.', 0, 1, 7, 0, 0, 1, NULL, 2),
(3, NULL, 'Airport', 'Free Sibulan Airport meet & greet', 'Airport pick-up service is free on all bookings — our agent meets you at arrivals so you skip the taxi line entirely.', 0, 0, 0, 0, 0, 1, NULL, 3),
(4, 'LOCAL5', 'Local', 'Negros Oriental resident discount', 'Show a valid ID with a Negros Oriental address at pick-up and get 5% off. Can be combined with the early bird promo.', 5, 0, 0, 0, 1, 1, NULL, 4);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `review_text` varchar(600) NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `rating`, `review_text`, `approved`, `created_at`) VALUES
(5, 3, 5, 'Nice', 1, '2026-09-08 16:44:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `admin_lock` varchar(5) GENERATED ALWAYS AS (if(`role` = 'admin','admin',NULL)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Admin', 'sheene@admin.com', '09170000000', '$2y$10$GRF4uhc0NRkHKkWv4JiBkuxX3HXIiRQGA/Ug2OLCvtA09GfstjwCG', 'admin', 1, '2026-09-08 14:51:38', NULL),
(3, 'SheeneP', 'sheene1@customer.com', '09367419181', '$2y$10$ZpCC/TvWKETvcPVXHopc2.14S.B6skKiwuBZI1GQxehTb8MwDMnV2', 'customer', 1, '2026-09-08 15:51:59', '2026-09-09 00:46:19'),
(4, 'carz', 'carz@customer.com', '09627464589', '$2y$10$BHcgq0pyQhHAqgURDQ7rIOZIKayS3AWIZTCmlCQIq.IX0fvU2FHnS', 'customer', 1, '2026-09-08 18:48:54', '2026-09-09 01:00:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bk_user` (`user_id`,`created_at`),
  ADD KEY `idx_bk_range` (`car_id`,`status`,`pickup_date`,`return_date`),
  ADD KEY `idx_car_dates` (`car_id`,`pickup_date`,`return_date`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cars_type` (`type`,`available`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_read` (`is_read`),
  ADD KEY `fk_messages_user` (`user_id`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_promos_code` (`code`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reviews_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uniq_single_admin` (`admin_lock`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bk_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `fk_bk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_rv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
