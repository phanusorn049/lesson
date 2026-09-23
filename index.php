<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------- เชื่อมต่อฐานข้อมูล ----------
require_once __DIR__ . '/inc/ConnDB.php';

// ---------- โหลดคลาสหลัก ----------
require_once __DIR__ . '/api/core/Response.php';
require_once __DIR__ . '/api/core/Router.php';

// --------- โหลด controller ----------
require_once __DIR__ . '/api/controllers/CategoryController.php';
require_once __DIR__ . '/api/controllers/SupplierController.php';
require_once __DIR__ . '/api/controllers/ProductController.php';

try {
    $categoryController = new CategoryController($conn);
    $supplierController = new SupplierController($conn);
    $productController  = new ProductController($conn);

    $router = new Router();

    // ลงทะเบียน Route สำหรับ redirect หน้าแรกไปยัง products.html
    $router->get('/', function() {
        header('Location: /products.html');
        exit;
    });

    // ลงทะเบียน Route ปกติ
    $router->get('/suppliers', [$supplierController, 'index']);
    $router->get('/suppliers/{id}', [$supplierController, 'show']);
    $router->get('/categories', [$categoryController, 'index']);
    $router->get('/categories/{id}', [$categoryController, 'show']);
    $router->get('/products', [$productController, 'index']);
    $router->get('/products/{id}', [$productController, 'show']);
    $router->post('/products', [$productController, 'store']);
    $router->put('/products/{id}', [$productController, 'update']);
    $router->delete('/products/{id}', [$productController, 'destroy']);

    // ---------- จัดการ URL Routing ----------
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    $requestPath = str_replace('/index.php', '', $requestUri);
    
    if (strpos($requestPath, '/api') === 0) {
        $requestPath = substr($requestPath, 4);
    }

    if (empty($requestPath) || $requestPath === '') {
        $requestPath = '/';
    }

    $router->dispatch($requestMethod, $requestPath);

} catch (Throwable $e) {
    Response::error('เกิดข้อผิดพลาดที่ไม่คาดคิดภายในระบบ: ' . $e->getMessage(), 500);
}
