<?php
$page_title = 'Thanh Toán Đơn Hàng';
require_once 'includes/header.php';

// Nếu giỏ hàng trống, chuyển hướng về trang chủ
if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$total_price = 0;

// Tính tổng tiền đơn hàng
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// Xử lý khi nhấn nút Đặt Hàng (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_email = trim($_POST['customer_email']);
    $customer_address = trim($_POST['customer_address']);

    // Validate dữ liệu
    if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
        $error = 'Vui lòng nhập đầy đủ các thông tin bắt buộc: Họ tên, Số điện thoại và Địa chỉ giao hàng.';
    } else {
        try {
            // Khởi động Database Transaction để đảm bảo tính toàn vẹn dữ liệu
            $pdo->beginTransaction();

            // 1. Ghi nhận thông tin đơn hàng vào bảng `orders`
            $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, total_price, status) 
                                   VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$customer_name, $customer_email, $customer_phone, $customer_address, $total_price]);
            $order_id = $pdo->lastInsertId();

            // 2. Thêm chi tiết các mặt hàng vào bảng `order_items` và trừ hàng tồn kho
            foreach ($_SESSION['cart'] as $key => $item) {
                // Kiểm tra lại tồn kho thực tế trong DB bằng khóa FOR UPDATE để chống race condition
                $prod_stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
                $prod_stmt->execute([$item['id']]);
                $curr_stock = intval($prod_stmt->fetchColumn());

                // Nếu số lượng tồn kho không đủ
                if ($curr_stock < $item['quantity']) {
                    throw new Exception("Sản phẩm '{$item['name']}' không đủ hàng tồn kho. Hiện chỉ còn: {$curr_stock} chiếc.");
                }

                // Ghi nhận chi tiết đơn hàng
                $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, size, quantity, price) 
                                           VALUES (?, ?, ?, ?, ?)");
                $item_stmt->execute([$order_id, $item['id'], $item['size'], $item['quantity'], $item['price']]);

                // Trừ hàng tồn kho của sản phẩm trong bảng products
                $update_stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update_stmt->execute([$item['quantity'], $item['id']]);
            }

            // Commit Transaction nếu mọi lệnh chạy thành công
            $pdo->commit();

            // Làm sạch giỏ hàng trong session
            $_SESSION['cart'] = [];

            // Chuyển hướng đến trang đặt hàng thành công
            header("Location: order-success.php?id=" . $order_id);
            exit;

        } catch (Exception $e) {
            // Rollback Transaction nếu xảy ra bất cứ lỗi nào
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h1 class="cart-title">Thanh toán đơn hàng</h1>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="checkout-grid">
        <!-- Cột trái: Form nhập thông tin khách hàng -->
        <div class="checkout-card">
            <h3>Thông tin giao hàng</h3>
            <form action="checkout.php" method="POST">
                <div class="form-group">
                    <label for="customer_name">Họ và tên khách hàng <span style="color: red;">*</span></label>
                    <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Ví dụ: Nguyễn Văn A" required value="<?php echo isset($_POST['customer_name']) ? htmlspecialchars($_POST['customer_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="customer_phone">Số điện thoại liên hệ <span style="color: red;">*</span></label>
                    <input type="tel" name="customer_phone" id="customer_phone" class="form-control" placeholder="Ví dụ: 0987654321" required value="<?php echo isset($_POST['customer_phone']) ? htmlspecialchars($_POST['customer_phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="customer_email">Địa chỉ Email (Tùy chọn)</label>
                    <input type="email" name="customer_email" id="customer_email" class="form-control" placeholder="Ví dụ: nguyenvala@gmail.com" value="<?php echo isset($_POST['customer_email']) ? htmlspecialchars($_POST['customer_email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="customer_address">Địa chỉ giao hàng chi tiết <span style="color: red;">*</span></label>
                    <textarea name="customer_address" id="customer_address" rows="4" class="form-control" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố..." required><?php echo isset($_POST['customer_address']) ? htmlspecialchars($_POST['customer_address']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1.1rem; margin-top: 10px;">
                    Xác nhận đặt hàng
                </button>
            </form>
        </div>

        <!-- Cột phải: Xem tóm tắt giỏ hàng -->
        <div class="checkout-card" style="height: fit-content;">
            <h3>Tóm tắt đơn hàng</h3>
            <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px; padding-right: 5px;">
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div>
                            <div style="font-weight: 600; font-size: 0.95rem;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="color: var(--text-muted); font-size: 0.85rem;">Size: <?php echo htmlspecialchars($item['size']); ?> | SL: <?php echo $item['quantity']; ?></div>
                        </div>
                        <div style="font-weight: 600; font-size: 0.95rem;">
                            <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="border-top: 2px solid var(--border-color); padding-top: 15px;">
                <div class="summary-row" style="font-size: 0.95rem; color: var(--text-muted); margin-bottom: 8px;">
                    <span>Tổng tiền hàng:</span>
                    <span><?php echo number_format($total_price, 0, ',', '.'); ?> đ</span>
                </div>
                <div class="summary-row" style="font-size: 0.95rem; color: var(--text-muted); margin-bottom: 8px;">
                    <span>Phí vận chuyển:</span>
                    <span style="color: green; font-weight: 500;">Miễn phí</span>
                </div>
                <div class="summary-row total" style="margin-top: 10px;">
                    <span>Tổng cộng:</span>
                    <span><?php echo number_format($total_price, 0, ',', '.'); ?> đ</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
