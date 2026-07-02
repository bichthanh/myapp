<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); // Bật bộ đệm đầu ra (Output Buffering) để tránh lỗi "headers already sent" khi redirect
require_once __DIR__ . '/../config/database.php';

// Tính tổng số lượng sản phẩm trong giỏ hàng để hiển thị trên badge
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hệ thống quản lý bán hàng quần áo thời trang nam nữ cao cấp Fashion.">
    <title><?php echo isset($page_title) ? $page_title : 'Fashion - Cửa Hàng Thời Trang'; ?></title>
    <!-- Nhúng CSS chính -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Thanh điều hướng Header -->
    <header>
        <div class="container navbar">
            <a href="index.php" class="nav-logo">FASHION</a>
            
            <ul class="nav-links">
                <li><a href="index.php">Trang chủ / Cửa hàng</a></li>
                <li><a href="admin/index.php">Trang quản trị (Admin)</a></li>
                <li>
                    <a href="cart.php" class="nav-cart">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-cart"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span>Giỏ hàng</span>
                        <span class="cart-count" id="cart-badge"><?php echo $cart_count; ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </header>
