<?php

declare(strict_types=1);

namespace SemantiQ\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom Logging
 */
class Logger {

    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Log message
     */
    public function log(string $message, string $level = 'info'): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('mysql');
        $formatted = sprintf("[%s] [SemantiQ %s]: %s", $timestamp, strtoupper($level), $message);

        error_log($formatted);
    }

    /**
     * Log Error
     */
    public function error(string $message): void {
        $this->log($message, 'error');
    }

    /**
     * Log Info
     */
    public function info(string $message): void {
        $this->log($message, 'info');
    }
}
