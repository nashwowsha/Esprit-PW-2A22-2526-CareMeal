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
}
