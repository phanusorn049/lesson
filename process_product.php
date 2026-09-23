<?php
require_once __DIR__ . '/inc/ConnDB.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// 1. ดึงข้อมูล Dropdown (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if ($action === 'get_suppliers') {
            $stmt = $conn->prepare("SELECT i_SupplierID, c_SupplierName FROM tb_suppliers ORDER BY i_SupplierID ASC");
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
            exit;
        } 
        
        if ($action === 'get_categories') {
            $stmt = $conn->prepare("SELECT i_CategoryID, c_CategoryName FROM tb_categories ORDER BY i_CategoryID ASC");
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2. เพิ่มและแก้ไขข้อมูล (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจาก $_POST หรือ php://input (กรณี JS ส่งมาเป็น JSON)
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
    }

    $productName = trim($input['ProductName'] ?? '');
    $supplierId  = trim($input['SupplierID'] ?? '');
    $catId       = trim($input['CatID'] ?? '');
    $unit        = trim($input['Unit'] ?? '');
    $price       = trim($input['Price'] ?? 0);
    $productID   = trim($input['ProductID'] ?? '');
    $action      = $action ?: ($input['action'] ?? '');

    // เช็คเฉพาะชื่อสินค้า
    if (empty($productName)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'กรุณากรอกชื่อสินค้า'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        if ($action === 'update' || !empty($productID)) {
            $sql = "UPDATE tb_products 
                    SET c_ProductName = :productName, 
                        i_SupplierID  = :supplierId, 
                        i_CategoryID  = :catId, 
                        c_Unit        = :unit, 
                        i_Price       = :price
                    WHERE i_ProductID = :productID";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':productName' => $productName,
                ':supplierId'  => $supplierId ?: null,
                ':catId'       => $catId ?: null,
                ':unit'        => $unit,
                ':price'       => $price,
                ':productID'   => $productID
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลสินค้าเรียบร้อยแล้ว',
                'id'      => $productID
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } else {
            // เพิ่มสินค้าใหม่
            $sql = "INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price)
                    VALUES (:productName, :supplierId, :catId, :unit, :price)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':productName' => $productName,
                ':supplierId'  => $supplierId ?: null,
                ':catId'       => $catId ?: null,
                ':unit'        => $unit,
                ':price'       => $price
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'บันทึกข้อมูลสินค้าเรียบร้อยแล้ว',
                'id'      => $conn->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
