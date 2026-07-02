<?php
require_once '../config/database.php';

// 1. Tính tổng doanh thu (Chỉ tính các đơn hàng đã giao thành công 'completed')
$revenue_stmt = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'completed'");
$total_revenue = floatval($revenue_stmt->fetchColumn());

// 2. Tổng số đơn hàng
$orders_count_stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = intval($orders_count_stmt->fetchColumn());

// 3. Tổng số lượng sản phẩm trong danh mục
$products_count_stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = intval($products_count_stmt->fetchColumn());

// 4. Số lượng sản phẩm đã hết hàng trong kho (stock = 0)
$out_of_stock_stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 0");
$out_of_stock = intval($out_of_stock_stmt->fetchColumn());

// 5. Lấy danh sách 5 đơn hàng mới nhất
$recent_orders_stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
$recent_orders = $recent_orders_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tổng Quan Hệ Thống</title>
    <!-- Nhúng CSS trang quản trị -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    
    <!-- Nhúng Sidebar Menu -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Phần nội dung chính (bên phải Sidebar) -->
    <div class="admin-content">
        <div class="admin-header-row">
            <h1>Tổng Quan Hệ Thống</h1>
            <span style="color: var(--text-muted); font-size: 0.95rem;">
                Thời gian hệ thống: <?php echo date('d/m/Y H:i'); ?>
            </span>
        </div>
        
        <!-- Các hộp chỉ số thống kê nhanh (Dashboard Widgets) -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-title">Doanh Thu Đã Giao</span>
                <span class="stat-value"><?php echo number_format($total_revenue, 0, ',', '.'); ?> đ</span>
                <span class="stat-desc">Tổng giá trị đơn hàng "Đã giao"</span>
            </div>
            
            <div class="stat-card">
                <span class="stat-title">Đơn Đặt Hàng</span>
                <span class="stat-value"><?php echo $total_orders; ?> đơn</span>
                <span class="stat-desc">Tổng số đơn hàng trong hệ thống</span>
            </div>
            
            <div class="stat-card">
                <span class="stat-title">Sản Phẩm Đang Bán</span>
                <span class="stat-value"><?php echo $total_products; ?> chiếc</span>
                <span class="stat-desc">Số lượng mẫu quần áo hiện có</span>
            </div>
            
            <div class="stat-card" style="<?php echo $out_of_stock > 0 ? 'border-color: var(--accent-color);' : ''; ?>">
                <span class="stat-title">Mẫu Đã Hết Hàng</span>
                <span class="stat-value" style="<?php echo $out_of_stock > 0 ? 'color: var(--accent-color);' : ''; ?>">
                    <?php echo $out_of_stock; ?>
                </span>
                <span class="stat-desc">Sản phẩm cần nhập thêm kho</span>
            </div>
        </div>
        
        <!-- Bảng danh sách đơn hàng mới cập nhật -->
        <div class="admin-card">
            <div class="admin-card-header">Danh sách 5 đơn hàng mới nhất</div>
            
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Ngày đặt hàng</th>
                            <th>Tổng thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_orders) > 0): ?>
                            <?php foreach ($recent_orders as $ord): ?>
                                <tr>
                                    <td>#<?php echo $ord['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ord['customer_phone']); ?></td>
                                    <td><?php echo date('H:i - d/m/Y', strtotime($ord['created_at'])); ?></td>
                                    <td><strong style="color: var(--accent-color);"><?php echo number_format($ord['total_price'], 0, ',', '.'); ?> đ</strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $ord['status']; ?>">
                                            <?php 
                                            if ($ord['status'] === 'pending') echo 'Chờ xử lý';
                                            elseif ($ord['status'] === 'shipping') echo 'Đang giao';
                                            elseif ($ord['status'] === 'completed') echo 'Đã giao';
                                            elseif ($ord['status'] === 'cancelled') echo 'Đã hủy';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orders.php?id=<?php echo $ord['id']; ?>" class="btn btn-secondary btn-sm" style="border: 1px solid var(--border-color);">
                                            Cập nhật đơn
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    Chưa có đơn hàng nào được ghi nhận.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
