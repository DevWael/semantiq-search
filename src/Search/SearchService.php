<?php

declare(strict_types=1);

namespace SemantiQ\Search;

use SemantiQ\Core\Config;
use SemantiQ\Database\QdrantClient;
use SemantiQ\Embedding\LocalEmbeddingProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search Service
 */
class SearchService {

    private $config;
    private $qdrant;
    private $embedding_provider;
    private $query_builder;
    private $formatter;

    public function __construct() {
        $this->config = Config::get_instance();
        $this->qdrant = new QdrantClient();
        $this->embedding_provider = new LocalEmbeddingProvider();
        $this->query_builder = new QueryBuilder();
        $this->formatter = new ResultFormatter();
    }

    /**
     * Perform Semantic Search
     */
    public function search(string $query_text, int $limit = 10, array $post_types = []): array {
        if (empty($post_types)) {
            $post_types = $this->config->get_enabled_post_types();
        }

        // 1. Generate Query Vector
        $vector = $this->embedding_provider->embed($query_text);

        // 2. Build Qdrant Query
        $filters = $this->query_builder
            ->set_post_types($post_types)
            ->build();

        // 3. Execute Search
        $collection = $this->config->get_qdrant_collection();
        $points = $this->qdrant->search($collection, $vector, $limit, $filters);

        // 4. Format Results
        return $this->formatter->format($points);
    }
}
