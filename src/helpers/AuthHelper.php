<?php
class AuthHelper
{
    public static function isLoggedIn($role = null)
    {
        Session::start();

        $userId   = Session::get('user_id');
        $userRole = Session::get('user_role');
        $lastActivity = Session::get('last_activity');

        if (!$userId) return false;

        $sessionTimeouts = [
            'buyer'  => 60 * 60 * 24 * 7,
            'seller' => 60 * 60 * 2,
            'admin'  => 60 * 30
        ];

        $timeout = $sessionTimeouts[$userRole] ?? (60 * 60);

        if ($lastActivity && (time() - $lastActivity) > $timeout) {
            Session::destroy();
            return false;
        }

        Session::set('last_activity', time());

        if ($role) {
            return strtolower($userRole) === strtolower($role);
        }

        return true;
    }
}
