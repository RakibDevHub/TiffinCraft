<?php
class ServiceArea
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
        $sql = "SELECT COUNT(*) FROM service_areas";
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_row($stmt);
        oci_free_statement($stmt);
        return $row[0] ?? 0;
    }

    public function getAllAreaDetails($limit, $offset)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT 
                    sa.area_id,
                    sa.name,
                    sa.city,
                    sa.status,
                    COUNT(ksz.kitchen_id) as total_kitchens
                FROM service_areas sa
                LEFT JOIN kitchen_service_zones ksz ON sa.area_id = ksz.area_id
                GROUP BY sa.area_id, sa.name, sa.city, sa.status
                ORDER BY sa.city, sa.name
                OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Error: " . $error['message']);
            return [];
        }

        $areas = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $areas[] = $row;
        }
        oci_free_statement($stmt);

        return $areas;
    }

    public function getById($areaId)
    {
        $sql = "SELECT * FROM service_areas WHERE area_id = :area_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':area_id', $areaId);

        if (!oci_execute($stmt)) {
            return false;
        }

        $area = oci_fetch_assoc($stmt);
        $area = $this->processRow($area);
        oci_free_statement($stmt);

        return $area;
    }

    public function create($data)
    {
        $sql = "INSERT INTO service_areas (name, city, status) 
                VALUES (:name, :city, :status) 
                RETURNING area_id INTO :area_id";

        $stmt = oci_parse($this->conn, $sql);
        $areaId = null;

        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':city', $data['city']);
        oci_bind_by_name($stmt, ':status', $data['status']);
        oci_bind_by_name($stmt, ':area_id', $areaId, -1, SQLT_INT);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result ? $areaId : false;
    }

    public function update($data)
    {
        $sql = "UPDATE service_areas SET name = :name, city = :city, status = :status 
                WHERE area_id = :area_id";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':city', $data['city']);
        oci_bind_by_name($stmt, ':status', $data['status']);
        oci_bind_by_name($stmt, ':area_id', $data['area_id']);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function delete($areaId)
    {
        $sqlDeleteAssoc = "DELETE FROM kitchen_service_zones WHERE area_id = :area_id";
        $stmt = oci_parse($this->conn, $sqlDeleteAssoc);
        oci_bind_by_name($stmt, ':area_id', $areaId);
        oci_execute($stmt);

        $sql = "DELETE FROM service_areas WHERE area_id = :area_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':area_id', $areaId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function checkUnique($name, $city, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM service_areas WHERE name = :name AND city = :city";
        if ($excludeId) {
            $sql .= " AND area_id != :exclude_id";
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':name', $name);
        oci_bind_by_name($stmt, ':city', $city);

        if ($excludeId) {
            oci_bind_by_name($stmt, ':exclude_id', $excludeId);
        }

        oci_execute($stmt);
        $count = oci_fetch_row($stmt)[0];
        oci_free_statement($stmt);

        return $count == 0;
    }

    public function getAllActiveAreas()
    {
        $sql = "SELECT * FROM service_areas WHERE status = 'active'";
        $stmt = oci_parse($this->conn, $sql);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            error_log("Oracle Error: " . $error['message']);
            return [];
        }

        $areas = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->processRow($row);
            $areas[] = $row;
        }
        oci_free_statement($stmt);

        return $areas;
    }

    public function getKitchenServiceArea($kitchenId)
    {
        $sql =
            "SELECT 
                sa.area_id,
                sa.name as area_name,
                sa.city,
                ksz.delivery_fee,
                ksz.min_order,
                sa.status
            FROM kitchen_service_zones ksz
            JOIN service_areas sa ON ksz.area_id = sa.area_id
            WHERE ksz.kitchen_id = :kitchen_id
            ORDER BY sa.name";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);

        if (!oci_execute($stmt)) {
            return [];
        }

        $serviceAreas = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $serviceAreas[] = $this->processRow($row);
        }

        oci_free_statement($stmt);
        return $serviceAreas;
    }

    public function addKitchenServiceArea($data)
    {
        $sql = "INSERT INTO kitchen_service_zones (kitchen_id, area_id, delivery_fee, min_order) 
            VALUES (:kitchen_id, :area_id, :delivery_fee, :min_order)";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':kitchen_id', $data['kitchen_id']);
        oci_bind_by_name($stmt, ':area_id', $data['area_id']);
        oci_bind_by_name($stmt, ':delivery_fee', $data['delivery_fee']);
        oci_bind_by_name($stmt, ':min_order', $data['min_order']);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function updateKitchenServiceArea($data)
    {
        $sql = "UPDATE kitchen_service_zones 
            SET delivery_fee = :delivery_fee, min_order = :min_order 
            WHERE kitchen_id = :kitchen_id AND area_id = :area_id";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':delivery_fee', $data['delivery_fee']);
        oci_bind_by_name($stmt, ':min_order', $data['min_order']);
        oci_bind_by_name($stmt, ':kitchen_id', $data['kitchen_id']);
        oci_bind_by_name($stmt, ':area_id', $data['area_id']);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function removeKitchenServiceArea($data)
    {
        $sql = "DELETE FROM kitchen_service_zones 
            WHERE kitchen_id = :kitchen_id AND area_id = :area_id";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':kitchen_id', $data['kitchen_id']);
        oci_bind_by_name($stmt, ':area_id', $data['area_id']);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function isAreaAlreadyAdded($kitchenId, $areaId)
    {
        $sql = "SELECT COUNT(*) FROM kitchen_service_zones 
            WHERE kitchen_id = :kitchen_id AND area_id = :area_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':area_id', $areaId);

        oci_execute($stmt);
        $count = oci_fetch_row($stmt)[0];
        oci_free_statement($stmt);

        return $count > 0;
    }

    public function getDeliveryFeeForKitchenArea($kitchenId, $areaId)
    {
        $sql = "SELECT delivery_fee 
            FROM kitchen_service_zones 
            WHERE kitchen_id = :kitchen_id 
            AND area_id = :area_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':kitchen_id', $kitchenId);
        oci_bind_by_name($stmt, ':area_id', $areaId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        $deliveryFee = $row ? $row['DELIVERY_FEE'] : 0;

        oci_free_statement($stmt);
        return $deliveryFee;
    }
}
