<?php

declare(strict_types=1);

namespace SemantiQ\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration Management
 */
class Config {

    private static $instance = null;
    private $options = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->refresh();
    }

    /**
     * Refresh options from database
     */
    public function refresh(): void {
        $this->options = get_option('semantiq_settings', []);
    }

    /**
     * Get a specific setting
     */
    public function get(string $key, $default = null) {
        return $this->options[$key] ?? $default;
    }

    /**
     * Get Qdrant Host
     */
    public function get_qdrant_host(): string {
        return (string) $this->get('qdrant_host', 'http://localhost');
    }

    /**
     * Get Qdrant Port
     */
    public function get_qdrant_port(): int {
        return (int) $this->get('qdrant_port', 6333);
    }

    /**
     * Get Qdrant API Key
     */
    public function get_qdrant_api_key(): string {
        return (string) $this->get('qdrant_api_key', '');
    }

    /**
     * Get Qdrant Collection Name
     */
    public function get_qdrant_collection(): string {
        return (string) $this->get('qdrant_collection', 'wordpress_posts');
    }

    /**
     * Get Embedding URL
     */
    public function get_embedding_url(): string {
        return (string) $this->get('embedding_url', 'http://localhost:8000/v1/embeddings');
    }

    /**
     * Get Embedding API Key
     */
    public function get_embedding_api_key(): string {
        return (string) $this->get('embedding_api_key', '');
    }

    /**
     * Get Enabled Post Types
     */
    public function get_enabled_post_types(): array {
        $types = $this->get('enabled_post_types', ['post', 'page']);
        return apply_filters('semantiq_enabled_post_types', (array) $types);
    }

    /**
     * Get Batch Size
     */
    public function get_batch_size(): int {
        return (int) $this->get('batch_size', 5);
    }
}
