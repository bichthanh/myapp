<?php
require_once '../config/database.php';

$message = '';
$error = '';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. XỬ LÝ XÓA DANH MỤC
if ($action === 'delete' && $id > 0) {
    // Note: Do có thiết lập khóa ngoại CASCADE ở database, khi xóa danh mục, các sản phẩm thuộc danh mục này cũng sẽ tự động bị xóa.
    $delete_stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    if ($delete_stmt->execute([$id])) {
        header("Location: categories.php?msg=deleted");
        exit;
    }
}

// 2. XỬ LÝ POST THÊM HOẶC CẬP NHẬT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $error = 'Tên danh mục là bắt buộc, không được để trống.';
    } else {
        if ($id > 0) {
            // SỬA danh mục
            $update_stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($update_stmt->execute([$name, $description, $id])) {
                header("Location: categories.php?msg=updated");
                exit;
            }
        } else {
            // THÊM danh mục
            $insert_stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($insert_stmt->execute([$name, $description])) {
                header("Location: categories.php?msg=added");
                exit;
            }
        }
    }
}

// Lấy thông tin để sửa nếu ở chế độ Sửa
$edit_category = null;
if ($action === 'edit' && $id > 0) {
    $edit_stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $edit_stmt->execute([$id]);
    $edit_category = $edit_stmt->fetch();
}

// Lấy danh sách danh mục để hiển thị ra bảng
$categories_stmt = $pdo->query("SELECT c.*, COUNT(p.id) as total_products FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name ASC");
$categories = $categories_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Danh Mục - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header-row">
            <h1>Quản Lý Danh Mục</h1>
        </div>
        
        <!-- Hiển thị thông báo kết quả -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">Đã thêm danh mục thành công!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">Đã cập nhật thông tin danh mục thành công!</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success">Đã xóa danh mục thành công khỏi hệ thống!</div>
        <?php endif; ?>
        
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 340px 1fr; gap: 30px; align-items: start;">
            
            <!-- FORM NHẬP LIỆU (THÊM / SỬA) -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <?php echo $action === 'edit' ? 'Sửa danh mục' : 'Thêm danh mục mới'; ?>
                </div>
                <form action="categories.php<?php echo $action === 'edit' ? '?id=' . $edit_category['id'] : ''; ?>" method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="name">Tên danh mục <span style="color:red;">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required value="<?php echo $action === 'edit' ? htmlspecialchars($edit_category['name']) : ''; ?>" placeholder="Ví dụ: Áo Sơ Mi Nam">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Mô tả chi tiết</label>
                        <textarea name="description" id="description" rows="5" class="form-control" placeholder="Mô tả tóm tắt về loại sản phẩm này..."><?php echo $action === 'edit' ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="flex-grow: 1;">Lưu lại</button>
                        <?php if ($action === 'edit'): ?>
                            <a href="categories.php" class="btn btn-secondary" style="flex-grow: 1;">Hủy</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- BẢNG DANH SÁCH DANH MỤC -->
            <div class="admin-card">
                <div class="admin-card-header">Danh sách các danh mục quần áo</div>
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Mã</th>
                                <th>Tên danh mục</th>
                                <th>Mô tả chi tiết</th>
                                <th style="width: 140px;">Số sản phẩm</th>
                                <th style="width: 150px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td>#<?php echo $cat['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                        <td style="color: var(--text-muted); font-size: 0.9rem; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($cat['description']); ?>
                                        </td>
                                        <td style="text-align: center; font-weight: 500;">
                                            <?php echo $cat['total_products']; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-secondary btn-sm" style="border: 1px solid var(--border-color);">Sửa</a>
                                                <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Chú ý: Xóa danh mục này sẽ XÓA TOÀN BỘ sản phẩm thuộc danh mục đó. Bạn có chắc chắn?')">Xóa</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                        Chưa có danh mục nào trên hệ thống.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>
