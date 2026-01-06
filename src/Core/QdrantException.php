<?php

declare(strict_types=1);

namespace SemantiQ\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Qdrant API Exception
 */
class QdrantException extends SemantiQException {}
