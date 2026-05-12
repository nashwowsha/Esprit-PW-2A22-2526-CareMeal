<?php

require_once __DIR__ . '/env.php';

if (!function_exists('caremeal_app_base_path')) {
    function caremeal_app_base_path(): string
    {
        $forced = trim((string)Env::get('CAREMEAL_BASE_PATH', '/'));
        if ($forced !== '' && $forced !== '/') {
            return '/' . trim($forced, '/');
        }

        $scriptPath = (string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? $_SERVER['REQUEST_URI'] ?? '');
        if ($scriptPath === '') {
            return '';
        }

        $markers = [
            '/View/FrontOffice/',
            '/View/BackOffice/',
            '/admin/',
            '/student/',
            '/partner/',
            '/public/',
            '/api/',
            '/Controller/',
        ];

        foreach ($markers as $marker) {
            $idx = strpos($scriptPath, $marker);
            if ($idx !== false) {
                $base = substr($scriptPath, 0, $idx);
                return $base === '/' ? '' : rtrim($base, '/');
            }
        }

        $dir = str_replace('\\', '/', dirname($scriptPath));
        if ($dir === '' || $dir === '.' || $dir === '/') {
            return '';
        }

        return rtrim($dir, '/');
    }
}

if (!function_exists('caremeal_public_base_url')) {
    function caremeal_public_base_url(): string
    {
        $forced = trim((string)Env::get('CAREMEAL_PUBLIC_BASE_URL', ''));
        if ($forced !== '') {
            return rtrim($forced, '/');
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        $scheme = $isHttps ? 'https' : 'http';
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));

        return $scheme . '://' . $host . caremeal_app_base_path();
    }
}

if (!function_exists('caremeal_path')) {
    function caremeal_path(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        return caremeal_app_base_path() . ($path === '/' ? '' : $path);
    }
}

if (!function_exists('caremeal_url')) {
    function caremeal_url(string $path = ''): string
    {
        if ($path === '') {
            return caremeal_public_base_url();
        }

        return caremeal_public_base_url() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('caremeal_js_public_config')) {
    function caremeal_js_public_config(): array
    {
        return [
            'publicBaseUrl' => caremeal_public_base_url(),
            'basePath' => caremeal_app_base_path(),
        ];
    }
}

if (!function_exists('caremeal_env')) {
    function caremeal_env(string $key, string $default = ''): string
    {
        return (string)Env::get($key, $default);
    }
}

if (!function_exists('caremeal_realtime_public_config')) {
    function caremeal_realtime_public_config(): array
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

if (!function_exists('caremeal_public_url')) {
    function caremeal_public_url(string $path = ''): string
    {
        if ($path === '') {
            return caremeal_public_base_url();
        }

        return caremeal_public_base_url() . '/' . ltrim($path, '/');
    }
}
