<?php
$page_title = 'Đặt Hàng Thành Công';
require_once 'includes/header.php';

// Lấy id đơn hàng vừa đặt từ URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Truy vấn thông tin đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Nếu không tồn tại đơn hàng tương ứng
if (!$order) {
    header("Location: index.php");
    exit;
}
?>

<div class="container">
    <div class="success-card">
        <!-- Biểu tượng check màu xanh lá -->
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        
        <h2>Đặt Hàng Thành Công!</h2>
        <p>Cảm ơn bạn đã mua sắm tại <strong>Antigravity Fashion</strong>. Nhân viên bán hàng sẽ liên hệ với bạn để xác nhận đơn hàng trong thời gian sớm nhất.</p>
        
        <!-- Hộp thông tin tóm tắt hóa đơn -->
        <div style="background-color: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; text-align: left; margin: 30px 0; font-size: 0.95rem;">
            <h4 style="margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px; font-size: 1.05rem; font-weight: 600;">
                Mã đơn hàng: #<?php echo $order['id']; ?>
            </h4>
            <div style="display: grid; gap: 8px;">
                <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                <?php if (!empty($order['customer_email'])): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                <?php endif; ?>
                <p><strong>Địa chỉ giao:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>
                <p><strong>Tổng thanh toán:</strong> <strong style="color: var(--accent-color); font-size: 1.1rem;"><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</strong></p>
                <p><strong>Phương thức:</strong> Thanh toán khi nhận hàng (COD)</p>
            </div>
        </div>
        
        <!-- Nút thao tác điều hướng -->
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="index.php" class="btn btn-primary">Tiếp tục mua hàng</a>
            <a href="admin/index.php" class="btn btn-secondary">Quản lý đơn hàng (Admin)</a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
