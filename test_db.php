<?php
require_once __DIR__ . '/inc/ConnDB.php';
try {
    $stmt = $conn->query("SELECT * FROM tb_products LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
