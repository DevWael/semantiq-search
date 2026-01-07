<?php

declare(strict_types=1);

namespace SemantiQ\Hooks;

use SemantiQ\Sync\SyncManager;
use SemantiQ\Core\Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Hooks
 */
class PostHooks {

    private $config;

    public function __construct() {
        $this->config = Config::get_instance();
    }

    /**
     * Register Hooks
     */
    public function register(): void {
        add_action('save_post', [$this, 'handle_save_post'], 20, 3);
        add_action('before_delete_post', [$this, 'handle_delete_post']);
    }

    /**
     * Handle Save Post
     */
    public function handle_save_post(int $post_id, \WP_Post $post, bool $update): void {
        // Basic Checks
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ($post->post_status !== 'publish') {
            return;
        }

        $enabled_types = $this->config->get_enabled_post_types();
        if (!in_array($post->post_type, $enabled_types)) {
            return;
        }

        // Avoid infinite loops
        remove_action('save_post', [$this, 'handle_save_post'], 20);

        try {
            $sync_manager = SyncManager::get_instance();
            $sync_manager->sync_single_post($post_id);
        } catch (\Exception $e) {
            // Logged inside sync_single_post
        }

        add_action('save_post', [$this, 'handle_save_post'], 20, 3);
    }

    /**
     * Handle Delete Post
     */
    public function handle_delete_post(int $post_id): void {
        try {
            // Placeholder: Ideally remove from Qdrant
            $sync_manager = SyncManager::get_instance();
            $sync_manager->remove_single_post($post_id);
        } catch (\Exception $e) {
            // Silently fail on delete
        }
    }
}
