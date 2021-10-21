-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 11, 2021 at 04:08 PM
-- Server version: 5.7.33-0ubuntu0.16.04.1
-- PHP Version: 7.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rent_park_akshay`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `about` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'http://localhost:8000/placeholder.jpg',
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timezone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Los_Angeles',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `about`, `mobile`, `picture`, `password`, `timezone`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@rentcubo.com', '', '', 'http://localhost:8000/placeholder.jpg', '$2y$10$jtFZfUhw4VZqXbgI7vDP3eBgBqvxMBbsxeHEnKA14OeSZM77cyDDi', 'Asia/Kolkata', 1, NULL, '2021-09-11 05:06:53', '2021-09-11 05:07:49'),
(2, 'Test', 'test@rentcubo.com', '', '', 'http://localhost:8000/placeholder.jpg', '$2y$10$Sw8tOHaHH3xHt9mGMWwsueL66iJsp3fjoXzqUMiD1TCTTZ8h7QKTe', 'America/Los_Angeles', 1, NULL, '2021-09-11 05:06:53', '2021-09-11 05:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `bell_notifications`
--

CREATE TABLE `bell_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_id` int(11) NOT NULL,
  `to_id` int(11) NOT NULL,
  `notification_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirection_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver` enum('user','provider','others') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `booking_id` int(11) NOT NULL DEFAULT '0',
  `host_id` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bell_notification_templates`
--

CREATE TABLE `bell_notification_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_vehicle_id` int(11) NOT NULL,
  `duration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `per_day` double(8,2) NOT NULL DEFAULT '0.00',
  `per_hour` double(8,2) NOT NULL DEFAULT '0.00',
  `per_week` double(8,2) NOT NULL DEFAULT '0.00',
  `per_month` double(8,2) NOT NULL DEFAULT '0.00',
  `checkin` datetime NOT NULL,
  `checkout` datetime NOT NULL,
  `actual_checkin` datetime DEFAULT NULL,
  `actual_checkout` datetime DEFAULT NULL,
  `total_days` double(8,2) NOT NULL DEFAULT '0.00',
  `price_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'per_month',
  `currency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '$',
  `total` double(8,2) NOT NULL DEFAULT '0.00',
  `payment_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `is_automatic_booking` int(11) NOT NULL DEFAULT '0',
  `cancelled_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cancelled_date` datetime NOT NULL,
  `is_rebooking` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `checkin_verification_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_chats`
--

CREATE TABLE `booking_chats` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_delivered` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_payments`
--

CREATE TABLE `booking_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `payment_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cod',
  `currency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '$',
  `total_time` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `base_price` double(8,2) NOT NULL DEFAULT '0.00',
  `per_hour` double(8,2) NOT NULL DEFAULT '0.00',
  `per_day` double(8,2) NOT NULL DEFAULT '0.00',
  `per_week` double(8,2) NOT NULL DEFAULT '0.00',
  `per_month` double(8,2) NOT NULL DEFAULT '0.00',
  `cleaning_fee` double(8,2) NOT NULL DEFAULT '0.00',
  `time_price` double(8,2) NOT NULL DEFAULT '0.00',
  `other_price` double(8,2) NOT NULL DEFAULT '0.00',
  `sub_total` double(8,2) NOT NULL DEFAULT '0.00',
  `tax_price` double(8,2) NOT NULL DEFAULT '0.00',
  `actual_total` double(8,2) NOT NULL DEFAULT '0.00',
  `total` double(8,2) NOT NULL DEFAULT '0.00',
  `paid_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `paid_date` datetime DEFAULT NULL,
  `admin_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `provider_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_addtional_hours` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `total_hours` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_provider_reviews`
--

CREATE TABLE `booking_provider_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `ratings` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `review` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_user_reviews`
--

CREATE TABLE `booking_user_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `ratings` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `review` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(11) NOT NULL DEFAULT '0',
  `host_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_delivered` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'others',
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'http://localhost:8000/placeholder.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hosts`
--

CREATE TABLE `hosts` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` int(11) NOT NULL,
  `host_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `host_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'http://localhost:8000/host-placeholder.jpg',
  `service_location_id` int(11) NOT NULL,
  `total_spaces` double(8,2) NOT NULL DEFAULT '1.00',
  `access_note` text COLLATE utf8mb4_unicode_ci,
  `access_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `security_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `host_owner_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `per_hour` double(8,2) NOT NULL DEFAULT '0.00',
  `width_of_space` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `height_of_space` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `length_of_space` int(11) NOT NULL DEFAULT '0',
  `amenities` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `available_days` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1,2,3,4,5,6,7',
  `latitude` double(15,8) NOT NULL,
  `longitude` double(15,8) NOT NULL,
  `full_address` text COLLATE utf8mb4_unicode_ci,
  `street_details` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkin` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkout` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_days` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `max_days` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `base_price` double(8,2) NOT NULL DEFAULT '0.00',
  `per_guest_price` double(8,2) NOT NULL DEFAULT '0.00',
  `per_day` double(8,2) NOT NULL DEFAULT '0.00',
  `per_week` double(8,2) NOT NULL DEFAULT '0.00',
  `per_month` double(8,2) NOT NULL DEFAULT '0.00',
  `cleaning_fee` double(8,2) NOT NULL DEFAULT '0.00',
  `tax_price` double(8,2) NOT NULL DEFAULT '0.00',
  `overall_ratings` double(8,2) NOT NULL DEFAULT '0.00',
  `total_ratings` int(11) NOT NULL DEFAULT '0',
  `is_admin_verified` tinyint(4) NOT NULL DEFAULT '0',
  `admin_status` tinyint(4) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `is_automatic_booking` int(11) NOT NULL DEFAULT '0',
  `uploaded_by` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'provider',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `dimension` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_availabilities`
--

CREATE TABLE `host_availabilities` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `checkin_status` tinyint(4) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `slot` int(11) NOT NULL,
  `total_spaces` double(8,2) NOT NULL DEFAULT '1.00',
  `used_spaces` double(8,2) NOT NULL DEFAULT '1.00',
  `remaining_spaces` double(8,2) NOT NULL DEFAULT '1.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_availability_lists`
--

CREATE TABLE `host_availability_lists` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `from_date` datetime NOT NULL,
  `to_date` datetime NOT NULL,
  `spaces` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_details`
--

CREATE TABLE `host_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_galleries`
--

CREATE TABLE `host_galleries` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(11) NOT NULL,
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_default` tinyint(4) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `host_inventories`
--

CREATE TABLE `host_inventories` (
  `id` int(10) UNSIGNED NOT NULL,
  `host_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lookups`
--

CREATE TABLE `lookups` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `is_amenity` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lookups`
--

INSERT INTO `lookups` (`id`, `type`, `picture`, `key`, `value`, `status`, `is_amenity`, `created_at`, `updated_at`) VALUES
(1, 'host_type', '', 'driveway', 'Driveway', 1, 0, NULL, NULL),
(2, 'host_type', '', 'garage', 'Garage', 1, 0, NULL, NULL),
(3, 'host_type', '', 'carpark', 'CarPark', 1, 0, NULL, NULL),
(4, 'host_owner_type', '', 'owner', 'Owner', 1, 0, NULL, NULL),
(5, 'host_owner_type', '', 'business', 'Business / Organization', 1, 0, NULL, NULL),
(6, 'driveway', 'http://localhost:8000/images/cctv.png', 'cctv', 'CCTV', 1, 1, NULL, NULL),
(7, 'driveway', 'http://localhost:8000/images/plug.png', 'electric-charging', 'Electric Charging', 1, 1, NULL, NULL),
(8, 'garage', 'http://localhost:8000/images/plug.png', 'electric-charging', 'Electric Charging', 1, 1, NULL, NULL),
(9, 'garage', 'http://localhost:8000/images/cctv.png', 'cctv', 'CCTV', 1, 1, NULL, NULL),
(10, 'garage', 'http://localhost:8000/images/mutiple-entry.png', 'multiple-entry-exit', 'Multiple Entry/ Exit', 1, 1, NULL, NULL),
(11, 'carpark', 'http://localhost:8000/images/plug.png', 'electric-charging', 'Electric Charging', 1, 1, NULL, NULL),
(12, 'carpark', 'http://localhost:8000/images/cctv.png', 'cctv', 'CCTV', 1, 1, NULL, NULL),
(13, 'carpark', '', 'multiple-entry-exit', 'Multiple Entry/ Exit', 1, 1, NULL, NULL),
(14, 'carpark', '', 'covered', 'Covered', 1, 1, NULL, NULL),
(15, 'carpark', '', 'onsite-staff', 'OnSite Staff', 1, 1, NULL, NULL),
(16, 'carpark', '', 'disabled-access', 'Disabled Access', 1, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_100000_create_password_resets_table', 1),
(2, '2017_08_24_000000_create_settings_table', 1),
(3, '2019_07_16_130736_create_jobs_table', 1),
(4, '2019_07_16_130746_create_failed_jobs_table', 1),
(5, '2019_09_09_082906_add_v1_auth_related_tables', 1),
(6, '2019_09_09_083432_add_v1_settings_related_tables', 1),
(7, '2019_09_09_103505_add_v1_host_related_migrations', 1),
(8, '2019_09_09_105001_add_v1_bookings_related_migrations', 1),
(9, '2019_09_17_160941_add_availability_related_fields_to_tables', 1),
(10, '2019_09_23_135910_add_is_amenity_to_lookups_table', 1),
(11, '2019_09_26_105855_add_v1_1_fields', 1),
(12, '2019_11_15_123237_add_v2_0_fields', 1),
(13, '2019_11_28_071206_add_price_type_to_bookings_table', 1),
(14, '2019_12_06_125643_add_is_additional_hours_in_booking_payments', 1),
(15, '2019_12_10_061043_add_dimension_to_host_table', 1),
(16, '2019_12_20_050647_add_referal_code_to_bookings_table', 1),
(17, '2020_01_17_050745_add_verified_providers_to_provider_table', 1),
(18, '2020_05_07_081537_add_apple_to_users_table', 1),
(19, '2021_08_18_071758_add_deteled_acc_related_fields', 1);

-- --------------------------------------------------------

--
-- Table structure for table `mobile_registers`
--

CREATE TABLE `mobile_registers` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` int(11) NOT NULL,
  `user_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mobile_registers`
--

INSERT INTO `mobile_registers` (`id`, `type`, `count`, `user_type`, `created_at`, `updated_at`) VALUES
(1, 'android', 0, 'user', NULL, NULL),
(2, 'ios', 0, 'user', NULL, NULL),
(3, 'web', 2, 'user', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `page_counters`
--

CREATE TABLE `page_counters` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'http://localhost:8000/placeholder.jpg',
  `token_expiry` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language_id` int(11) NOT NULL DEFAULT '0',
  `work` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `school` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `languages` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_rate` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `device_token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `device_type` enum('web','android','ios') COLLATE utf8mb4_unicode_ci NOT NULL,
  `register_type` enum('web','android','ios') COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_by` enum('manual','facebook','google','twitter','linkedin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `social_unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gender` enum('male','female','others') COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` double(15,8) NOT NULL,
  `longitude` double(15,8) NOT NULL,
  `full_address` text COLLATE utf8mb4_unicode_ci,
  `street_details` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `zipcode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `payment_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_card_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `timezone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Los_Angeles',
  `registration_steps` tinyint(4) NOT NULL DEFAULT '0',
  `push_notification_status` int(11) NOT NULL DEFAULT '1',
  `email_notification_status` int(11) NOT NULL DEFAULT '1',
  `is_verified` int(11) NOT NULL DEFAULT '0',
  `verification_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `verification_code_expiry` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `identity_verification_file` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_document_verified` int(11) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `unique_id`, `username`, `first_name`, `last_name`, `name`, `email`, `token`, `provider_type`, `email_verified_at`, `password`, `description`, `mobile`, `picture`, `token_expiry`, `language_id`, `work`, `school`, `languages`, `response_rate`, `device_token`, `device_type`, `register_type`, `login_by`, `social_unique_id`, `gender`, `latitude`, `longitude`, `full_address`, `street_details`, `city`, `state`, `zipcode`, `payment_mode`, `provider_card_id`, `timezone`, `registration_steps`, `push_notification_status`, `email_notification_status`, `is_verified`, `verification_code`, `verification_code_expiry`, `status`, `created_at`, `updated_at`, `identity_verification_file`, `is_document_verified`, `is_deleted`) VALUES
(1, '613c86c674607', 'providerdemo', 'Provider', 'Provider', 'Provider', 'provider@rentcubo.com', '2y10p5nWDYFAMf5yzadOQl4sbm6c1aQxuQTDMVM7CEwnfI269yiy', '0', NULL, '$2y$10$pzF0KJAJAM0mCs6J1r0lo.xDzdWaNlNuAjQjLwoB7x4yl/d6oVCiC', NULL, '', 'https://admin-rentroom.rentcubo.info/placeholder.jpg', '1631360214', 1, '', '', '', '', '', 'web', 'web', 'manual', '', 'male', 0.00000000, 0.00000000, NULL, '', '', '', '', 'COD', '0', 'America/Los_Angeles', 1, 1, 1, 1, '', '', 1, '2021-09-11 05:06:54', '2021-09-11 05:06:54', '', 0, 0),
(2, '613c86c69cb62', 'Test', 'Provider', 'Provider', 'Test', 'test@rentcubo.com', '2y10lt6myCAmEXDNjXJxLNJXfOldKYsAAAgVYFtw1yjYn9I3XomP49tpe', '0', NULL, '$2y$10$XnV8tnypZZqHdAmPwdGNU.4HTAVvijLSx7kNSl0Kxj746cbXqTpzi', NULL, '', 'https://admin-rentroom.rentcubo.info/placeholder.jpg', '1631360214', 1, '', '', '', '', '', 'web', 'web', 'manual', '', 'male', 0.00000000, 0.00000000, NULL, '', '', '', '', 'COD', '0', 'America/Los_Angeles', 1, 1, 1, 1, '', '', 1, '2021-09-11 05:06:54', '2021-09-11 05:06:54', '', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `provider_billing_infos`
--

CREATE TABLE `provider_billing_infos` (
  `id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(11) NOT NULL,
  `account_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paypal_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_cards`
--

CREATE TABLE `provider_cards` (
  `id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(11) NOT NULL,
  `card_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_four` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_details`
--

CREATE TABLE `provider_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_documents`
--

CREATE TABLE `provider_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `document_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_redeems`
--

CREATE TABLE `provider_redeems` (
  `id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(11) NOT NULL,
  `total` double(8,2) NOT NULL DEFAULT '0.00',
  `paid_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `dispute_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `paid_date` datetime NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_subscriptions`
--

CREATE TABLE `provider_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_type` enum('month','day','year') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'month',
  `total_subscribers` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `is_popular` tinyint(4) NOT NULL DEFAULT '0',
  `is_free_subscription` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_subscription_payments`
--

CREATE TABLE `provider_subscription_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `provider_id` int(11) NOT NULL,
  `provider_subscription_id` int(11) NOT NULL,
  `payment_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CARD',
  `expiry_date` datetime NOT NULL,
  `subscription_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `paid_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `paid_date` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  `is_current_subscription` int(11) NOT NULL DEFAULT '0',
  `is_cancelled` int(11) NOT NULL DEFAULT '0',
  `cancelled_reason` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `subscribed_by` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'provider',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_locations`
--

CREATE TABLE `service_locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_radius` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '10',
  `latitude` double(15,8) NOT NULL DEFAULT '0.00000000',
  `longitude` double(15,8) NOT NULL DEFAULT '0.00000000',
  `status` int(11) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Rental', NULL, NULL, NULL),
(2, 'tag_name', 'Rental', NULL, NULL, NULL),
(3, 'site_logo', 'http://localhost:8000/logo.png', NULL, NULL, NULL),
(4, 'site_icon', 'http://localhost:8000/favicon.png', NULL, NULL, NULL),
(5, 'frontend_url', 'http://rentroom.rentcubo.info/', NULL, NULL, NULL),
(6, 'version', 'v1.0.0', NULL, NULL, NULL),
(7, 'default_lang', 'en', NULL, NULL, NULL),
(8, 'currency', '$', NULL, NULL, NULL),
(9, 'currency_code', 'usd', NULL, NULL, NULL),
(10, 'tax_percentage', '10', NULL, NULL, NULL),
(11, 'admin_take_count', '12', NULL, NULL, NULL),
(12, 'is_demo_control_enabled', '0', NULL, NULL, NULL),
(13, 'is_account_email_verification', '1', NULL, NULL, NULL),
(14, 'is_email_notification', '1', NULL, NULL, NULL),
(15, 'is_email_configured', '1', NULL, NULL, NULL),
(16, 'is_push_notification', '1', NULL, NULL, NULL),
(17, 'installation_steps', '0', NULL, NULL, NULL),
(18, 'chat_socket_url', '', NULL, NULL, NULL),
(19, 'google_api_key', 'AIzaSyARW_YBJ-OU_RfSlMLlvLBHJaG-W_EQv4I', NULL, NULL, NULL),
(20, 'MAILGUN_PUBLIC_KEY', '', NULL, NULL, NULL),
(21, 'MAILGUN_PRIVATE_KEY', '', NULL, NULL, NULL),
(22, 'stripe_publishable_key', 'pk_test_uDYrTXzzAuGRwDYtu7dkhaF3', NULL, NULL, NULL),
(23, 'stripe_secret_key', 'sk_test_lRUbYflDyRP3L2UbnsehTUHW', NULL, NULL, NULL),
(24, 'stripe_mode', 'sandbox', NULL, NULL, NULL),
(25, 'token_expiry_hour', '1000000', NULL, NULL, NULL),
(26, 'copyright_content', 'Copyrights Date(\'Y-m-d\') . All rights reserved.', NULL, NULL, NULL),
(27, 'contact_email', '', NULL, NULL, NULL),
(28, 'contact_address', '', NULL, NULL, NULL),
(29, 'contact_mobile', '', NULL, NULL, NULL),
(30, 'google_analytics', '', NULL, NULL, NULL),
(31, 'header_scripts', '', NULL, NULL, NULL),
(32, 'body_scripts', '', NULL, NULL, NULL),
(33, 'appstore_user', '', NULL, NULL, NULL),
(34, 'playstore_user', '', NULL, NULL, NULL),
(35, 'appstore_provider', '', NULL, NULL, NULL),
(36, 'playstore_provider', '', NULL, NULL, NULL),
(37, 'facebook_link', '', NULL, NULL, NULL),
(38, 'linkedin_link', '', NULL, NULL, NULL),
(39, 'twitter_link', '', NULL, NULL, NULL),
(40, 'google_plus_link', '', NULL, NULL, NULL),
(41, 'pinterest_link', '', NULL, NULL, NULL),
(42, 'instagram_link', '', NULL, NULL, NULL),
(43, 'youtube_link', '', NULL, NULL, NULL),
(44, 'demo_admin_email', '', NULL, NULL, NULL),
(45, 'demo_admin_password', '', NULL, NULL, NULL),
(46, 'demo_user_email', '', NULL, NULL, NULL),
(47, 'demo_user_password', '', NULL, NULL, NULL),
(48, 'demo_provider_email', '', NULL, NULL, NULL),
(49, 'demo_provider_password', '', NULL, NULL, NULL),
(50, 'per_base_price', '1', NULL, NULL, NULL),
(51, 'is_appstore_updated', '0', NULL, NULL, NULL),
(52, 'user_fcm_sender_id', '865212328189', NULL, NULL, NULL),
(53, 'user_fcm_server_key', 'AAAASJFloB0:APA91bHBe54g5RP63U3EMTRClOVIXV3R8dwQ0xdwGTimGIWuKklipnpn3a7ASHDmEIuZ_OHTUDpWPYIzsXLTXXPE_UEJOz0BR1GgZ7s_gF41DKZjmJVsO3qfUOpZT2SqVMInOcL1Z55e', NULL, NULL, NULL),
(54, 'provider_fcm_sender_id', '652769449242', NULL, NULL, NULL),
(55, 'provider_fcm_server_key', 'AAAAl_wXVRo:APA91bF2ns02jAWbkSMX7GndZw5noBZpKQhvTqYVZHAYQRuE0VV3nf7LdpA1cgyIopEMwa69S9stHL4Q9_iIrp-txSQs8fooAoOvl4kYQomsNfe6XBzFKQf64LDMBc9kU1EZNaEUb5hc', NULL, NULL, NULL),
(56, 'search_radius', '100', NULL, NULL, NULL),
(57, 'booking_admin_commission', '0', NULL, NULL, NULL),
(58, 'demo_logins_token', 'user@rentcubo.com,test@rentcubo.com,developer@rentcubo.com,user@rentpark.com,test@rentpark.com,developer@rentpark.com,provider@rentcubo.com, provider@rentpark.com', NULL, NULL, NULL),
(59, 'FB_CLIENT_ID', '', NULL, '2021-09-11 05:06:54', '2021-09-11 05:06:54'),
(60, 'FB_CLIENT_SECRET', '', NULL, '2021-09-11 05:06:54', '2021-09-11 05:06:54'),
(61, 'FB_CALL_BACK', '', NULL, '2021-09-11 05:06:54', '2021-09-11 05:06:54'),
(62, 'TWITTER_CLIENT_ID', '', NULL, '2021-09-11 05:06:54', '2021-09-11 05:06:54'),
(63, 'TWITTER_CLIENT_SECRET', '', NULL, '2021-09-11 05:06:54', '2021-09-11 05:06:54'),
(64, 'TWITTER_CALL_BACK', '', NULL, '2021-09-11 05:06:54', '2021-09-11 05:06:54'),
(65, 'GOOGLE_CLIENT_ID', '', NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(66, 'GOOGLE_CLIENT_SECRET', '', NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(67, 'GOOGLE_CALL_BACK', '', NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(68, 'identity_verification_preview', 'http://localhost:8000/verification-placeholder.jpg', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `static_pages`
--

CREATE TABLE `static_pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '613c869bc51b7',
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('about','privacy','terms','refund','cancellation','faq','help','contact','others') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `section_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `static_pages`
--

INSERT INTO `static_pages` (`id`, `unique_id`, `title`, `description`, `type`, `status`, `section_type`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'about', 'about', 'about', 'about', 1, NULL, NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(2, 'contact', 'contact', 'contact', 'contact', 1, NULL, NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(3, 'privacy', 'privacy', 'privacy', 'privacy', 1, NULL, NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(4, 'terms', 'terms', 'terms', 'terms', 1, NULL, NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(5, 'help', 'help', 'help', 'help', 1, NULL, NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(6, 'faq', 'faq', 'faq', 'faq', 1, NULL, NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55'),
(7, 'refund', 'refund', 'refund', 'refund', 1, NULL, NULL, '2021-09-11 05:06:55', '2021-09-11 05:06:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'http://localhost:8000/placeholder.jpg',
  `token_expiry` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` tinyint(4) NOT NULL DEFAULT '0',
  `language_id` int(11) NOT NULL DEFAULT '0',
  `device_token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `device_type` enum('web','android','ios') COLLATE utf8mb4_unicode_ci NOT NULL,
  `register_type` enum('web','android','ios') COLLATE utf8mb4_unicode_ci NOT NULL,
  `login_by` enum('manual','facebook','apple','twitter','instagram','google') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'manual',
  `social_unique_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `gender` enum('male','female','others') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_card_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `timezone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Los_Angeles',
  `registration_steps` tinyint(4) NOT NULL DEFAULT '0',
  `push_notification_status` int(11) NOT NULL DEFAULT '1',
  `email_notification_status` int(11) NOT NULL DEFAULT '1',
  `is_verified` int(11) NOT NULL DEFAULT '0',
  `verification_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `verification_code_expiry` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `is_deleted` tinyint(4) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `unique_id`, `username`, `first_name`, `last_name`, `name`, `email`, `token`, `email_verified_at`, `password`, `dob`, `description`, `mobile`, `picture`, `token_expiry`, `user_type`, `language_id`, `device_token`, `device_type`, `register_type`, `login_by`, `social_unique_id`, `gender`, `payment_mode`, `user_card_id`, `timezone`, `registration_steps`, `push_notification_status`, `email_notification_status`, `is_verified`, `verification_code`, `verification_code_expiry`, `is_deleted`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, '613c86c60f794', 'userdemo', 'User', 'User', 'User', 'user@rentcubo.com', '2y10TwPgumeGExFYsI5ABSkSeSSz6cgWvKjAU9ZeBmcHrRlcgVRAy6s2', NULL, '$2y$10$ZEhfZA.7DiPDLSaF84gwoebnl9P5y7u2BV7lExXSqtRSlXc3Pm8d6', '', NULL, '', 'https://admin-rentroom.rentcubo.info/placeholder.jpg', '1631360214', 0, 1, '', 'web', 'web', 'manual', '', 'male', 'COD', '0', 'America/Los_Angeles', 1, 1, 1, 1, '', '', 0, 1, NULL, '2021-09-11 05:06:54', '2021-09-11 05:06:54'),
(2, '613c86c63f79f', 'Test', 'TEST', 'TEST', 'Test', 'test@rentcubo.com', '2y10e9I8S4itnxYf31r4NM3VMuxq42w8BpppcZL7NHRb4Tw9EyLfboa', NULL, '$2y$10$mhsfrhljqP1vFZma8un3FOxjkhw/H86o9hbraRs0MLfbYY6OTSqn6', '', NULL, '', 'https://admin-rentroom.rentcubo.info/placeholder.jpg', '1631360214', 0, 1, '', 'web', 'web', 'manual', '', 'male', 'COD', '0', 'America/Los_Angeles', 1, 1, 1, 1, '', '', 0, 1, NULL, '2021-09-11 05:06:54', '2021-09-11 05:06:54');

-- --------------------------------------------------------

--
-- Table structure for table `user_billing_infos`
--

CREATE TABLE `user_billing_infos` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paypal_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_cards`
--

CREATE TABLE `user_cards` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `card_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_four` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_refunds`
--

CREATE TABLE `user_refunds` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` double(8,2) NOT NULL DEFAULT '0.00',
  `paid_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` double(8,2) NOT NULL DEFAULT '0.00',
  `paid_date` datetime NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_vehicles`
--

CREATE TABLE `user_vehicles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_brand` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_model` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DEFAULT',
  `host_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `bell_notifications`
--
ALTER TABLE `bell_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bell_notification_templates`
--
ALTER TABLE `bell_notification_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_chats`
--
ALTER TABLE `booking_chats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_payments`
--
ALTER TABLE `booking_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_provider_reviews`
--
ALTER TABLE `booking_provider_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `booking_user_reviews`
--
ALTER TABLE `booking_user_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hosts`
--
ALTER TABLE `hosts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `host_availabilities`
--
ALTER TABLE `host_availabilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `host_availability_lists`
--
ALTER TABLE `host_availability_lists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `host_details`
--
ALTER TABLE `host_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `host_galleries`
--
ALTER TABLE `host_galleries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `host_inventories`
--
ALTER TABLE `host_inventories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `lookups`
--
ALTER TABLE `lookups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mobile_registers`
--
ALTER TABLE `mobile_registers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `page_counters`
--
ALTER TABLE `page_counters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `providers_email_unique` (`email`);

--
-- Indexes for table `provider_billing_infos`
--
ALTER TABLE `provider_billing_infos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `provider_cards`
--
ALTER TABLE `provider_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `provider_details`
--
ALTER TABLE `provider_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `provider_documents`
--
ALTER TABLE `provider_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `provider_redeems`
--
ALTER TABLE `provider_redeems`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `provider_subscriptions`
--
ALTER TABLE `provider_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `provider_subscription_payments`
--
ALTER TABLE `provider_subscription_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_locations`
--
ALTER TABLE `service_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `settings_key_index` (`key`);

--
-- Indexes for table `static_pages`
--
ALTER TABLE `static_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `static_pages_title_unique` (`title`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_billing_infos`
--
ALTER TABLE `user_billing_infos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_cards`
--
ALTER TABLE `user_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_refunds`
--
ALTER TABLE `user_refunds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `bell_notifications`
--
ALTER TABLE `bell_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `bell_notification_templates`
--
ALTER TABLE `bell_notification_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `booking_chats`
--
ALTER TABLE `booking_chats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `booking_payments`
--
ALTER TABLE `booking_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `booking_provider_reviews`
--
ALTER TABLE `booking_provider_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `booking_user_reviews`
--
ALTER TABLE `booking_user_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `hosts`
--
ALTER TABLE `hosts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `host_availabilities`
--
ALTER TABLE `host_availabilities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `host_availability_lists`
--
ALTER TABLE `host_availability_lists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `host_details`
--
ALTER TABLE `host_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `host_galleries`
--
ALTER TABLE `host_galleries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `host_inventories`
--
ALTER TABLE `host_inventories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `lookups`
--
ALTER TABLE `lookups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT for table `mobile_registers`
--
ALTER TABLE `mobile_registers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `page_counters`
--
ALTER TABLE `page_counters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `provider_billing_infos`
--
ALTER TABLE `provider_billing_infos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `provider_cards`
--
ALTER TABLE `provider_cards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `provider_details`
--
ALTER TABLE `provider_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `provider_documents`
--
ALTER TABLE `provider_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `provider_redeems`
--
ALTER TABLE `provider_redeems`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `provider_subscriptions`
--
ALTER TABLE `provider_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `provider_subscription_payments`
--
ALTER TABLE `provider_subscription_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `service_locations`
--
ALTER TABLE `service_locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;
--
-- AUTO_INCREMENT for table `static_pages`
--
ALTER TABLE `static_pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `user_billing_infos`
--
ALTER TABLE `user_billing_infos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_cards`
--
ALTER TABLE `user_cards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_refunds`
--
ALTER TABLE `user_refunds`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
