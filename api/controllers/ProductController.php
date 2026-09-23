<?php
class ProductController
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /** 1. GET /api/products — ดึงรายการสินค้าแบบแบ่งหน้า Pagination หรือ ค้นหาผ่าน ?q=keyword */
    public function index(): void
    {
        try {
            $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
            $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit   = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
            $offset  = ($page - 1) * $limit;

            // 1. นัยจำนวนทั้งหมดเพื่อคำนวณ Pagination
            $countSql = "SELECT COUNT(*) FROM tb_products p";
            if (!empty($keyword)) {
                $countSql .= " WHERE p.c_ProductName LIKE :keyword OR p.i_ProductID = :id_keyword";
            }

            $countStmt = $this->conn->prepare($countSql);
            if (!empty($keyword)) {
                $countStmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
                $countStmt->bindValue(':id_keyword', is_numeric($keyword) ? (int)$keyword : 0, PDO::PARAM_INT);
            }
            $countStmt->execute();
            $totalItems = (int)$countStmt->fetchColumn();
            $totalPages = ceil($totalItems / $limit);

            // 2. ดึงข้อมูลแบบ Pagination
            $sql = "SELECT 
                        p.i_ProductID AS ProductID,
                        p.c_ProductName AS ProductName,
                        p.i_SupplierID AS SupplierID,
                        p.i_CategoryID AS CatID,
                        p.c_Unit AS Unit,
                        p.i_Price AS Price,
                        c.c_CategoryName AS CategoryName,
                        s.c_SupplierName AS SupplierName
                    FROM tb_products p
                    LEFT JOIN tb_categories c ON p.i_CategoryID = c.i_CategoryID
                    LEFT JOIN tb_suppliers s ON p.i_SupplierID = s.i_SupplierID";

            if (!empty($keyword)) {
                $sql .= " WHERE p.c_ProductName LIKE :keyword OR p.i_ProductID = :id_keyword";
            }

            $sql .= " ORDER BY p.i_ProductID DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            if (!empty($keyword)) {
                $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
                $stmt->bindValue(':id_keyword', is_numeric($keyword) ? (int)$keyword : 0, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ส่งข้อมูลกลับรวม Pagination Metadata
            Response::success([
                'items' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages'  => $totalPages,
                    'total_items'  => $totalItems,
                    'per_page'     => $limit
                ]
            ]);
        } catch (PDOException $e) {
            Response::error('ไม่สามารถดึงข้อมูลสินค้าได้: ' . $e->getMessage(), 500);
        }
    }

    /** 2. GET /api/products/{id} — ดึงข้อมูลสินค้ารายตัว */
    public function show(string $id): void
    {
        try {
            $sql = "SELECT 
                        p.i_ProductID AS ProductID,
                        p.c_ProductName AS ProductName,
                        p.i_SupplierID AS SupplierID,
                        p.i_CategoryID AS CatID,
                        p.c_Unit AS Unit,
                        p.i_Price AS Price,
                        c.c_CategoryName AS CategoryName,
                        s.c_SupplierName AS SupplierName
                    FROM tb_products p
                    LEFT JOIN tb_categories c ON p.i_CategoryID = c.i_CategoryID
                    LEFT JOIN tb_suppliers s ON p.i_SupplierID = s.i_SupplierID
                    WHERE p.i_ProductID = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();

            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                Response::notFound('ไม่พบข้อมูลสินค้ารหัสนี้');
                return;
            }

            Response::success($product);
        } catch (PDOException $e) {
            Response::error('ไม่สามารถดึงข้อมูลสินค้าได้: ' . $e->getMessage(), 500);
        }
    }

    /** 3. POST /api/products — เพิ่มสินค้าใหม่ */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                Response::error('ข้อมูลไม่ถูกต้อง', 400);
                return;
            }

            $sql = "INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price) 
                    VALUES (:name, :supplier, :cat, :unit, :price)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':name'     => $data['ProductName'] ?? '',
                ':supplier' => !empty($data['SupplierID']) ? $data['SupplierID'] : null,
                ':cat'      => !empty($data['CatID']) ? $data['CatID'] : null,
                ':unit'     => $data['Unit'] ?? '',
                ':price'    => $data['Price'] ?? 0
            ]);

            Response::success(['id' => $this->conn->lastInsertId()], 'เพิ่มสินค้าสำเร็จ');
        } catch (PDOException $e) {
            Response::error('ไม่สามารถบันทึกสินค้าได้: ' . $e->getMessage(), 500);
        }
    }

    /** 4. PUT /api/products/{id} — แก้ไขข้อมูลสินค้า */
    public function update(string $id): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                Response::error('ข้อมูลไม่ถูกต้อง', 400);
                return;
            }

            $sql = "UPDATE tb_products 
                    SET c_ProductName = :name, 
                        i_SupplierID = :supplier, 
                        i_CategoryID = :cat, 
                        c_Unit = :unit, 
                        i_Price = :price 
                    WHERE i_ProductID = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id'       => (int)$id,
                ':name'     => $data['ProductName'] ?? '',
                ':supplier' => !empty($data['SupplierID']) ? $data['SupplierID'] : null,
                ':cat'      => !empty($data['CatID']) ? $data['CatID'] : null,
                ':unit'     => $data['Unit'] ?? '',
                ':price'    => $data['Price'] ?? 0
            ]);

            Response::success(null, 'แก้ไขข้อมูลสินค้าสำเร็จ');
        } catch (PDOException $e) {
            Response::error('ไม่สามารถอัปเดตข้อมูลสินค้าได้: ' . $e->getMessage(), 500);
        }
    }

    /** 5. DELETE /api/products/{id} — ลบสินค้า */
    public function destroy(string $id): void
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM tb_products WHERE i_ProductID = :id");
            $stmt->execute([':id' => (int)$id]);

            Response::success(null, 'ลบข้อมูลสินค้าเรียบร้อยแล้ว');
        } catch (PDOException $e) {
            Response::error('ไม่สามารถลบข้อมูลสินค้าได้: ' . $e->getMessage(), 500);
        }
    }
}
