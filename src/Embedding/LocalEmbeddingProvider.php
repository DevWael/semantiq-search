<?php

declare(strict_types=1);

namespace SemantiQ\Embedding;

use SemantiQ\Core\Config;
use SemantiQ\Core\Logger;
use SemantiQ\Core\EmbeddingException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Local Embedding Provider
 */
class LocalEmbeddingProvider implements EmbeddingProviderInterface {

    private $config;
    private $logger;

    public function __construct() {
        $this->config = Config::get_instance();
        $this->logger = Logger::get_instance();
    }

    /**
     * Generate embedding
     */
    public function embed(string $text): array {
        $url = $this->config->get_embedding_url();
        $api_key = $this->config->get_embedding_api_key();

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($api_key) {
            $headers['Authorization'] = "Bearer {$api_key}";
        }

        $args = [
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => wp_json_encode(['text' => $text]),
            'timeout' => 30,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->error("Embedding API Request Failed: {$error_message}");
            throw new EmbeddingException("Embedding API Request Failed: {$error_message}");
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code >= 400) {
            $error = $data['error'] ?? 'Unknown Error';
            $this->logger->error("Embedding API Error ({$status_code}): {$error}");
            throw new EmbeddingException("Embedding API Error: {$error}", $status_code);
        }

        if (empty($data['embedding']) || !is_array($data['embedding'])) {
            $this->logger->error("Invalid embedding response format");
            throw new EmbeddingException("Invalid embedding response format");
        }

        return $data['embedding'];
    }

    /**
     * Test connection
     */
    public function test_connection(): bool {
        try {
            // Simple test with minimal text
            $this->embed('test');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get vector size (often model specific)
     * Note: This might need to be configurable or auto-detected
     */
    public function get_vector_size(): int {
        return (int) $this->config->get('vector_dimensions', 384);
    }

    /**
     * Get model name
     */
    public function get_model_name(): string {
        return (string) $this->config->get('embedding_model', 'local-model');
    }
}
