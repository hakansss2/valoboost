-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 16 Mar 2025, 21:54:24
-- Sunucu sürümü: 8.0.40-cll-lve
-- PHP Sürümü: 8.3.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `kuvvet_boost`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `balances`
--

CREATE TABLE `balances` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int NOT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `account_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `iban` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_name`, `account_number`, `iban`, `branch_code`, `branch_name`, `description`, `logo`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ziraat Bankası', 'Elo Boost Hizmetleri', NULL, 'TR00 0000 0000 0000 0000 0000 00', '123', 'Merkez', 'Havale yaparken açıklama kısmına kullanıcı adınızı yazmayı unutmayın.', NULL, 'active', '2025-03-15 16:40:25', NULL),
(2, 'Ziraat bankası', 'Arif Kuvvet', '6516165', 'TR710004600457888000095031', '0211', 'HATAY', 'Açıklama kısmını boş bırakın', NULL, 'active', '2025-03-15 16:43:31', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `boosters`
--

CREATE TABLE `boosters` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `pending_balance` decimal(10,2) DEFAULT '0.00',
  `total_balance` decimal(10,2) DEFAULT '0.00',
  `withdrawn_balance` decimal(10,2) DEFAULT '0.00',
  `average_rating` decimal(3,2) DEFAULT '0.00',
  `total_orders` int DEFAULT '0',
  `completed_orders` int DEFAULT '0',
  `cancelled_orders` int DEFAULT '0',
  `success_rate` decimal(5,2) DEFAULT '0.00',
  `iban` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_holder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `boosters`
--

INSERT INTO `boosters` (`id`, `user_id`, `pending_balance`, `total_balance`, `withdrawn_balance`, `average_rating`, `total_orders`, `completed_orders`, `cancelled_orders`, `success_rate`, `iban`, `bank_name`, `account_holder`, `last_payment_date`, `created_at`, `updated_at`) VALUES
(4, 7, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, '', '', '', NULL, '2025-03-16 04:35:46', '2025-03-16 05:12:29'),
(5, 29, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, 'TR710004600457888000095031', 'Zirrat', 'arf', NULL, '2025-03-16 04:35:46', '2025-03-16 04:36:54'),
(7, 30, -880.00, 0.00, 880.00, 0.00, 0, 0, 0, 0.00, 'TR710004600457888000095031', 'boost2', 'boost2', NULL, '2025-03-16 04:42:34', '2025-03-16 16:37:55'),
(8, 31, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, 'TR72314600457888000095031', 'boost3', 'boost3', NULL, '2025-03-16 04:43:43', '2025-03-16 04:43:43'),
(9, 7, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, NULL, NULL, NULL, NULL, '2025-03-16 04:51:25', '2025-03-16 04:51:25'),
(10, 29, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, NULL, NULL, NULL, NULL, '2025-03-16 04:51:25', '2025-03-16 04:51:25'),
(11, 30, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, NULL, NULL, NULL, NULL, '2025-03-16 04:51:25', '2025-03-16 04:51:25'),
(12, 31, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, NULL, NULL, NULL, NULL, '2025-03-16 04:51:25', '2025-03-16 04:51:25'),
(16, 32, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, 'TR710boost331310457888000095031', 'boost33131', 'boost33131', NULL, '2025-03-16 05:03:41', '2025-03-16 05:03:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `booster_games`
--

CREATE TABLE `booster_games` (
  `id` int NOT NULL,
  `booster_id` int NOT NULL,
  `game_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `booster_games`
--

INSERT INTO `booster_games` (`id`, `booster_id`, `game_id`, `created_at`) VALUES
(1, 16, 1, '2025-03-16 05:03:41'),
(2, 4, 1, '2025-03-16 05:12:29');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `booster_payments`
--

CREATE TABLE `booster_payments` (
  `id` int NOT NULL,
  `booster_id` int NOT NULL,
  `order_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `booster_payments`
--

INSERT INTO `booster_payments` (`id`, `booster_id`, `order_id`, `amount`, `notes`, `status`, `payment_date`, `created_at`, `updated_at`) VALUES
(1, 7, 1, 400.00, NULL, 'completed', '2025-03-16 16:36:28', '2025-03-15 04:36:48', '2025-03-16 16:36:28'),
(2, 7, 4, 160.00, NULL, 'completed', '2025-03-16 16:36:26', '2025-03-15 15:38:43', '2025-03-16 16:36:26'),
(3, 7, 5, 120.00, NULL, 'completed', '2025-03-16 16:36:24', '2025-03-16 03:05:18', '2025-03-16 16:36:24'),
(4, 7, 6, 120.00, NULL, 'completed', '2025-03-16 16:35:56', '2025-03-16 05:14:13', '2025-03-16 16:35:56'),
(5, 7, 3, 80.00, NULL, 'completed', '2025-03-16 16:33:09', '2025-03-16 05:14:23', '2025-03-16 16:33:09'),
(6, 7, 7, 120.00, NULL, 'completed', '2025-03-16 16:33:02', '2025-03-16 05:14:41', '2025-03-16 16:33:02'),
(7, 7, 8, 80.00, NULL, 'completed', '2025-03-16 16:37:55', '2025-03-16 16:37:47', '2025-03-16 16:37:55');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `booster_ratings`
--

CREATE TABLE `booster_ratings` (
  `id` int NOT NULL,
  `booster_id` int NOT NULL,
  `user_id` int NOT NULL,
  `order_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `booster_stats`
--

CREATE TABLE `booster_stats` (
  `id` int NOT NULL,
  `booster_id` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `total_orders` int DEFAULT '0',
  `completed_orders` int DEFAULT '0',
  `cancelled_orders` int DEFAULT '0',
  `total_earnings` decimal(10,2) DEFAULT '0.00',
  `average_rating` decimal(3,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `boost_prices`
--

CREATE TABLE `boost_prices` (
  `id` int NOT NULL,
  `game_id` int NOT NULL,
  `current_rank_id` int NOT NULL,
  `target_rank_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `priority_multiplier` decimal(4,2) DEFAULT '1.20',
  `streaming_multiplier` decimal(4,2) DEFAULT '1.10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `boost_prices`
--

INSERT INTO `boost_prices` (`id`, `game_id`, `current_rank_id`, `target_rank_id`, `price`, `created_at`, `updated_at`, `priority_multiplier`, `streaming_multiplier`) VALUES
(103, 2, 54, 55, 100.00, '2025-03-15 01:23:56', '2025-03-15 01:23:56', 1.20, 1.10),
(104, 2, 54, 56, 150.00, '2025-03-15 01:23:56', '2025-03-15 01:23:56', 1.20, 1.10),
(105, 2, 55, 56, 200.00, '2025-03-15 01:23:56', '2025-03-15 01:23:56', 1.20, 1.10),
(106, 1, 58, 57, 500.00, '2025-03-15 01:53:56', '2025-03-15 01:53:56', 1.20, 1.10);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `games`
--

CREATE TABLE `games` (
  `id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `games`
--

INSERT INTO `games` (`id`, `name`, `description`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Valorant', 'Valorant, Riot Games tarafından geliştirilen ücretsiz bir FPS oyunudur.', 'assets/img/games/valorant.png', 'active', '2025-03-14 23:13:09', '2025-03-14 23:23:28'),
(2, 'League of Legends', 'League of Legends, Riot Games tarafından geliştirilen MOBA türünde bir oyundur.', 'assets/img/games/lol.jpg', 'active', '2025-03-14 23:13:09', '2025-03-14 23:23:28');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('unread','read') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 7, 'Size yeni bir sipariş atandı. Sipariş #2', 'unread', '2025-03-15 03:43:16', '2025-03-15 03:43:16'),
(2, 5, 'Siparişiniz (#2) booster\'a atandı ve işleme alındı.', 'read', '2025-03-15 03:43:16', '2025-03-15 04:38:29'),
(3, 5, 'Sipariş #2 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:16:18', '2025-03-15 04:16:35'),
(4, 7, 'Sipariş #2 için müşteriden yeni bir mesaj var.', 'unread', '2025-03-15 04:18:48', '2025-03-15 04:18:48'),
(5, 5, 'Sipariş #2 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:21:32', '2025-03-15 04:38:29'),
(6, 7, 'Sipariş #2 için müşteriden yeni bir mesaj var.', 'unread', '2025-03-15 04:22:06', '2025-03-15 04:22:06'),
(7, 5, 'Sipariş #2 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:22:15', '2025-03-15 04:38:29'),
(8, 5, 'Sipariş #2 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:23:47', '2025-03-15 04:38:29'),
(9, 5, 'Sipariş #2 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:23:53', '2025-03-15 04:38:29'),
(10, 7, 'Sipariş #2 için müşteriden yeni bir mesaj var.', 'unread', '2025-03-15 04:27:07', '2025-03-15 04:27:07'),
(11, 5, 'Sipariş #2 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:27:40', '2025-03-15 04:38:29'),
(12, 5, 'Sipariş #2 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:28:47', '2025-03-15 04:38:29'),
(13, 5, 'Sipariş #2 için ilerleme %10 olarak güncellendi.', 'read', '2025-03-15 04:29:53', '2025-03-15 04:38:29'),
(14, 5, 'Sipariş #2 için ilerleme %15 olarak güncellendi.', 'read', '2025-03-15 04:30:15', '2025-03-15 04:38:29'),
(15, 5, 'Sipariş #2 için ilerleme %15 olarak güncellendi.', 'read', '2025-03-15 04:30:18', '2025-03-15 04:38:29'),
(16, 5, 'Sipariş #2 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:30:24', '2025-03-15 04:38:29'),
(17, 5, 'Sipariş #2 tamamlandı! Boost işlemi başarıyla tamamlanmıştır.', 'read', '2025-03-15 04:30:56', '2025-03-15 04:38:29'),
(18, 7, 'Size yeni bir sipariş atandı. Sipariş #1', 'unread', '2025-03-15 04:34:02', '2025-03-15 04:34:02'),
(19, 5, 'Siparişiniz (#1) booster\'a atandı ve işleme alındı.', 'read', '2025-03-15 04:34:02', '2025-03-15 04:38:29'),
(20, 7, 'Sipariş #1 için müşteriden yeni bir mesaj var.', 'unread', '2025-03-15 04:34:34', '2025-03-15 04:34:34'),
(21, 5, 'Sipariş #1 için booster\'dan yeni bir mesajınız var.', 'read', '2025-03-15 04:34:38', '2025-03-15 04:38:29'),
(22, 5, 'Sipariş #1 tamamlandı! Boost işlemi başarıyla tamamlanmıştır.', 'read', '2025-03-15 04:36:48', '2025-03-15 04:38:28'),
(23, 5, '#2 numaralı 100,00 ₺ tutarındaki ödemeniz onaylandı.', 'read', '2025-03-15 05:15:14', '2025-03-15 05:32:55'),
(24, 5, 'Hesabınıza 100,00 ₺ tutarında bakiye eklendi. (Bakiye yükleme)', 'read', '2025-03-15 05:15:14', '2025-03-15 05:32:55'),
(25, 5, '#1 numaralı 100,00 ₺ tutarındaki ödemeniz onaylandı.', 'read', '2025-03-15 05:26:04', '2025-03-15 05:32:55'),
(26, 5, 'Hesabınıza 100,00 ₺ tutarında bakiye eklendi. (Bakiye yükleme)', 'read', '2025-03-15 05:26:04', '2025-03-15 05:32:55'),
(27, 5, '#2 numaralı 100,00 ₺ tutarındaki ödemeniz onaylandı.', 'read', '2025-03-15 05:28:07', '2025-03-15 05:32:55'),
(28, 5, 'Hesabınıza 100,00 ₺ tutarında bakiye eklendi. (Bakiye yükleme)', 'read', '2025-03-15 05:28:07', '2025-03-15 05:32:55'),
(29, 7, 'Size yeni bir sipariş atandı. Sipariş #3', 'unread', '2025-03-15 15:03:27', '2025-03-15 15:03:27'),
(30, 5, 'Siparişiniz (#3) booster\'a atandı ve işleme alındı.', 'unread', '2025-03-15 15:03:27', '2025-03-15 15:03:27'),
(31, 5, 'Sipariş #3 için booster\'dan yeni bir mesajınız var.', 'unread', '2025-03-15 15:26:55', '2025-03-15 15:26:55'),
(32, 5, 'Sipariş #3 için booster\'dan yeni bir mesajınız var.', 'unread', '2025-03-15 15:27:01', '2025-03-15 15:27:01'),
(33, 7, 'Sipariş #3 için müşteriden yeni bir mesaj var.', 'unread', '2025-03-15 15:28:09', '2025-03-15 15:28:09'),
(34, 5, 'Sipariş #3 için booster\'dan yeni bir mesajınız var.', 'unread', '2025-03-15 15:32:55', '2025-03-15 15:32:55'),
(35, 7, 'Sipariş #3 için müşteriden yeni bir mesaj var.', 'unread', '2025-03-15 15:33:07', '2025-03-15 15:33:07'),
(36, 5, 'Sipariş #3 için booster\'dan yeni bir mesajınız var.', 'unread', '2025-03-15 15:36:04', '2025-03-15 15:36:04'),
(37, 7, 'Size yeni bir sipariş atandı. Sipariş #4', 'unread', '2025-03-15 15:37:51', '2025-03-15 15:37:51'),
(38, 5, 'Siparişiniz (#4) booster\'a atandı ve işleme alındı.', 'unread', '2025-03-15 15:37:51', '2025-03-15 15:37:51'),
(39, 5, 'Sipariş #4 için booster\'dan yeni bir mesajınız var.', 'unread', '2025-03-15 15:38:11', '2025-03-15 15:38:11'),
(40, 5, 'Sipariş #4 tamamlandı! Boost işlemi başarıyla tamamlanmıştır.', 'unread', '2025-03-15 15:38:43', '2025-03-15 15:38:43'),
(41, 5, '#7 numaralı 100,00 ₺ tutarındaki ödemeniz onaylandı.', 'unread', '2025-03-15 16:38:16', '2025-03-15 16:38:16'),
(42, 5, 'Hesabınıza 100,00 ₺ tutarında bakiye eklendi. (Bakiye yükleme)', 'unread', '2025-03-15 16:38:16', '2025-03-15 16:38:16'),
(43, 5, 'Bakiye yükleme talebiniz reddedildi. Lütfen destek ekibiyle iletişime geçin.', 'unread', '2025-03-15 16:52:16', '2025-03-15 16:52:16'),
(44, 5, '#15 numaralı 100,00 ₺ tutarındaki ödemeniz onaylandı.', 'unread', '2025-03-15 19:27:17', '2025-03-15 19:27:17'),
(45, 5, 'Hesabınıza 100,00 ₺ tutarında bakiye eklendi. (Bakiye yükleme)', 'unread', '2025-03-15 19:27:17', '2025-03-15 19:27:17'),
(46, 5, 'Ödeme onayı bekleniyor', 'unread', '2025-03-15 19:30:50', '2025-03-15 19:30:50'),
(47, 4, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:30:50', '2025-03-15 19:30:50'),
(48, 22, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:30:50', '2025-03-15 19:30:50'),
(49, 5, 'Ödeme onayı bekleniyor', 'unread', '2025-03-15 19:31:10', '2025-03-15 19:31:10'),
(50, 4, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:31:10', '2025-03-15 19:31:10'),
(51, 22, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:31:10', '2025-03-15 19:31:10'),
(52, 5, 'Ödeme onayı bekleniyor', 'unread', '2025-03-15 19:34:01', '2025-03-15 19:34:01'),
(53, 4, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:34:01', '2025-03-15 19:34:01'),
(54, 22, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:34:01', '2025-03-15 19:34:01'),
(55, 5, 'Ödeme onayı bekleniyor', 'unread', '2025-03-15 19:36:39', '2025-03-15 19:36:39'),
(56, 4, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:36:39', '2025-03-15 19:36:39'),
(57, 22, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:36:40', '2025-03-15 19:36:40'),
(58, 5, 'Ödeme onayı bekleniyor', 'unread', '2025-03-15 19:38:58', '2025-03-15 19:38:58'),
(59, 4, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:38:58', '2025-03-15 19:38:58'),
(60, 22, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:38:58', '2025-03-15 19:38:58'),
(61, 5, 'Ödeme onayı bekleniyor', 'unread', '2025-03-15 19:42:35', '2025-03-15 19:42:35'),
(62, 4, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:42:35', '2025-03-15 19:42:35'),
(63, 22, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:42:35', '2025-03-15 19:42:35'),
(64, 5, 'Ödeme onayı bekleniyor', 'unread', '2025-03-15 19:43:07', '2025-03-15 19:43:07'),
(65, 4, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:43:07', '2025-03-15 19:43:07'),
(66, 22, 'Yeni ödeme onayı', 'unread', '2025-03-15 19:43:07', '2025-03-15 19:43:07'),
(67, 5, '#22 numaralı 500,00 ₺ tutarındaki ödemeniz başarısız oldu.', 'unread', '2025-03-15 19:46:24', '2025-03-15 19:46:24'),
(68, 5, '#21 numaralı 1.500,00 ₺ tutarındaki ödemeniz başarısız oldu.', 'unread', '2025-03-15 19:46:40', '2025-03-15 19:46:40'),
(69, 7, 'Sipariş #3 için müşteriden yeni bir mesaj var.', 'unread', '2025-03-16 00:43:05', '2025-03-16 00:43:05'),
(70, 5, 'Sipariş #3 için booster\'dan yeni bir mesajınız var.', 'unread', '2025-03-16 02:28:30', '2025-03-16 02:28:30'),
(71, 5, 'Sipariş #3 için booster\'dan yeni bir mesajınız var.', 'unread', '2025-03-16 02:43:14', '2025-03-16 02:43:14'),
(72, 7, 'Size yeni bir sipariş atandı. Sipariş #5', 'unread', '2025-03-16 03:02:12', '2025-03-16 03:02:12'),
(73, 5, 'Sipariş #5 tamamlandı! Boost işlemi başarıyla tamamlanmıştır.', 'unread', '2025-03-16 03:05:18', '2025-03-16 03:05:18'),
(74, 7, 'Size yeni bir sipariş atandı. Sipariş #6', 'unread', '2025-03-16 03:08:08', '2025-03-16 03:08:08'),
(75, 5, 'Siparişiniz (#6) booster\'a atandı ve işleme alındı.', 'unread', '2025-03-16 03:08:08', '2025-03-16 03:08:08'),
(76, 5, '#6 numaralı siparişiniz işleme alındı. Booster\'ınız çalışmaya başladı.', 'unread', '2025-03-16 04:02:30', '2025-03-16 04:02:30'),
(77, 5, 'Sipariş #6 tamamlandı! Boost işlemi başarıyla tamamlanmıştır.', 'unread', '2025-03-16 05:14:13', '2025-03-16 05:14:13'),
(78, 5, 'Sipariş #3 tamamlandı! Boost işlemi başarıyla tamamlanmıştır.', 'unread', '2025-03-16 05:14:23', '2025-03-16 05:14:23'),
(79, 7, 'Size yeni bir sipariş atandı. Sipariş #7', 'unread', '2025-03-16 05:14:30', '2025-03-16 05:14:30'),
(80, 5, 'Siparişiniz (#7) booster\'a atandı ve işleme alındı.', 'unread', '2025-03-16 05:14:30', '2025-03-16 05:14:30'),
(81, 5, 'Sipariş #7 tamamlandı! Boost işlemi başarıyla tamamlanmıştır.', 'unread', '2025-03-16 05:14:41', '2025-03-16 05:14:41'),
(82, 30, '120,00 ₺ tutarındaki ödemeniz bakiyenize aktarıldı.', 'unread', '2025-03-16 16:33:02', '2025-03-16 16:33:02'),
(83, 30, '80,00 ₺ tutarındaki ödemeniz bakiyenize aktarıldı.', 'unread', '2025-03-16 16:33:09', '2025-03-16 16:33:09'),
(84, 30, '120,00 ₺ tutarındaki ödemeniz bakiyenize aktarıldı.', 'unread', '2025-03-16 16:35:56', '2025-03-16 16:35:56'),
(85, 30, '120,00 ₺ tutarındaki ödemeniz bakiyenize aktarıldı.', 'unread', '2025-03-16 16:36:24', '2025-03-16 16:36:24'),
(86, 30, '160,00 ₺ tutarındaki ödemeniz bakiyenize aktarıldı.', 'unread', '2025-03-16 16:36:26', '2025-03-16 16:36:26'),
(87, 30, '400,00 ₺ tutarındaki ödemeniz bakiyenize aktarıldı.', 'unread', '2025-03-16 16:36:28', '2025-03-16 16:36:28'),
(88, 7, 'Size yeni bir sipariş atandı. Sipariş #8', 'unread', '2025-03-16 16:37:30', '2025-03-16 16:37:30'),
(89, 5, 'Siparişiniz (#8) booster\'a atandı ve işleme alındı.', 'unread', '2025-03-16 16:37:30', '2025-03-16 16:37:30'),
(90, 5, 'Sipariş #8 tamamlandı! Boost işlemi başarıyla tamamlanmıştır.', 'unread', '2025-03-16 16:37:47', '2025-03-16 16:37:47'),
(91, 30, '80,00 ₺ tutarındaki ödemeniz bakiyenize aktarıldı.', 'unread', '2025-03-16 16:37:55', '2025-03-16 16:37:55'),
(92, 7, 'Size yeni bir sipariş atandı. Sipariş #9', 'unread', '2025-03-16 16:54:12', '2025-03-16 16:54:12'),
(93, 5, 'Siparişiniz (#9) booster\'a atandı ve işleme alındı.', 'unread', '2025-03-16 16:54:12', '2025-03-16 16:54:12');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `game_id` int NOT NULL,
  `current_rank_id` int NOT NULL,
  `target_rank_id` int NOT NULL,
  `booster_id` int DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `progress` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `priority` tinyint(1) DEFAULT '0',
  `streaming` tinyint(1) DEFAULT '0',
  `extra_options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `base_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `booster_earnings` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `game_id`, `current_rank_id`, `target_rank_id`, `booster_id`, `price`, `notes`, `status`, `progress`, `created_at`, `updated_at`, `completed_at`, `priority`, `streaming`, `extra_options`, `base_price`, `total_price`, `booster_earnings`) VALUES
(1, 5, 1, 58, 57, 7, 500.00, '', 'completed', 100, '2025-03-15 02:56:58', '2025-03-16 02:16:23', '2025-03-15 04:36:48', 0, 0, NULL, 0.00, 0.00, 400.00),
(2, 5, 2, 54, 55, 7, 100.00, '', 'completed', 100, '2025-03-15 02:59:39', '2025-03-16 02:16:23', '2025-03-15 04:30:56', 0, 0, NULL, 0.00, 0.00, 80.00),
(3, 5, 2, 54, 55, 7, 100.00, 'deneme', 'completed', 100, '2025-03-15 15:03:18', '2025-03-16 16:44:06', '2025-03-16 05:14:23', 0, 0, NULL, 0.00, 0.00, 80.00),
(4, 5, 2, 55, 56, 7, 200.00, 'sadsa', 'completed', 100, '2025-03-15 15:37:42', '2025-03-16 02:16:23', '2025-03-15 15:38:43', 0, 0, NULL, 0.00, 0.00, 160.00),
(5, 5, 2, 54, 56, 7, 150.00, '', 'completed', 100, '2025-03-16 01:01:38', '2025-03-16 03:16:03', '2025-03-16 03:05:18', 0, 0, NULL, 0.00, 0.00, 120.00),
(6, 5, 2, 54, 56, 7, 150.00, '', 'completed', 100, '2025-03-16 03:03:56', '2025-03-16 16:44:06', '2025-03-16 05:14:13', 0, 0, NULL, 0.00, 0.00, 120.00),
(7, 5, 2, 54, 56, 7, 150.00, '', 'completed', 100, '2025-03-16 05:13:43', '2025-03-16 16:44:06', '2025-03-16 05:14:41', 0, 0, NULL, 0.00, 0.00, 120.00),
(8, 5, 2, 54, 55, 7, 100.00, '', 'completed', 100, '2025-03-16 16:37:07', '2025-03-16 16:44:06', '2025-03-16 16:37:47', 0, 0, NULL, 0.00, 0.00, 80.00),
(9, 5, 2, 54, 55, 7, 100.00, '', 'in_progress', 0, '2025-03-16 16:53:49', '2025-03-16 16:54:12', NULL, 0, 0, NULL, 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_messages`
--

CREATE TABLE `order_messages` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `order_messages`
--

INSERT INTO `order_messages` (`id`, `order_id`, `user_id`, `message`, `created_at`) VALUES
(1, 2, 7, 'test', '2025-03-15 07:16:18'),
(2, 2, 5, 'naber', '2025-03-15 07:18:48'),
(3, 2, 7, 'iyi senden', '2025-03-15 07:21:32'),
(4, 2, 5, 'eh', '2025-03-15 07:22:06'),
(5, 2, 7, 'dasdsa', '2025-03-15 07:22:15'),
(6, 2, 7, 'dsadqdwasd', '2025-03-15 07:23:47'),
(7, 2, 7, '213213213', '2025-03-15 07:23:53'),
(8, 2, 5, 'deneme', '2025-03-15 07:27:07'),
(9, 2, 7, 'deneme', '2025-03-15 07:27:40'),
(10, 2, 7, 'dasdsadas', '2025-03-15 07:28:47'),
(11, 2, 7, 'sadasdsad', '2025-03-15 07:30:24'),
(12, 1, 5, 'test', '2025-03-15 07:34:34'),
(13, 1, 7, 'test', '2025-03-15 07:34:38'),
(14, 3, 7, 'test', '2025-03-15 18:26:55'),
(15, 3, 7, 'testtt', '2025-03-15 18:27:01'),
(16, 3, 5, 'merhaba', '2025-03-15 18:28:09'),
(17, 3, 7, 'naber', '2025-03-15 18:32:55'),
(18, 3, 5, 'iyi', '2025-03-15 18:33:07'),
(19, 3, 7, 'test', '2025-03-15 18:36:04'),
(20, 4, 7, 'sss', '2025-03-15 18:38:11'),
(21, 3, 5, 'dsadsad', '2025-03-16 03:43:05'),
(22, 3, 5, 'sad', '2025-03-16 03:45:47'),
(23, 4, 5, 's', '2025-03-16 04:02:20'),
(24, 1, 5, 's', '2025-03-16 04:02:29'),
(25, 3, 7, 'test', '2025-03-16 05:28:30'),
(26, 3, 7, 'asa', '2025-03-16 05:43:14');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('paypal','credit_card','paytr','crypto','bank_transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `order_id`, `amount`, `payment_method`, `token`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, NULL, 100.00, '', NULL, 'completed', '2025-03-15 04:36:16', '2025-03-15 05:26:04'),
(2, 5, NULL, 100.00, '', NULL, 'completed', '2025-03-15 04:36:19', '2025-03-15 05:28:07'),
(3, 5, NULL, 100.00, '', NULL, 'pending', '2025-03-15 06:15:39', '2025-03-15 06:15:39'),
(4, 5, NULL, 100.00, '', NULL, 'pending', '2025-03-15 16:35:15', '2025-03-15 16:35:15'),
(5, 5, NULL, 100.00, '', NULL, 'pending', '2025-03-15 16:35:31', '2025-03-15 16:35:31'),
(6, 5, NULL, 100.00, 'credit_card', NULL, 'completed', '2025-03-15 16:35:33', '2025-03-15 16:35:47'),
(7, 5, NULL, 100.00, '', NULL, 'completed', '2025-03-15 16:35:55', '2025-03-15 16:38:16'),
(8, 5, NULL, 100.00, '', NULL, 'pending', '2025-03-15 16:38:53', '2025-03-15 16:38:53'),
(9, 5, NULL, 100.00, 'credit_card', NULL, '', '2025-03-15 16:38:55', '2025-03-15 16:52:16'),
(10, 5, NULL, 100.00, '', NULL, 'pending', '2025-03-15 19:15:24', '2025-03-15 19:15:24'),
(11, 5, NULL, 100.00, '', NULL, 'pending', '2025-03-15 19:17:08', '2025-03-15 19:17:08'),
(12, 5, NULL, 100.00, '', NULL, 'pending', '2025-03-15 19:17:17', '2025-03-15 19:17:17'),
(13, 5, NULL, 320.00, '', NULL, 'pending', '2025-03-15 19:18:08', '2025-03-15 19:18:08'),
(14, 5, NULL, 100.00, '', NULL, 'pending', '2025-03-15 19:23:53', '2025-03-15 19:23:53'),
(15, 5, NULL, 100.00, '', NULL, 'completed', '2025-03-15 19:23:54', '2025-03-15 19:27:17'),
(16, 5, NULL, 100.00, '', NULL, '', '2025-03-15 19:30:50', '2025-03-15 19:30:50'),
(17, 5, NULL, 100.00, '', NULL, '', '2025-03-15 19:31:10', '2025-03-15 19:31:10'),
(18, 5, NULL, 500.00, '', NULL, '', '2025-03-15 19:34:01', '2025-03-15 19:34:01'),
(19, 5, NULL, 100.00, '', NULL, '', '2025-03-15 19:36:39', '2025-03-15 19:36:39'),
(20, 5, NULL, 100.00, '', NULL, '', '2025-03-15 19:38:58', '2025-03-15 19:38:58'),
(21, 5, NULL, 1500.00, 'bank_transfer', NULL, 'failed', '2025-03-15 19:42:35', '2025-03-15 19:46:40'),
(22, 5, NULL, 500.00, 'bank_transfer', NULL, 'failed', '2025-03-15 19:43:07', '2025-03-15 19:46:24');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `settings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `min_amount` decimal(10,2) DEFAULT '10.00',
  `max_amount` decimal(10,2) DEFAULT '10000.00',
  `fee_percentage` decimal(5,2) DEFAULT '0.00',
  `fee_fixed` decimal(10,2) DEFAULT '0.00',
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `description`, `instructions`, `icon`, `settings`, `min_amount`, `max_amount`, `fee_percentage`, `fee_fixed`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Banka Havalesi', 'Banka hesaplarımıza havale yaparak bakiye yükleyebilirsiniz.', 'Aşağıdaki banka hesaplarından birine havale yapın ve ardından formu doldurun.', 'fas fa-university', NULL, 10.00, 10000.00, 0.00, 0.00, 1, 'active', '2025-03-15 16:40:25', NULL),
(2, 'Kredi Kartı', 'Kredi kartı ile güvenli ödeme yapabilirsiniz.', NULL, 'fas fa-credit-card', NULL, 10.00, 10000.00, 0.00, 0.00, 2, 'active', '2025-03-15 16:40:25', NULL),
(3, 'Papara', 'Papara hesabınız ile hızlı ödeme yapabilirsiniz.', NULL, 'fas fa-wallet', NULL, 10.00, 10000.00, 0.00, 0.00, 3, 'active', '2025-03-15 16:40:25', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ranks`
--

CREATE TABLE `ranks` (
  `id` int NOT NULL,
  `game_id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ranks`
--

INSERT INTO `ranks` (`id`, `game_id`, `name`, `value`, `image`, `price`, `created_at`, `updated_at`) VALUES
(54, 2, 'Demir 3', 1, 'uploads/ranks/rank_67d4d66d8cf89.jpg', 0.00, '2025-03-15 01:22:53', '2025-03-15 01:22:53'),
(55, 2, 'Demir 2', 2, 'uploads/ranks/rank_67d4d678b9e68.jpg', 0.00, '2025-03-15 01:23:04', '2025-03-15 01:23:04'),
(56, 2, 'Demir 3', 3, 'uploads/ranks/rank_67d4d682f0fee.jpg', 0.00, '2025-03-15 01:23:14', '2025-03-15 01:23:14'),
(57, 1, 'Demir 3', 3, 'uploads/ranks/rank_67d4d695a8582.png', 0.00, '2025-03-15 01:23:33', '2025-03-15 01:23:33'),
(58, 1, 'Demir 2', 2, 'uploads/ranks/rank_67d4d6a012f57.png', 0.00, '2025-03-15 01:23:44', '2025-03-15 01:23:44');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `referrals`
--

CREATE TABLE `referrals` (
  `id` int NOT NULL,
  `referrer_id` int NOT NULL,
  `referred_id` int NOT NULL,
  `earnings` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'site_title', '', '2025-03-14 23:13:09', '2025-03-16 18:18:22'),
(2, 'site_description', '', '2025-03-14 23:13:09', '2025-03-16 18:18:22'),
(3, 'commission_rate', '20', '2025-03-14 23:13:09', '2025-03-14 23:13:09'),
(4, 'min_withdrawal', '60', '2025-03-14 23:13:09', '2025-03-16 18:18:22'),
(5, 'maintenance_mode', '0', '2025-03-14 23:13:09', '2025-03-14 23:13:09'),
(8, 'contact_email', 'test@test.com', '2025-03-14 23:59:53', '2025-03-14 23:59:53'),
(21, 'contact_phone', '', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(22, 'site_address', '', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(23, 'facebook_url', '', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(24, 'twitter_url', '', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(25, 'instagram_url', '', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(26, 'discord_invite', '', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(29, 'currency_symbol', '₺', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(31, 'registration_enabled', '0', '2025-03-15 05:31:31', '2025-03-16 18:16:44'),
(32, 'auto_approve_boosters', '0', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(33, 'site_favicon', 'uploads/favicon.png', '2025-03-15 05:31:31', '2025-03-15 05:31:31'),
(36, 'site_email', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(37, 'site_phone', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(39, 'site_discord', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(50, 'require_id_verification', '0', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(51, 'meta_title', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(52, 'meta_description', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(53, 'meta_keywords', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(54, 'og_title', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(55, 'og_description', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(56, 'smtp_host', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(57, 'smtp_port', '587', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(58, 'smtp_username', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(59, 'smtp_password', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(60, 'smtp_from_email', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(61, 'smtp_from_name', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(62, 'discord_webhook_url', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(63, 'discord_new_order', '0', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(64, 'discord_completed_order', '0', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(65, 'discord_new_payment', '0', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(66, 'sms_api_key', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(67, 'sms_sender', '', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(68, 'sms_notifications', '0', '2025-03-16 18:16:44', '2025-03-16 18:16:44'),
(88, 'smtp_user', '', '2025-03-16 18:18:03', '2025-03-16 18:18:03'),
(89, 'smtp_pass', '', '2025-03-16 18:18:03', '2025-03-16 18:18:03'),
(90, 'discord_webhook', '', '2025-03-16 18:18:03', '2025-03-16 18:18:03');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `support_messages`
--

CREATE TABLE `support_messages` (
  `id` int NOT NULL,
  `ticket_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `support_messages`
--

INSERT INTO `support_messages` (`id`, `ticket_id`, `user_id`, `message`, `created_at`) VALUES
(1, 1, 5, 'test', '2025-03-15 04:41:28'),
(2, 1, 22, 'test', '2025-03-15 04:46:35'),
(3, 1, 22, 'Destek talebi kapatıldı.', '2025-03-15 05:00:06'),
(4, 2, 5, 'test11', '2025-03-16 01:09:19'),
(5, 3, 5, 'teste1111', '2025-03-16 01:10:56'),
(6, 4, 5, '1', '2025-03-16 01:11:33'),
(7, 4, 22, 'Destek talebi kapatıldı.', '2025-03-16 01:12:06'),
(8, 3, 22, 'Destek talebi kapatıldı.', '2025-03-16 01:12:10'),
(9, 2, 22, 'Destek talebi kapatıldı.', '2025-03-16 01:12:14'),
(10, 5, 5, 's', '2025-03-16 01:20:56'),
(11, 8, 5, 'a', '2025-03-16 01:29:09'),
(12, 8, 22, 'Destek talebi kapatıldı.', '2025-03-16 01:30:31'),
(13, 7, 22, 'Destek talebi kapatıldı.', '2025-03-16 01:30:34'),
(14, 6, 22, 'Destek talebi kapatıldı.', '2025-03-16 01:30:38'),
(15, 5, 22, 'Destek talebi kapatıldı.', '2025-03-16 01:30:40'),
(16, 9, 5, 'sadsad', '2025-03-16 01:31:07'),
(17, 9, 22, 'dsad', '2025-03-16 01:31:13'),
(18, 9, 22, 'Destek talebi kapatıldı.', '2025-03-16 01:31:22'),
(19, 10, 7, 'test', '2025-03-16 02:40:34'),
(20, 10, 7, '22', '2025-03-16 02:42:11'),
(21, 10, 7, '22', '2025-03-16 02:42:13'),
(22, 11, 7, 'a', '2025-03-16 05:15:18');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `booster_id` int DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `booster_id`, `subject`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, NULL, 'teste', '', 'closed', '2025-03-15 04:41:28', '2025-03-15 05:00:06'),
(2, 5, NULL, 'test11', '', 'closed', '2025-03-16 01:09:19', '2025-03-16 01:12:13'),
(3, 5, NULL, 'teste1111', '', 'closed', '2025-03-16 01:10:56', '2025-03-16 01:12:10'),
(4, 5, NULL, '1', '', 'closed', '2025-03-16 01:11:33', '2025-03-16 01:12:06'),
(5, 5, NULL, 's', '', 'closed', '2025-03-16 01:20:56', '2025-03-16 01:30:40'),
(6, 5, NULL, 'sad', 'asdsa', 'closed', '2025-03-16 01:23:29', '2025-03-16 01:30:38'),
(7, 5, NULL, 'uuuuuuuuu', 'uuuuuuuuuuuuuuuuuuuuuu', 'closed', '2025-03-16 01:23:35', '2025-03-16 01:30:34'),
(8, 5, NULL, 'a1a1a1', 'a1a1a1', 'closed', '2025-03-16 01:28:25', '2025-03-16 01:30:31'),
(9, 5, NULL, 'z1z1', 'z1z1z1', 'closed', '2025-03-16 01:31:02', '2025-03-16 01:31:22'),
(10, 7, NULL, 'test', '', 'open', '2025-03-16 02:40:34', '2025-03-16 02:42:13'),
(11, 7, NULL, 'a', '', 'open', '2025-03-16 05:15:18', '2025-03-16 05:15:18');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','booster','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `balance`, `status`, `created_at`, `updated_at`) VALUES
(4, 'admin', 'admin@eloboost.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0.00, 'active', '2025-03-14 23:36:34', '2025-03-14 23:36:34'),
(5, 'akuvvet', 'kuvvetweb@gmail.com', '$2y$10$69u2bXedQUGCYvVur6G4UOsuCd12IkxRWizhGSv/yxPrBfsyJfAiW', 'user', 610.00, 'active', '2025-03-14 23:47:23', '2025-03-16 16:54:02'),
(7, 'boost', '131a1boost@boosss1t.com', '$2y$10$69u2bXedQUGCYvVur6G4UOsuCd12IkxRWizhGSv/yxPrBfsyJfAiW', 'booster', 0.00, 'active', '2025-03-14 23:51:21', '2025-03-16 05:12:29'),
(22, 'yonetici', 'yonetici@yonetici.com', '$2y$10$69u2bXedQUGCYvVur6G4UOsuCd12IkxRWizhGSv/yxPrBfsyJfAiW', 'admin', 0.00, 'active', '2025-03-14 23:52:40', '2025-03-14 23:52:40'),
(29, '211boost', '11sadasdtweb@gmail.com', '$2y$10$1IT8sHBti2g2inKNwNfrPuUU9g9AOW6M8qnGUk7P1ppotR4Rs5pc6', 'booster', 10.00, 'active', '2025-03-16 03:30:43', '2025-03-16 04:16:27'),
(30, 'boost2', 'boost2@gmail.com', '$2y$10$rx8nnTN9T9Hr7WaY5PwjPeAMLv/isGFvLm1l16zYf5mqlJ2zsKWw.', 'booster', 0.00, 'active', '2025-03-16 04:42:34', '2025-03-16 04:42:59'),
(31, 'boost311', 'boost3@gmail.com', '$2y$10$XZwQgcTpYYSswY1sPOtoC./Y.jnIdNSkBO4JRYlGPJFuIaKAmp/G6', 'booster', 0.00, 'active', '2025-03-16 04:43:43', '2025-03-16 05:02:46'),
(32, 'boost33131', 'boost33131@gmail.com', '$2y$10$/xis5Gp7Ctp9oJNp/DKBiOfOErUacN6cSu28cBnDg1xPtYqfGqhS.', 'booster', 0.00, 'active', '2025-03-16 05:03:41', '2025-03-16 05:03:41');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `balances`
--
ALTER TABLE `balances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `boosters`
--
ALTER TABLE `boosters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `booster_games`
--
ALTER TABLE `booster_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_booster_game` (`booster_id`,`game_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Tablo için indeksler `booster_payments`
--
ALTER TABLE `booster_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booster_id` (`booster_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `booster_ratings`
--
ALTER TABLE `booster_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booster_id` (`booster_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `booster_stats`
--
ALTER TABLE `booster_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `month_year_booster` (`month`,`year`,`booster_id`),
  ADD KEY `booster_id` (`booster_id`);

--
-- Tablo için indeksler `boost_prices`
--
ALTER TABLE `boost_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_boost_price` (`game_id`,`current_rank_id`,`target_rank_id`),
  ADD KEY `current_rank_id` (`current_rank_id`),
  ADD KEY `target_rank_id` (`target_rank_id`);

--
-- Tablo için indeksler `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `current_rank_id` (`current_rank_id`),
  ADD KEY `target_rank_id` (`target_rank_id`),
  ADD KEY `booster_id` (`booster_id`);

--
-- Tablo için indeksler `order_messages`
--
ALTER TABLE `order_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `ranks`
--
ALTER TABLE `ranks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- Tablo için indeksler `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referrer_id` (`referrer_id`),
  ADD KEY `referred_id` (`referred_id`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Tablo için indeksler `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booster_id` (`booster_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `balances`
--
ALTER TABLE `balances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `boosters`
--
ALTER TABLE `boosters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Tablo için AUTO_INCREMENT değeri `booster_games`
--
ALTER TABLE `booster_games`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `booster_payments`
--
ALTER TABLE `booster_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `booster_ratings`
--
ALTER TABLE `booster_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `booster_stats`
--
ALTER TABLE `booster_stats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `boost_prices`
--
ALTER TABLE `boost_prices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- Tablo için AUTO_INCREMENT değeri `games`
--
ALTER TABLE `games`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `order_messages`
--
ALTER TABLE `order_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Tablo için AUTO_INCREMENT değeri `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `ranks`
--
ALTER TABLE `ranks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Tablo için AUTO_INCREMENT değeri `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- Tablo için AUTO_INCREMENT değeri `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Tablo için AUTO_INCREMENT değeri `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `boosters`
--
ALTER TABLE `boosters`
  ADD CONSTRAINT `boosters_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `booster_games`
--
ALTER TABLE `booster_games`
  ADD CONSTRAINT `booster_games_ibfk_1` FOREIGN KEY (`booster_id`) REFERENCES `boosters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booster_games_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `booster_payments`
--
ALTER TABLE `booster_payments`
  ADD CONSTRAINT `booster_payments_ibfk_1` FOREIGN KEY (`booster_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booster_payments_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `booster_stats`
--
ALTER TABLE `booster_stats`
  ADD CONSTRAINT `booster_stats_ibfk_1` FOREIGN KEY (`booster_id`) REFERENCES `boosters` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `boost_prices`
--
ALTER TABLE `boost_prices`
  ADD CONSTRAINT `boost_prices_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `boost_prices_ibfk_2` FOREIGN KEY (`current_rank_id`) REFERENCES `ranks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `boost_prices_ibfk_3` FOREIGN KEY (`target_rank_id`) REFERENCES `ranks` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`current_rank_id`) REFERENCES `ranks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`target_rank_id`) REFERENCES `ranks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_5` FOREIGN KEY (`booster_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_6` FOREIGN KEY (`booster_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `order_messages`
--
ALTER TABLE `order_messages`
  ADD CONSTRAINT `order_messages_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `ranks`
--
ALTER TABLE `ranks`
  ADD CONSTRAINT `ranks_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `support_messages`
--
ALTER TABLE `support_messages`
  ADD CONSTRAINT `support_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `support_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `support_tickets_ibfk_2` FOREIGN KEY (`booster_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
