<?php
class CSRF
{
    public static function generateToken()
    {
        Session::start();
        if (!Session::get('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }

    public static function validateToken($token)
    {
        Session::start();
        $storedToken = Session::get('csrf_token') ?? null;
        $isValid = $storedToken && hash_equals($storedToken, $token);

        self::refreshToken();

        return $isValid;
    }

    public static function refreshToken()
    {
        Session::start();
        Session::set('csrf_token', bin2hex(random_bytes(32)));
        return Session::get('csrf_token');
    }
}
