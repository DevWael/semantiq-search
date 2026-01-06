<?php

declare(strict_types=1);

namespace SemantiQ;

use SemantiQ\Core\Config;
use SemantiQ\Core\Logger;
use SemantiQ\Hooks\AdminHooks;
use SemantiQ\Hooks\PostHooks;
use SemantiQ\Hooks\RESTHooks;
use SemantiQ\Hooks\CLIHooks;
use SemantiQ\Hooks\FrontendHooks;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Orchestrator
 */
final class SemantiQ {

    /**
     * @var SemantiQ|null
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $components = [];

    /**
     * Get instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->boot();
    }

    /**
     * Boot the plugin
     */
    private function boot(): void {
        // Initialize Core
        $this->components['config'] = Config::get_instance();
        $this->components['logger'] = Logger::get_instance();

        // Register Hooks
        $this->register_hooks();
    }

    /**
     * Register All Hooks
     */
    private function register_hooks(): void {
        // Admin Hooks
        if (is_admin()) {
            $admin_hooks = new AdminHooks();
            $admin_hooks->register();
            $this->components['admin_hooks'] = $admin_hooks;
        }

        // Post & Content Hooks
        $post_hooks = new PostHooks();
        $post_hooks->register();
        $this->components['post_hooks'] = $post_hooks;

        // REST API Hooks
        $rest_hooks = new RESTHooks();
        $rest_hooks->register();
        $this->components['rest_hooks'] = $rest_hooks;

        // Frontend Hooks
        $frontend_hooks = new FrontendHooks();
        $frontend_hooks->register();
        $this->components['frontend_hooks'] = $frontend_hooks;

        // CLI Hooks
        if (defined('WP_CLI') && WP_CLI) {
            $cli_hooks = new CLIHooks();
            $cli_hooks->register();
            $this->components['cli_hooks'] = $cli_hooks;
        }
    }

    /**
     * Get a component
     */
    public function get(string $key) {
        return $this->components[$key] ?? null;
    }
}
