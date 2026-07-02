<?php
$page_title = 'Chi Tiết Sản Phẩm';
require_once 'includes/header.php';

// Lấy id sản phẩm từ URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Truy vấn thông tin sản phẩm và tên danh mục
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

// Nếu không tìm thấy sản phẩm
if (!$product) {
    echo '<div class="container" style="margin: 80px auto; max-width:600px;">
            <div class="alert alert-danger" style="text-align:center;">
                Sản phẩm không tồn tại hoặc đã bị xóa. <br><br>
                <a href="index.php" class="btn btn-primary">Quay về cửa hàng</a>
            </div>
          </div>';
    require_once 'includes/footer.php';
    exit;
}

// Chuyển danh sách kích cỡ (cách nhau bởi dấu phẩy) thành mảng
$sizes = explode(',', $product['sizes']);
?>

<div class="container">
    <div class="detail-container">
        
        <!-- Cột trái: Hình ảnh sản phẩm -->
        <div class="detail-gallery">
            <?php 
            $image_src = 'assets/uploads/' . $product['image_url'];
            if (!file_exists(__DIR__ . '/' . $image_src) || empty($product['image_url']) || $product['image_url'] == 'default.jpg') {
                if ($product['category_id'] == 1) {
                    $image_src = 'https://images.unsplash.com/photo-1581655353564-df123a1eb820?w=600&q=80';
                } else if ($product['category_id'] == 2) {
                    $image_src = 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&q=80';
                } else if ($product['category_id'] == 3) {
                    $image_src = 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=600&q=80';
                } else if ($product['category_id'] == 4) {
                    $image_src = 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=600&q=80';
                } else {
                    $image_src = 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=600&q=80';
                }
            }
            ?>
            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>

        <!-- Cột phải: Thông tin chi tiết sản phẩm và Form thêm vào giỏ hàng -->
        <div class="detail-info">
            <span class="product-category" style="font-size: 0.95rem; margin-bottom: 10px; display: inline-block;">
                <?php echo htmlspecialchars($product['category_name']); ?>
            </span>
            <h1 class="detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="detail-price"><?php echo number_format($product['price'], 0, ',', '.'); ?> đ</div>
            
            <p class="detail-desc">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </p>
            
            <form action="cart.php?action=add" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <!-- Chọn size -->
                <div class="detail-options">
                    <label style="font-weight: 600; display: block; margin-bottom: 10px;">Chọn Kích Thước (Size):</label>
                    <div style="display: flex; gap: 10px;">
                        <?php foreach ($sizes as $index => $sz): 
                            $sz = trim($sz);
                        ?>
                            <label style="cursor: pointer; position: relative;">
                                <input type="radio" name="size" value="<?php echo htmlspecialchars($sz); ?>" <?php echo $index === 0 ? 'checked' : ''; ?> style="position: absolute; opacity: 0; width: 0; height: 0;">
                                <span class="size-option" onclick="changeActiveSize(this)">
                                    <?php echo htmlspecialchars($sz); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Chọn số lượng -->
                <div class="qty-select">
                    <label style="font-weight: 600;">Số Lượng:</label>
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="qty-input">
                    <span style="color: var(--text-muted); font-size: 0.9rem;">
                        (Số lượng còn trong kho: <?php echo $product['stock']; ?> chiếc)
                    </span>
                </div>

                <!-- Nút thao tác -->
                <div class="detail-actions">
                    <?php if ($product['stock'] > 0): ?>
                        <button type="submit" class="btn btn-primary" style="flex-grow: 1; padding: 14px 28px; font-size: 1.05rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            Thêm vào giỏ hàng
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" style="flex-grow: 1; padding: 14px 28px; font-size: 1.05rem; cursor: not-allowed; color: red;" disabled>
                            Sản phẩm này đã Hết Hàng
                        </button>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Xử lý hiệu ứng chọn Size trên giao diện
function changeActiveSize(selectedSpan) {
    // Lấy tất cả thẻ span chứa class size-option
    const sizeSpans = document.querySelectorAll('.size-option');
    
    // Reset background và màu sắc về trạng thái mặc định
    sizeSpans.forEach(span => {
        span.classList.remove('active');
    });
    
    // Thêm class active cho thẻ được click
    selectedSpan.classList.add('active');
}

// Đánh dấu size đầu tiên được kích hoạt mặc định lúc load trang
document.addEventListener("DOMContentLoaded", function() {
    const firstSizeSpan = document.querySelector('.size-option');
    if (firstSizeSpan) {
        firstSizeSpan.classList.add('active');
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>
