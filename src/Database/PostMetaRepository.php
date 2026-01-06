<?php

declare(strict_types=1);

namespace SemantiQ\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Meta Repository
 */
class PostMetaRepository {

    /**
     * Get sync timestamp
     */
    public function get_sync_timestamp(int $post_id): ?int {
        $timestamp = get_post_meta($post_id, '_semantiq_synced_at', true);
        return $timestamp ? (int) $timestamp : null;
    }

    /**
     * Set sync timestamp
     */
    public function set_sync_timestamp(int $post_id, int $timestamp): void {
        update_post_meta($post_id, '_semantiq_synced_at', $timestamp);
    }

    /**
     * Get sync error
     */
    public function get_sync_error(int $post_id): ?string {
        $error = get_post_meta($post_id, '_semantiq_sync_error', true);
        return $error ?: null;
    }

    /**
     * Set sync error
     */
    public function set_sync_error(int $post_id, string $error): void {
        update_post_meta($post_id, '_semantiq_sync_error', $error);
    }

    /**
     * Clear sync error
     */
    public function clear_sync_error(int $post_id): void {
        delete_post_meta($post_id, '_semantiq_sync_error');
    }

    /**
     * Get posts for sync
     */
    public function get_posts_batch(int $offset, int $limit, array $post_types): array {
        $query = new \WP_Query([
            'post_type'      => $post_types,
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'post_status'    => 'publish',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);

        return $query->posts;
    }

    /**
     * Get total posts count for sync
     */
    public function get_total_posts_count(array $post_types): int {
        $query = new \WP_Query([
            'post_type'      => $post_types,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ]);

        return $query->found_posts;
    }
}
