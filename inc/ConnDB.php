<?php
// inc/ConnDB.php

// อ่านค่าจาก Environment Variables ของ Railway (ถ้าไม่มีให้ใช้ค่า Default สำหรับ Local)
$host     = getenv('MYSQLHOST')     ?: (getenv('DB_HOST') ?: 'localhost');
$user     = getenv('MYSQLUSER')     ?: (getenv('DB_USER') ?: 'root');
$password = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$dbname   = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'railway');
$port     = getenv('MYSQLPORT')     ?: (getenv('DB_PORT') ?: 3306);

try {
    // สร้าง DSN สำหรับ PDO Connection
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // ตัดบรรทัด PDO::MYSQL_ATTR_INIT_COMMAND ออกเพื่อแก้ปัญหา Undefined Constant
    ];

    $conn = new PDO($dsn, $user, $password, $options);

} catch (PDOException $e) {
    // หากเกิด Error ให้ส่ง JSON 500 กลับไป
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'status'  => false,
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
