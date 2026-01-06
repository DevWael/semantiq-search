<?php

declare(strict_types=1);

namespace SemantiQ\Hooks;

use SemantiQ\API\SyncBatchEndpoint;
use SemantiQ\API\SyncPostEndpoint;
use SemantiQ\API\SearchEndpoint;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST Hooks
 */
class RESTHooks {

    /**
     * Register Hooks
     */
    public function register(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST Routes
     */
    public function register_routes(): void {
        $sync_batch = new SyncBatchEndpoint();
        $sync_post = new SyncPostEndpoint();
        $search = new SearchEndpoint();

        // Sync Endpoints
        register_rest_route('semantiq/v1', '/sync/start', [
            'methods'             => 'POST',
            'callback'            => [$sync_batch, 'start'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('semantiq/v1', '/sync/process', [
            'methods'             => 'POST',
            'callback'            => [$sync_batch, 'process'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('semantiq/v1', '/sync/status', [
            'methods'             => 'GET',
            'callback'            => [$sync_batch, 'get_status'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        register_rest_route('semantiq/v1', '/sync/post/(?P<id>\d+)', [
            'methods'             => 'POST',
            'callback'            => [$sync_post, 'sync'],
            'permission_callback' => [$this, 'check_edit_permission'],
        ]);

        // Search Endpoint
        register_rest_route('semantiq/v1', '/search', [
            'methods'             => 'POST',
            'callback'            => [$search, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Check Admin Permission
     */
    public function check_admin_permission(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check Edit Permission
     */
    public function check_edit_permission(\WP_REST_Request $request): bool {
        $post_id = (int)$request->get_param('id');
        return current_user_can('edit_post', $post_id);
    }
}
