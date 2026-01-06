<?php

declare(strict_types=1);

namespace SemantiQ\Database;

use SemantiQ\Core\Config;
use SemantiQ\Core\Logger;
use SemantiQ\Core\QdrantException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Qdrant API Client
 */
class QdrantClient {

    private $config;
    private $logger;

    public function __construct() {
        $this->config = Config::get_instance();
        $this->logger = Logger::get_instance();
    }

    /**
     * Get Base URL
     */
    private function get_base_url(): string {
        $host = rtrim($this->config->get_qdrant_host(), '/');
        $port = $this->config->get_qdrant_port();
        return "{$host}:{$port}";
    }

    /**
     * Get Headers
     */
    private function get_headers(): array {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $api_key = $this->config->get_qdrant_api_key();
        if ($api_key) {
            $headers['api-key'] = $api_key;
        }

        return $headers;
    }

    /**
     * Remote Request
     */
    private function request(string $endpoint, string $method = 'GET', ?array $body = null, int $timeout = 10): array {
        $url = $this->get_base_url() . $endpoint;
        
        $args = [
            'method'  => $method,
            'headers' => $this->get_headers(),
            'timeout' => $timeout,
        ];

        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->error("Qdrant Request Failed: {$error_message}");
            throw new QdrantException("Qdrant Request Failed: {$error_message}");
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($status_code >= 400) {
            $error = $data['status']['error'] ?? $data['result']['error'] ?? 'Unknown Error';
            $this->logger->error("Qdrant API Error ({$status_code}): {$error}");
            throw new QdrantException("Qdrant API Error: {$error}", $status_code);
        }

        return $data;
    }

    /**
     * Test Connection
     */
    public function test_connection(): bool {
        try {
            $collection = $this->config->get_qdrant_collection();
            if ($collection) {
                $this->request("/collections/{$collection}", 'GET', null, 5);
            } else {
                $this->request('/', 'GET', null, 5);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Qdrant Connection Test Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upsert Points
     */
    public function upsert_points(string $collection, array $points): bool {
        $this->request("/collections/{$collection}/points", 'PUT', [
            'points' => $points,
        ]);
        $this->logger->info("Successfully upserted " . count($points) . " points to collection: {$collection}");
        return true;
    }

    /**
     * Delete Point
     */
    public function delete_point(string $collection, int $point_id): bool {
        try {
            $this->request("/collections/{$collection}/points/delete", 'POST', [
                'points' => [$point_id],
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Search
     */
    public function search(string $collection, array $vector, int $limit = 10, array $filters = []): array {
        $body = [
            'vector'      => $vector,
            'limit'       => $limit,
            'with_payload' => true,
        ];

        if (!empty($filters)) {
            $body['filter'] = $filters;
        }

        $response = $this->request("/collections/{$collection}/points/search", 'POST', $body);
        return $response['result'] ?? [];
    }

    /**
     * Create Collection
     */
    public function create_collection(string $name, int $vector_size): bool {
        try {
            $this->request("/collections/{$name}", 'PUT', [
                'vectors' => [
                    'size'     => $vector_size,
                    'distance' => 'Cosine',
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * Get Collections
     */
    public function get_collections(): array {
        try {
            $data = $this->request('/collections', 'GET', null, 5);
            $collections = [];
            if (isset($data['result']['collections'])) {
                foreach ($data['result']['collections'] as $collection) {
                    $collections[] = $collection['name'];
                }
            }
            return $collections;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch collections: " . $e->getMessage());
            return [];
        }
    }
}
