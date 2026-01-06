<?php

declare(strict_types=1);

namespace SemantiQ\Search;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Result Formatter
 */
class ResultFormatter {

    /**
     * Format search results
     */
    public function format(array $points): array {
        $formatted = [];

        foreach ($points as $point) {
            $payload = $point['payload'] ?? [];
            $post_type = $payload['post_type'] ?? 'unknown';

            if (!isset($formatted[$post_type])) {
                $formatted[$post_type] = [];
            }

            $formatted[$post_type][] = [
                'id'             => $payload['post_id'] ?? null,
                'title'          => $payload['post_title'] ?? '',
                'url'            => $payload['post_url'] ?? '',
                'excerpt'        => $payload['excerpt'] ?? '',
                'featured_image' => $payload['featured_image'] ?? '',
                'score'          => $point['score'] ?? 0,
            ];
        }

        return $formatted;
    }
}
