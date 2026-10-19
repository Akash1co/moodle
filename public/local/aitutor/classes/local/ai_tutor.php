<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aitutor\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Sends chat completions to Groq, with OpenRouter as a resilient fallback.
 *
 * @package    local_aitutor
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_tutor {

    /** @var string Groq chat completions endpoint. */
    private const GROQ_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    /** @var string Groq model. */
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';

    /** @var string OpenRouter chat completions endpoint. */
    private const OPENROUTER_ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    /** @var string OpenRouter model. */
    private const OPENROUTER_MODEL = 'meta-llama/llama-3.3-70b-instruct';

    /**
     * Request a chat completion.
     *
     * @param array $messages Chat messages in OpenAI chat-completions format.
     * @return array Decoded provider response.
     * @throws \moodle_exception If no key is configured or the request fails.
     */
    public static function complete(array $messages): array {
        $groqapikey = trim((string)get_config('local_aitutor', 'groqapikey'));
        $openrouterapikey = trim((string)get_config('local_aitutor', 'openrouterapikey'));

        if ($groqapikey === '' && $openrouterapikey === '') {
            throw new \moodle_exception('missingapikey', 'local_aitutor');
        }

        if ($groqapikey === '') {
            $result = ['fallback' => true, 'reason' => 'Groq API key is not configured'];
        } else {
            $result = self::request_provider(
                self::GROQ_ENDPOINT,
                self::GROQ_MODEL,
                $groqapikey,
                $messages
            );
        }

        if ($result['fallback']) {
            debugging('Groq AI Tutor request failed (' . $result['reason'] . '); switching to OpenRouter.', DEBUG_NORMAL);

            if ($openrouterapikey === '') {
                throw new \moodle_exception('requestfailed', 'local_aitutor');
            }

            $result = self::request_provider(
                self::OPENROUTER_ENDPOINT,
                self::OPENROUTER_MODEL,
                $openrouterapikey,
                $messages
            );
        }

        if ($result['fallback']) {
            debugging('OpenRouter AI Tutor request failed.', DEBUG_NORMAL);
            throw new \moodle_exception('requestfailed', 'local_aitutor');
        }

        return $result['response'];
    }

    /**
     * Send a request to one provider.
     *
     * @param string $endpoint Provider endpoint.
     * @param string $model Provider model.
     * @param string $apikey Provider API key.
     * @param array $messages Chat messages.
     * @return array Result containing either a decoded response or a fallback reason.
     * @throws \moodle_exception If the provider returns a non-retryable error or invalid JSON.
     */
    private static function request_provider(string $endpoint, string $model, string $apikey, array $messages): array {
        $body = json_encode(['model' => $model, 'messages' => $messages]);
        if ($body === false) {
            throw new \moodle_exception('requestfailed', 'local_aitutor');
        }

        $curl = new \curl();
        $curl->setHeader([
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
        ]);

        try {
            $responsebody = $curl->post($endpoint, $body, [
                'CURLOPT_CONNECTTIMEOUT' => 10,
                'CURLOPT_TIMEOUT' => 30,
            ]);
        } catch (\Throwable $exception) {
            return ['fallback' => true, 'reason' => 'transport error'];
        }

        if ($responsebody === false || $curl->get_errno() !== 0) {
            return ['fallback' => true, 'reason' => 'transport error'];
        }

        $status = (int)($curl->get_info()['http_code'] ?? 0);
        if ($status === 429 || ($status >= 500 && $status < 600)) {
            return ['fallback' => true, 'reason' => 'HTTP ' . $status];
        }

        if ($status < 200 || $status >= 300) {
            throw new \moodle_exception('requestfailed', 'local_aitutor');
        }

        $decodedresponse = json_decode($responsebody, true);
        if (!is_array($decodedresponse)) {
            throw new \moodle_exception('requestfailed', 'local_aitutor');
        }

        return ['fallback' => false, 'response' => $decodedresponse];
    }
}