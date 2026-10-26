CREATE DATABASE IF NOT EXISTS quan_ly_san_bong CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quan_ly_san_bong;
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL,
 full_name VARCHAR(100) NOT NULL, phone VARCHAR(20), email VARCHAR(100) UNIQUE, role ENUM('user','admin') DEFAULT 'user',
 status ENUM('active','locked') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE fields(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, type ENUM('5','7','11') DEFAULT '5',
 address VARCHAR(255) NOT NULL, district VARCHAR(80), price_per_hour INT NOT NULL CHECK(price_per_hour>=0), image VARCHAR(255),
 description TEXT, rating DECIMAL(2,1) DEFAULT 4.5, review_count INT DEFAULT 0, status ENUM('active','maintenance') DEFAULT 'active');
CREATE TABLE bookings(id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, field_id INT NOT NULL, booking_date DATE NOT NULL,
 start_time TIME NOT NULL, end_time TIME NOT NULL, total_price INT NOT NULL,
 status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(field_id) REFERENCES fields(id) ON DELETE CASCADE,
 CHECK(end_time>start_time), INDEX(field_id,booking_date));
CREATE TABLE notifications(id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, message VARCHAR(255) NOT NULL,
 is_read TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);
CREATE TABLE payments(id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT NOT NULL, method ENUM('cash','bank','momo') DEFAULT 'cash',
 amount INT NOT NULL, status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid', paid_at DATETIME NULL,
 FOREIGN KEY(booking_id) REFERENCES bookings(id) ON DELETE CASCADE);
-- Mật khẩu mẫu của mọi tài khoản: password
INSERT INTO users(username,password,full_name,phone,email,role) VALUES
('admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Quản trị viên','0900000000','admin@sanbong.vn','admin'),
('nguyenvana','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Nguyễn Văn A','0911111111','a@mail.com','user'),
('tranvanb','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Trần Văn B','0922222222','b@mail.com','user');
INSERT INTO fields(name,type,address,district,price_per_hour,image,rating,review_count) VALUES
('Sân Cỏ Nhân Tạo Bình Thạnh','7','Bình Thạnh, TP. HCM','Bình Thạnh',120000,'https://images.unsplash.com/photo-1459865264687-595d652de67e?w=600',4.8,120),
('Sân Bóng Đá Phú Nhuận','5','Phú Nhuận, TP. HCM','Phú Nhuận',100000,'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=600',4.6,98),
('Sân Bóng Đá Quận 7','7','Quận 7, TP. HCM','Quận 7',150000,'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=600',4.7,85),
('Sân Bóng Đá Thủ Đức','11','Thủ Đức, TP. HCM','Thủ Đức',110000,'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?w=600',4.5,76);
INSERT INTO bookings(user_id,field_id,booking_date,start_time,end_time,total_price,status) VALUES
(2,1,CURDATE(),'17:00','19:00',240000,'confirmed'),(3,2,CURDATE(),'19:00','21:00',200000,'pending'),
(2,3,CURDATE()-INTERVAL 1 DAY,'16:00','18:00',300000,'completed'),(3,4,CURDATE()-INTERVAL 2 DAY,'20:00','22:00',220000,'completed'),
(2,1,CURDATE()-INTERVAL 3 DAY,'18:00','20:00',240000,'confirmed'),(3,2,CURDATE()-INTERVAL 4 DAY,'17:00','18:00',100000,'cancelled');
