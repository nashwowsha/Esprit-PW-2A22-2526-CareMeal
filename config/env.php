<?php

class Env
{
    private static bool $loaded = false;
    private static bool $localLoaded = false;

    private static function loadLocalOverrides(): void
    {
        if (self::$localLoaded) {
            return;
        }
        self::$localLoaded = true;

        $localPath = __DIR__ . '/env.local.php';
        if (!is_file($localPath)) {
            return;
        }

        $local = require $localPath;
        if (!is_array($local)) {
            return;
        }

        foreach ($local as $name => $value) {
            $key = trim((string)$name);
            if ($key === '') {
                continue;
            }

            if (is_bool($value)) {
                $stringValue = $value ? '1' : '0';
            } elseif (is_scalar($value) || $value === null) {
                $stringValue = (string)$value;
            } else {
                continue;
            }

            $_ENV[$key] = $stringValue;
            putenv($key . '=' . $stringValue);
        }
    }

    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $envPath = $path ?? dirname(__DIR__) . '/.env';
        if (!is_file($envPath)) {
            self::loadLocalOverrides();
            self::$loaded = true;
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            self::loadLocalOverrides();
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if ($name !== '') {
                $_ENV[$name] = $value;
                putenv($name . '=' . $value);
            }
        }

        // Allow per-machine PHP config overrides committed as local array.
        self::loadLocalOverrides();

        self::$loaded = true;
    }

    public static function get(string $name, ?string $default = null): ?string
    {
        self::load();
        $value = $_ENV[$name] ?? getenv($name);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string)$value;
    }
}
