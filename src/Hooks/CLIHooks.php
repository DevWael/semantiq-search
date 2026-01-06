<?php

declare(strict_types=1);

namespace SemantiQ\Hooks;

use SemantiQ\CLI\SyncCommand;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CLI Hooks
 */
class CLIHooks {

    /**
     * Register Hooks
     */
    public function register(): void {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('vector-search', SyncCommand::class);
        }
    }
}
