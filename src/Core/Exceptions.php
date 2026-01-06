<?php

declare(strict_types=1);

namespace SemantiQ\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Exception
 */
class SemantiQException extends \Exception {}

/**
 * Configuration Exception
 */
class ConfigException extends SemantiQException {}

/**
 * Qdrant API Exception
 */
class QdrantException extends SemantiQException {}

/**
 * Embedding API Exception
 */
class EmbeddingException extends SemantiQException {}

/**
 * Sync Exception
 */
class SyncException extends SemantiQException {}
