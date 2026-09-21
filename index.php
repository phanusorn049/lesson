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
// ทั้งหมดที่อยู่ในโฟลเดอร์ controllers/ โดยสร้างเป็นชื่อเดียวกับชื่อไฟล์ controller เช่น CategoryController ในโฟลเดอร์ controllers/CategoryController.ph
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/SupplierController.php';

try {
    // ---------- สร้าง instance ของ controller ----------
    // โดยสร้างเป็นชื่อเดียวกับชื่อไฟล์ controller เช่น CategoryController ในโฟลเดอร์ controllers/CategoryController.php
    $categoryController = new CategoryController($conn);
    $supplierController = new SupplierController($conn);

    // ---------- ลงทะเบียน Route ทั้งหมดของระบบไว้ที่เดียว ----------
    $router = new Router();

    // Suppliers (Read-only ณ ตอนนี้)
    $router->get('/suppliers', [$supplierController, 'index']);
    $router->get('/suppliers/{id}', [$supplierController, 'show']);

    // Categories (Read-only ณ ตอนนี้)
    $router->get('/categories', [$categoryController, 'index']);
    $router->get('/categories/{id}', [$categoryController, 'show']);

    // ---------- ดึง path จริงจาก query string ที่ .htaccess ส่งมาให้ (__route) ----------
    $requestPath    = $_GET['__route'] ?? '';
    $requestMethod  = $_SERVER['REQUEST_METHOD'];

    $router->dispatch($requestMethod, $requestPath);

} catch (Throwable $e) {
    Response::error('เกิดข้อผิดพลาดที่ไม่คาดคิดภายในระบบ', 500);
}
