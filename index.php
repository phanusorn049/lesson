<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------- เชื่อมต่อฐานข้อมูล ----------
require_once __DIR__ . '/../inc/ConnDB.php';


// ---------- โหลดคลาสหลัก ----------
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Router.php';

// --------- โหลด controller ----------
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/SupplierController.php';
// 1. เพิ่มrequire โหลด ProductController
require_once __DIR__ . '/controllers/ProductController.php';

try {
    // ---------- สร้าง instance ของ controller ----------
    $categoryController = new CategoryController($conn);
    $supplierController = new SupplierController($conn);
    // 2. สร้าง instance ของ ProductController
    $productController  = new ProductController($conn);

    // ---------- ลงทะเบียน Route ทั้งหมดของระบบไว้ที่เดียว ----------
    $router = new Router();

    // Suppliers (Read-only ณ ตอนนี้)
    $router->get('/suppliers', [$supplierController, 'index']);
    $router->get('/suppliers/{id}', [$supplierController, 'show']);

    // Categories (Read-only ณ ตอนนี้)
    $router->get('/categories', [$categoryController, 'index']);
    $router->get('/categories/{id}', [$categoryController, 'show']);

    // 3. เพิ่ม Route สำหรับ Products (Full CRUD)
    $router->get('/products', [$productController, 'index']);          // อ่านทั้งหมด / ค้นหาผ่าน ?q=keyword
    $router->get('/products/{id}', [$productController, 'show']);      // อ่านสินค้าตาม ID
    $router->post('/products', [$productController, 'store']);        // เพิ่มสินค้าใหม่
    $router->put('/products/{id}', [$productController, 'update']);     // แก้ไขสินค้า
    $router->delete('/products/{id}', [$productController, 'destroy']); // ลบสินค้า

    // ---------- ดึง path จริงจาก query string ที่ .htaccess ส่งมาให้ (__route) ----------
    $requestPath    = $_GET['__route'] ?? '';
    $requestMethod  = $_SERVER['REQUEST_METHOD'];

    $router->dispatch($requestMethod, $requestPath);

} catch (Throwable $e) {
    Response::error('เกิดข้อผิดพลาดที่ไม่คาดคิดภายในระบบ: ' . $e->getMessage(), 500);
}