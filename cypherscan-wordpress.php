<?php
/**
 * Plugin Name: CypherScan
 * Plugin URI: https://cyphernetsecurity.com/plugins/wordpress
 * Description: Scan uploaded files with CypherScan before they are stored in WordPress.
 * Version: 1.0.1
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

require_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/upload-handler.php';