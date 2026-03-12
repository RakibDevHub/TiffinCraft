<?php

class Auth
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

    public function getById($id)
    {
        $sql = "SELECT * FROM users
            WHERE user_id = :user_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $id);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        $row = $this->processRow($row);
        oci_free_statement($stmt);
        return $row ?: null;
    }

    public function getByEmail($email)
    {
        $sql = "SELECT u.*, TO_CHAR(token_expires_at, 'YYYY-MM-DD HH24:MI:SS') as token_expires_formatted
            FROM users u 
            WHERE email = :email";
            
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':email', $email);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        $row = $this->processRow($row);
        oci_free_statement($stmt);
        return $row ?: null;
    }

    public function create($data)
    {
        $sql = "INSERT INTO users (name, email, phone, gender, role, password_hash, status, verification_token, token_expires_at, created_at)
            VALUES (:name, :email, :phone, :gender, :role, :password_hash, 'pending', :token, TO_TIMESTAMP(:expires_at, 'YYYY-MM-DD HH24:MI:SS'), SYSTIMESTAMP)
            RETURNING user_id INTO :user_id";

        $userId = null;
        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':email', $data['email']);
        oci_bind_by_name($stmt, ':phone', $data['phone']);
        oci_bind_by_name($stmt, ':gender', $data['gender']);
        oci_bind_by_name($stmt, ':role', $data['role']);
        oci_bind_by_name($stmt, ':password_hash', $data['password_hash']);
        oci_bind_by_name($stmt, ':token', $data['verification_token']);
        oci_bind_by_name($stmt, ':expires_at', $data['token_expires_at']);
        oci_bind_by_name($stmt, ':user_id', $userId, 32);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result ? $userId : false;
    }

    public function createAdmin($data)
    {
        $sql = "INSERT INTO users (name, email, phone, password_hash, role, status) 
            VALUES (:name, :email, :phone, :password, 'admin', 'active') 
            RETURNING user_id INTO :user_id";

        $userId = null;
        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':email', $data['email']);
        oci_bind_by_name($stmt, ':phone', $data['phone']);
        oci_bind_by_name($stmt, ':password', $data['password']);
        oci_bind_by_name($stmt, ':user_id', $userId, 32);

        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);

        oci_free_statement($stmt);

        return $result ? $userId : false;
    }

    public function activateUser($userId)
    {
        $sql = "UPDATE users
            SET status = 'active', verification_token = NULL, token_expires_at = NULL
            WHERE user_id = :user_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function updatePassword($userId, $passwordHash): bool
    {
        $sql = "UPDATE users 
            SET password_hash = :password_hash, verification_token = NULL, token_expires_at = NULL
            WHERE user_id = :user_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':password_hash', $passwordHash);
        oci_bind_by_name($stmt, ':user_id', $userId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function updateToken($userId, $tokenHash, $expiresAt): bool
    {
        $sql = "UPDATE users 
                SET verification_token = :token, token_expires_at = TO_TIMESTAMP(:expires_at, 'YYYY-MM-DD HH24:MI:SS')
                WHERE user_id = :user_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':token', $tokenHash);
        oci_bind_by_name($stmt, ':expires_at', $expiresAt);
        oci_bind_by_name($stmt, ':user_id', $userId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function addSuspension($data)
    {
        $sql = "INSERT INTO suspensions 
            (reference_id, reference_type, reason, suspended_until, created_by, status)
            VALUES (:reference_id, :reference_type, :reason, "
            . ($data['end_date'] ? "TO_TIMESTAMP(:end_date, 'YYYY-MM-DD HH24:MI:SS')" : "NULL") .
            ", :admin_id, 'active')";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':reference_id', $data['id']);
        oci_bind_by_name($stmt, ':reference_type', $data['type']);
        oci_bind_by_name($stmt, ':reason', $data['reason']);
        if ($data['end_date']) {
            oci_bind_by_name($stmt, ':end_date', $data['end_date']);
        }
        oci_bind_by_name($stmt, ':admin_id', $data['admin_id']);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function delete($userId)
    {
        $sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function isSuspended($userId, &$suspension = null)
    {
        $sql = "SELECT 
                    TO_CHAR(suspended_until, 'YYYY-MM-DD HH24:MI:SS') AS suspended_until,
                    status, 
                    reason
                FROM suspensions 
                WHERE reference_id = :user_id AND reference_type = 'USER' AND status = 'active'
                ORDER BY suspended_at DESC FETCH FIRST 1 ROWS ONLY";


        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        $row = $this->processRow($row);

        if (!$row) {
            return false;
        }

        if ($row['SUSPENDED_UNTIL'] && strtotime($row['SUSPENDED_UNTIL']) < time()) {
            $this->liftSuspension($userId);
            return false;
        }

        $suspension = $row;
        return true;
    }

    public function liftSuspension($data)
    {
        $sql = "UPDATE suspensions 
            SET status = 'lifted' 
            WHERE reference_id = :reference_id AND reference_type = :reference_type AND status = 'active'";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':reference_id', $data['id']);
        oci_bind_by_name($stmt, ':reference_type', $data['type']);

        $result = oci_execute($stmt);
        oci_free_statement($stmt);

        return $result;
    }

    public function updateProfile($userId, $data)
    {
        $sql = "UPDATE users SET 
            name = :name, 
            phone = :phone, 
            gender = :gender, 
            " . ($data['profile_image'] ? "profile_image = :profile_image, " : "") . "
            updated_at = SYSTIMESTAMP 
            WHERE user_id = :user_id";

        $stmt = oci_parse($this->conn, $sql);

        oci_bind_by_name($stmt, ':name', $data['name']);
        oci_bind_by_name($stmt, ':phone', $data['phone']);
        oci_bind_by_name($stmt, ':gender', $data['gender']);
        oci_bind_by_name($stmt, ':user_id', $userId);

        if ($data['profile_image']) {
            oci_bind_by_name($stmt, ':profile_image', $data['profile_image']);
        }

        return oci_execute($stmt);
    }

    public function updateEmail($userId, $newEmail)
    {
        $sql = "UPDATE users SET email = :email, updated_at = SYSTIMESTAMP WHERE user_id = :user_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':email', $newEmail);
        oci_bind_by_name($stmt, ':user_id', $userId);

        return oci_execute($stmt);
    }
}
