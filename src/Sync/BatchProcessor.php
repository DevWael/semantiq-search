<?php

declare(strict_types=1);

namespace SemantiQ\Sync;

use SemantiQ\Database\PostMetaRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Batch Processor
 */
class BatchProcessor {

    private $syncer;
    private $meta;

    public function __construct(PostSyncer $syncer, PostMetaRepository $meta) {
        $this->syncer = $syncer;
        $this->meta = $meta;
    }

    /**
     * Process batch of posts
     */
    public function process(int $offset, int $batch_size, array $post_types): array {
        $post_ids = $this->meta->get_posts_batch($offset, $batch_size, $post_types);
        
        $processed = 0;
        $errors = [];

        foreach ($post_ids as $post_id) {
            try {
                $this->syncer->sync((int) $post_id);
                $processed++;
            } catch (\Exception $e) {
                $errors[] = [
                    'post_id' => $post_id,
                    'error'   => $e->getMessage(),
                ];
            }

            // Cleanup periodically to save memory
            if ($processed % 10 === 0) {
                wp_cache_flush();
            }
        }

        return [
            'processed'   => $processed,
            'errors'      => $errors,
            'next_offset' => $offset + count($post_ids),
            'is_complete' => count($post_ids) < $batch_size,
        ];
    }
}
