<?php

declare(strict_types=1);

namespace SemantiQ\Sync;

use SemantiQ\Core\Config;
use SemantiQ\Database\QdrantClient;
use SemantiQ\Database\PostMetaRepository;
use SemantiQ\Embedding\LocalEmbeddingProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync Manager (Orchestrator)
 */
class SyncManager implements SyncManagerInterface {

    private static $instance = null;
    
    private $config;
    private $qdrant;
    private $meta;
    private $syncer;
    private $batch_processor;
    private $state_manager;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->config = Config::get_instance();
        $this->qdrant = new QdrantClient();
        $this->meta = new PostMetaRepository();
        
        $embedding_provider = new LocalEmbeddingProvider();
        $this->syncer = new PostSyncer($embedding_provider, $this->qdrant, $this->meta);
        $this->batch_processor = new BatchProcessor($this->syncer, $this->meta);
        $this->state_manager = new SyncStateManager();
    }

    /**
     * Start Bulk Sync
     */
    public function start_bulk_sync(): void {
        $post_types = $this->config->get_enabled_post_types();
        $total = $this->meta->get_total_posts_count($post_types);
        
        $this->state_manager->reset();
        $this->state_manager->start($total);
    }

    /**
     * Process Batch
     */
    public function process_batch(): array {
        $state = $this->state_manager->get_state();
        if (!$state || !$state['is_running']) {
            return ['error' => 'No active sync session'];
        }

        $post_types = $this->config->get_enabled_post_types();
        $batch_size = $this->config->get_batch_size();
        
        $result = $this->batch_processor->process($state['offset'], $batch_size, $post_types);
        
        $new_processed = $state['processed'] + $result['processed'];
        $new_errors = array_merge($state['errors'], $result['errors']);
        
        $this->state_manager->update([
            'processed' => $new_processed,
            'offset'    => $result['next_offset'],
            'errors'    => $new_errors,
            'status'    => $result['is_complete'] ? 'completed' : 'processing',
        ]);

        if ($result['is_complete']) {
            $this->state_manager->complete();
        }

        return $result;
    }

    /**
     * Sync single post
     */
    public function sync_single_post(int $post_id): void {
        $this->syncer->sync($post_id);
    }

    /**
     * Get Progress
     */
    public function get_progress(): ?array {
        return $this->state_manager->get_state();
    }

    /**
     * Cancel Sync
     */
    public function cancel_sync(): void {
        $this->state_manager->reset();
    }
}
