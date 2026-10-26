<?php
// Cấu hình kết nối MySQL (XAMPP mặc định: root, không mật khẩu)
$DB_HOST='localhost'; $DB_NAME='quan_ly_san_bong'; $DB_USER='root'; $DB_PASS='';
try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",$DB_USER,$DB_PASS,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
} catch (PDOException $e) { die('Không kết nối được CSDL. Hãy import database.sql bằng phpMyAdmin.'); }
