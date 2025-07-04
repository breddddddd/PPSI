-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 04 Jul 2025 pada 15.19
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ppsi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `custom_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`, `custom_image`) VALUES
(72, 3, 0, 1, '2025-07-02 06:54:42', 'uploads/custom_1751439282.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `custom_uploads`
--

CREATE TABLE `custom_uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `custom_uploads`
--

INSERT INTO `custom_uploads` (`id`, `user_id`, `product_id`, `image_path`, `created_at`) VALUES
(1, 10, 2, 'uploads/1747714936_6.png', '2025-05-20 04:22:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `receiver_name` varchar(255) NOT NULL,
  `shipping_address` text NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `payment_proof_image` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','diproses','dikirim','selesai') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `receiver_name`, `shipping_address`, `phone_number`, `payment_proof_image`, `payment_method`, `status`, `order_date`) VALUES
(30, 3, 20000.00, 'Marcellinus Ryan Danuarta', 'Jl.haji Samiin Gang Haji Jayadih No.58 Rt08/05\r\nNo.58', '11', 'proof_68649b4f3bc8f3.63017368.jpg', 'QRIS', 'selesai', '2025-07-02 02:37:03'),
(32, 3, 15000.00, 'Marcellinus Ryan Danuarta', 'Jl.haji Samiin Gang Haji Jayadih No.58 Rt08/05\r\nNo.58', '11', 'proof_6864a1d5b89599.75224053.jpg', 'QRIS', 'pending', '2025-07-02 03:04:53'),
(33, 3, 15000.00, 'Marcellinus Ryan Danuarta', 'Jl.haji Samiin Gang Haji Jayadih No.58 Rt08/05\r\nNo.58', '11', 'proof_6864a5ae800662.98705801.jpg', 'QRIS', 'pending', '2025-07-02 03:21:18'),
(34, 3, 15000.00, 'Marcellinus Ryan Danuarta', 'Jl.haji Samiin Gang Haji Jayadih No.58 Rt08/05\r\nNo.58', '11', 'proof_6864d798f314e0.42660116.jpg', 'QRIS', 'pending', '2025-07-02 06:54:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `custom_image` varchar(255) DEFAULT NULL,
  `item_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `item_name`, `quantity`, `custom_image`, `item_price`) VALUES
(19, 30, 3, 'Rose Choker', 1, '', 20000.00),
(21, 32, NULL, 'Custom Design', 1, 'uploads/custom_1751425457.png', 15000.00),
(22, 33, NULL, 'Custom Design', 1, 'uploads/custom_1751426365.png', 15000.00),
(23, 34, NULL, 'Custom Design', 1, 'uploads/custom_1751439236.png', 15000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL,
  `variant` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `description`, `price`, `image`, `seller_id`, `stock`, `created_at`, `status`, `variant`) VALUES
(1, 'Flower Rings', 'cincin', '0', 15000.00, 'assets/assets/image/katalog1.png\r\n', 2, 10, '2025-05-14 01:40:04', 'Hot', ''),
(2, 'Y2K Sunglasses', NULL, NULL, 25000.00, 'assets/assets/image/katalog2.png', 2, NULL, '2025-05-14 01:40:04', 'Normal', NULL),
(3, 'Rose Choker', NULL, NULL, 20000.00, 'assets/assets/image/katalog3.png', 2, NULL, '2025-05-14 01:40:04', 'New Arrival', NULL),
(4, 'Hair Clips', NULL, NULL, 15000.00, 'assets/assets/image/katalog4.png', 2, NULL, '2025-05-14 01:40:04', 'Normal', NULL),
(5, 'Rose Braclet', NULL, NULL, 35000.00, 'assets/assets/image/katalog5.png', 2, NULL, '2025-05-14 01:40:04', 'Normal', NULL),
(6, 'Eye Rings', NULL, NULL, 25000.00, 'assets/assets/image/katalog6.png', 2, NULL, '2025-05-14 01:40:04', 'Normal', NULL),
(7, 'Pasteria Phone Strap', NULL, NULL, 30000.00, 'assets/assets/image/katalog7.png', 2, NULL, '2025-05-14 01:40:04', 'Normal', NULL),
(8, 'Bow Hair Clips', NULL, NULL, 30000.00, 'assets/assets/image/katalog8.png', 2, 50, '2025-05-14 01:40:04', 'Normal', NULL),
(9, 'Bow Scrunchie', NULL, NULL, 20000.00, 'assets/assets/image/katalog9.png', 2, NULL, '2025-05-14 01:40:04', 'Normal', NULL),
(10, 'Puffy Headband', NULL, NULL, 20000.00, 'assets/assets/image/katalog10.png', 2, NULL, '2025-05-14 01:40:04', 'Normal', NULL),
(11, 'Orchid Hair Claw', NULL, NULL, 35000.00, 'assets/assets/image/Orchidhairclaw.jpg', 2, NULL, '2025-05-14 02:59:22', 'Hot', NULL),
(52, '1111', 'Gelang', '1', 1.00, '0', 2, 2, '2025-07-02 02:24:20', 'Normal', '<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string ');

-- --------------------------------------------------------

--
-- Struktur dari tabel `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_name` varchar(100) DEFAULT NULL,
  `variant_image` varchar(255) DEFAULT NULL,
  `variant_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `variant_name`, `variant_image`, `variant_price`, `stock`) VALUES
(1, 11, 'BlossomTale', NULL, 35000.00, 0),
(2, 11, 'CelestialKiss', NULL, 35000.00, 0),
(3, 11, 'CozyFlutter', NULL, 35000.00, 0),
(4, 11, 'DaintyBloom', NULL, 35000.00, 0),
(5, 11, 'DaydreamLoop', NULL, 35000.00, 0),
(6, 11, 'DewdropHold', NULL, 35000.00, 0),
(7, 11, 'FloralDrift', NULL, 35000.00, 0),
(8, 11, 'GardenLullaby', NULL, 35000.00, 0),
(9, 11, 'GoldenHour', NULL, 35000.00, 0),
(10, 11, 'GraceLock', NULL, 35000.00, 0),
(11, 11, 'LucidDream', NULL, 35000.00, 0),
(12, 11, 'MoonlitCharm', NULL, 35000.00, 0),
(13, 11, 'MorningHaze', NULL, 35000.00, 0),
(14, 11, 'PetalWhisper', NULL, 35000.00, 0),
(15, 11, 'PureDelight', NULL, 35000.00, 0),
(16, 11, 'QuietBloom', NULL, 35000.00, 0),
(17, 11, 'SereneTouch', NULL, 35000.00, 0),
(18, 11, 'SoftEcho', NULL, 35000.00, 0),
(19, 11, 'SundayMuse', NULL, 35000.00, 0),
(20, 11, 'SweetSerenade', NULL, 35000.00, 0),
(21, 11, 'VelvetSecret', NULL, 35000.00, 0),
(22, 11, 'WhimsyLoop', NULL, 35000.00, 0),
(23, 11, 'WildMeadow', NULL, 35000.00, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sellerproducts`
--

CREATE TABLE `sellerproducts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT 'Umum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_google_login` tinyint(1) NOT NULL DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo_url` text DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('Pria','Wanita') DEFAULT NULL,
  `nomor_hp` varchar(20) DEFAULT NULL,
  `user_type` enum('penjual','pembeli') NOT NULL DEFAULT 'pembeli'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `is_google_login`, `password`, `created_at`, `photo_url`, `tanggal_lahir`, `jenis_kelamin`, `nomor_hp`, `user_type`) VALUES
(1, 'admin', 'admin@gmail.com', 0, '$2y$10$i5kCnThAx9TcREFHc3nYU.i39B4sj3ehbsNifUb7WhpbPsLuE3PSi', '2025-05-15 02:50:14', '', NULL, NULL, NULL, 'pembeli'),
(2, 'penjual', 'penjual@gmail.com', 0, '$2y$10$F6Cx6wwep/sqcG4T5BAX/uMehFNxG2TKvCyMyzvCaX.x4It/A85rC', '2025-05-16 04:00:17', 'https://www.gravatar.com/avatar/9b3d5246e6b64cabd12e7bc50f4c214d?s=100&d=identicon', NULL, NULL, NULL, 'penjual'),
(3, 'Marcell', 'marcellinusryan12@gmail.com', 0, '$2y$10$bKhMlB5.wmJJwqTmr0b3Delm6QUvIzN7YoGJDvtym4GVG2eQcRAK2', '2025-05-07 17:06:11', 'https://lh3.googleusercontent.com/a/ACg8ocJNPNrRb-gc-sf8vqojaZakTVFWAXe4dUS1SiVtxSCtEQmjAABB=s96-c', '2010-10-15', 'Pria', '081319201912', 'pembeli'),
(4, 'Una', 'ivanaaristiyanti23@gmail.com', 0, '', '2025-05-08 06:58:56', 'https://lh3.googleusercontent.com/a/ACg8ocJjkDMPobRMkOPjNq9S5V9NfhjllQv_wIdZ_tBucfBoE0Wmrcxg=s96-c', '2012-08-15', 'Wanita', NULL, 'pembeli'),
(5, 'Bernadi', 'bernad@gmail.com', 0, '$2y$10$ZSq9jAHBAweHnNYdSGPRa.OeCTWFohxkV6Z2prN6m3F1yuw6cxVbS', '2025-05-08 07:31:56', '', '2010-09-13', 'Wanita', '628810022', 'pembeli'),
(10, 'Bernadi sayudha', 'bernadisayudha@gmail.com', 0, '$2y$10$L2nd.RQ13dnQcNTXeOVqwOW6OVQ98qhQbRsUD5IuhST9lRi6gQqgG', '2025-05-20 04:03:51', 'https://www.gravatar.com/avatar/d0f0110c14785e1467c2abcba04779fd?s=100&d=identicon', NULL, NULL, NULL, 'pembeli');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `custom_uploads`
--
ALTER TABLE `custom_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `sellerproducts`
--
ALTER TABLE `sellerproducts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT untuk tabel `custom_uploads`
--
ALTER TABLE `custom_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `sellerproducts`
--
ALTER TABLE `sellerproducts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `custom_uploads`
--
ALTER TABLE `custom_uploads`
  ADD CONSTRAINT `custom_uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `custom_uploads_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_buyer` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sellerproducts`
--
ALTER TABLE `sellerproducts`
  ADD CONSTRAINT `sellerproducts_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
