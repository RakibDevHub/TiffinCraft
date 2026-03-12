<?php

require_once __DIR__ . '/app.php';
require_once BASE_PATH . '/src/helpers/Logger.php';

class Database
{
    private static $conn;
 
    public static function getConnection()
    {
        if (!self::$conn) {
            $host = $_ENV['DB_HOST'];
            $port = $_ENV['DB_PORT'];
            $service = $_ENV['DB_SERVICE'];
            $user = $_ENV['DB_USER'];
            $pass = $_ENV['DB_PASS'];

            $tns = "(DESCRIPTION =
                        (ADDRESS = (PROTOCOL = TCP)(HOST = $host)(PORT = $port))
                        (CONNECT_DATA = (SERVICE_NAME = $service))
                    )";

            self::$conn = @oci_connect($user, $pass, $tns, 'AL32UTF8');

            if (!self::$conn) {
                $e = oci_error();
                Logger::error('500', "Database connection failed: " . $e['message']);
                throw new Exception("Database connection failed.");
            }
        }
        return self::$conn;
    }
}
