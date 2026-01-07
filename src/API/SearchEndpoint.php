<?php

declare(strict_types=1);

namespace SemantiQ\API;

use SemantiQ\Search\SearchService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search Endpoint
 */
class SearchEndpoint {

    /**
     * Handle Search
     */
    public function handle(\WP_REST_Request $request): \WP_REST_Response {
        $body_params = $request->get_body_params();
        $query = $body_params['query'] ?? '';

        if (empty($query)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Query is required',
            ], 400);
        }

        try {
            $search_service = new SearchService();
            $results = $search_service->search(
                $query,
                (int) ($body_params['limit'] ?? 10),
                (array) ($body_params['post_types'] ?? [])
            );

            return new \WP_REST_Response([
                'success' => true,
                'query'   => $query,
                'results' => $results,
            ], 200);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
