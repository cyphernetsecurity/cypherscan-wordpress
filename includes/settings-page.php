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

function cypherscan_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>CypherScan</h1>';
    echo '<form method="post" action="options.php">';

    settings_fields('cypherscan_settings');
    do_settings_sections('cypherscan-wordpress');
    submit_button('Save Settings');

    echo '</form>';
    echo '</div>';
}