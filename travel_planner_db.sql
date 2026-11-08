-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 03:01 PM
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
-- Database: `travel_planner_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `provider_id`, `name`, `city`, `price`, `duration_hours`, `rating`, `image_url`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'رحلة صحراوية مع عشاء بدوي', 'دبي', 120.00, 3, 4.9, 'uploads/activities/activity_292f928edf845f27_1774618036.jpg', 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:27:16'),
(2, 1, 'جولة طيران هليكوبتر فوق دبي', 'دبي', 450.00, 3, 5.0, 'uploads/activities/activity_e1f72976fb51f739_1774618021.jpg', 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:27:01'),
(3, 3, 'جولة خاصة في متحف اللوفر', 'باريس', 95.00, 3, 4.9, 'uploads/activities/activity_32ba116a62593081_1774619720.jpg', 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:55:20'),
(4, 3, 'رحلة نهرية على السين مع عشاء', 'باريس', 120.00, 3, 4.8, 'uploads/activities/activity_3c5a2eb89b92c052_1774619694.jpg', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:54:54'),
(5, 5, 'جولة البوسفور بالقارب', 'إسطنبول', 45.00, 3, 4.9, 'uploads/activities/activity_1351a0495b71bc87_1774619208.jpg', 'https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:46:48'),
(6, 5, 'جولة آيا صوفيا والمسجد الأزرق', 'إسطنبول', 55.00, 3, 4.8, 'uploads/activities/activity_baf083e4d8416881_1774619195.jpg', 'https://images.unsplash.com/photo-1541432901042-2d3669f9d464?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:46:35'),
(7, 6, 'جولة معابد طوكيو القديمة', 'طوكيو', 85.00, 5, 4.9, 'زيارة معابد سينسوجي وميجي', 'https://images.unsplash.com/photo-1503899036084-c55cdd92da26?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:21:19'),
(8, 6, 'تجربة السوشي مع شيف محترف', 'طوكيو', 150.00, 2, 5.0, 'تعلم فن صنع السوشي', 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:21:19'),
(9, 7, 'جولة الأهرامات وأبو الهول', 'القاهرة', 65.00, 3, 4.9, 'uploads/activities/activity_31491960fd50584b_1774619807.jpg', 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:56:47'),
(10, 7, 'رحلة نيلية مع عشاء شعبي', 'القاهرة', 40.00, 3, 4.7, 'uploads/activities/activity_fefbd72aba0ee563_1774619794.jpg', 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:56:34');

-- --------------------------------------------------------

--
-- Table structure for table `ai_predictions`
--

CREATE TABLE `ai_predictions` (
  `id` int(11) NOT NULL,
  `destination` varchar(50) DEFAULT NULL,
  `predicted_price_level` enum('low','medium','high') DEFAULT NULL,
  `best_time_to_book` varchar(50) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_predictions`
--

INSERT INTO `ai_predictions` (`id`, `destination`, `predicted_price_level`, `best_time_to_book`, `generated_at`) VALUES
(1, 'دبي', 'high', 'يناير - فبراير', '2026-03-27 13:21:20'),
(2, 'باريس', 'medium', 'أبريل - مايو', '2026-03-27 13:21:20'),
(3, 'إسطنبول', 'low', 'مارس - أبريل', '2026-03-27 13:21:20'),
(4, 'طوكيو', 'high', 'أكتوبر - نوفمبر', '2026-03-27 13:21:20'),
(5, 'القاهرة', 'low', 'نوفمبر - فبراير', '2026-03-27 13:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `traveler_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_type` enum('hotel','restaurant','activity') DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `traveler_id`, `service_id`, `service_type`, `booking_date`, `total_price`, `status`, `created_at`) VALUES
(1, 1, 1, 'hotel', '2026-05-10', 2500.00, 'confirmed', '2026-03-27 13:21:19'),
(2, 1, 1, 'activity', '2026-05-11', 120.00, 'confirmed', '2026-03-27 13:21:19'),
(3, 1, 1, 'restaurant', '2026-05-12', 180.00, 'pending', '2026-03-27 13:21:19'),
(4, 1, 3, 'hotel', '2026-06-01', 1800.00, 'confirmed', '2026-03-27 13:21:19'),
(5, 1, 3, 'activity', '2026-06-02', 95.00, 'pending', '2026-03-27 13:21:19'),
(6, 1, 4, 'hotel', '2026-07-15', 950.00, 'confirmed', '2026-03-27 13:21:19'),
(7, 1, 5, 'hotel', '2026-09-01', 2200.00, 'pending', '2026-03-27 13:21:19'),
(8, 1, 6, 'hotel', '2026-12-20', 450.00, 'pending', '2026-03-27 13:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `country`, `description`, `image_url`, `is_featured`, `created_at`) VALUES
(1, 'دبي', 'الإمارات العربية المتحدة', 'مدينة المستقبل حيث يلتقي الفخامة بالحداثة', 'uploads/destinations/dest_69c6855a220362.43768340.jpg', 1, '2026-03-27 13:21:19'),
(2, 'باريس', 'فرنسا', 'مدينة الحب والأضواء، موطن برج إيفل', 'uploads/destinations/dest_69c68545538996.86637158.jpg', 1, '2026-03-27 13:21:19'),
(3, 'إسطنبول', 'تركيا', 'جسر بين الشرق والغرب', 'uploads/destinations/dest_69c6853808f903.59235558.jpg', 1, '2026-03-27 13:21:19'),
(4, 'طوكيو', 'اليابان', 'عالم من التكنولوجيا والتقاليد', 'uploads/destinations/dest_69c6852aea9488.29495101.jpg', 1, '2026-03-27 13:21:19'),
(5, 'القاهرة', 'مصر', 'أم الدنيا وأرض الحضارات', 'uploads/destinations/dest_69c6851c543e19.17244304.jpg', 1, '2026-03-27 13:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `category` enum('economic','medium','luxury') DEFAULT NULL,
  `price_per_night` decimal(10,2) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `provider_id`, `name`, `city`, `category`, `price_per_night`, `rating`, `description`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'برج العرب دبي', 'دبي', 'medium', 2500.00, 5.0, 'أيقونة دبي الفاخرة على شكل شراع', 'uploads/hotels/hotel_e1fbc8e829503166_1774618002.jpg', '2026-03-27 13:21:19', '2026-03-27 13:26:42'),
(2, 1, 'أتلانتس النخلة', 'دبي', 'medium', 1200.00, 4.9, 'منتجع خيالي على جزيرة النخلة', 'uploads/hotels/hotel_a6c0c15fe48601eb_1774617988.jpg', '2026-03-27 13:21:19', '2026-03-27 13:26:28'),
(3, 3, 'فندق ريتز باريس', 'باريس', 'luxury', 1800.00, 5.0, 'الفخامة الفرنسية الأصيلة', 'uploads/hotels/hotel_69c684f543229_1774617845.jpg', '2026-03-27 13:21:19', '2026-03-27 13:24:05'),
(4, 5, 'فندق فور سيزنز البوسفور', 'إسطنبول', 'luxury', 950.00, 4.9, 'قصر عثماني مُرمم على ضفاف البوسفور', 'uploads/hotels/hotel_69c684f02a621_1774617840.jpg', '2026-03-27 13:21:19', '2026-03-27 13:24:00'),
(5, 8, 'فندق أمان طوكيو', 'طوكيو', 'luxury', 2200.00, 5.0, 'واحة من الهدوء في قلب المدينة', 'uploads/hotels/hotel_69c684ebd87b7_1774617835.jpg', '2026-03-27 13:21:19', '2026-03-27 13:23:55'),
(6, 9, 'فندق فور سيزنز النيل', 'القاهرة', 'luxury', 450.00, 4.8, 'إطلالات ساحرة على نهر النيل', 'uploads/hotels/hotel_69c684e43a747_1774617828.jpg', '2026-03-27 13:21:19', '2026-03-27 13:23:48');

-- --------------------------------------------------------

--
-- Table structure for table `price_history`
--

CREATE TABLE `price_history` (
  `id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_type` enum('hotel','restaurant','activity') DEFAULT NULL,
  `recorded_price` decimal(10,2) DEFAULT NULL,
  `recorded_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `business_name` varchar(100) DEFAULT NULL,
  `business_type` enum('hotel','restaurant','activity') NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `user_id`, `business_name`, `business_type`, `is_verified`) VALUES
(1, 3, 'فنادق دبي الفاخرة', 'hotel', 1),
(2, 3, 'منتجعات الشاطئ الذهبي', 'hotel', 1),
(3, 4, 'مطاعم باريس الأصيلة', 'restaurant', 1),
(4, 4, 'مقاهي مونمارتر', 'restaurant', 1),
(5, 5, 'جولات إسطنبول التاريخية', 'activity', 1),
(6, 5, 'مغامرات البوسفور', 'activity', 1),
(7, 6, 'تجارب طوكيو الفريدة', 'activity', 1),
(8, 6, 'فنادق طوكيو الحديثة', 'hotel', 1),
(9, 7, 'فنادق القاهرة التراثية', 'hotel', 1),
(10, 7, 'مطاعم القاهرة الشعبية', 'restaurant', 1);

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `cuisine_type` varchar(50) DEFAULT NULL,
  `avg_meal_price` decimal(10,2) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `provider_id`, `name`, `city`, `cuisine_type`, `avg_meal_price`, `rating`, `image_url`, `description`, `created_at`, `updated_at`) VALUES
(1, 3, 'لو جوردان', 'باريس', 'فرنسي', 180.00, 5.0, 'uploads/restaurants/restaurant_3df1855379602d9c_1774619166.jpg', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:52:35'),
(2, 4, 'كافيه دي فلور', 'باريس', 'فرنسي', 45.00, 4.7, 'uploads/restaurants/restaurant_3df1855379602d9c_1774619166.jpg', 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:51:12'),
(3, 5, 'مطعم 360 إسطنبول', 'إسطنبول', 'عربي', 55.00, 4.8, 'uploads/restaurants/restaurant_eae9105c4b0e62ff_1774619181.jpg', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:46:21'),
(4, 5, 'مطعم حمدى', 'إسطنبول', 'عربي', 25.00, 4.9, 'uploads/restaurants/restaurant_3df1855379602d9c_1774619166.jpg', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:46:06'),
(5, 6, 'سوكياباشي جيرو', 'طوكيو', 'سوشي', 300.00, 5.0, 'uploads/restaurants/restaurant_3df1855379602d9c_1774619166.jpg', 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:52:34'),
(6, 10, 'مطعم أبو طارق', 'القاهرة', 'مصري', 12.00, 4.7, 'uploads/restaurants/restaurant_3df1855379602d9c_1774619166.jpg', 'https://images.unsplash.com/photo-1563379926898-0c02c7e7c0f8?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:52:32');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_type` enum('hotel','restaurant','activity') DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `service_id`, `service_type`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 1, 'hotel', 5, 'تجربة لا تُنسى في برج العرب! الخدمة ملكية والإطلالة خيالية ✨', '2026-03-27 13:21:20'),
(2, 2, 1, 'activity', 5, 'الرحلة الصحراوية كانت ساحرة! العشاء البدوي تحت النجوم لحظة سحرية 🐫', '2026-03-27 13:21:20'),
(3, 2, 3, 'hotel', 5, 'ريتز باريس هو تعريف الفخامة الحقيقية 🇫🇷💫', '2026-03-27 13:21:20'),
(4, 2, 4, 'hotel', 5, 'فور سيزنز البوسفور مزيج مثالي من التاريخ والرفاهية 🌅', '2026-03-27 13:21:20'),
(5, 2, 4, 'restaurant', 4, 'مطعم حمدى يقدم أفضل بيتزا لحم في إسطنبول! 😋', '2026-03-27 13:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `available_count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `hotel_id`, `room_type`, `price`, `available_count`) VALUES
(1, 1, 'جناح بانورامي', 3500.00, 2),
(2, 1, 'جناح ملكي', 8000.00, 1),
(3, 2, 'غرفة ديلوكس بحرية', 1400.00, 5),
(4, 3, 'جناح كوكو شانيل', 4500.00, 1),
(5, 4, 'جناح البوسفور', 1800.00, 2),
(6, 5, 'جناح طوكيو', 4000.00, 1),
(7, 6, 'جناح النيل الملكي', 900.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'TravelPlanner', 'string', 'اسم الموقع الرسمي', '2026-03-27 13:21:20'),
(2, 'commission_rate', '12', 'number', 'نسبة عمولة المنصة (%)', '2026-03-27 13:21:20'),
(3, 'min_budget', '500', 'number', 'أدنى ميزانية مسموحة ($)', '2026-03-27 13:21:20'),
(4, 'max_budget', '100000', 'number', 'أقصى ميزانية مسموحة ($)', '2026-03-27 13:21:20'),
(5, 'ai_enabled', 'true', 'boolean', 'تفعيل ميزات الذكاء الاصطناعي', '2026-03-27 13:21:20'),
(6, 'maintenance_mode', 'false', 'boolean', 'وضع الصيانة', '2026-03-27 13:21:20'),
(7, 'default_currency', 'USD', 'string', 'العملة الافتراضية', '2026-03-27 13:21:20'),
(8, 'loyalty_points_per_dollar', '1', 'number', 'نقاط الولاء لكل دولار', '2026-03-27 13:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `travelers`
--

CREATE TABLE `travelers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travelers`
--

INSERT INTO `travelers` (`id`, `user_id`, `full_name`, `phone`, `loyalty_points`) VALUES
(1, 2, 'سيدرة كرزون', '+963912345678', 2500),
(2, 5, 'لارا', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `id` int(11) NOT NULL,
  `traveler_id` int(11) DEFAULT NULL,
  `destination` varchar(50) DEFAULT NULL,
  `total_budget` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `itinerary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`id`, `traveler_id`, `destination`, `total_budget`, `start_date`, `end_date`, `itinerary_json`, `created_at`, `updated_at`) VALUES
(1, 1, 'دبي - رحلة الفخامة', 10000.00, '2026-05-10', '2026-05-15', '[{\"type\":\"hotel\",\"id\":1,\"name\":\"برج العرب دبي\",\"price\":2500},{\"type\":\"activity\",\"id\":1,\"name\":\"رحلة صحراوية مع عشاء بدوي\",\"price\":120},{\"type\":\"restaurant\",\"id\":1,\"name\":\"لو جوردان\",\"price\":180}]', '2026-03-27 13:21:20', '2026-03-27 13:21:20'),
(2, 1, 'باريس - مدينة الحب', 8000.00, '2026-06-01', '2026-06-07', '[{\"type\":\"hotel\",\"id\":3,\"name\":\"فندق ريتز باريس\",\"price\":1800},{\"type\":\"activity\",\"id\":3,\"name\":\"جولة خاصة في متحف اللوفر\",\"price\":95},{\"type\":\"restaurant\",\"id\":1,\"name\":\"لو جوردان\",\"price\":180}]', '2026-03-27 13:21:20', '2026-03-27 13:21:20'),
(3, 1, 'إسطنبول - جسر الحضارات', 5000.00, '2026-07-15', '2026-07-21', '[{\"type\":\"hotel\",\"id\":4,\"name\":\"فندق فور سيزنز البوسفور\",\"price\":950},{\"type\":\"activity\",\"id\":5,\"name\":\"جولة البوسفور بالقارب\",\"price\":45},{\"type\":\"restaurant\",\"id\":3,\"name\":\"مطعم 360 إسطنبول\",\"price\":55}]', '2026-03-27 13:21:20', '2026-03-27 13:21:20'),
(4, 1, 'طوكيو - بين التقليد والحداثة', 12000.00, '2026-09-01', '2026-09-10', '[{\"type\":\"hotel\",\"id\":5,\"name\":\"فندق أمان طوكيو\",\"price\":2200},{\"type\":\"activity\",\"id\":7,\"name\":\"جولة معابد طوكيو القديمة\",\"price\":85},{\"type\":\"restaurant\",\"id\":5,\"name\":\"سوكياباشي جيرو\",\"price\":300}]', '2026-03-27 13:21:20', '2026-03-27 13:21:20'),
(5, 1, 'القاهرة - أم الدنيا', 3000.00, '2026-12-20', '2026-12-27', '[{\"type\":\"hotel\",\"id\":6,\"name\":\"فندق فور سيزنز النيل\",\"price\":450},{\"type\":\"activity\",\"id\":9,\"name\":\"جولة الأهرامات وأبو الهول\",\"price\":65},{\"type\":\"restaurant\",\"id\":6,\"name\":\"مطعم أبو طارق\",\"price\":12}]', '2026-03-27 13:21:20', '2026-03-27 13:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','provider','traveler') NOT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'admin_sidra', 'sidraa@gmail.com', '1111', 'admin', 'active', '2026-03-27 13:21:19'),
(2, 'sidra_traveler', 'sidrat@gmail.com', '1111', 'traveler', 'active', '2026-03-27 13:21:19'),
(3, 'dubai_hotels', 'sidrap@gmail.com', '1111', 'provider', 'active', '2026-03-27 13:21:19'),
(4, 'paris_restaurants', 'sidrap2@gmail.com', '1111', 'provider', 'active', '2026-03-27 13:21:19'),
(5, 'istanbul_activities', 'sidrap3@gmail.com', '1111', 'provider', 'active', '2026-03-27 13:21:19'),
(6, 'tokyo_services', 'sidrap4@gmail.com', '1111', 'provider', 'active', '2026-03-27 13:21:19'),
(7, 'cairo_provider', 'sidrap5@gmail.com', '1111', 'provider', 'active', '2026-03-27 13:21:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `ai_predictions`
--
ALTER TABLE `ai_predictions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `traveler_id` (`traveler_id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `price_history`
--
ALTER TABLE `price_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `travelers`
--
ALTER TABLE `travelers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `traveler_id` (`traveler_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ai_predictions`
--
ALTER TABLE `ai_predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `price_history`
--
ALTER TABLE `price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `travelers`
--
ALTER TABLE `travelers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`traveler_id`) REFERENCES `travelers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotels`
--
ALTER TABLE `hotels`
  ADD CONSTRAINT `hotels_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `providers`
--
ALTER TABLE `providers`
  ADD CONSTRAINT `providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `travelers`
--
ALTER TABLE `travelers`
  ADD CONSTRAINT `travelers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`traveler_id`) REFERENCES `travelers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
