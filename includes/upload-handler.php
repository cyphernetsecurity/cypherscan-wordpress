<?php

if (!defined('ABSPATH')) {
    exit;
}

function cypherscan_option($key, $default = null)
{
    $value = get_option($key, null);

    if ($value === null || $value === '') {
        return $default;
    }

    return $value;
}

function cypherscan_debug_enabled()
{
    return cypherscan_option('cypherscan_debug_logs', '1') === '1';
}

function cypherscan_log($message)
{
    if (!cypherscan_debug_enabled()) {
        return;
    }

    error_log('[cypherscan-wordpress] ' . $message);
}

function cypherscan_fail_upload($upload, $message, $fail_open = true)
{
    cypherscan_log($message);

    if ($fail_open) {
        return $upload;
    }

    $upload['error'] = 'CypherScan could not verify this upload. Please try again later.';
    return $upload;
}

add_filter('wp_handle_upload', function ($upload) {
    cypherscan_log('upload detected');

    if (!isset($upload['file']) || !file_exists($upload['file'])) {
        return cypherscan_fail_upload($upload, 'file missing', true);
    }

    $api_key = cypherscan_option('cypherscan_api_key', '');

    if (empty($api_key)) {
        return cypherscan_fail_upload($upload, 'missing API key', true);
    }

    $file_path = $upload['file'];
    $file_name = basename($file_path);
    $content_type = isset($upload['type']) ? $upload['type'] : 'application/octet-stream';
    $size_bytes = filesize($file_path);

    $base_url = rtrim(
        cypherscan_option('cypherscan_api_base_url', 'https://cyphernetsecurity.com'),
        '/'
    );

    $block_infected = cypherscan_option('cypherscan_block_infected', '1') === '1';
    $fail_open = cypherscan_option('cypherscan_fail_open', '1') === '1';

    $timeout = (int) cypherscan_option('cypherscan_timeout_seconds', 30);

    if ($timeout < 5) {
        $timeout = 5;
    }

    if ($timeout > 120) {
        $timeout = 120;
    }

    cypherscan_log('scanning: ' . $file_name);
    cypherscan_log('size: ' . $size_bytes);

    $presign_response = wp_remote_post($base_url . '/api/v1/upload/presign', [
        'timeout' => $timeout,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'filename' => $file_name,
            'contentType' => $content_type,
            'sizeBytes' => $size_bytes,
        ]),
    ]);

    if (is_wp_error($presign_response)) {
        return cypherscan_fail_upload(
            $upload,
            'presign failed: ' . $presign_response->get_error_message(),
            $fail_open
        );
    }

    $presign_status = wp_remote_retrieve_response_code($presign_response);
    $presign_body = wp_remote_retrieve_body($presign_response);

    cypherscan_log('presign status: ' . $presign_status);

    if ($presign_status < 200 || $presign_status >= 300) {
        return cypherscan_fail_upload(
            $upload,
            'presign response: ' . $presign_body,
            $fail_open
        );
    }

    $presign_data = json_decode($presign_body, true);

    if (!is_array($presign_data) || empty($presign_data['url']) || empty($presign_data['key'])) {
        return cypherscan_fail_upload($upload, 'invalid presign response', $fail_open);
    }

    $file_contents = file_get_contents($file_path);

    if ($file_contents === false) {
        return cypherscan_fail_upload($upload, 'failed to read file', $fail_open);
    }

    $s3_response = wp_remote_request($presign_data['url'], [
        'method' => 'PUT',
        'timeout' => $timeout,
        'headers' => [
            'Content-Type' => $content_type,
        ],
        'body' => $file_contents,
    ]);

    if (is_wp_error($s3_response)) {
        return cypherscan_fail_upload(
            $upload,
            's3 upload failed: ' . $s3_response->get_error_message(),
            $fail_open
        );
    }

    $s3_status = wp_remote_retrieve_response_code($s3_response);
    cypherscan_log('s3 upload status: ' . $s3_status);

    if ($s3_status < 200 || $s3_status >= 300) {
        return cypherscan_fail_upload($upload, 's3 upload failed', $fail_open);
    }

    $scan_response = wp_remote_post($base_url . '/api/v1/scan', [
        'timeout' => $timeout,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'objectKey' => $presign_data['key'],
        ]),
    ]);

    if (is_wp_error($scan_response)) {
        return cypherscan_fail_upload(
            $upload,
            'scan failed: ' . $scan_response->get_error_message(),
            $fail_open
        );
    }

    $scan_status = wp_remote_retrieve_response_code($scan_response);
    $scan_body = wp_remote_retrieve_body($scan_response);

    cypherscan_log('scan status: ' . $scan_status);

    if ($scan_status < 200 || $scan_status >= 300) {
        return cypherscan_fail_upload(
            $upload,
            'scan response: ' . $scan_body,
            $fail_open
        );
    }

    $scan_data = json_decode($scan_body, true);

    if (!is_array($scan_data)) {
        return cypherscan_fail_upload($upload, 'invalid scan response', $fail_open);
    }

    $verdict = isset($scan_data['verdict']) ? $scan_data['verdict'] : 'unknown';
    $blocked = !empty($scan_data['blocked']);
    $scan_id = isset($scan_data['scanId']) ? $scan_data['scanId'] : 'unknown';

    cypherscan_log(
        'result: ' .
        $file_name .
        ' verdict=' .
        $verdict .
        ' blocked=' .
        ($blocked ? 'true' : 'false') .
        ' scanId=' .
        $scan_id
    );

    if ($block_infected && $blocked) {
        @unlink($file_path);

        $upload['error'] = 'CypherScan blocked this upload. Verdict: ' . $verdict;
        cypherscan_log('blocked file removed: ' . $file_name);
    }

    return $upload;
});