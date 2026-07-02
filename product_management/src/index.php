<?php
$page_title = 'Antigravity Fashion - Trang Chủ';
require_once 'includes/header.php';

// Lấy các tham số lọc và tìm kiếm từ URL
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 1. Lấy danh sách toàn bộ danh mục sản phẩm từ DB
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categories_stmt->fetchAll();

// 2. Xây dựng câu SQL để truy vấn sản phẩm
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if ($search !== '') {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY p.id DESC";
$products_stmt = $pdo->prepare($query);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll();
?>

<!-- Hero Banner (Giao diện hiện đại) -->
<div class="hero">
    <div class="container">
        <h1>Bộ Sưu Tập Thời Trang Hè 2026</h1>
        <p>Trải nghiệm phong cách thời trang thiết kế trẻ trung, năng động và đẳng cấp. Khám phá ngay ưu đãi giảm giá lên tới 30% hôm nay.</p>
        <a href="#store" class="btn btn-primary">Khám Phá Cửa Hàng</a>
    </div>
</div>

<!-- Nội dung Cửa Hàng -->
<div class="container store-container" id="store">
    
    <!-- Sidebar bộ lọc (Áp dụng thiết kế CSS Grid) -->
    <aside class="filters-sidebar">
        <!-- Bộ tìm kiếm tên sản phẩm -->
        <div class="filter-group">
            <h3>Tìm kiếm</h3>
            <form action="index.php" method="GET">
                <?php if ($category_id): ?>
                    <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Tên sản phẩm..." class="form-control" value="<?php echo htmlspecialchars($search); ?>" style="margin-bottom: 10px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Tìm kiếm</button>
            </form>
        </div>

        <!-- Bộ lọc danh mục -->
        <div class="filter-group">
            <h3>Danh mục sản phẩm</h3>
            <ul class="filter-list">
                <li>
                    <a href="index.php<?php echo $search !== '' ? '?search=' . urlencode($search) : ''; ?>" class="<?php echo is_null($category_id) ? 'active' : ''; ?>">
                        Tất cả sản phẩm
                    </a>
                </li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="index.php?category_id=<?php echo $cat['id']; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $category_id === intval($cat['id']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <!-- Khu vực hiển thị lưới sản phẩm -->
    <main class="products-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 1.5rem; font-weight: 600;">
                <?php 
                if ($category_id) {
                    $selected_cat = null;
                    foreach ($categories as $c) {
                        if (intval($c['id']) === $category_id) {
                            $selected_cat = $c['name'];
                            break;
                        }
                    }
                    echo htmlspecialchars($selected_cat);
                } else {
                    echo 'Tất Cả Sản Phẩm';
                }
                ?>
            </h2>
            <span style="color: var(--text-muted); font-size: 0.95rem;">Đang hiển thị <?php echo count($products); ?> sản phẩm</span>
        </div>

        <!-- Lưới sản phẩm -->
        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $prod): ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <?php 
                            // Nếu có ảnh thật tải lên thì dùng, không thì lấy Unsplash mẫu
                            $image_src = 'assets/uploads/' . $prod['image_url'];
                            if (!file_exists(__DIR__ . '/' . $image_src) || empty($prod['image_url']) || $prod['image_url'] == 'default.jpg') {
                                // Gán ảnh mẫu cho đẹp mắt theo tên danh mục
                                if ($prod['category_id'] == 1) { // Áo Polo
                                    $image_src = 'https://images.unsplash.com/photo-1581655353564-df123a1eb820?w=500&q=80';
                                } else if ($prod['category_id'] == 2) { // Áo khoác
                                    $image_src = 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500&q=80';
                                } else if ($prod['category_id'] == 3) { // Quần
                                    $image_src = 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=500&q=80';
                                } else if ($prod['category_id'] == 4) { // Đầm
                                    $image_src = 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=500&q=80';
                                } else {
                                    $image_src = 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=500&q=80';
                                }
                            }
                            ?>
                            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                        </div>
                        <div class="product-info">
                            <span class="product-category"><?php echo htmlspecialchars($prod['category_name']); ?></span>
                            <a href="product-detail.php?id=<?php echo $prod['id']; ?>">
                                <h3 class="product-title"><?php echo htmlspecialchars($prod['name']); ?></h3>
                            </a>
                            <div class="product-price"><?php echo number_format($prod['price'], 0, ',', '.'); ?> đ</div>
                            <div class="product-meta">
                                <span>Size: <?php echo htmlspecialchars($prod['sizes']); ?></span>
                                <span>Kho: <?php echo $prod['stock'] > 0 ? $prod['stock'] . ' chiếc' : '<strong style="color:red;">Hết hàng</strong>'; ?></span>
                            </div>
                            <a href="product-detail.php?id=<?php echo $prod['id']; ?>" class="btn btn-secondary" style="margin-top: auto;">Xem chi tiết</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" style="text-align: center; padding: 40px 20px;">
                Không tìm thấy sản phẩm nào phù hợp với tiêu chí lọc của bạn.
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
require_once 'includes/footer.php';
?>
