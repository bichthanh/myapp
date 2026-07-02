FROM php:8.2-apache

# Cài đặt các extension PHP cần thiết để kết nối MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Kích hoạt mod_rewrite của Apache
RUN a2enmod rewrite

# Thiết lập thư mục làm việc trong container
WORKDIR /var/www/html
