<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_options_page(
        'CypherScan',
        'CypherScan',
        'manage_options',
        'cypherscan-wordpress',
        'cypherscan_render_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('cypherscan_settings', 'cypherscan_api_key');
    register_setting('cypherscan_settings', 'cypherscan_api_base_url');
    register_setting('cypherscan_settings', 'cypherscan_block_infected');
    register_setting('cypherscan_settings', 'cypherscan_fail_open');
    register_setting('cypherscan_settings', 'cypherscan_timeout_seconds');
    register_setting('cypherscan_settings', 'cypherscan_debug_logs');

    add_settings_section(
        'cypherscan_main_section',
        'CypherScan API Settings',
        function () {
            echo '<p>Configure CypherScan upload scanning for WordPress media uploads.</p>';
        },
        'cypherscan-wordpress'
    );

    add_settings_field(
        'cypherscan_api_key',
        'API Key',
        function () {
            $value = esc_attr(get_option('cypherscan_api_key', ''));
            echo '<input type="password" name="cypherscan_api_key" value="' . $value . '" class="regular-text" autocomplete="off" />';
            echo '<p class="description">Create an API key from your CypherScan dashboard.</p>';
        },
        'cypherscan-wordpress',
        'cypherscan_main_section'
    );

    add_settings_field(
        'cypherscan_api_base_url',
        'API Base URL',
        function () {
            $value = esc_attr(get_option('cypherscan_api_base_url', 'https://cyphernetsecurity.com'));
            echo '<input type="url" name="cypherscan_api_base_url" value="' . $value . '" class="regular-text" />';
        },
        'cypherscan-wordpress',
        'cypherscan_main_section'
    );

    add_settings_field(
        'cypherscan_block_infected',
        'Block infected uploads',
        function () {
            $value = get_option('cypherscan_block_infected', '1');
            echo '<label>';
            echo '<input type="checkbox" name="cypherscan_block_infected" value="1" ' . checked('1', $value, false) . ' />';
            echo ' Remove blocked files automatically when CypherScan returns blocked=true.';
            echo '</label>';
        },
        'cypherscan-wordpress',
        'cypherscan_main_section'
    );

    add_settings_field(
        'cypherscan_fail_open',
        'Fail open',
        function () {
            $value = get_option('cypherscan_fail_open', '1');
            echo '<label>';
            echo '<input type="checkbox" name="cypherscan_fail_open" value="1" ' . checked('1', $value, false) . ' />';
            echo ' Allow uploads if CypherScan is temporarily unavailable.';
            echo '</label>';
        },
        'cypherscan-wordpress',
        'cypherscan_main_section'
    );

    add_settings_field(
        'cypherscan_timeout_seconds',
        'Timeout seconds',
        function () {
            $value = esc_attr(get_option('cypherscan_timeout_seconds', '30'));
            echo '<input type="number" min="5" max="120" name="cypherscan_timeout_seconds" value="' . $value . '" class="small-text" />';
            echo '<p class="description">Network timeout for CypherScan requests. Default: 30 seconds.</p>';
        },
        'cypherscan-wordpress',
        'cypherscan_main_section'
    );

    add_settings_field(
        'cypherscan_debug_logs',
        'Debug logs',
        function () {
            $value = get_option('cypherscan_debug_logs', '1');
            echo '<label>';
            echo '<input type="checkbox" name="cypherscan_debug_logs" value="1" ' . checked('1', $value, false) . ' />';
            echo ' Write CypherScan debug messages to the PHP error log.';
            echo '</label>';
        },
        'cypherscan-wordpress',
        'cypherscan_main_section'
    );
});

add_action('wp_ajax_cypherscan_test_connection', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error([
            'message' => 'Unauthorized.',
        ], 403);
    }

    check_ajax_referer('cypherscan_test_connection');

    $api_key = get_option('cypherscan_api_key', '');
    $base_url = rtrim(get_option('cypherscan_api_base_url', 'https://cyphernetsecurity.com'), '/');
    $timeout = (int) get_option('cypherscan_timeout_seconds', 30);

    if ($timeout < 5) {
        $timeout = 5;
    }

    if ($timeout > 120) {
        $timeout = 120;
    }

    if (empty($api_key)) {
        wp_send_json_error([
            'message' => 'Missing API key.',
        ], 400);
    }

    $response = wp_remote_post($base_url . '/api/v1/upload/presign', [
        'timeout' => $timeout,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'filename' => 'cypherscan-test.txt',
            'contentType' => 'text/plain',
            'sizeBytes' => 1,
        ]),
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => 'Unable to reach CypherScan: ' . $response->get_error_message(),
        ], 500);
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($status >= 200 && $status < 300 && is_array($data) && !empty($data['ok'])) {
        wp_send_json_success([
            'message' => 'Connected to CypherScan.',
            'plan' => isset($data['plan']) ? $data['plan'] : null,
            'traceId' => isset($data['traceId']) ? $data['traceId'] : null,
        ]);
    }

    if ($status === 401 || $status === 403) {
        wp_send_json_error([
            'message' => 'Invalid API key.',
        ], $status);
    }

    wp_send_json_error([
        'message' => 'CypherScan connection failed.',
        'status' => $status,
        'response' => $data,
    ], 500);
});

function cypherscan_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $nonce = wp_create_nonce('cypherscan_test_connection');

    echo '<div class="wrap">';
    echo '<h1>CypherScan</h1>';
    echo '<form method="post" action="options.php">';

    settings_fields('cypherscan_settings');
    do_settings_sections('cypherscan-wordpress');
    submit_button('Save Settings');

    echo '</form>';

    echo '<hr />';
    echo '<h2>Connection Test</h2>';
    echo '<p>Save your settings first, then test your CypherScan API connection.</p>';
    echo '<button type="button" class="button button-secondary" id="cypherscan-test-connection">Test Connection</button>';
    echo '<p id="cypherscan-test-result" style="margin-top:12px;"></p>';

    echo '<script>
    (function () {
        const button = document.getElementById("cypherscan-test-connection");
        const result = document.getElementById("cypherscan-test-result");

        if (!button || !result) {
            return;
        }

        button.addEventListener("click", async function () {
            button.disabled = true;
            result.textContent = "Testing connection...";
            result.style.color = "";

            const form = new FormData();
            form.append("action", "cypherscan_test_connection");
            form.append("_ajax_nonce", "' . esc_js($nonce) . '");

            try {
                const response = await fetch(ajaxurl, {
                    method: "POST",
                    body: form
                });

                const data = await response.json();

                if (data.success) {
                    const plan = data.data && data.data.plan ? " Plan: " + data.data.plan + "." : "";
                    result.textContent = "✓ " + data.data.message + plan;
                    result.style.color = "#008000";
                } else {
                    const message = data.data && data.data.message ? data.data.message : "Connection failed.";
                    result.textContent = "✗ " + message;
                    result.style.color = "#b32d2e";
                }
            } catch (error) {
                result.textContent = "✗ Unable to test connection.";
                result.style.color = "#b32d2e";
            } finally {
                button.disabled = false;
            }
        });
    })();
    </script>';

    echo '</div>';
}