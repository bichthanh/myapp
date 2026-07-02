# DỰ ÁN WEBSITE QUẢN LÝ BÁN HÀNG QUẦN ÁO (PHP + MYSQL + DOCKER)
> Học phần: Quy trình và công cụ phát triển phần mềm - UTT

Dự án này được thiết kế và đóng gói bằng Docker nhằm phục vụ thi vấn đáp trực tiếp trên máy. Hệ thống bao gồm website bán hàng cho khách hàng và trang quản trị (Admin Dashboard) để quản lý sản phẩm, danh mục và đơn đặt hàng.

---

## I. HƯỚNG DẪN KHỞI CHẠY HỆ THỐNG TRÊN DOCKER

Mở terminal (PowerShell trên Windows hoặc Shell trên Ubuntu) tại thư mục `product_management` chứa dự án và chạy các lệnh sau:

### 1. Khởi chạy các Container (Web server & MySQL)
```bash
# Xây dựng lại image và khởi chạy các container chạy ngầm (-d)
docker compose up --build -d
```
*Lưu ý:* Lệnh này sẽ tự động:
1. Đọc `Dockerfile` và build image cho web server Apache + PHP.
2. Tải image MySQL 8.0 từ Docker Hub.
3. Khởi tạo mạng nội bộ (network) liên kết 2 container.
4. Chạy script `database/init.sql` để tạo các bảng dữ liệu và chèn dữ liệu mẫu lúc container DB được tạo lần đầu.

### 2. Đường dẫn truy cập hệ thống
*   **Trang cửa hàng (Khách hàng):** [http://localhost:8080](http://localhost:8080)
*   **Trang quản trị (Admin Dashboard):** [http://localhost:8080/admin](http://localhost:8080/admin)
    *   *Tài khoản Admin mặc định:* **`bichthanh`**
    *   *Mật khẩu:* **`123456`**

### 3. Dừng các Container
Khi thi xong hoặc muốn dừng hệ thống, chạy lệnh:
```bash
docker compose down
```
*   Dữ liệu trong MySQL sẽ **không bị mất** vì đã được cấu hình lưu bền vững tại Docker Volume có tên `db_data`.

---

## II. ĐỀ CƯƠNG ÔN TẬP THI VẤN ĐÁP (CÂU HỎI & TRẢ LỜI)

### CHỦ ĐỀ 1: SSH VÀ TRUYỀN FILE
1.  **Cách SSH vào Server (máy ảo Ubuntu):**
    *   Lệnh: `ssh <username>@<dia_chi_ip>`
    *   Ví dụ: `ssh root@192.168.1.50` (sau đó nhập mật khẩu).
2.  **Cách truyền file lên server:**
    *   *Cách 1 (FTP - Vsftpd):* Cần cài đặt Vsftpd trên Linux, mở port 21. Sử dụng FileZilla hoặc Bitvise Client kết nối giao thức FTP.
    *   *Cách 2 (SFTP):* Dựa trực tiếp trên giao thức SSH bảo mật (mặc định mở port 22). Sử dụng lệnh `scp` hoặc dùng Bitvise SFTP client để kéo thả file trực tiếp mà không cần cài thêm FTP server.

### CHỦ ĐỀ 2: GIT (QUẢN LÝ MÃ NGUỒN)
1.  **Các lệnh Git cơ bản:**
    *   `git init`: Khởi tạo kho chứa Git cục bộ (local repository).
    *   `git status`: Kiểm tra trạng thái thay đổi các file trong thư mục làm việc.
    *   `git add .`: Thêm toàn bộ các file thay đổi vào khu vực chờ (Staging Area).
    *   `git commit -m "nội dung"`: Lưu lại ảnh chụp mã nguồn kèm thông điệp mô tả.
    *   `git push origin <branch>`: Đẩy code từ máy cục bộ lên GitHub/GitLab.
    *   `git pull origin <branch>`: Kéo code mới nhất từ trên mạng về máy của mình.
2.  **Cách giải quyết xung đột (Git Conflict):**
    *   *Nguyên nhân:* Xảy ra khi 2 người cùng sửa chung một dòng code trên cùng một file và push lên Git.
    *   *Cách xử lý:*
        1. Chạy lệnh `git pull` để tải code mới nhất về, Git sẽ báo lỗi Conflict và chèn các ký hiệu `<<<<<<<`, `=======`, `>>>>>>>` vào dòng bị xung đột trong file.
        2. Mở file bị conflict bằng VS Code. VS Code sẽ hiển thị các lựa chọn: "Accept Current Change" (Giữ code của mình), "Accept Incoming Change" (Lấy code của người khác), hoặc "Accept Both Changes" (Lấy cả hai).
        3. Chọn cách giải quyết phù hợp, lưu file lại.
        4. Chạy `git add <tên_file>`, `git commit -m "Fix conflict"`, sau đó `git push` lên lại GitHub.

### CHỦ ĐỀ 3: DOCKER (CONTAINERIZATION)
1.  **Phân biệt Container (Docker) và Máy ảo (VM):**
    *   *Máy ảo (VM):* Chạy trên một bộ giám sát ảo (Hypervisor). Mỗi VM sở hữu một Hệ điều hành (OS) khách riêng biệt hoàn chỉnh. Do đó, VM rất nặng, khởi động lâu (vài phút) và tốn tài nguyên phần cứng (RAM, CPU).
    *   *Container (Docker):* Chia sẻ chung nhân hệ điều hành (Kernel OS) của máy chủ vật lý, chỉ cô lập tài nguyên ở tầng ứng dụng. Do đó, Docker Container siêu nhẹ (chỉ vài chục MB), khởi động tính bằng mili-giây, tốn rất ít tài nguyên.
2.  **Ý nghĩa của Port Mapping (Ánh xạ cổng):**
    *   Trong file `docker-compose.yml`, cấu hình `ports: - "8080:80"` nghĩa là ánh xạ cổng `8080` của máy thật (Host) vào cổng `80` của Apache trong container. Khi ta truy cập `http://localhost:8080` từ máy thật, Docker sẽ chuyển hướng request đó vào cổng `80` bên trong container để xử lý.
3.  **Ý nghĩa của Volume (Gắn ổ đĩa bền vững):**
    *   Mặc định, dữ liệu sinh ra trong container (như cơ sở dữ liệu MySQL) sẽ biến mất hoàn toàn khi container bị xóa (`docker compose down` hoặc xóa container).
    *   Gắn volume `db_data:/var/lib/mysql` giúp liên kết thư mục chứa dữ liệu của MySQL trong container ra một phân vùng ổ đĩa trên máy thật. Khi container bị tắt hoặc xóa đi, dữ liệu cơ sở dữ liệu vẫn được bảo toàn nguyên vẹn trên máy chủ vật lý.
4.  **Các lệnh Docker thông dụng:**
    *   `docker build -t <tên_image> .`: Build một docker image từ Dockerfile.
    *   `docker run -d -p 8080:80 <tên_image>`: Khởi chạy container chạy ngầm và map port.
    *   `docker ps`: Hiển thị danh sách các container đang chạy.
    *   `docker images`: Hiển thị danh sách các Docker Image đang có trên máy.
    *   `docker exec -it <tên_container> bash`: Truy cập vào terminal (shell) của một container đang chạy.
    *   `docker logs <tên_container>`: Xem log đầu ra của container (để debug lỗi).
