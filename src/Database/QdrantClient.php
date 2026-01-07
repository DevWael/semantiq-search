<?php

declare(strict_types=1);

namespace SemantiQ\Database;

use SemantiQ\Core\Config;
use SemantiQ\Core\Logger;
use SemantiQ\Core\QdrantException;
use Tenqz\Qdrant\QdrantClient as TenqzQdrantClient;
use Tenqz\Qdrant\Transport\Infrastructure\Factory\CurlHttpClientFactory;
use Tenqz\Qdrant\Transport\Domain\Exception\TransportException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Qdrant API Client
 */
class QdrantClient {

    private $config;
    private $logger;
    private $client;

    public function __construct() {
        $this->config = Config::get_instance();
        $this->logger = Logger::get_instance();
        $this->initializeClient();
    }

    /**
     * Initialize Qdrant Client
     */
    private function initializeClient(): void {
        $originalHost = $this->config->get_qdrant_host();
        $port = $this->config->get_qdrant_port();
        $apiKey = $this->config->get_qdrant_api_key();
        
        // Detect scheme and strip from host
        $scheme = 'http';
        $host = $originalHost;
        
        if (preg_match('#^https://#', $host)) {
            $scheme = 'https';
            $host = preg_replace('#^https://#', '', $host);
        } elseif (preg_match('#^http://#', $host)) {
            $scheme = 'http';
            $host = preg_replace('#^http://#', '', $host);
        }
        
        $this->logger->info("Initializing Qdrant client with host: {$host}, port: {$port}, scheme: {$scheme}");
        
        try {
            $factory = new CurlHttpClientFactory();
            // Pass scheme as the 5th parameter
            $httpClient = $factory->create($host, $port, $apiKey, 30, $scheme);
            
            $this->client = new TenqzQdrantClient($httpClient);
        } catch (\Exception $e) {
            $this->logger->error("Failed to initialize Qdrant client: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test Connection
     */
    public function test_connection(): bool {
        try {
            $collection = $this->config->get_qdrant_collection();
           
            if ($collection) {
                $result = $this->client->getCollection($collection);
                $this->logger->info("Successfully connected to collection: {$collection}");
            } else {
                $result = $this->client->listCollections();
                $this->logger->info("Successfully listed collections");
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Qdrant Connection Test Failed: " . $e->getMessage());
            $this->logger->error("Exception type: " . get_class($e));
            if (method_exists($e, 'getResponse')) {
                $this->logger->error("Response: " . print_r($e->getResponse(), true));
            }
            return false;
        }
    }

    /**
     * Upsert Points
     */
    public function upsert_points(string $collection, array $points): bool {
        try {
            $result = $this->client->upsertPoints($collection, $points);
            $this->logger->info("Successfully upserted " . count($points) . " points to collection: {$collection}" . "Result: ". print_r($result, true));
            return true;
        } catch (TransportException $e) {
            $this->logger->error("Failed to upsert points: " . $e->getMessage());
            throw new QdrantException("Failed to upsert points: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get Point by ID
     */
    public function get_point(string $collection, int $point_id): ?array {
        try {
            $result = $this->client->getPoint($collection, $point_id);
            return $result['result'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error("Failed to get point {$point_id}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete Point
     */
    public function delete_point(string $collection, int $point_id): bool {
        try {
            $this->client->deletePoints($collection, [$point_id]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete point: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search
     */
    public function search(string $collection, array $vector, int $limit = 10, array $filters = []): array {
        try {
            $results = $this->client->search($collection, $vector, $limit, $filters);
            return $results['result'] ?? [];
        } catch (TransportException $e) {
            $this->logger->error("Search failed: " . $e->getMessage());
            throw new QdrantException("Search failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create Collection
     */
    public function create_collection(string $name, int $vector_size): bool {
        try {
            $this->client->createCollection($name, $vector_size, 'Cosine');
            $this->logger->info("Successfully created collection: {$name}");
            return true;
        } catch (\Exception $e) {
            // Collection might already exist, which is fine
            $this->logger->info("Collection creation note: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Collections
     */
    public function get_collections(): array {
        try {
            $response = $this->client->listCollections();
            $collections = [];
            
            if (isset($response['result']['collections'])) {
                foreach ($response['result']['collections'] as $collection) {
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
