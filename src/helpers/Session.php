<?php
class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key)
    {
        return $_SESSION[$key] ?? null;
    }

    public static function remove($key){
        unset($_SESSION[$key]);
    }

    public static function destroy()
    {
        session_destroy();
    }

    public static function exists()
    {
        return session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION);
    }

    public static function regenerate()
    {
        session_regenerate_id(true);
    }

    public static function flash($key, $message = null)
    {
        self::start();

        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
        } else {
            if (isset($_SESSION['flash'][$key])) {
                $msg = $_SESSION['flash'][$key];
                unset($_SESSION['flash'][$key]);
                return $msg;
            }
        }
        return null;
    }
}
