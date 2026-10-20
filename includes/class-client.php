<?php

if (!defined('ABSPATH')) {
    exit;
}

final class DocuHook_Client
{
    /**
     * @param array<string, mixed> $data
     * @param array{mode?: string, filename?: string, timeout?: int} $args
     * @return array{ok: bool, code: int, body: mixed, error?: string, html?: string, job_id?: string, raw?: string}
     */
    public static function render(array $data, string $html, array $args = []): array
    {
        $settings = self::settings();
        $api_key = trim((string) $settings['api_key']);
        $base = rtrim((string) $settings['api_url'], '/');

        if ($api_key === '' || $base === '') {
            return ['ok' => false, 'code' => 0, 'body' => null, 'error' => 'Missing API key or API URL'];
        }

        $mode = isset($args['mode']) ? (string) $args['mode'] : (string) $settings['mode'];
        if ($mode !== 'async') {
            $mode = 'sync';
        }

        $payload = [
            'mode' => $mode,
            'data' => $data,
            'html' => $html,
            'filename' => isset($args['filename']) ? (string) $args['filename'] : 'document.pdf',
        ];

        $timeout = isset($args['timeout']) ? max(5, (int) $args['timeout']) : 30;

        $response = wp_remote_post($base . '/api/v1/render', [
            'timeout' => $timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'X-DocuHook-Key' => $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'code' => 0,
                'body' => null,
                'error' => $response->get_error_message(),
            ];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        $json = json_decode($raw, true);

        if ($code === 202 && is_array($json)) {
            return [
                'ok' => true,
                'code' => $code,
                'body' => $json,
                'job_id' => isset($json['job_id']) ? (string) $json['job_id'] : '',
                'raw' => $raw,
            ];
        }

        if ($code >= 200 && $code < 300 && is_array($json) && isset($json['html'])) {
            return [
                'ok' => true,
                'code' => $code,
                'body' => $json,
                'html' => (string) $json['html'],
                'raw' => $raw,
            ];
        }

        $error = 'Request failed';
        if (is_array($json) && !empty($json['error'])) {
            $error = (string) $json['error'];
        }

        return [
            'ok' => false,
            'code' => $code,
            'body' => $json,
            'error' => $error,
            'raw' => $raw,
        ];
    }

    /**
     * @return array{ok: bool, code: int, body: mixed, error?: string, status?: string}
     */
    public static function job_status(string $job_id): array
    {
        return self::get_json('/api/v1/jobs/' . rawurlencode($job_id));
    }

    /**
     * @return array{ok: bool, code: int, body: mixed, error?: string, html?: string}
     */
    public static function job_result(string $job_id): array
    {
        $result = self::get_json('/api/v1/jobs/' . rawurlencode($job_id) . '/result');
        if ($result['ok'] && is_array($result['body']) && isset($result['body']['html'])) {
            $result['html'] = (string) $result['body']['html'];
        }
        return $result;
    }

    /**
     * @return array{ok: bool, code: int, body: mixed, error?: string, status?: string}
     */
    private static function get_json(string $path): array
    {
        $settings = self::settings();
        $api_key = trim((string) $settings['api_key']);
        $base = rtrim((string) $settings['api_url'], '/');

        if ($api_key === '' || $base === '') {
            return ['ok' => false, 'code' => 0, 'body' => null, 'error' => 'Missing API key or API URL'];
        }

        $response = wp_remote_get($base . $path, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'X-DocuHook-Key' => $api_key,
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'code' => 0, 'body' => null, 'error' => $response->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        $json = json_decode($raw, true);

        if ($code >= 200 && $code < 300 && is_array($json)) {
            $out = ['ok' => true, 'code' => $code, 'body' => $json];
            if (isset($json['status'])) {
                $out['status'] = (string) $json['status'];
            }
            return $out;
        }

        $error = is_array($json) && !empty($json['error']) ? (string) $json['error'] : 'Request failed';
        return ['ok' => false, 'code' => $code, 'body' => $json, 'error' => $error];
    }

    /** @return array{api_url: string, api_key: string, mode: string} */
    public static function settings(): array
    {
        $defaults = [
            'api_url' => 'https://docuhook.com',
            'api_key' => '',
            'mode' => 'sync',
        ];
        $stored = get_option('docuhook_settings', []);
        if (!is_array($stored)) {
            $stored = [];
        }
        return array_merge($defaults, $stored);
    }
}
