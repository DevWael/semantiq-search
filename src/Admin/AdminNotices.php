<?php

declare(strict_types=1);

namespace SemantiQ\Admin;

use SemantiQ\Core\Config;
use SemantiQ\Database\QdrantClient;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Notices
 */
class AdminNotices {

    /**
     * Register Hooks
     */
    public function register(): void {
        add_action('admin_notices', [$this, 'display_notices']);
    }

    /**
     * Display Notices
     */
    public function display_notices(): void {
        $config = Config::get_instance();
        $qdrant = new QdrantClient();

        // 1. Check if Qdrant Host is configured
        if (!$config->get('qdrant_host')) {
            $this->render_notice(
                __('SemantiQ Search: Please configure your Qdrant Host URL in settings.', 'semantiq-search'),
                'error'
            );
            return;
        }

        // 2. Check Connection (Cache this in a transient to avoid overhead)
        $connection_ok = get_transient('semantiq_connection_ok');
        if (false === $connection_ok) {
            $connection_ok = $qdrant->test_connection();
            set_transient('semantiq_connection_ok', (int) $connection_ok, HOUR_IN_SECONDS);
        }

        if (!$connection_ok) {
            $collection = $config->get_qdrant_collection();
            $message = $collection 
                ? sprintf(__('SemantiQ Search: Unable to connect to Qdrant or collection "%s" does not exist.', 'semantiq-search'), $collection)
                : __('SemantiQ Search: Unable to connect to Qdrant. Please check your configuration.', 'semantiq-search');
            
            $this->render_notice($message, 'error');
        }
    }

    /**
     * Render Notice
     */
    private function render_notice(string $message, string $type = 'info'): void {
        ?>
        <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
}
