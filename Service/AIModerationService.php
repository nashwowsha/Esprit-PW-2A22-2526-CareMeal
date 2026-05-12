<?php

class AIModerationService
{
    private array $config;
    private string $logFilePath;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? $this->loadConfig();
        $this->logFilePath = dirname(__DIR__) . '/logs/ai_moderation.log';
    }

    public function moderateText(string $text, string $contentType = 'publication'): array
    {
        $text = trim($text);
        $provider = (string)($this->config['provider'] ?? 'gemini');
        $logType = $this->normalizeContentType($contentType);

        if ($text === '') {
            return $this->buildDecision(true, 'low', 'Contenu texte vide.', 'unknown', $provider, 'ok', null, null);
        }

        if (!$this->isEnabled()) {
            $decision = $this->buildUnavailableDecision($provider, null, 'moderation_disabled');
            $this->writeModerationLog($logType, null, false, $decision['risk'], $decision['reason'], 'Moderation desactivee.');
            return $decision;
        }

        if (!$this->hasApiKey()) {
            $decision = $this->buildUnavailableDecision($provider, null, 'missing_api_key');
            $this->writeModerationLog($logType, null, false, $decision['risk'], $decision['reason'], 'Cle API manquante ou invalide.');
            return $decision;
        }

        if (strtolower($provider) !== 'gemini') {
            $decision = $this->buildUnavailableDecision($provider, null, 'unsupported_provider');
            $this->writeModerationLog($logType, null, false, $decision['risk'], $decision['reason'], 'Provider IA non supporte.');
            return $decision;
        }

        try {
            $apiResult = $this->callGeminiModeration($text);
            $parsed = $this->extractModerationJson($apiResult['model_text']);
            if ($parsed === null) {
                throw new ModerationUnavailableException(
                    'Reponse Gemini JSON invalide.',
                    'invalid_json',
                    $apiResult['http_status']
                );
            }

            $decision = $this->normalizeDecision($parsed, $provider, $apiResult['http_status']);
            if ($decision === null) {
                throw new ModerationUnavailableException(
                    'Reponse Gemini incomplete ou non conforme.',
                    'invalid_schema',
                    $apiResult['http_status']
                );
            }

            $this->writeModerationLog(
                $logType,
                $apiResult['http_status'],
                (bool)$decision['allowed'],
                (string)$decision['risk'],
                (string)$decision['reason'],
                null
            );

            return $decision;
        } catch (ModerationUnavailableException $e) {
            $decision = $this->buildUnavailableDecision($provider, $e->getHttpStatus(), $e->getErrorCode());
            $this->writeModerationLog($logType, $e->getHttpStatus(), false, $decision['risk'], $decision['reason'], $e->getMessage());
            return $decision;
        } catch (Throwable $e) {
            $decision = $this->buildUnavailableDecision($provider, null, 'service_error');
            $this->writeModerationLog($logType, null, false, $decision['risk'], $decision['reason'], $e->getMessage());
            return $decision;
        }
    }

    public function correctText(string $text): array
    {
        $text = trim($text);
        $provider = (string)($this->config['provider'] ?? 'gemini');

        if ($text === '') {
            return [
                'success' => true,
                'corrected_text' => '',
                'provider' => $provider,
                'status' => 'ok'
            ];
        }

        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Correction IA indisponible. Veuillez reessayer.',
                'provider' => $provider,
                'error_code' => 'moderation_disabled'
            ];
        }

        if (!$this->hasApiKey()) {
            return [
                'success' => false,
                'message' => 'Correction IA indisponible. Veuillez reessayer.',
                'provider' => $provider,
                'error_code' => 'missing_api_key'
            ];
        }

        if (strtolower($provider) !== 'gemini') {
            return [
                'success' => false,
                'message' => 'Correction IA indisponible. Veuillez reessayer.',
                'provider' => $provider,
                'error_code' => 'unsupported_provider'
            ];
        }

        try {
            $apiResult = $this->callGeminiTextCorrection($text);
            $corrected = $this->finalizeCorrectedText((string)($apiResult['model_text'] ?? ''));

            if ($corrected === '') {
                throw new ModerationUnavailableException(
                    'Texte corrige vide.',
                    'empty_candidate',
                    $apiResult['http_status'] ?? null
                );
            }

            return [
                'success' => true,
                'corrected_text' => $corrected,
                'provider' => $provider,
                'status' => 'ok',
                'http_status' => $apiResult['http_status'] ?? null
            ];
        } catch (ModerationUnavailableException $e) {
            $errorCode = $e->getErrorCode();
            $httpStatus = $e->getHttpStatus();
            return [
                'success' => false,
                'message' => $this->buildAIErrorMessage('gemini', $errorCode, $httpStatus, $e->getMessage()),
                'provider' => $provider,
                'error_code' => $errorCode,
                'http_status' => $httpStatus
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $this->buildAIErrorMessage('gemini', 'service_error', null, $e->getMessage()),
                'provider' => $provider,
                'error_code' => 'service_error'
            ];
        }
    }

    private function loadConfig(): array
    {
        $defaults = [
            'provider' => 'gemini',
            'api_key' => '',
            'api_keys' => [],
            'enabled' => false,
            'model' => 'gemini-2.5-flash-lite',
            'timeout' => 25,
            'retry_attempts' => 3,
            'retry_delay_ms' => 450,
            'stt_provider' => 'groq',
            'stt_enabled' => true,
            'stt_model' => 'whisper-large-v3-turbo',
            'stt_timeout' => 35,
            'groq_stt_api_key' => ''
        ];

        // Optional dedicated publication AI config (array return).
        $publicationConfigPath = dirname(__DIR__) . '/config/ai.publications.php';
        if (is_file($publicationConfigPath)) {
            $loaded = include $publicationConfigPath;
            if (is_array($loaded)) {
                return array_merge($defaults, $loaded);
            }
        }

        // Compatibility with legacy array-based config.
        $legacyConfigPath = dirname(__DIR__) . '/config/ai.php';
        if (is_file($legacyConfigPath)) {
            $loaded = include $legacyConfigPath;
            if (is_array($loaded)) {
                return array_merge($defaults, $loaded);
            }
        }

        // Bridge to this repository's class-based AI config.
        if (class_exists('AIConfig')) {
            $geminiKeys = [];
            $geminiModels = [];
            $groqKeys = [];

            try {
                $geminiKeys = AIConfig::geminiApiKeys();
            } catch (Throwable $ignored) {
            }

            try {
                $geminiModels = AIConfig::geminiModels();
            } catch (Throwable $ignored) {
            }

            try {
                $groqKeys = AIConfig::groqApiKeys();
            } catch (Throwable $ignored) {
            }

            $resolvedModel = trim((string)($geminiModels[0] ?? ''));
            if ($resolvedModel === '') {
                $resolvedModel = (string)$defaults['model'];
            }

            return array_merge($defaults, [
                'provider' => 'gemini',
                'enabled' => !empty($geminiKeys),
                'api_key' => (string)($geminiKeys[0] ?? ''),
                'api_keys' => $geminiKeys,
                'model' => $resolvedModel,
                'stt_provider' => 'groq',
                'stt_enabled' => !empty($groqKeys),
                'groq_stt_api_key' => (string)($groqKeys[0] ?? '')
            ]);
        }

        return $defaults;
    }

    private function isEnabled(): bool
    {
        return (bool)($this->config['enabled'] ?? false);
    }

    public function transcribeAudioFile(string $audioPath, string $language = 'fr'): array
    {
        $provider = strtolower(trim((string)($this->config['stt_provider'] ?? 'groq')));
        if ($provider !== 'groq') {
            return [
                'success' => false,
                'message' => 'STT provider non supporte.',
                'error_code' => 'unsupported_provider'
            ];
        }

        if (!(bool)($this->config['stt_enabled'] ?? true)) {
            return [
                'success' => false,
                'message' => 'Transcription vocale indisponible.',
                'error_code' => 'stt_disabled'
            ];
        }

        $apiKey = $this->getGroqSttApiKey();
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'Cle STT manquante.',
                'error_code' => 'missing_stt_api_key'
            ];
        }

        if (!is_file($audioPath) || !is_readable($audioPath)) {
            return [
                'success' => false,
                'message' => 'Fichier audio invalide.',
                'error_code' => 'invalid_audio_file'
            ];
        }

        try {
            $result = $this->callGroqTranscription($audioPath, $apiKey, $language);
            return [
                'success' => true,
                'text' => $result['text'],
                'provider' => 'groq',
                'http_status' => $result['http_status'] ?? null
            ];
        } catch (ModerationUnavailableException $e) {
            $errorCode = $e->getErrorCode();
            $httpStatus = $e->getHttpStatus();
            return [
                'success' => false,
                'message' => $this->buildAIErrorMessage('groq', $errorCode, $httpStatus, $e->getMessage()),
                'error_code' => $errorCode,
                'http_status' => $httpStatus
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $this->buildAIErrorMessage('groq', 'service_error', null, $e->getMessage()),
                'error_code' => 'service_error'
            ];
        }
    }

    private function hasApiKey(): bool
    {
        return count($this->getApiKeyPool()) > 0;
    }

    private function callGeminiModeration(string $text): array
    {
        $configuredModel = trim((string)($this->config['model'] ?? 'gemini-2.5-flash-lite'));
        $timeout = $this->getTimeoutSeconds();

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $this->buildPrompt($text)]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0,
                'responseMimeType' => 'application/json',
                'maxOutputTokens' => 180
            ]
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            throw new ModerationUnavailableException('Payload JSON invalide.', 'invalid_payload');
        }

        $modelName = $this->normalizeModelName($configuredModel);
        if ($modelName === '') {
            $modelName = 'gemini-2.5-flash-lite';
        }

        return $this->executeGeminiRequest($modelName, $timeout, $jsonPayload);
    }

    private function callGeminiTextCorrection(string $text): array
    {
        $configuredModel = trim((string)($this->config['model'] ?? 'gemini-2.5-flash-lite'));
        $timeout = $this->getTimeoutSeconds();

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $this->buildTextCorrectionPrompt($text)]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => 500
            ]
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            throw new ModerationUnavailableException('Payload JSON invalide.', 'invalid_payload');
        }

        $modelName = $this->normalizeModelName($configuredModel);
        if ($modelName === '') {
            $modelName = 'gemini-2.5-flash-lite';
        }

        return $this->executeGeminiRequest($modelName, $timeout, $jsonPayload);
    }

    private function executeGeminiRequest(string $modelName, int $timeout, string $jsonPayload): array
    {
        $apiKeys = $this->getApiKeyPool();
        if (empty($apiKeys)) {
            throw new ModerationUnavailableException('Cle API manquante ou invalide.', 'missing_api_key');
        }

        $maxAttempts = $this->getRetryAttempts();
        $baseDelayMs = $this->getRetryDelayMs();
        $lastException = null;

        foreach ($apiKeys as $apiKey) {
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    return $this->executeGeminiRequestOnce($modelName, $apiKey, $timeout, $jsonPayload);
                } catch (ModerationUnavailableException $e) {
                    $lastException = $e;

                    $errorCode = $e->getErrorCode();
                    $switchKeyNow = in_array($errorCode, ['quota_exceeded', 'invalid_api_key'], true);
                    if ($switchKeyNow) {
                        break;
                    }

                    $canRetry = $this->isRetryableGeminiError($errorCode, $e->getHttpStatus());
                    if (!$canRetry || $attempt >= $maxAttempts) {
                        break;
                    }

                    $waitMs = $baseDelayMs * (2 ** ($attempt - 1));
                    $jitterMs = random_int(0, 120);
                    usleep(($waitMs + $jitterMs) * 1000);
                }
            }
        }

        if ($lastException instanceof ModerationUnavailableException) {
            throw $lastException;
        }

        throw new ModerationUnavailableException('Erreur IA inconnue.', 'service_error');
    }

    private function getApiKeyPool(): array
    {
        $pool = [];

        $primary = trim((string)($this->config['api_key'] ?? ''));
        if ($this->isUsableApiKey($primary)) {
            $pool[$primary] = true;
        }

        $extra = $this->config['api_keys'] ?? [];
        if (is_array($extra)) {
            foreach ($extra as $candidate) {
                $key = trim((string)$candidate);
                if ($this->isUsableApiKey($key)) {
                    $pool[$key] = true;
                }
            }
        }

        return array_keys($pool);
    }

    private function isUsableApiKey(string $key): bool
    {
        if ($key === '') return false;
        if (stripos($key, 'PUT_YOUR_') !== false) return false;
        return true;
    }

    private function getGroqSttApiKey(): string
    {
        $direct = trim((string)($this->config['groq_stt_api_key'] ?? ''));
        if ($this->isUsableApiKey($direct)) {
            return $direct;
        }

        $fallback = trim((string)($this->config['api_key'] ?? ''));
        if ($this->isUsableApiKey($fallback)) {
            return $fallback;
        }

        return '';
    }

    private function callGroqTranscription(string $audioPath, string $apiKey, string $language = 'fr'): array
    {
        if (!function_exists('curl_init')) {
            throw new ModerationUnavailableException('cURL non disponible.', 'curl_unavailable');
        }

        $model = trim((string)($this->config['stt_model'] ?? 'whisper-large-v3-turbo'));
        if ($model === '') {
            $model = 'whisper-large-v3-turbo';
        }

        $timeout = (int)($this->config['stt_timeout'] ?? 35);
        if ($timeout < 8) $timeout = 8;
        if ($timeout > 90) $timeout = 90;

        $mimeType = function_exists('mime_content_type') ? (string)(mime_content_type($audioPath) ?: '') : '';
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        $file = new CURLFile($audioPath, $mimeType, basename($audioPath));

        $postFields = [
            'file' => $file,
            'model' => $model,
            'language' => trim($language) !== '' ? trim($language) : 'fr',
            'response_format' => 'json',
            'temperature' => '0'
        ];

        $ch = curl_init('https://api.groq.com/openai/v1/audio/transcriptions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min($timeout, 10)
        ]);

        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new ModerationUnavailableException('Erreur curl: ' . $error, 'curl_error');
        }

        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            throw new ModerationUnavailableException('Reponse STT non JSON.', 'invalid_api_response', $httpCode ?: null);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $apiError = trim((string)($decoded['error']['message'] ?? ''));
            if ($apiError === '') {
                $apiError = 'HTTP STT ' . $httpCode;
            }
            $errorCode = 'http_error';
            if ($httpCode === 429) {
                $errorCode = 'quota_exceeded';
            } elseif ($httpCode === 401 || $httpCode === 403) {
                $errorCode = 'invalid_api_key';
            }

            throw new ModerationUnavailableException($apiError, $errorCode, $httpCode);
        }

        $text = trim((string)($decoded['text'] ?? ''));
        if ($text === '') {
            throw new ModerationUnavailableException('Texte STT vide.', 'empty_candidate', $httpCode);
        }

        return [
            'text' => $text,
            'http_status' => $httpCode
        ];
    }

    private function executeGeminiRequestOnce(string $modelName, string $apiKey, int $timeout, string $jsonPayload): array
    {
        if (!function_exists('curl_init')) {
            throw new ModerationUnavailableException('cURL non disponible.', 'curl_unavailable');
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($modelName),
            rawurlencode($apiKey)
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min($timeout, 8)
        ]);

        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new ModerationUnavailableException('Erreur curl: ' . $error, 'curl_error');
        }

        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            throw new ModerationUnavailableException('Reponse Gemini non JSON.', 'invalid_api_response', $httpCode ?: null);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $apiError = trim((string)($decoded['error']['message'] ?? ''));
            if ($apiError === '') {
                $apiError = 'HTTP Gemini ' . $httpCode;
            }
            $errorCode = 'http_error';
            if ($httpCode === 429) {
                $errorCode = 'quota_exceeded';
            } elseif (
                $httpCode === 400 &&
                stripos($apiError, 'API key not valid') !== false
            ) {
                $errorCode = 'invalid_api_key';
            } elseif ($httpCode === 401 || $httpCode === 403) {
                $errorCode = 'invalid_api_key';
            }

            throw new ModerationUnavailableException($apiError, $errorCode, $httpCode);
        }

        $candidateText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($candidateText) || trim($candidateText) === '') {
            $blockReason = trim((string)($decoded['promptFeedback']['blockReason'] ?? ''));
            $message = $blockReason !== '' ? 'Prompt bloque: ' . $blockReason : 'Texte de moderation vide.';
            throw new ModerationUnavailableException($message, 'empty_candidate', $httpCode);
        }

        return [
            'http_status' => $httpCode,
            'model_text' => $candidateText
        ];
    }

    private function getTimeoutSeconds(): int
    {
        $timeout = (int)($this->config['timeout'] ?? 25);
        if ($timeout < 8) return 8;
        if ($timeout > 60) return 60;
        return $timeout;
    }

    private function getRetryAttempts(): int
    {
        $attempts = (int)($this->config['retry_attempts'] ?? 3);
        if ($attempts < 1) return 1;
        if ($attempts > 5) return 5;
        return $attempts;
    }

    private function getRetryDelayMs(): int
    {
        $delay = (int)($this->config['retry_delay_ms'] ?? 450);
        if ($delay < 100) return 100;
        if ($delay > 3000) return 3000;
        return $delay;
    }

    private function isRetryableGeminiError(string $errorCode, ?int $httpStatus): bool
    {
        if (in_array($errorCode, ['curl_error', 'http_error'], true)) {
            return true;
        }

        if ($httpStatus !== null && in_array($httpStatus, [408, 429, 500, 502, 503, 504], true)) {
            return true;
        }

        return false;
    }

    private function buildAIErrorMessage(string $provider, string $errorCode, ?int $httpStatus, string $rawMessage = ''): string
    {
        $providerLabel = strtolower(trim($provider)) === 'groq' ? 'API Groq' : 'API Gemini';
        $raw = strtolower(trim($rawMessage));

        if ($errorCode === 'quota_exceeded' || $httpStatus === 429) {
            return 'Quota atteint. Veuillez reessayer plus tard.';
        }

        if ($this->looksLikeNetworkOrDnsIssue($raw, $errorCode)) {
            return 'Probleme reseau ou DNS. Verifiez votre connexion puis reessayez.';
        }

        if ($httpStatus !== null && $httpStatus >= 500) {
            return $providerLabel . ' indisponible pour le moment. Veuillez reessayer.';
        }

        if ($errorCode === 'invalid_api_key' || $httpStatus === 401 || $httpStatus === 403) {
            return 'Configuration API invalide. Contactez l administrateur.';
        }

        return $providerLabel . ' indisponible. Veuillez reessayer.';
    }

    private function looksLikeNetworkOrDnsIssue(string $rawMessage, string $errorCode): bool
    {
        if ($errorCode !== 'curl_error') {
            return false;
        }

        if ($rawMessage === '') {
            return true;
        }

        return strpos($rawMessage, 'could not resolve host') !== false
            || strpos($rawMessage, 'operation timed out') !== false
            || strpos($rawMessage, 'timed out') !== false
            || strpos($rawMessage, 'failed to connect') !== false
            || strpos($rawMessage, 'name lookup timed out') !== false
            || strpos($rawMessage, 'network') !== false
            || strpos($rawMessage, 'dns') !== false;
    }

    private function normalizeModelName(string $model): string
    {
        $value = trim($model);
        if (stripos($value, 'models/') === 0) {
            $value = substr($value, strlen('models/'));
        }
        return trim($value);
    }

    private function buildPrompt(string $text): string
    {
        return "Tu es un service de moderation de contenu.\n"
            . "Analyse le texte suivant dans les langues francais, anglais et arabe.\n"
            . "Refuse tout contenu insultant, toxique, haineux, violent, menacant, harcelant, spam, dangereux ou inapproprie.\n"
            . "Retourne uniquement un JSON strict, sans markdown, sans explication, avec exactement ces champs:\n"
            . "{\"allowed\": true|false, \"risk\": \"low|medium|high\", \"reason\": \"raison courte\", \"language\": \"fr|en|ar|unknown\"}\n"
            . "Si tu as le moindre doute de securite, retourne allowed=false et risk=high.\n\n"
            . "Texte a analyser:\n"
            . $text;
    }

    private function buildTextCorrectionPrompt(string $text): string
    {
        return "Tu es un correcteur de texte en francais.\n"
            . "Corrige uniquement: orthographe, mots incomplets si le sens est evident, grammaire, conjugaison, espaces, majuscule initiale, ponctuation finale.\n"
            . "Conserve strictement le meme sens general. N'ajoute aucune idee nouvelle.\n"
            . "Ne transforme pas en texte long. Garde une formulation courte et naturelle.\n"
            . "Retourne uniquement la phrase corrigee, sans guillemets, sans markdown, sans commentaire.\n"
            . "Exemples attendus:\n"
            . "- je cherche une repa -> Je cherche un repas.\n"
            . "- bonjour je veux manger aujourdui -> Bonjour, je veux manger aujourd'hui.\n"
            . "- il reste 5 repa disponible -> Il reste 5 repas disponibles.\n\n"
            . "Texte a corriger:\n"
            . $text;
    }

    private function finalizeCorrectedText(string $raw): string
    {
        $text = trim($raw);
        $text = trim($text, " \t\n\r\0\x0B\"'");
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+([,.;:!?])/u', '$1', $text) ?? $text;
        $text = preg_replace('/([,.;:!?])([^\s])/u', '$1 $2', $text) ?? $text;
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            $first = mb_substr($text, 0, 1, 'UTF-8');
            $rest = mb_substr($text, 1, null, 'UTF-8');
            $text = mb_strtoupper($first, 'UTF-8') . $rest;
        } else {
            $text = strtoupper(substr($text, 0, 1)) . substr($text, 1);
        }

        if (!preg_match('/[.!?…]$/u', $text)) {
            $text .= '.';
        }

        return $text;
    }

    private function extractModerationJson(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $decodedFallback = json_decode($matches[0], true);
            if (is_array($decodedFallback)) {
                return $decodedFallback;
            }
        }

        return null;
    }

    private function normalizeDecision(array $parsed, string $provider, int $httpStatus): ?array
    {
        if (!array_key_exists('allowed', $parsed) || !is_bool($parsed['allowed'])) {
            return null;
        }

        $risk = strtolower(trim((string)($parsed['risk'] ?? '')));
        if (!in_array($risk, ['low', 'medium', 'high'], true)) {
            return null;
        }

        $reason = trim((string)($parsed['reason'] ?? ''));
        if ($reason === '') {
            return null;
        }
        $reason = $this->safeSubstr($reason, 255);

        $language = strtolower(trim((string)($parsed['language'] ?? 'unknown')));
        if (!in_array($language, ['fr', 'en', 'ar', 'unknown'], true)) {
            $language = 'unknown';
        }

        return $this->buildDecision(
            (bool)$parsed['allowed'],
            $risk,
            $reason,
            $language,
            $provider,
            'ok',
            null,
            $httpStatus
        );
    }

    private function buildUnavailableDecision(string $provider, ?int $httpStatus, string $errorCode): array
    {
        return $this->buildDecision(
            false,
            'high',
            'Moderation IA indisponible. Veuillez reessayer.',
            'unknown',
            $provider,
            'unavailable',
            $errorCode,
            $httpStatus
        );
    }

    private function buildDecision(
        bool $allowed,
        string $risk,
        string $reason,
        string $language,
        string $provider,
        string $status,
        ?string $errorCode,
        ?int $httpStatus
    ): array {
        return [
            'allowed' => $allowed,
            'risk' => $risk,
            'reason' => $reason,
            'language' => $language,
            'provider' => $provider,
            'status' => $status,
            'error_code' => $errorCode,
            'http_status' => $httpStatus
        ];
    }

    private function writeModerationLog(string $type, ?int $httpStatus, bool $allowed, string $risk, string $reason, ?string $error): void
    {
        $logDir = dirname($this->logFilePath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $line = sprintf(
            "[%s] type=%s http_status=%s allowed=%s risk=%s reason=\"%s\" error=\"%s\"\n",
            date('c'),
            $this->sanitizeLogValue($type),
            $httpStatus === null ? 'null' : (string)$httpStatus,
            $allowed ? 'true' : 'false',
            $this->sanitizeLogValue($risk),
            $this->sanitizeLogValue($reason),
            $this->sanitizeLogValue((string)($error ?? ''))
        );

        @file_put_contents($this->logFilePath, $line, FILE_APPEND);
    }

    private function normalizeContentType(string $type): string
    {
        $value = strtolower(trim($type));
        if ($value === 'commentaire' || $value === 'comment') {
            return 'commentaire';
        }
        return 'publication';
    }

    private function sanitizeLogValue(string $value): string
    {
        $clean = preg_replace('/[\r\n\t]+/', ' ', $value);
        $clean = str_replace('"', "'", (string)$clean);
        return trim($this->safeSubstr($clean, 255));
    }

    private function safeSubstr(string $value, int $max): string
    {
        if ($max <= 0) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }
}

class ModerationUnavailableException extends RuntimeException
{
    private ?int $httpStatus;
    private string $errorCode;

    public function __construct(string $message, string $errorCode, ?int $httpStatus = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->httpStatus = $httpStatus;
        $this->errorCode = $errorCode;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
