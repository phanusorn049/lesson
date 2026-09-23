<?php
// inc/ConnDB.php

// อ่านค่าจาก Environment Variables ของ Railway (ถ้าไม่มีให้ใช้ค่า Default สำหรับ Local)
$host     = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
$user     = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS')     ?: '';
$dbname   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'railway';
$port     = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: 3306;

try {
    $conn = new mysqli($host, $user, $password, $dbname, (int)$port);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception("Database Connection Failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // ส่ง Error เป็น JSON กลับไป
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status'  => false,
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
