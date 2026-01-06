<?php

declare(strict_types=1);

namespace SemantiQ\API;

use SemantiQ\Sync\SyncManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync Post Endpoint
 */
class SyncPostEndpoint {

    /**
     * Sync Single Post
     */
    public function sync(\WP_REST_Request $request): \WP_REST_Response {
        $post_id = (int) $request->get_param('id');
        
        try {
            $sync_manager = SyncManager::get_instance();
            $sync_manager->sync_single_post($post_id);
            
            return new \WP_REST_Response([
                'success' => true,
                'message' => __('Post synced successfully', 'semantiq-search'),
                'post_id' => $post_id,
            ], 200);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
