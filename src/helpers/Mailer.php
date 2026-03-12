<?php

class Mailer
{

    public static function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $headers = [
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
            'Reply-To: ' . MAIL_FROM_ADDRESS,
            'X-Mailer: PHP/' . phpversion()
        ];

        if ($isHtml) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }

        $success = mail($to, $subject, $body, implode("\r\n", $headers));

        if (!$success) {
            $error = error_get_last();
            $msg = $error ? $error['message'] : 'Unknown mail error';
            file_put_contents(
                BASE_PATH . '/logs/error.log',
                "[" . date('Y-m-d H:i:s') . "] Mail Error: $msg (to: $to)\n",
                FILE_APPEND
            );
        }

        return $success;
    }

    public static function sendVerification(string $to, string $token): bool
    {
        $verificationUrl = BASE_URL . "/verify?token=" . urlencode($token) . "&email=" . urlencode($to);

        $subject = "Verify Your TiffinCraft Account";
        $body = <<<HTML
        <html>
        <body style="font-family: Arial, sans-serif;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">
                <h2 style="color: #2c3e50;">TiffinCraft Account Verification</h2>
                <p>Please click the button below to verify your account:</p>
                <a href="$verificationUrl" 
                   style="display: inline-block; padding: 10px 20px; background: #3498db; 
                          color: white; text-decoration: none; border-radius: 4px;">
                   Verify My Account
                </a>
                <p style="margin-top: 20px; color: #7f8c8d;">
                    Or copy this URL into your browser:<br>
                    <code>$verificationUrl</code>
                </p>
            </div>
        </body>
        </html>
        HTML;

        return self::send($to, $subject, $body, true);
    }

    public static function sendPassword(string $to, string $password)
    {

        $subject = "Verify Your TiffinCraft Account";
        $body = <<<HTML
        <html>
        <body style="font-family: Arial, sans-serif;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">
                <h2 style="color: #2c3e50;">TiffinCraft Account Verification</h2>
                <p>Please use the password below to login to your account:</p>
                <p style="display: inline-block; padding: 10px 20px; background: #3498db; 
                          color: white; text-decoration: none; border-radius: 4px;" >
                   <code>$password</code>
                </p>
            </div>
        </body>
        </html>
        HTML;

        return self::send($to, $subject, $body, true);
    }

    public static function sendPasswordReset(string $to, string $token): bool
    {
        $resetUrl = BASE_URL . "/reset-password?token=" . urlencode($token) . "&email=" . urlencode($to);

        $subject = "Reset Your TiffinCraft Password";
        $body = <<<HTML
        <html>
        <body style="font-family: Arial, sans-serif;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">
                <h2 style="color: #2c3e50;">Password Reset Request</h2>
                <p>Please click the button below to reset your password:</p>
                <a href="$resetUrl" style="display:inline-block;padding:10px 20px;background:#3498db;color:white;text-decoration:none;border-radius:4px;">
                    Reset My Password
                </a>
                <p style="margin-top:20px;color:#7f8c8d;">
                    Or copy this URL into your browser:<br>
                    <code>$resetUrl</code>
                </p>
            </div>
        </body>
        </html>
        HTML;

        return self::send($to, $subject, $body, true);
    }
}
