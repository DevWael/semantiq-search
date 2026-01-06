<?php

declare(strict_types=1);

namespace SemantiQ\Sync;

use SemantiQ\Core\Config;
use SemantiQ\Core\Logger;
use SemantiQ\Database\QdrantClient;
use SemantiQ\Database\PostMetaRepository;
use SemantiQ\Embedding\EmbeddingProviderInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single Post Syncer
 */
class PostSyncer {

    private $config;
    private $logger;
    private $qdrant;
    private $meta;
    private $embedding_provider;

    public function __construct(
        EmbeddingProviderInterface $embedding_provider,
        QdrantClient $qdrant,
        PostMetaRepository $meta
    ) {
        $this->config = Config::get_instance();
        $this->logger = Logger::get_instance();
        $this->embedding_provider = $embedding_provider;
        $this->qdrant = $qdrant;
        $this->meta = $meta;
    }

    /**
     * Sync single post
     */
    public function sync(int $post_id): void {
        $post = get_post($post_id);
        if (!$post) {
            throw new \Exception("Post not found: {$post_id}");
        }

        $this->meta->clear_sync_error($post_id);

        try {
            $content = $this->prepare_content($post);
            $vector = $this->embedding_provider->embed($content);

            $payload = $this->prepare_payload($post);
            $collection = $this->config->get_qdrant_collection();

            $point = [
                'id'      => $post_id,
                'vector'  => $vector,
                'payload' => $payload,
            ];

            $this->qdrant->upsert_points($collection, [$point]);
            $this->meta->set_sync_timestamp($post_id, (int) current_time('timestamp'));
            
            $this->logger->info("Post synced successfully: {$post_id}");
        } catch (\Exception $e) {
            $this->meta->set_sync_error($post_id, $e->getMessage());
            $this->logger->error("Failed to sync post {$post_id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare content for embedding
     */
    private function prepare_content(\WP_Post $post): string {
        $content = $post->post_title . "\n\n" . $post->post_content;
        
        // Strip HTML tags and normalize whitespace
        $content = wp_strip_all_tags($content);
        $content = html_entity_decode($content);
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    /**
     * Prepare payload for Qdrant
     */
    private function prepare_payload(\WP_Post $post): array {
        return [
            'post_id'        => $post->ID,
            'post_type'      => $post->post_type,
            'post_title'     => $post->post_title,
            'post_url'       => get_permalink($post->ID),
            'post_date'      => $post->post_date,
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
            'excerpt'        => wp_trim_words($post->post_content, 30),
        ];
    }
}
