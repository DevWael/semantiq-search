<?php

declare(strict_types=1);

namespace SemantiQ\API;

use SemantiQ\Sync\SyncManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync Batch Endpoint
 */
class SyncBatchEndpoint {

    /**
     * Start Sync
     */
    public function start(): \WP_REST_Response {
        $sync_manager = SyncManager::get_instance();
        $sync_manager->start_bulk_sync();
        $state = $sync_manager->get_progress();

        return new \WP_REST_Response($state, 200);
    }

    /**
     * Process Batch
     */
    public function process(): \WP_REST_Response {
        $sync_manager = SyncManager::get_instance();
        $result = $sync_manager->process_batch();
        $state = $sync_manager->get_progress();

        if (isset($result['error'])) {
            return new \WP_REST_Response($result, 400);
        }

        return new \WP_REST_Response($state, 200);
    }

    /**
     * Get Status
     */
    public function get_status(): \WP_REST_Response {
        $sync_manager = SyncManager::get_instance();
        $state = $sync_manager->get_progress();

        return new \WP_REST_Response($state, 200);
    }
}
