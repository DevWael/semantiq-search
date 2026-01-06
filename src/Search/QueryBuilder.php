<?php

declare(strict_types=1);

namespace SemantiQ\Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Qdrant Query Builder
 */
class QueryBuilder {

    private $post_types = [];
    private $min_score = 0.5;

    /**
     * Set Post Types Filter
     */
    public function set_post_types(array $post_types): self {
        $this->post_types = $post_types;
        return $this;
    }

    /**
     * Set Min Score
     */
    public function set_min_score(float $score): self {
        $this->min_score = $score;
        return $this;
    }

    /**
     * Build Filter Array
     */
    public function build(): array {
        $filter = ['must' => []];

        if (!empty($this->post_types)) {
            $filter['must'][] = [
                'key'   => 'post_type',
                'match' => ['any' => $this->post_types],
            ];
        }

        return $filter;
    }
}
