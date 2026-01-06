<?php
/**
 * Plugin Name: SemantiQ Search
 * Description: Vector-based semantic search for WordPress using Qdrant and local embeddings.
 * Version: 1.0.0
 * Author: Ahmad Wael
 * Author URI: https://www.bbioon.com
 * License: MIT
 * Text Domain: semantiq-search
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.9
 */

declare(strict_types=1);

namespace SemantiQ;

if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('SEMANTIQ_VERSION', '1.0.0');
define('SEMANTIQ_URL', plugin_dir_url(__FILE__));
define('SEMANTIQ_PATH', plugin_dir_path(__FILE__));
define('SEMANTIQ_ASSETS_URL', SEMANTIQ_URL . 'assets/');


// Load Autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Initialize Plugin
 */
function run_semantiq_search(): void {
    SemantiQ::get_instance();
}

add_action('plugins_loaded', 'SemantiQ\\run_semantiq_search');

