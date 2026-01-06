<?php

declare(strict_types=1);

namespace SemantiQ\Embedding;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Embedding Provider Interface
 */
interface EmbeddingProviderInterface {
    
    /**
     * Generate embedding for text
     */
    public function embed(string $text): array;

    /**
     * Test connection to provider
     */
    public function test_connection(): bool;

    /**
     * Get vector dimensions
     */
    public function get_vector_size(): int;

    /**
     * Get model name
     */
    public function get_model_name(): string;
}
