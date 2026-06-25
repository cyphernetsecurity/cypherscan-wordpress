<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_handle_upload', function ($upload) {
    error_log('[cypherscan-wordpress] upload detected');

    if (isset($upload['file'])) {
        error_log('[cypherscan-wordpress] file: ' . $upload['file']);
    }

    return $upload;
});