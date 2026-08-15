<?php
/**
 * Plugin Name: CypherScan
 * Plugin URI: https://cyphernetsecurity.com/plugins/wordpress
 * Description: Scan uploaded files with CypherScan before they are stored in WordPress and report completed scans to CypherScan Agent.
 * Version: 1.1.0
 * Author: CypherNet Security
 * Author URI: https://cyphernetsecurity.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cypherscan-wordpress
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CYPHERSCAN_WORDPRESS_VERSION', '1.1.0');

require_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/upload-handler.php';
