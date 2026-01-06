<?php

declare(strict_types=1);

namespace SemantiQ\Admin;

use SemantiQ\Database\PostMetaRepository;
use SemantiQ\Core\Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post List Columns
 */
class PostListColumns {

    private $meta;
    private $config;

    public function __construct() {
        $this->meta = new PostMetaRepository();
        $this->config = Config::get_instance();
    }

    /**
     * Register Hooks
     */
    public function register(): void {
        $post_types = $this->config->get_enabled_post_types();
        
        foreach ($post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'add_columns']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'render_columns'], 10, 2);
        }
    }

    /**
     * Add Columns
     */
    public function add_columns(array $columns): array {
        $columns['semantiq_sync'] = __('Vector Sync', 'semantiq-search');
        return $columns;
    }

    /**
     * Render Columns
     */
    public function render_columns(string $column, int $post_id): void {
        if ($column !== 'semantiq_sync') {
            return;
        }

        $last_synced = $this->meta->get_sync_timestamp($post_id);
        $error = $this->meta->get_sync_error($post_id);
        $post = get_post($post_id);

        $status_color = '#ccc'; // Gray (Never)
        $status_text = __('Never Synced', 'semantiq-search');

        if ($error) {
            $status_color = '#d63638'; // Red (Error)
            $status_text = __('Sync Error', 'semantiq-search');
        } elseif ($last_synced) {
            $post_modified = strtotime($post->post_modified_gmt);
            if ($last_synced < $post_modified) {
                $status_color = '#dba617'; // Orange (Outdated)
                $status_text = __('Outdated', 'semantiq-search');
            } else {
                $status_color = '#00a32a'; // Green (Synced)
                $status_text = __('Synced', 'semantiq-search');
            }
        }

        echo sprintf(
            '<span class="semantiq-status-dot" style="display:inline-block;width:10px;height:10px;border-radius:50%%;background:%s;margin-right:5px;" title="%s"></span>',
            $status_color,
            esc_attr($status_text)
        );

        echo '<br>';
        echo '<a href="#" class="semantiq-manual-sync" data-post-id="' . $post_id . '">' . __('Sync Now', 'semantiq-search') . '</a>';
    }
}
