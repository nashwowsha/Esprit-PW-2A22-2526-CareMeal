<?php
require_once __DIR__ . '/env.php';

class AIConfig
{
    public static function geminiApiKey(): string
    {
        $key = Env::get('GEMINI_API_KEY', '');
        if ($key === '') {
            throw new RuntimeException('GEMINI_API_KEY is missing. Add it to your local .env file.');
        }
        return $key;
    }

    /**
     * Priority:
     * 1) GEMINI_API_KEYS (comma-separated list)
     * 2) GEMINI_API_KEY (single key fallback)
     */
    public static function geminiApiKeys(): array
    {
        $keysRaw = Env::get('GEMINI_API_KEYS', '');
        $keys = [];

        if ($keysRaw !== '') {
            $parts = explode(',', $keysRaw);
            foreach ($parts as $part) {
                $k = trim($part);
                if ($k !== '') {
                    $keys[] = $k;
                }
            }
        }

        $single = trim(Env::get('GEMINI_API_KEY', '') ?? '');
        if ($single !== '') {
            $keys[] = $single;
        }

        $keys = array_values(array_unique($keys));
        if (empty($keys)) {
            throw new RuntimeException('No Gemini API key found. Set GEMINI_API_KEYS or GEMINI_API_KEY in .env.');
        }

        return $keys;
    }
}
