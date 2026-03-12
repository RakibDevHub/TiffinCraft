<?php

class AuthController
{
    private $authModel;

    public function __construct()
    {
        $conn = Database::getConnection();
        $this->authModel = new Auth($conn);
    }

    public function logout()
    {
        Session::destroy();
        header("Location: /login");
        exit;
    }

    public function login()
    {
        if (AuthHelper::isLoggedIn()) {
            $role = Session::get('user_role');
            $this->redirectAfterLogin($role);
        }

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log('POST');
            $this->processLogin($_POST);
        }

        return [
            'viewFile' => BASE_PATH . '/src/views/pages/auth/login.php',
            'title' => 'Login',
        ];
    }

    private function processLogin(array $postData)
    {
        $email = $postData['email'] ?? '';
        $password = $postData['password'] ?? '';
        $csrfToken = $postData['csrf_token'] ?? '';

        if (!CSRF::validateToken($csrfToken)) {
            Session::flash('error', "Invalid request. Please try again.");
            header("Location: /login");
            exit;
        }

        $user = $this->authModel->getByEmail($email);

        if ($user && password_verify($password, $user['PASSWORD_HASH'])) {
            if (strtolower($user['STATUS']) === 'active') {

                if ($this->authModel->isSuspended($user['USER_ID'], $suspension)) {
                    $reason = $suspension['REASON'] ?? 'No reason provided';
                    $until  = $suspension['SUSPENDED_UNTIL']
                        ? date('M j, Y g:i A', strtotime($suspension['SUSPENDED_UNTIL']))
                        : 'Permanent';

                    Session::flash(
                        'error',
                        "Your account is suspended: [$until]. Reason: $reason"
                    );
                    header("Location: /login");
                    exit;
                }

                Session::regenerate();
                Session::set('user_id', $user['USER_ID']);
                Session::set('user_name', $user['NAME']);
                Session::set('user_role', strtolower($user['ROLE']));
                Session::set('user_image', strtolower($user['PROFILE_IMAGE'] ?? ''));
                Session::set('last_activity', time());

                $this->redirectAfterLogin($user['ROLE']);
            } elseif (strtolower($user['STATUS']) === 'pending') {
                Session::flash('warning', "Your account hasen't been verified yet, Please chek your email.");
                header("Location: /login");
                exit;
            } else {
                Session::flash('error', "Your account is not active.");
                header("Location: /login");
                exit;
            }
        }

        Session::flash('error', "Invalid email or password.");
        header("Location: /login");
        exit;
    }

    public function register()
    {
        if (AuthHelper::isLoggedIn()) {
            $role = Session::get('user_role');
            $this->redirectAfterLogin($role);
        }

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processRegister($_POST);
        }

        return [
            'viewFile' => BASE_PATH . '/src/views/pages/auth/register.php',
            'title' => 'Register',
        ];
    }

    private function processRegister(array $postData)
    {
        $name = trim($postData['name'] ?? '');
        $email = filter_var(trim($postData['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = trim($postData['phone'] ?? '');
        $gender = trim($postData['gender'] ?? '');
        $role = strtolower(trim($postData['role'] ?? 'buyer'));
        $password = $postData['password'] ?? '';
        $confirmPassword = $postData['confirm_password'] ?? '';
        $csrfToken = $postData['csrf_token'] ?? '';

        if (!CSRF::validateToken($csrfToken)) {
            Session::flash('error', "Invalid request. Please try again.");
            header("Location: /register");
            exit;
        }

        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            Session::flash('error', "All fields are required.");
            header("Location: /register");
            exit;
        }

        if (!preg_match("/^[a-zA-Z. ]+$/", $name)) {
            Session::flash('error', "Name can only contain letters, spaces, and dots.");
            header("Location: /register");
            exit;
        }

        if (preg_match("/(\.\.| {2,})/", $name)) {
            Session::flash('error', "Name cannot contain consecutive dots or spaces.");
            header("Location: /register");
            exit;
        }

        if (preg_match("/(\.\s|\s\.){2,}/", $name)) {
            Session::flash('error', "Name contains invalid formatting near dots and spaces.");
            header("Location: /register");
            exit;
        }

        if (strlen($name) < 2 || strlen($name) > 50) {
            Session::flash('error', "Name must be between 2 and 50 characters.");
            header("Location: /register");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', "Invalid email format.");
            header("Location: /register");
            exit;
        }

        if (!preg_match("/^01[0-9]{9}$/", $phone)) {
            Session::flash('error', "Phone number must be 11 digits and start with 01.");
            header("Location: /register");
            exit;
        }

        if ($password !== $confirmPassword) {
            Session::flash('error', "Passwords do not match.");
            header("Location: /register");
            exit;
        }

        // if (!preg_match(
        //     "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/",
        //     $password
        // )) {
        //     Session::flash(
        //         'error',
        //         "Password must be at least 8 characters, include 1 uppercase, 1 lowercase, 1 number, and 1 special character."
        //     );
        //     header("Location: /register");
        //     exit;
        // }

        if (!preg_match(
            "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/",
            $password
        )) {
            Session::flash(
                'error',
                "Password must be at least 8 characters, include 1 uppercase, 1 lowercase, and 1 number."
            );
            header("Location: /register");
            exit;
        }

        if ($this->authModel->getByEmail($email)) {
            Session::flash('error', "Email already registered.");
            header("Location: /register");
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hour'));

        $userId = $this->authModel->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'gender' => $gender,
            'role' => $role,
            'password_hash' => $passwordHash,
            'verification_token' => $tokenHash,
            'token_expires_at' => $expiresAt
        ]);

        if (!$userId) {
            Session::flash('error', "Failed to register. Please try again.");
            header("Location: /register");
            exit;
        }

        if (Mailer::sendVerification($email, $token)) {
            Session::flash('success', "Registration successful!. Please check your email to verify your account.");
        } else {
            Session::flash('warning', "Registration successful!. We couldn’t send the verification email. Please request a new one from the login page.");
        }
        header("Location: /register");
        exit;
    }

    public function resendVerification()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->resendVerificationEmail($_POST);
        }
    }

    private function resendVerificationEmail(array $postData)
    {
        $email = filter_var(trim($postData['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $csrfToken = $postData['csrf_token'] ?? '';

        if (!CSRF::validateToken($csrfToken)) {
            Session::flash('error', "Invalid request. Please try again.");
            header("Location: /login");
            exit;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', "Please enter a valid email.");
            header("Location: /login");
            exit;
        }

        $user = $this->authModel->getByEmail($email);

        if (!$user) {
            Session::flash('error', "Account not found.");
            header("Location: /login");
            exit;
        }

        if (strtolower($user['STATUS']) == 'active') {
            Session::flash('info', "Your account is already verified.");
            header("Location: /login");
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hour'));

        $result = $this->authModel->updateToken($user['USER_ID'], $tokenHash, $expiresAt);

        if ($result) {
            if (Mailer::sendVerification($email, $token)) {
                Session::flash('success', "A new verification link has been sent to your email.");
            } else {
                Session::flash('warning', "Couldn’t send verification email. Try again later.");
            }
            header("Location: /login");
            exit;
        }
    }

    public function verifyEmail()
    {
        $email = $_GET['email'] ?? '';
        $token = $_GET['token'] ?? '';

        if (empty($email) || empty($token)) {
            Session::flash('error', "Invalid verification link.");
            header("Location: /login");
            exit;
        }

        $user = $this->authModel->getByEmail($email);

        if (!$user) {
            Session::flash('error', "Account not found.");
            header("Location: /login");
            exit;
        }

        if (strtolower($user['STATUS']) == 'active') {
            Session::flash('info', "Your account is already verified.");
            header("Location: /login");
            exit;
        }

        $tokenHash = hash('sha256', $token);

        if (
            $user['VERIFICATION_TOKEN'] !== $tokenHash ||
            strtotime($user['TOKEN_EXPIRES_FORMATTED']) < time()
        ) {
            Session::flash('error', "Verification link is invalid or expired.");
            header("Location: /login");
            exit;
        }

        $this->authModel->activateUser($user['USER_ID']);

        Session::flash('success', "Email verified successfully! You can now login.");
        header("Location: /login");
        exit;
    }

    public function forgotPassword()
    {
        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleForgotPassword($_POST);
        }

        return [
            'viewFile' => BASE_PATH . '/src/views/pages/auth/forgot_password.php',
            'title' => 'Forgot Password',
        ];
    }

    private function handleForgotPassword(array $postData)
    {
        $email = filter_var(trim($postData['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $csrfToken = $postData['csrf_token'] ?? '';

        if (!CSRF::validateToken($csrfToken)) {
            Session::flash('error', "Invalid request.");
            header("Location: /forgot-password");
            exit;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', "Please enter a valid email.");
            header("Location: /forgot-password");
            exit;
        }

        $user = $this->authModel->getByEmail($email);

        if (!$user) {
            Session::flash('error', "We couldn’t find an account with that email.");
            header("Location: /forgot-password");
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->authModel->updateToken($user['USER_ID'], $tokenHash, $expiresAt);

        if (Mailer::sendPasswordReset($email, $token)) {
            Session::flash('success', "We’ve emailed you a password reset link.");
        } else {
            Session::flash('error', "Could not send email. Try again later.");
        }

        header("Location: /forgot-password");
        exit;
    }

    public function resetPassword()
    {
        $email = urldecode($_GET['email'] ?? '');
        $token = $_GET['token'] ?? '';

        if (empty($email) || empty($token)) {
            Session::flash('error', "Unauthorized. Invalid action.");
            header("Location: /login");
            exit;
        }

        $user = $this->authModel->getByEmail($email);

        if (!$user) {
            Session::flash('error', "Account not found.");
            header("Location: /login");
            exit;
        }

        $tokenHash = hash('sha256', $token);

        if (
            $user['VERIFICATION_TOKEN'] !== $tokenHash ||
            strtotime($user['TOKEN_EXPIRES_FORMATTED']) < time()
        ) {
            Session::flash('error', "Reset link is invalid or expired.");
            header("Location: /forgot-password");
            exit;
        }

        CSRF::generateToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleResetPassword($_POST, $user);
            return;
        }

        return [
            'viewFile' => BASE_PATH . '/src/views/pages/auth/reset_password.php',
            'title' => 'Reset Password',
            'extra' => [
                'email' => $email,
                'token' => $token,
                'formAction' => "/reset-password?email=" . urlencode($email) . "&token=" . urlencode($token)
            ]
        ];
    }

    private function handleResetPassword(array $postData, array $user)
    {
        $email = filter_var(trim($postData['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $postData['password'] ?? '';
        $confirmPassword = $postData['confirm_password'] ?? '';
        $csrfToken = $postData['csrf_token'] ?? '';

        if (!CSRF::validateToken($csrfToken)) {
            Session::flash('error', "Invalid request.");
            header("Location: /reset-password?token=" . urlencode($_GET['token']) . "&email=" . urlencode($email));
            exit;
        }

        if ($password !== $confirmPassword) {
            Session::flash('error', "Passwords do not match.");
            header("Location: /reset-password?token=" . urlencode($_GET['token']) . "&email=" . urlencode($email));
            exit;
        }

        if (!preg_match(
            "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/",
            $password
        )) {
            Session::flash(
                'error',
                "Password must be at least 8 characters, include 1 uppercase, 1 lowercase, 1 number, and 1 special character."
            );
            header("Location: /reset-password?token=" . urlencode($_GET['token']) . "&email=" . urlencode($email));
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $this->authModel->updatePassword($user['USER_ID'], $passwordHash);

        Session::flash('success', "Password reset successfully! You can now login.");
        header("Location: /login");
        exit;
    }

    private function redirectAfterLogin($role)
    {
        switch (strtolower($role)) {
            case 'admin':
                header("Location: /admin/dashboard");
                break;
            case 'seller':
                header("Location: /business/dashboard");
                break;
            default:
                header("Location: /");
        }
        exit;
    }
}
