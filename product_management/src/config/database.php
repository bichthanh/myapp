<?php
// Cấu hình kết nối cơ sở dữ liệu MySQL bằng PDO

$host = 'db'; // Quan trọng: Sử dụng tên service "db" của MySQL trong docker-compose.yml thay vì "localhost"
$db_name = 'clothing_db';
$username = 'clothing_user';
$password = 'clothing_pass123';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Ném ra ngoại lệ khi có lỗi
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Trả về dữ liệu dạng mảng kết hợp
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt chế độ giả lập prepare statements để bảo mật hơn
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Nếu kết nối lỗi (ví dụ container MySQL chưa khởi động xong), sẽ hiển thị thông báo lỗi
    die("Lỗi kết nối Cơ sở dữ liệu: " . $e->getMessage());
}
