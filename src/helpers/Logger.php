<?php
class Logger
{
    public static function message($level, $message)
    {
        $logDir  = BASE_PATH . '/logs';
        $logFile = $logDir . '/app.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $time = date('Y-m-d H:i:s');
        $log  = "[$time] $level: $message\n";

        file_put_contents($logFile, $log, FILE_APPEND);
    }

    public static function error($code, $message)
    {
        $logDir  = BASE_PATH . '/logs';
        $logFile = $logDir . '/error.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $time = date('Y-m-d H:i:s');
        $log  = "[$time] Error $code: $message\n";

        file_put_contents($logFile, $log, FILE_APPEND);
    }
}


