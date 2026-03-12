<?php
class Category
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function convertLobToString($value)
    {
        if (is_object($value) && get_class($value) === 'OCILob') {
            return $value->load() ?: '';
        }
        return $value;
    }

    private function processRow($row)
    {
        if (!$row) {
            return false;
        }

        foreach ($row as $key => $value) {
            $row[$key] = $this->convertLobToString($value);
        }
        return $row;
    }

    public function countAll()
    {
        $sql = "SELECT COUNT(*) AS total FROM categories";
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)($row['TOTAL'] ?? 0);
    }

    public function getAllCategories()
    {
        $sql = "SELECT category_id, name, description, image FROM categories ORDER BY name";
        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            return [];
        }

        $categories = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $categories[] = $this->processRow($row);
        }

        oci_free_statement($stmt);
        return $categories;
    }

    public function getAllCategoryDetails($limit, $offset)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT 
                    c.category_id,
                    c.name,
                    c.description,
                    c.image,
                    COUNT(mic.item_id) as total_items
                FROM categories c
                LEFT JOIN menu_item_categories mic ON c.category_id = mic.category_id
                GROUP BY c.category_id, c.name, c.description, c.image
                ORDER BY c.name
                OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Error: " . $error['message']);
            return [];
        }

        $categories = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $categories[] = $row;
        }
        oci_free_statement($stmt);

        return $categories;
    }

    public function getFeaturedCategories($limit = 8)
    {
        try {
            $limit = max(1, min(50, (int)$limit));

            $sql = "SELECT 
                    c.category_id,
                    c.name,
                    c.image,
                    COUNT(mic.item_id) as item_count
                FROM categories c
                LEFT JOIN menu_item_categories mic ON c.category_id = mic.category_id
                GROUP BY c.category_id, c.name, c.image
                ORDER BY 
                    -- item_count DESC,
                    -- c.name
                    c.category_id
                FETCH FIRST :limit ROWS ONLY";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':limit', $limit);

            if (!oci_execute($stmt)) {
                $error = oci_error($stmt);
                error_log("Oracle Error: " . $error['message']);
                return [];
            }

            $categories = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $row = $this->processRow($row);
                $categories[] = $row;
            }

            oci_free_statement($stmt);
            return $categories;
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            return [];
        }
    }

    public function getById($categoryId)
    {
        $sql = "SELECT * FROM categories WHERE category_id = :category_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':category_id', $categoryId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $category = oci_fetch_assoc($stmt);
        $category = $this->processRow($category);
        oci_free_statement($stmt);

        return $category;
    }

    public function create($categoryData)
    {
        if (!empty($categoryData['image'])) {
            $sql = "INSERT INTO categories (name, description, image) 
                VALUES (:name, :description, :image) 
                RETURNING category_id INTO :category_id";
        } else {
            $sql = "INSERT INTO categories (name, description) 
                VALUES (:name, :description) 
                RETURNING category_id INTO :category_id";
        }

        $stmt = oci_parse($this->conn, $sql);
        $categoryId = null;

        oci_bind_by_name($stmt, ':name', $categoryData['name']);
        oci_bind_by_name($stmt, ':description', $categoryData['description']);
        oci_bind_by_name($stmt, ':category_id', $categoryId, -1, SQLT_INT);

        if (!empty($categoryData['image'])) {
            oci_bind_by_name($stmt, ':image', $categoryData['image']);
        }

        if (oci_execute($stmt)) {
            return $categoryId;
        }

        return false;
    }

    public function update($categoryId, $categoryData)
    {
        if (!empty($categoryData['categoryData'])) {
            $sql = "UPDATE categories 
                SET name = :name, description = :description, image = :image 
                WHERE category_id = :category_id";
        } else {
            $sql = "UPDATE categories 
                SET name = :name, description = :description 
                WHERE category_id = :category_id";
        }

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':name', $categoryData['name']);
        oci_bind_by_name($stmt, ':description', $categoryData['description']);
        oci_bind_by_name($stmt, ':category_id', $categoryId);

        if (!empty($categoryData['image'])) {
            oci_bind_by_name($stmt, ':image', $categoryData['image']);
        }

        return oci_execute($stmt);
    }

    public function delete($categoryId)
    {
        $sqlDeleteAssoc = "DELETE FROM menu_item_categories WHERE category_id = :category_id";
        $stmt = oci_parse($this->conn, $sqlDeleteAssoc);
        oci_bind_by_name($stmt, ':category_id', $categoryId);
        oci_execute($stmt);

        $sql = "DELETE FROM categories WHERE category_id = :category_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':category_id', $categoryId);

        return oci_execute($stmt);
    }

    public function getImagePath($categoryId)
    {
        $sql = "SELECT image FROM categories WHERE category_id = :category_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':category_id', $categoryId);

        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);
            return $row['IMAGE'] ?? null;
        }

        return null;
    }
}
