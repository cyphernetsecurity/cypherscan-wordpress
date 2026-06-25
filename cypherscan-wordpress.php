<?php
/**
 * Plugin Name: CypherScan WordPress
 * Plugin URI: https://cyphernetsecurity.com
 * Description: Scan uploaded files with CypherScan before they enter WordPress workflows.
 * Version: 0.1.0
 * Author: CypherNet Security
 * Author URI: https://cyphernetsecurity.com
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/upload-handler.php';