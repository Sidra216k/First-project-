-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 12:01 PM
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
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'password_reset', 'تم استعادة كلمة المرور بنجاح', NULL, '2026-04-17 18:24:48');

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
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `traveler_id`, `service_id`, `service_type`, `booking_date`, `total_price`, `status`, `icon`, `created_at`) VALUES
(1, 1, 1, 'hotel', '2026-05-10', 2500.00, 'confirmed', NULL, '2026-03-27 13:21:19'),
(2, 1, 1, 'activity', '2026-05-11', 120.00, 'confirmed', NULL, '2026-03-27 13:21:19'),
(3, 1, 1, 'restaurant', '2026-05-12', 180.00, 'completed', NULL, '2026-03-27 13:21:19'),
(4, 1, 3, 'hotel', '2026-06-01', 1800.00, 'completed', NULL, '2026-03-27 13:21:19'),
(5, 1, 3, 'activity', '2026-06-02', 95.00, 'completed', NULL, '2026-03-27 13:21:19'),
(6, 1, 4, 'hotel', '2026-07-15', 950.00, 'completed', NULL, '2026-03-27 13:21:19'),
(7, 1, 5, 'hotel', '2026-09-01', 2200.00, 'pending', NULL, '2026-03-27 13:21:19'),
(8, 1, 6, 'hotel', '2026-12-20', 450.00, 'confirmed', NULL, '2026-03-27 13:21:19'),
(11, 1, 8, 'activity', '2026-04-30', 150.00, 'cancelled', NULL, '2026-04-29 09:55:04'),
(12, 1, 8, 'activity', '2026-05-01', 150.00, 'pending', NULL, '2026-04-29 09:55:33'),
(13, 1, 5, 'hotel', '2026-04-30', 2200.00, 'pending', NULL, '2026-04-29 09:56:27');

--
-- Triggers `bookings`
--
DELIMITER $$
CREATE TRIGGER `after_booking_insert` AFTER INSERT ON `bookings` FOR EACH ROW BEGIN
    DECLARE traveler_name VARCHAR(100);
    DECLARE service_name VARCHAR(100);
    DECLARE action_link VARCHAR(255);
    
    -- جلب اسم المسافر
    SELECT full_name INTO traveler_name FROM travelers WHERE id = NEW.traveler_id LIMIT 1;
    
    -- جلب اسم الخدمة حسب النوع
    CASE NEW.service_type
        WHEN 'hotel' THEN 
            SELECT name INTO service_name FROM hotels WHERE id = NEW.service_id LIMIT 1;
            SET action_link = CONCAT('/Service-Provider/bookings.php?action=view&id=', NEW.id);
        WHEN 'restaurant' THEN 
            SELECT name INTO service_name FROM restaurants WHERE id = NEW.service_id LIMIT 1;
            SET action_link = CONCAT('/Service-Provider/bookings.php?action=view&id=', NEW.id);
        WHEN 'activity' THEN 
            SELECT name INTO service_name FROM activities WHERE id = NEW.service_id LIMIT 1;
            SET action_link = CONCAT('/Service-Provider/bookings.php?action=view&id=', NEW.id);
    END CASE;
    
    -- إنشاء الإشعار لمقدم الخدمة
    INSERT INTO notifications (user_id, role, title, message, type, related_id, related_type, action_url, icon_class)
    SELECT 
        user_id, 
        'provider',
        '? حجز جديد',
        CONCAT('حجز جديد من ', traveler_name, ' على خدمة: ', service_name),
        'booking',
        NEW.id,
        'booking',
        action_link,
        'fa-calendar-plus'
    FROM providers WHERE id = (
        SELECT provider_id FROM hotels WHERE id = NEW.service_id AND NEW.service_type = 'hotel'
        UNION SELECT provider_id FROM restaurants WHERE id = NEW.service_id AND NEW.service_type = 'restaurant'
        UNION SELECT provider_id FROM activities WHERE id = NEW.service_id AND NEW.service_type = 'activity'
        LIMIT 1
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_booking_insert_provider` AFTER INSERT ON `bookings` FOR EACH ROW BEGIN
    DECLARE provider_uid INT;
    
    -- جلب معرف المستخدم لمقدم الخدمة حسب نوع الخدمة
    IF NEW.service_type = 'hotel' THEN
        SELECT user_id INTO provider_uid FROM providers 
        WHERE id = (SELECT provider_id FROM hotels WHERE id = NEW.service_id) LIMIT 1;
    ELSEIF NEW.service_type = 'restaurant' THEN
        SELECT user_id INTO provider_uid FROM providers 
        WHERE id = (SELECT provider_id FROM restaurants WHERE id = NEW.service_id) LIMIT 1;
    ELSEIF NEW.service_type = 'activity' THEN
        SELECT user_id INTO provider_uid FROM providers 
        WHERE id = (SELECT provider_id FROM activities WHERE id = NEW.service_id) LIMIT 1;
    END IF;
    
    IF provider_uid IS NOT NULL THEN
        INSERT INTO notifications (user_id, role, title, message, type, icon, related_type, related_id, action_url, priority)
        VALUES (
            provider_uid,
            'provider',
            '? حجز جديد!',
            CONCAT('حجز جديد من مسافر على خدمتك بقيمة ', NEW.total_price, ' $'),
            'booking',
            'fa-shopping-cart',
            'booking',
            NEW.id,
            'Service-Provider/bookings.php?action=view&id=' || NEW.id,
            'high'
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_booking_insert_traveler` AFTER INSERT ON `bookings` FOR EACH ROW BEGIN
    -- إشعار للمسافر
    INSERT INTO notifications (user_id, role, title, message, type, icon, related_type, related_id, action_url, priority)
    SELECT 
        t.user_id,
        'traveler',
        'تم استلام طلب حجزك ?',
        CONCAT('تم استلام طلب حجزك لـ: ', 
            CASE 
                WHEN NEW.service_type = 'hotel' THEN (SELECT name FROM hotels WHERE id = NEW.service_id)
                WHEN NEW.service_type = 'restaurant' THEN (SELECT name FROM restaurants WHERE id = NEW.service_id)
                WHEN NEW.service_type = 'activity' THEN (SELECT name FROM activities WHERE id = NEW.service_id)
            END,
            ' بتاريخ ', DATE_FORMAT(NEW.booking_date, '%Y-%m-%d')
        ),
        'booking',
        'fa-calendar-check',
        'booking',
        NEW.id,
        CONCAT('Traveler/trip_details.php?id=', 
            (SELECT id FROM trips WHERE traveler_id = NEW.traveler_id ORDER BY created_at DESC LIMIT 1)
        ),
        'high'
    FROM travelers t WHERE t.id = NEW.traveler_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_booking_update` AFTER UPDATE ON `bookings` FOR EACH ROW BEGIN
    IF NEW.status != OLD.status THEN
        -- إشعار للمسافر بتحديث الحالة
        INSERT INTO notifications (user_id, role, title, message, type, icon, related_type, related_id, is_read, action_url, priority)
        SELECT 
            t.user_id,
            'traveler',
            CASE NEW.status
                WHEN 'confirmed' THEN '✅ تم تأكيد حجزك'
                WHEN 'cancelled' THEN '❌ تم إلغاء الحجز'
                WHEN 'completed' THEN '? اكتملت رحلتك'
                ELSE '? تحديث حالة الحجز'
            END,
            CONCAT('تم تغيير حالة حجزك لـ: ', 
                CASE 
                    WHEN NEW.service_type = 'hotel' THEN (SELECT name FROM hotels WHERE id = NEW.service_id)
                    WHEN NEW.service_type = 'restaurant' THEN (SELECT name FROM restaurants WHERE id = NEW.service_id)
                    WHEN NEW.service_type = 'activity' THEN (SELECT name FROM activities WHERE id = NEW.service_id)
                END,
                ' إلى: ', 
                CASE NEW.status
                    WHEN 'confirmed' THEN 'مؤكد'
                    WHEN 'cancelled' THEN 'ملغي'
                    WHEN 'completed' THEN 'مكتمل'
                    ELSE NEW.status
                END
            ),
            'booking',
            CASE NEW.status
                WHEN 'confirmed' THEN 'fa-check-circle'
                WHEN 'cancelled' THEN 'fa-times-circle'
                WHEN 'completed' THEN 'fa-flag-checkered'
                ELSE 'fa-info-circle'
            END,
            'booking',
            NEW.id,
            0,
            'Traveler/my_bookings.php',
            CASE WHEN NEW.status IN ('confirmed','cancelled') THEN 'high' ELSE 'medium' END
        FROM travelers t WHERE t.id = NEW.traveler_id;
    END IF;
END
$$
DELIMITER ;

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
(2, 1, 'أتلانتس النخلة', 'دبي', 'medium', 13000.00, 4.9, 'منتجع خيالي على جزيرة النخلة', 'uploads/hotels/hotel_a6c0c15fe48601eb_1774617988.jpg', '2026-03-27 13:21:19', '2026-04-29 09:23:51'),
(3, 3, 'فندق ريتز باريس', 'باريس', 'luxury', 1800.00, 5.0, 'الفخامة الفرنسية الأصيلة', 'uploads/hotels/hotel_69c684f543229_1774617845.jpg', '2026-03-27 13:21:19', '2026-03-27 13:24:05'),
(4, 5, 'فندق فور سيزنز البوسفور', 'إسطنبول', 'luxury', 950.00, 4.9, 'قصر عثماني مُرمم على ضفاف البوسفور', 'uploads/hotels/hotel_69c684f02a621_1774617840.jpg', '2026-03-27 13:21:19', '2026-03-27 13:24:00'),
(5, 8, 'فندق أمان طوكيو', 'طوكيو', 'luxury', 2200.00, 5.0, 'واحة من الهدوء في قلب المدينة', 'uploads/hotels/hotel_69c684ebd87b7_1774617835.jpg', '2026-03-27 13:21:19', '2026-03-27 13:23:55'),
(6, 9, 'فندق فور سيزنز النيل', 'القاهرة', 'luxury', 450.00, 4.8, 'إطلالات ساحرة على نهر النيل', 'uploads/hotels/hotel_69c684e43a747_1774617828.jpg', '2026-03-27 13:21:19', '2026-03-27 13:23:48');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'معرف المستخدم المستلم',
  `role` enum('admin','provider','traveler') NOT NULL COMMENT 'دور المستخدم',
  `title` varchar(150) NOT NULL COMMENT 'عنوان الإشعار',
  `message` text NOT NULL COMMENT 'نص الإشعار',
  `type` enum('booking','system','promotion','review','alert') DEFAULT 'system' COMMENT 'نوع الإشعار',
  `icon` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0 COMMENT '0=غير مقروء، 1=مقروء',
  `related_id` int(11) DEFAULT NULL COMMENT 'معرف العنصر المرتبط (حجز، خدمة، إلخ)',
  `related_type` enum('booking','hotel','restaurant','activity','trip','review') DEFAULT NULL COMMENT 'نوع العنصر المرتبط',
  `action_url` varchar(255) DEFAULT NULL COMMENT 'رابط الإجراء عند النقر',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `icon_class` varchar(50) DEFAULT 'fa-bell' COMMENT 'أيقونة العرض',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'وقت القراءة'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='نظام الإشعارات المركزي';

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `role`, `title`, `message`, `type`, `icon`, `is_read`, `related_id`, `related_type`, `action_url`, `priority`, `icon_class`, `created_at`, `read_at`) VALUES
(1, 2, 'traveler', 'تم تأكيد حجزك ✈️', 'تم تأكيد حجزك في برج العرب دبي للفترة 10-15 مايو 2026', 'booking', NULL, 1, 1, 'booking', '/Traveler/trip_details.php?id=1', 'medium', 'fa-check-circle', '2026-04-22 17:57:49', '2026-04-22 18:28:41'),
(2, 2, 'traveler', 'تذكير برحلتك القادمة 🗓️', 'رحلتك إلى باريس تبدأ خلال 3 أيام. تأكد من تجهيز مستنداتك', 'alert', NULL, 1, 2, 'trip', '/Traveler/my_itinerary.php', 'medium', 'fa-calendar-alert', '2026-04-22 17:57:49', '2026-04-22 18:28:41'),
(3, 2, 'traveler', 'عرض خاص لك 🎁', 'احصل على خصم 15% على حجوزات إسطنبول عند الحجز خلال 48 ساعة', 'promotion', NULL, 1, NULL, NULL, '/Traveler/search.php?city=إسطنبول', 'medium', 'fa-gift', '2026-04-22 17:57:49', NULL),
(4, 2, 'traveler', 'تمت إضافة تقييم جديد ⭐', 'تم تقييم رحلتك إلى دبي بـ 5 نجوم. شكراً لثقتك!', 'review', NULL, 1, 1, 'booking', '/Traveler/reviews.php', 'medium', 'fa-star', '2026-04-22 17:57:49', NULL),
(5, 2, 'traveler', 'تحديث حالة الحجز 🔄', 'تم تحديث حالة حجز مطعم لو جوردان إلى \"مؤكد\"', 'booking', NULL, 1, 3, 'booking', '/Traveler/my_bookings.php', 'medium', 'fa-clock', '2026-04-22 17:57:49', '2026-04-22 18:28:41'),
(6, 2, 'traveler', '✅ تم تأكيد حجزك', 'تم تغيير حالة حجزك لـ: فندق فور سيزنز النيل إلى: مؤكد', 'booking', 'fa-check-circle', 1, 8, 'booking', 'Traveler/my_bookings.php', 'high', 'fa-bell', '2026-04-29 09:54:03', '2026-04-29 09:54:50'),
(7, 2, 'traveler', 'تم استلام طلب حجزك 📋', 'تم استلام طلب حجزك لـ: تجربة السوشي مع شيف محترف بتاريخ 2026-04-30', 'booking', 'fa-calendar-check', 1, 11, 'booking', 'Traveler/trip_details.php?id=5', 'high', 'fa-bell', '2026-04-29 09:55:04', '2026-04-29 09:57:03'),
(8, 5, 'provider', '🔔 حجز جديد!', 'حجز جديد من مسافر على خدمتك بقيمة 150.00 $', 'booking', 'fa-shopping-cart', 0, 11, 'booking', '1', 'high', 'fa-bell', '2026-04-29 09:55:04', NULL),
(9, 5, 'provider', '🔔 حجز جديد', 'حجز جديد من سيدرة كرزون على خدمة: تجربة السوشي مع شيف محترف', 'booking', NULL, 0, 11, 'booking', '/Service-Provider/bookings.php?action=view&id=11', 'medium', 'fa-calendar-plus', '2026-04-29 09:55:04', NULL),
(10, 2, 'traveler', 'تم استلام طلب حجزك 📋', 'تم استلام طلب حجزك لـ: تجربة السوشي مع شيف محترف بتاريخ 2026-05-01', 'booking', 'fa-calendar-check', 1, 12, 'booking', 'Traveler/trip_details.php?id=5', 'high', 'fa-bell', '2026-04-29 09:55:33', '2026-04-29 09:57:03'),
(11, 5, 'provider', '🔔 حجز جديد!', 'حجز جديد من مسافر على خدمتك بقيمة 150.00 $', 'booking', 'fa-shopping-cart', 0, 12, 'booking', '1', 'high', 'fa-bell', '2026-04-29 09:55:33', NULL),
(12, 5, 'provider', '🔔 حجز جديد', 'حجز جديد من سيدرة كرزون على خدمة: تجربة السوشي مع شيف محترف', 'booking', NULL, 0, 12, 'booking', '/Service-Provider/bookings.php?action=view&id=12', 'medium', 'fa-calendar-plus', '2026-04-29 09:55:33', NULL),
(13, 2, 'traveler', '❌ تم إلغاء الحجز', 'تم تغيير حالة حجزك لـ: تجربة السوشي مع شيف محترف إلى: ملغي', 'booking', 'fa-times-circle', 1, 11, 'booking', 'Traveler/my_bookings.php', 'high', 'fa-bell', '2026-04-29 09:56:11', '2026-04-29 09:57:03'),
(14, 2, 'traveler', 'تم استلام طلب حجزك 📋', 'تم استلام طلب حجزك لـ: فندق أمان طوكيو بتاريخ 2026-04-30', 'booking', 'fa-calendar-check', 1, 13, 'booking', 'Traveler/trip_details.php?id=5', 'high', 'fa-bell', '2026-04-29 09:56:27', '2026-04-29 09:57:03'),
(15, 6, 'provider', '🔔 حجز جديد!', 'حجز جديد من مسافر على خدمتك بقيمة 2200.00 $', 'booking', 'fa-shopping-cart', 0, 13, 'booking', '1', 'high', 'fa-bell', '2026-04-29 09:56:27', NULL),
(16, 6, 'provider', '🔔 حجز جديد', 'حجز جديد من سيدرة كرزون على خدمة: فندق أمان طوكيو', 'booking', NULL, 0, 13, 'booking', '/Service-Provider/bookings.php?action=view&id=13', 'medium', 'fa-calendar-plus', '2026-04-29 09:56:27', NULL),
(17, 2, 'traveler', '✅ تم تأكيد حجزك', 'تم تغيير حالة حجزك لـ: جولة خاصة في متحف اللوفر إلى: مؤكد', 'booking', 'fa-check-circle', 0, 5, 'booking', 'Traveler/my_bookings.php', 'high', 'fa-bell', '2026-04-29 09:57:53', NULL),
(18, 2, 'traveler', '🎉 اكتملت رحلتك', 'تم تغيير حالة حجزك لـ: جولة خاصة في متحف اللوفر إلى: مكتمل', 'booking', 'fa-flag-checkered', 0, 5, 'booking', 'Traveler/my_bookings.php', 'medium', 'fa-bell', '2026-04-29 09:58:11', NULL),
(19, 2, 'traveler', '🎉 اكتملت رحلتك', 'تم تغيير حالة حجزك لـ: فندق فور سيزنز البوسفور إلى: مكتمل', 'booking', 'fa-flag-checkered', 0, 6, 'booking', 'Traveler/my_bookings.php', 'medium', 'fa-bell', '2026-04-29 09:58:12', NULL),
(20, 2, 'traveler', '🎉 اكتملت رحلتك', 'تم تغيير حالة حجزك لـ: فندق ريتز باريس إلى: مكتمل', 'booking', 'fa-flag-checkered', 0, 4, 'booking', 'Traveler/my_bookings.php', 'medium', 'fa-bell', '2026-04-29 09:58:13', NULL);

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
(2, 3, 'فنادق دبي الفاخرة', 'hotel', 1),
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
(6, 10, 'مطعم أبو طارق', 'القاهرة', 'مصري', 12.00, 4.7, 'uploads/restaurants/restaurant_3df1855379602d9c_1774619166.jpg', 'https://images.unsplash.com/photo-1563379926898-0c02c7e7c0f8?auto=format&fit=crop&w=800&q=80', '2026-03-27 13:21:19', '2026-03-27 13:52:32'),
(7, 1, ';laasld;kasd', 'asdklasd asdaskjdasd ', 'عربي', 12000.00, 3.0, 'uploads/restaurants/restaurant_2d1485dfd1af3ad8_1777454697.png', 'efmea.fad asd', '2026-04-29 09:24:57', '2026-04-29 09:24:57');

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
(1, 2, 'سيدرة كرزون', '+963912345678', 2750),
(2, 5, 'لارا', '', 0),
(3, 8, 'ahmad', '0956534565', 0);

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
(5, 1, 'القاهرة - أم الدنيا', 3000.00, '2026-12-20', '2026-12-27', '[{\"type\":\"hotel\",\"id\":6,\"name\":\"فندق فور سيزنز النيل\",\"price\":450},{\"type\":\"activity\",\"id\":9,\"name\":\"جولة الأهرامات وأبو الهول\",\"price\":65},{\"type\":\"restaurant\",\"id\":6,\"name\":\"مطعم أبو طارق\",\"price\":12}]', '2026-03-27 13:21:20', '2026-03-27 13:21:20'),
(6, 1, 'شسيص شسيsdf', 3333.00, '2026-05-01', '2026-05-02', '[{\"type\":\"hotel\",\"id\":1,\"name\":\"برج العرب دبي\",\"price\":2500},{\"type\":\"restaurant\",\"id\":1,\"name\":\"لو جوردان\",\"price\":180},{\"type\":\"activity\",\"id\":1,\"name\":\"رحلة صحراوية مع عشاء بدوي\",\"price\":120}]', '2026-04-29 09:56:50', '2026-04-29 09:56:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','provider','traveler') NOT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'admin_sidra', 'sidraa@gmail.com', '0987654321', '111111', 'admin', 'active', '2026-03-27 13:21:19'),
(2, 'sidra_traveler', 'sidrat@gmail.com', '0987654322', '111111', 'traveler', 'active', '2026-03-27 13:21:19'),
(3, 'dubai_hotelsa', 'sidrap@gmail.com', '0987654323', '111111', 'provider', 'active', '2026-03-27 13:21:19'),
(4, 'paris_restaurants', 'sidrap2@gmail.com', '0987654324', '111111', 'provider', 'active', '2026-03-27 13:21:19'),
(5, 'istanbul_activities', 'sidrap3@gmail.com', '0987654325', '111111', 'provider', 'active', '2026-03-27 13:21:19'),
(6, 'tokyo_services', 'sidrap4@gmail.com', '0987654326', '111111', 'provider', 'active', '2026-03-27 13:21:19'),
(7, 'cairo_provider', 'sidrap5@gmail.com', '0987654327', '111111', 'provider', 'active', '2026-03-27 13:21:19'),
(8, 'ahmad123', 'ahmad@gmail.com', NULL, '111111@@AA', 'traveler', 'active', '2026-04-29 09:13:24');

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
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_role` (`user_id`,`role`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_is_read` (`is_read`);

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
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_predictions`
--
ALTER TABLE `ai_predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `travelers`
--
ALTER TABLE `travelers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
