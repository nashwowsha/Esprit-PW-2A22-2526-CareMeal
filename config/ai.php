<?php
require_once __DIR__ . '/env.php';

class AIConfig
{
    private static function parseCsvEnv(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $parts = explode(',', $raw);
        $out = [];
        foreach ($parts as $part) {
            $value = trim($part);
            if ($value !== '') {
                $out[] = $value;
            }
        }
        return array_values(array_unique($out));
    }

    private static function normalizeModel(string $model): string
    {
        $m = trim(strtolower($model));
        // Accept only Gemini API model ids (ex: gemini-2.5-flash-lite).
        if ($m === '' || preg_match('/^gemini-[a-z0-9._-]+$/', $m) !== 1) {
            return '';
        }
        return $m;
    }

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
        $keys = self::parseCsvEnv((string)Env::get('GEMINI_API_KEYS', ''));

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
        $models = [];
        $parts = self::parseCsvEnv((string)Env::get('GEMINI_MODELS', ''));
        foreach ($parts as $part) {
            $m = self::normalizeModel($part);
            if ($m !== '') {
                $models[] = $m;
            }
        }

        $single = self::normalizeModel((string)(Env::get('GEMINI_MODEL', 'gemini-2.5-flash-lite') ?? ''));
        if ($single !== '') {
            $models[] = $single;
        }

        $models = array_values(array_unique($models));
        if (empty($models)) {
            $models = [
                'gemini-2.5-flash-lite',
                'gemini-2.5-flash',
            ];
        }

        return $models;
    }

    /**
     * Priority:
     * 1) GROQ_API_KEYS (comma-separated list)
     * 2) GROQ_API_KEY (single key fallback)
     */
    public static function groqApiKeys(): array
    {
        $keys = self::parseCsvEnv((string)Env::get('GROQ_API_KEYS', ''));
        $single = trim((string)(Env::get('GROQ_API_KEY', '') ?? ''));
        if ($single !== '') {
            $keys[] = $single;
        }
        $keys = array_values(array_unique($keys));
        if (empty($keys)) {
            throw new RuntimeException('No Groq API key found. Set GROQ_API_KEYS or GROQ_API_KEY in .env.');
        }
        return $keys;
    }

    /**
     * Priority:
     * 1) GROQ_MODELS (comma-separated list)
     * 2) GROQ_MODEL (single model fallback)
     */
    public static function groqModels(): array
    {
        $models = self::parseCsvEnv((string)Env::get('GROQ_MODELS', ''));
        $single = trim((string)(Env::get('GROQ_MODEL', 'llama-3.1-8b-instant') ?? 'llama-3.1-8b-instant'));
        if ($single !== '') {
            $models[] = $single;
        }
        $models = array_values(array_unique($models));
        if (empty($models)) {
            $models = ['llama-3.1-8b-instant'];
        }
        return $models;
    }

    /**
     * ADMIN_AI_PRIMARY: 'gemini' (default) or 'groq'
     */
    public static function adminPrimaryProvider(): string
    {
        $primary = strtolower(trim((string)(Env::get('ADMIN_AI_PRIMARY', 'gemini') ?? 'gemini')));
        return $primary === 'groq' ? 'groq' : 'gemini';
    }
}
