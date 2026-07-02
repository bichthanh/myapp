<?php
require_once '../config/database.php';

$message = '';
$error = '';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$status_action = isset($_POST['update_status']) ? $_POST['update_status'] : '';

// 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0 && !empty($status_action)) {
    $new_status = trim($_POST['status']);
    
    // Đảm bảo trạng thái nằm trong danh sách cho phép
    $allowed_status = ['pending', 'shipping', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_status)) {
        $update_stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($update_stmt->execute([$new_status, $id])) {
            $message = 'Đã cập nhật trạng thái đơn hàng thành công!';
        } else {
            $error = 'Không thể cập nhật trạng thái đơn hàng.';
        }
    }
}

// 2. NẾU CÓ THAM SỐ ID -> HIỂN THỊ CHI TIẾT ĐƠN HÀNG
$order_detail = null;
$order_items = [];
if ($id > 0) {
    // Lấy thông tin đơn hàng chung
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $order_stmt->execute([$id]);
    $order_detail = $order_stmt->fetch();
    
    if ($order_detail) {
        // Lấy chi tiết các sản phẩm trong đơn hàng kèm tên sản phẩm từ bảng products
        $items_stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_url 
                                     FROM order_items oi 
                                     JOIN products p ON oi.product_id = p.id 
                                     WHERE oi.order_id = ?");
        $items_stmt->execute([$id]);
        $order_items = $items_stmt->fetchAll();
    }
}

// 3. LẤY TOÀN BỘ DANH SÁCH ĐƠN HÀNG (Hiển thị ở trang danh sách chính)
$orders_stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
$orders = $orders_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header-row">
            <h1>Quản Lý Đơn Hàng</h1>
            <?php if ($id > 0): ?>
                <a href="orders.php" class="btn btn-secondary btn-sm">Quay lại danh sách</a>
            <?php endif; ?>
        </div>
        
        <!-- Hiển thị thông báo thành công hoặc lỗi -->
        <?php if ($message !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- ==========================================================================
             CHI TIẾT ĐƠN HÀNG (Chỉ hiện khi click chọn một đơn hàng cụ thể)
             ========================================================================== -->
        <?php if ($id > 0 && $order_detail): ?>
            <div style="display: grid; grid-template-columns: 1fr 360px; gap: 30px; margin-bottom: 40px; align-items: start;">
                
                <!-- Bên trái: Bảng danh sách mặt hàng đặt mua -->
                <div class="admin-card">
                    <div class="admin-card-header">Chi tiết các mặt hàng - Đơn hàng #<?php echo $order_detail['id']; ?></div>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Kích thước</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_calculated = 0;
                                foreach ($order_items as $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total_calculated += $subtotal;
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <img src="../assets/uploads/<?php echo $item['image_url']; ?>" alt="" class="admin-prod-thumb" onerror="this.src='https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=80&q=80'">
                                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            </div>
                                        </td>
                                        <td><span class="badge" style="background-color:#e2e8f0; color: #1e293b;"><?php echo htmlspecialchars($item['size']); ?></span></td>
                                        <td><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                                        <td><?php echo $item['quantity']; ?> chiếc</td>
                                        <td><strong style="color: var(--accent-color);"><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: #f8fafc; font-weight: 700; font-size: 1.1rem;">
                                    <td colspan="4" style="text-align: right; padding-right: 20px;">TỔNG TIỀN ĐƠN HÀNG:</td>
                                    <td style="color: var(--accent-color);"><?php echo number_format($total_calculated, 0, ',', '.'); ?> đ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Bên phải: Thông tin khách hàng & Form cập nhật trạng thái -->
                <div class="admin-card">
                    <div class="admin-card-header">Thông tin khách hàng & Giao nhận</div>
                    <div class="admin-form" style="font-size: 0.95rem;">
                        <div style="margin-bottom: 20px; display: grid; gap: 10px;">
                            <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order_detail['customer_name']); ?></p>
                            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order_detail['customer_phone']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order_detail['customer_email'] ?: 'Chưa cung cấp'); ?></p>
                            <p><strong>Địa chỉ giao hàng:</strong> <br><span style="color: var(--text-muted); font-size:0.9rem;"><?php echo nl2br(htmlspecialchars($order_detail['customer_address'])); ?></span></p>
                            <p><strong>Ngày đặt hàng:</strong> <?php echo date('H:i - d/m/Y', strtotime($order_detail['created_at'])); ?></p>
                        </div>
                        
                        <!-- Form cập nhật trạng thái -->
                        <form action="orders.php?id=<?php echo $order_detail['id']; ?>" method="POST" style="border-top: 1px solid var(--border-color); padding-top: 20px;">
                            <input type="hidden" name="update_status" value="1">
                            <div class="form-group">
                                <label for="status">Cập nhật trạng thái đơn:</label>
                                <select name="status" id="status" class="form-control" style="margin-top: 6px;">
                                    <option value="pending" <?php echo $order_detail['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý (COD)</option>
                                    <option value="shipping" <?php echo $order_detail['status'] === 'shipping' ? 'selected' : ''; ?>>Đang giao hàng</option>
                                    <option value="completed" <?php echo $order_detail['status'] === 'completed' ? 'selected' : ''; ?>>Đã giao (Thành công)</option>
                                    <option value="cancelled" <?php echo $order_detail['status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy bỏ</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Cập nhật ngay</button>
                        </form>
                    </div>
                </div>
                
            </div>
        <?php endif; ?>
        
        <!-- ==========================================================================
             DANH SÁCH TOÀN BỘ ĐƠN HÀNG
             ========================================================================== -->
        <div class="admin-card">
            <div class="admin-card-header">Danh sách đơn hàng của cửa hàng</div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Tổng thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td>#<?php echo $ord['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($ord['customer_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ord['customer_phone']); ?></td>
                                    <td style="font-weight:600; color: var(--accent-color);"><?php echo number_format($ord['total_price'], 0, ',', '.'); ?> đ</td>
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
                                    <td><?php echo date('H:i d/m/Y', strtotime($ord['created_at'])); ?></td>
                                    <td>
                                        <a href="orders.php?id=<?php echo $ord['id']; ?>" class="btn btn-secondary btn-sm" style="border: 1px solid var(--border-color);">Xem chi tiết</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    Chưa có đơn hàng nào phát sinh trên cửa hàng.
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
