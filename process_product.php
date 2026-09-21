<?php
require_once "inc/ConnDB.php";

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

// 1. ดึงข้อมูล Dropdown
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2. เพิ่มและแก้ไขข้อมูล
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
   exit;
}

$productName = trim($_POST['ProductName'] ?? '');
$supplierId  = trim($_POST['SupplierID'] ?? '');
$catId       = trim($_POST['CatID'] ?? '');
$unit        = trim($_POST['Unit'] ?? '');
$price       = trim($_POST['Price'] ?? '');
$productID   = $_POST['ProductID'] ?? '';

try {
   if ($action === 'update' && !empty($productID)) {
       $sql = "UPDATE tb_products 
               SET c_ProductName = :productName, 
                   i_SupplierID  = :supplierId, 
                   i_CategoryID  = :catId, 
                   c_Unit        = :unit, 
                   i_Price       = :price
               WHERE i_ProductID = :productID";

       $result = $conn->prepare($sql);
       $result->execute([
           ':productName' => $productName,
           ':supplierId'  => $supplierId,
           ':catId'       => $catId,
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
       
   } else if ($action === 'insert') {
       $sql = "INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price)
               VALUES (:productName, :supplierId, :catId, :unit, :price)";

       $result = $conn->prepare($sql);
       $result->execute([
           ':productName' => $productName,
           ':supplierId'  => $supplierId,
           ':catId'       => $catId,
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
?>