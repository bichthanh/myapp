<?php
require_once '../config/database.php';

// Tạo thư mục uploads nếu chưa tồn tại
$upload_dir = '../assets/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$error = '';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. XỬ LÝ XÓA SẢN PHẨM
if ($action === 'delete' && $id > 0) {
    // Lấy ảnh cũ để xóa file vật lý
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $old_image = $stmt->fetchColumn();
    
    $delete_stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($delete_stmt->execute([$id])) {
        // Xóa file ảnh cũ nếu không phải là ảnh mặc định
        if ($old_image && $old_image !== 'default.jpg' && file_exists($upload_dir . $old_image)) {
            unlink($upload_dir . $old_image);
        }
        header("Location: products.php?msg=deleted");
        exit;
    }
}

// 2. XỬ LÝ THÊM HOẶC CẬP NHẬT SẢN PHẨM (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $sizes = trim($_POST['sizes']);
    $stock = intval($_POST['stock']);
    
    if (empty($name) || $category_id <= 0 || $price <= 0 || empty($sizes)) {
        $error = 'Vui lòng điền đầy đủ các thông tin bắt buộc.';
    } else {
        // Xử lý Upload file ảnh
        $image_name = 'default.jpg';
        
        // Nếu là cập nhật, giữ ảnh cũ làm mặc định
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $image_name = $stmt->fetchColumn();
        }
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($file_ext, $allowed_exts)) {
                // Tạo tên file ngẫu nhiên để tránh trùng lặp
                $new_filename = uniqid('prod_', true) . '.' . $file_ext;
                
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    // Xóa ảnh cũ nếu upload ảnh mới thành công
                    if ($id > 0 && $image_name !== 'default.jpg' && file_exists($upload_dir . $image_name)) {
                        unlink($upload_dir . $image_name);
                    }
                    $image_name = $new_filename;
                }
            } else {
                $error = 'Định dạng hình ảnh không hợp lệ (Chỉ chấp nhận JPG, PNG, WEBP, GIF).';
            }
        }
        
        // Tiến hành thêm hoặc sửa trong Database nếu không có lỗi file
        if (empty($error)) {
            if ($id > 0) {
                // UPDATE sản phẩm
                $update_stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, sizes = ?, image_url = ?, stock = ? WHERE id = ?");
                if ($update_stmt->execute([$category_id, $name, $description, $price, $sizes, $image_name, $stock, $id])) {
                    header("Location: products.php?msg=updated");
                    exit;
                }
            } else {
                // INSERT sản phẩm
                $insert_stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, sizes, image_url, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($insert_stmt->execute([$category_id, $name, $description, $price, $sizes, $image_name, $stock])) {
                    header("Location: products.php?msg=added");
                    exit;
                }
            }
        }
    }
}

// Lấy thông tin sản phẩm để sửa nếu ở chế độ Edit
$edit_product = null;
if ($action === 'edit' && $id > 0) {
    $edit_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $edit_stmt->execute([$id]);
    $edit_product = $edit_stmt->fetch();
}

// Lấy danh sách danh mục để đổ vào Dropdown Select
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categories_stmt->fetchAll();

// Lấy danh sách sản phẩm hiển thị ra bảng
$products_stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$products = $products_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header-row">
            <h1>Quản Lý Sản Phẩm</h1>
            <?php if ($action !== 'edit'): ?>
                <a href="products.php?action=add_form" class="btn btn-primary btn-sm">+ Thêm sản phẩm mới</a>
            <?php else: ?>
                <a href="products.php" class="btn btn-secondary btn-sm">Quay lại danh sách</a>
            <?php endif; ?>
        </div>
        
        <!-- Hiển thị thông báo kết quả -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">Đã thêm sản phẩm thành công!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">Đã cập nhật thông tin sản phẩm thành công!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success">Đã xóa sản phẩm thành công khỏi hệ thống!</div>
        <?php endif; ?>
        
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- FORM THÊM / CHỈNH SỬA SẢN PHẨM -->
        <?php if ($action === 'edit' || $action === 'add_form'): ?>
            <div class="admin-card" style="margin-bottom: 40px;">
                <div class="admin-card-header">
                    <?php echo $action === 'edit' ? 'Cập nhật sản phẩm: ' . htmlspecialchars($edit_product['name']) : 'Thêm sản phẩm mới'; ?>
                </div>
                
                <form action="products.php<?php echo $action === 'edit' ? '?id=' . $edit_product['id'] : ''; ?>" method="POST" enctype="multipart/form-data" class="admin-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        
                        <div class="form-group">
                            <label for="name">Tên sản phẩm <span style="color:red;">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required value="<?php echo $action === 'edit' ? htmlspecialchars($edit_product['name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Danh mục quần áo <span style="color:red;">*</span></label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($action === 'edit' && $edit_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Giá bán (đ) <span style="color:red;">*</span></label>
                            <input type="number" name="price" id="price" class="form-control" required min="1000" step="500" value="<?php echo $action === 'edit' ? $edit_product['price'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">Số lượng trong kho <span style="color:red;">*</span></label>
                            <input type="number" name="stock" id="stock" class="form-control" required min="0" value="<?php echo $action === 'edit' ? $edit_product['stock'] : '0'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="sizes">Kích cỡ khả dụng (cách nhau bằng dấu phẩy) <span style="color:red;">*</span></label>
                            <input type="text" name="sizes" id="sizes" class="form-control" required placeholder="Ví dụ: S,M,L,XL" value="<?php echo $action === 'edit' ? htmlspecialchars($edit_product['sizes']) : 'S,M,L,XL'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Hình ảnh sản phẩm</label>
                            <input type="file" name="image" id="image" class="form-control" style="padding: 8px;">
                            <?php if ($action === 'edit' && $edit_product['image_url'] !== 'default.jpg'): ?>
                                <p style="margin-top: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                    Ảnh hiện tại: <strong><?php echo htmlspecialchars($edit_product['image_url']); ?></strong>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <label for="description">Mô tả sản phẩm</label>
                        <textarea name="description" id="description" rows="5" class="form-control"><?php echo $action === 'edit' ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    </div>
                    
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Lưu lại</button>
                        <a href="products.php" class="btn btn-secondary">Hủy bỏ</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- BẢNG DANH SÁCH SẢN PHẨM -->
        <div class="admin-card">
            <div class="admin-card-header">Danh sách mẫu quần áo</div>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Đơn giá</th>
                            <th>Size</th>
                            <th>Kho hàng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $image_src = '../assets/uploads/' . $prod['image_url'];
                                        if (!file_exists(__DIR__ . '/' . $image_src) || empty($prod['image_url']) || $prod['image_url'] == 'default.jpg') {
                                            if ($prod['category_id'] == 1) {
                                                $image_src = 'https://images.unsplash.com/photo-1581655353564-df123a1eb820?w=80&q=80';
                                            } else if ($prod['category_id'] == 2) {
                                                $image_src = 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=80&q=80';
                                            } else if ($prod['category_id'] == 3) {
                                                $image_src = 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=80&q=80';
                                            } else if ($prod['category_id'] == 4) {
                                                $image_src = 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=80&q=80';
                                            } else {
                                                $image_src = 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=80&q=80';
                                            }
                                        }
                                        ?>
                                        <img src="<?php echo $image_src; ?>" alt="Thumbnail" class="admin-prod-thumb">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">ID: #<?php echo $prod['id']; ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($prod['category_name']); ?></td>
                                    <td style="font-weight:600; color: var(--accent-color);"><?php echo number_format($prod['price'], 0, ',', '.'); ?> đ</td>
                                    <td><?php echo htmlspecialchars($prod['sizes']); ?></td>
                                    <td>
                                        <?php if ($prod['stock'] > 0): ?>
                                            <span style="font-weight: 500; color: green;"><?php echo $prod['stock']; ?> chiếc</span>
                                        <?php else: ?>
                                            <span style="font-weight: 700; color: red;">Hết hàng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-secondary btn-sm" style="border: 1px solid var(--border-color);">Sửa</a>
                                            <a href="products.php?action=delete&id=<?php echo $prod['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi cơ sở dữ liệu?')">Xóa</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    Chưa có sản phẩm nào được nhập kho.
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
