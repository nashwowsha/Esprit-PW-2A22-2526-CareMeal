<?php

if (!function_exists('caremeal_load_local_env')) {
    function caremeal_load_local_env()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $envFile = __DIR__ . '/env.local.php';
        if (!is_file($envFile)) {
            return;
        }

        $values = require $envFile;
        if (!is_array($values)) {
            return;
        }

        foreach ($values as $key => $value) {
            $key = trim((string)$key);
            if ($key === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $value = (string)$value;
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

caremeal_load_local_env();

if (!function_exists('caremeal_env')) {
    function caremeal_env($key, $default = '')
    {
        caremeal_load_local_env();
        $value = getenv((string)$key);
        if ($value === false) {
            return $default;
        }
        $value = trim((string)$value);
        return $value === '' ? $default : $value;
    }
}

if (!function_exists('caremeal_detect_base_path')) {
    function caremeal_detect_base_path()
    {
        $forced = trim((string)caremeal_env('CAREMEAL_BASE_PATH', ''));
        if ($forced !== '') {
            if ($forced === '/') {
                return '';
            }
            return '/' . trim($forced, '/');
        }

        $scriptName = trim((string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName === '') {
            return '/caremeal';
        }

        $parts = explode('/', trim($scriptName, '/'));
        if (count($parts) <= 1) {
            return '';
        }

        return '/' . trim((string)$parts[0], '/');
    }
}

if (!function_exists('caremeal_public_base_url')) {
    function caremeal_public_base_url()
    {
        $forced = trim((string)caremeal_env('CAREMEAL_PUBLIC_BASE_URL', ''));
        if ($forced !== '') {
            return rtrim($forced, '/');
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $isHttps ? 'https' : 'http';
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $basePath = caremeal_detect_base_path();

        return $scheme . '://' . $host . $basePath;
    }
}

if (!function_exists('caremeal_public_url')) {
    function caremeal_public_url($path)
    {
        $path = '/' . ltrim((string)$path, '/');
        return caremeal_public_base_url() . $path;
    }
}

if (!function_exists('caremeal_realtime_public_config')) {
    function caremeal_realtime_public_config()
    {
        $enabled = strtolower((string)caremeal_env('CAREMEAL_PUSHER_ENABLED', '0'));
        $enabled = in_array($enabled, ['1', 'true', 'yes', 'on'], true);

        $provider = 'pusher';
        $key = trim((string)caremeal_env('CAREMEAL_PUSHER_KEY', ''));
        $cluster = trim((string)caremeal_env('CAREMEAL_PUSHER_CLUSTER', ''));

        if (!$enabled || $key === '' || $cluster === '') {
            return [
                'enabled' => false,
                'provider' => $provider,
                'key' => '',
                'cluster' => '',
            ];
        }

        return [
            'enabled' => true,
            'provider' => $provider,
            'key' => $key,
            'cluster' => $cluster,
        ];
    }
}
