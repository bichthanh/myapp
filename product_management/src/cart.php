<?php
$page_title = 'Giỏ Hàng Của Bạn';
require_once 'includes/header.php';

// Khởi tạo mảng giỏ hàng trong session nếu chưa tồn tại
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Nhận hành động
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Hành động: THÊM sản phẩm vào giỏ hàng
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $size = isset($_POST['size']) ? trim($_POST['size']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($product_id > 0 && $size !== '') {
        // Truy vấn DB lấy thông tin sản phẩm thực tế
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            // Định danh duy nhất trong giỏ hàng: "id_size"
            // Việc này giúp khách hàng có thể mua cùng 1 áo nhưng với nhiều size khác nhau
            $cart_key = $product_id . '_' . $size;
            $stock = intval($product['stock']);

            if (isset($_SESSION['cart'][$cart_key])) {
                // Đã có trong giỏ hàng -> Cộng dồn số lượng mua
                $new_qty = $_SESSION['cart'][$cart_key]['quantity'] + $quantity;
                if ($new_qty > $stock) {
                    $new_qty = $stock; // Giới hạn bằng số hàng tồn trong kho
                }
                $_SESSION['cart'][$cart_key]['quantity'] = $new_qty;
            } else {
                // Chưa có -> Thêm mới phần tử vào giỏ
                if ($quantity > $stock) {
                    $quantity = $stock;
                }
                $_SESSION['cart'][$cart_key] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'image_url' => $product['image_url'],
                    'price' => floatval($product['price']),
                    'category_id' => $product['category_id'],
                    'size' => $size,
                    'quantity' => $quantity,
                    'stock' => $stock
                ];
            }
            header("Location: cart.php?msg=added");
            exit;
        }
    }
}

// 2. Hành động: XÓA sản phẩm khỏi giỏ hàng
if ($action === 'remove') {
    $key = isset($_GET['key']) ? $_GET['key'] : '';
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
    }
    header("Location: cart.php");
    exit;
}

// 3. Hành động: CẬP NHẬT số lượng trong giỏ hàng
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $key => $qty) {
            $qty = intval($qty);
            if (isset($_SESSION['cart'][$key])) {
                $max_stock = $_SESSION['cart'][$key]['stock'];
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$key]); // Nếu số lượng nhỏ hơn hoặc bằng 0 -> Xóa luôn
                } else {
                    $_SESSION['cart'][$key]['quantity'] = ($qty > $max_stock) ? $max_stock : $qty;
                }
            }
        }
    }
    header("Location: cart.php?msg=updated");
    exit;
}
?>

<div class="container">
    <h1 class="cart-title">Giỏ Hàng Của Bạn</h1>
    
    <!-- Thông báo nếu có -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
        <div class="alert alert-success alert-animate" style="display: flex; align-items: center; gap: 10px; box-shadow: var(--shadow-sm); font-size: 1.05rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #059669;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span>Đã thêm sản phẩm vào giỏ hàng thành công!</span>
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success alert-animate" style="display: flex; align-items: center; gap: 10px; box-shadow: var(--shadow-sm); font-size: 1.05rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #059669;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span>Đã cập nhật số lượng giỏ hàng thành công!</span>
        </div>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <!-- Hiển thị khi giỏ hàng trống -->
        <div class="alert alert-danger" style="text-align: center; padding: 60px 20px;">
            Giỏ hàng của bạn đang trống. <br><br>
            <a href="index.php" class="btn btn-primary">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        <!-- Form cập nhật giỏ hàng -->
        <form action="cart.php?action=update" method="POST">
            <div class="cart-table-wrapper">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th style="width: 120px;">Số lượng</th>
                            <th>Thành tiền</th>
                            <th style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_price = 0;
                        foreach ($_SESSION['cart'] as $key => $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total_price += $subtotal;
                            
                            // Lấy link ảnh
                            $image_src = 'assets/uploads/' . $item['image_url'];
                            if (!file_exists(__DIR__ . '/' . $image_src) || empty($item['image_url']) || $item['image_url'] == 'default.jpg') {
                                if ($item['category_id'] == 1) {
                                    $image_src = 'https://images.unsplash.com/photo-1581655353564-df123a1eb820?w=150&q=80';
                                } else if ($item['category_id'] == 2) {
                                    $image_src = 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=150&q=80';
                                } else if ($item['category_id'] == 3) {
                                    $image_src = 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=150&q=80';
                                } else if ($item['category_id'] == 4) {
                                    $image_src = 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=150&q=80';
                                } else {
                                    $image_src = 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=150&q=80';
                                }
                            }
                        ?>
                            <tr>
                                <td>
                                    <div class="cart-item">
                                        <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img">
                                        <div>
                                            <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="cart-item-size">Size: <strong><?php echo htmlspecialchars($item['size']); ?></strong></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                                <td>
                                    <input type="number" name="quantities[<?php echo $key; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="qty-input" style="width: 70px;">
                                </td>
                                <td><strong><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</strong></td>
                                <td>
                                    <a href="cart.php?action=remove&key=<?php echo $key; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa sản phẩm này khỏi giỏ hàng?')">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Phần tổng kết tiền và nút thanh toán -->
            <div class="cart-summary">
                <div class="summary-card">
                    <div class="summary-row">
                        <span>Số loại sản phẩm:</span>
                        <span><?php echo count($_SESSION['cart']); ?></span>
                    </div>
                    <div class="summary-row total" style="margin-top: 10px;">
                        <span>Tổng tiền thanh toán:</span>
                        <span><?php echo number_format($total_price, 0, ',', '.'); ?> đ</span>
                    </div>
                    
                    <div style="display: flex; gap: 12px; margin-top: 24px;">
                        <button type="submit" class="btn btn-secondary" style="flex-grow: 1;">Cập nhật giỏ</button>
                        <a href="checkout.php" class="btn btn-primary" style="flex-grow: 2;">Thanh toán đơn</a>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>
