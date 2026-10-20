<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render via DocuHook API.
 *
 * @param array<string, mixed> $data Template data (JSON object).
 * @param string               $html HTML template with {{path.to.value}} tokens.
 * @param array{mode?: string, filename?: string, timeout?: int} $args
 * @return array{ok: bool, code: int, body: mixed, error?: string, html?: string, job_id?: string}
 */
function docuhook_render(array $data, string $html, array $args = []): array
{
    return DocuHook_Client::render($data, $html, $args);
}

/**
 * Poll job status.
 *
 * @return array{ok: bool, code: int, body: mixed, error?: string, status?: string}
 */
function docuhook_job_status(string $job_id): array
{
    return DocuHook_Client::job_status($job_id);
}

/**
 * Fetch completed job HTML.
 *
 * @return array{ok: bool, code: int, body: mixed, error?: string, html?: string}
 */
function docuhook_job_result(string $job_id): array
{
    return DocuHook_Client::job_result($job_id);
}
