<?php
class Env
{
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new Exception(".env file not found at $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);

            $name  = trim($name);
            $value = trim($value);

            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}
