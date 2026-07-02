-- Thiết lập mã hóa ký tự UTF-8
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Khởi tạo Database
CREATE DATABASE IF NOT EXISTS `clothing_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `clothing_db`;

-- 1. Bảng Danh mục sản phẩm (Categories)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng Sản phẩm (Products)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `sizes` VARCHAR(100) NOT NULL DEFAULT 'S,M,L,XL', -- Lưu danh sách size ngăn cách bằng dấu phẩy
  `image_url` VARCHAR(255) DEFAULT 'default.jpg',
  `stock` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng Đơn hàng (Orders)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_email` VARCHAR(255),
  `customer_phone` VARCHAR(50) NOT NULL,
  `customer_address` TEXT NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending (chờ xử lý), shipping (đang giao), completed (đã giao), cancelled (đã hủy)
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng Chi tiết đơn hàng (Order Items)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `size` VARCHAR(10) NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng Người dùng quản trị (Users/Admin)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- Sẽ lưu hash mật khẩu
  `fullname` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- CHÈN DỮ LIỆU MẪU BAN ĐẦU (SEED DATA)
-- --------------------------------------------------------

-- Chèn Danh mục
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Áo Thun & Áo Polo', 'Bộ sưu tập áo thun nam nữ năng động, chất liệu cotton thoáng mát.'),
(2, 'Áo Khoác thời trang', 'Áo khoác bomber, dù, denim cực chất cho mọi thời tiết.'),
(3, 'Quần Jeans & Kaki', 'Quần dáng đứng, năng động, ôm vừa vặn.'),
(4, 'Váy & Đầm Nữ', 'Đầm dự tiệc, váy dạo phố phong cách nhẹ nhàng, quý phái.');

-- Chèn Sản phẩm quần áo mẫu
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `sizes`, `image_url`, `stock`) VALUES
(1, 1, 'Áo Thun Polo Premium', 'Áo thun Polo chất liệu cá sấu gai mịn, co giãn 4 chiều tốt. Thấm hút mồ hôi hiệu quả, kiểu dáng lịch lãm thời thượng.', 250000.00, 'S,M,L,XL', 'polo_shirt.jpg', 50),
(2, 1, 'Áo Thun Unisex Oversize', 'Áo thun tay lỡ dáng rộng Hàn Quốc. 100% cotton tự nhiên, hình in sắc nét không bong tróc.', 180000.00, 'M,L,XL', 'oversize_tee.jpg', 80),
(3, 2, 'Áo Khoác Bomber Classic', 'Áo khoác bomber 2 lớp chống gió bụi cực tốt, lót dù bên trong mát mẻ. Khóa kéo kim loại bền bỉ.', 450000.00, 'M,L,XL', 'bomber_jacket.jpg', 30),
(4, 3, 'Quần Jeans Slimfit Nam', 'Chất denim cotton dày dặn co giãn nhẹ, không phai màu khi giặt. Form slimfit tôn dáng trẻ trung.', 390000.00, '29,30,31,32', 'slimfit_jeans.jpg', 40),
(5, 4, 'Đầm Hoa Nhí Vintage', 'Thiết kế cổ chữ V thanh lịch, họa tiết hoa nhí nhẹ nhàng nữ tính. Chất tơ hàn mềm mại lót lụa kín đáo.', 320000.00, 'S,M,L', 'vintage_dress.jpg', 25),
(6, 1, 'Áo Sơ Mi Nam Công Sở', 'Chất liệu vải sợi tre tự nhiên chống nhăn tốt, thấm hút mồ hôi. Dễ phối đồ với quần tây hoặc quần kaki.', 290000.00, 'S,M,L,XL', 'office_shirt.jpg', 45);

-- Chèn tài khoản Admin mặc định (tài khoản: bichthanh, mật khẩu: 123456)
-- Hash bên dưới là kết quả của password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `password`, `fullname`, `role`) VALUES
('bichthanh', '$2y$10$YHtTDQEjxSIr.UCLmj/JD.VN7UD4hMBOtJNzfdjxW3s1TmcMyaOYK', 'Bích Thành (Admin)', 'admin');
