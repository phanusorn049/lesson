<?php
$servername = getenv('MYSQLHOST');
$port       = getenv('MYSQLPORT');
$username   = getenv('MYSQLUSER');
$password   = getenv('MYSQLPASSWORD');
$dbname     = getenv('MYSQLDATABASE');

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
