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

    /**
     * Priority:
     * 1) GEMINI_MODELS (comma-separated list)
     * 2) GEMINI_MODEL (single model fallback)
     */
    public static function geminiModels(): array
    {
        $modelsRaw = Env::get('GEMINI_MODELS', '');
        $models = [];

        if ($modelsRaw !== '') {
            $parts = explode(',', $modelsRaw);
            foreach ($parts as $part) {
                $m = trim($part);
                if ($m !== '') {
                    $models[] = $m;
                }
            }
        }

        $single = trim(Env::get('GEMINI_MODEL', 'gemini-2.5-flash-lite') ?? '');
        if ($single !== '') {
            $models[] = $single;
        }

        $models = array_values(array_unique($models));
        if (empty($models)) {
            $models[] = 'gemini-2.5-flash-lite';
        }

        return $models;
    }

    public static function xaiApiKey(): string
    {
        return trim(Env::get('XAI_API_KEY', '') ?? '');
    }

    public static function xaiModel(): string
    {
        return trim(Env::get('XAI_MODEL', 'grok-2-latest') ?? 'grok-2-latest');
    }

    /**
     * Priority:
     * 1) XAI_MODELS (comma-separated list)
     * 2) XAI_MODEL (single model fallback)
     */
    public static function xaiModels(): array
    {
        $raw = Env::get('XAI_MODELS', '');
        $models = [];

        if ($raw !== '') {
            $parts = explode(',', $raw);
            foreach ($parts as $part) {
                $m = trim($part);
                if ($m !== '') {
                    $models[] = $m;
                }
            }
        }

        $single = trim(Env::get('XAI_MODEL', 'grok-4.20-reasoning') ?? 'grok-4.20-reasoning');
        if ($single !== '') {
            $models[] = $single;
        }

        $models = array_values(array_unique($models));
        if (empty($models)) {
            $models[] = 'grok-4.20-reasoning';
        }

        return $models;
    }
}
