<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_handle_upload', function ($upload) {
    error_log('[cypherscan-wordpress] upload detected');

    if (!isset($upload['file']) || !file_exists($upload['file'])) {
        error_log('[cypherscan-wordpress] file missing');
        return $upload;
    }

    if (!defined('CYPHERSCAN_API_KEY') || empty(CYPHERSCAN_API_KEY)) {
        error_log('[cypherscan-wordpress] missing CYPHERSCAN_API_KEY');
        return $upload;
    }

    $file_path = $upload['file'];
    $file_name = basename($file_path);
    $content_type = isset($upload['type']) ? $upload['type'] : 'application/octet-stream';
    $size_bytes = filesize($file_path);

    $base_url = defined('CYPHERSCAN_API_BASE_URL')
        ? rtrim(CYPHERSCAN_API_BASE_URL, '/')
        : 'https://cyphernetsecurity.com';

    $block_infected = defined('CYPHERSCAN_BLOCK_INFECTED')
        ? (bool) CYPHERSCAN_BLOCK_INFECTED
        : true;

    error_log('[cypherscan-wordpress] scanning: ' . $file_name);
    error_log('[cypherscan-wordpress] size: ' . $size_bytes);

    $presign_response = wp_remote_post($base_url . '/api/v1/upload/presign', [
        'timeout' => 20,
        'headers' => [
            'Authorization' => 'Bearer ' . CYPHERSCAN_API_KEY,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'filename' => $file_name,
            'contentType' => $content_type,
            'sizeBytes' => $size_bytes,
        ]),
    ]);

    if (is_wp_error($presign_response)) {
        error_log('[cypherscan-wordpress] presign failed: ' . $presign_response->get_error_message());
        return $upload;
    }

    $presign_status = wp_remote_retrieve_response_code($presign_response);
    $presign_body = wp_remote_retrieve_body($presign_response);

    error_log('[cypherscan-wordpress] presign status: ' . $presign_status);

    if ($presign_status < 200 || $presign_status >= 300) {
        error_log('[cypherscan-wordpress] presign response: ' . $presign_body);
        return $upload;
    }

    $presign_data = json_decode($presign_body, true);

    if (!is_array($presign_data) || empty($presign_data['url']) || empty($presign_data['key'])) {
        error_log('[cypherscan-wordpress] invalid presign response');
        return $upload;
    }

    $file_contents = file_get_contents($file_path);

    if ($file_contents === false) {
        error_log('[cypherscan-wordpress] failed to read file');
        return $upload;
    }

    $s3_response = wp_remote_request($presign_data['url'], [
        'method' => 'PUT',
        'timeout' => 30,
        'headers' => [
            'Content-Type' => $content_type,
        ],
        'body' => $file_contents,
    ]);

    if (is_wp_error($s3_response)) {
        error_log('[cypherscan-wordpress] s3 upload failed: ' . $s3_response->get_error_message());
        return $upload;
    }

    $s3_status = wp_remote_retrieve_response_code($s3_response);
    error_log('[cypherscan-wordpress] s3 upload status: ' . $s3_status);

    if ($s3_status < 200 || $s3_status >= 300) {
        error_log('[cypherscan-wordpress] s3 upload failed');
        return $upload;
    }

    $scan_response = wp_remote_post($base_url . '/api/v1/scan', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . CYPHERSCAN_API_KEY,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'objectKey' => $presign_data['key'],
        ]),
    ]);

    if (is_wp_error($scan_response)) {
        error_log('[cypherscan-wordpress] scan failed: ' . $scan_response->get_error_message());
        return $upload;
    }

    $scan_status = wp_remote_retrieve_response_code($scan_response);
    $scan_body = wp_remote_retrieve_body($scan_response);

    error_log('[cypherscan-wordpress] scan status: ' . $scan_status);

    if ($scan_status < 200 || $scan_status >= 300) {
        error_log('[cypherscan-wordpress] scan response: ' . $scan_body);
        return $upload;
    }

    $scan_data = json_decode($scan_body, true);

    if (!is_array($scan_data)) {
        error_log('[cypherscan-wordpress] invalid scan response');
        return $upload;
    }

    $verdict = isset($scan_data['verdict']) ? $scan_data['verdict'] : 'unknown';
    $blocked = !empty($scan_data['blocked']);
    $scan_id = isset($scan_data['scanId']) ? $scan_data['scanId'] : 'unknown';

    error_log('[cypherscan-wordpress] result: ' . $file_name . ' verdict=' . $verdict . ' blocked=' . ($blocked ? 'true' : 'false') . ' scanId=' . $scan_id);

    if ($block_infected && $blocked) {
        @unlink($file_path);

        $upload['error'] = 'CypherScan blocked this upload. Verdict: ' . $verdict;
        error_log('[cypherscan-wordpress] blocked file removed: ' . $file_name);
    }

    return $upload;
});