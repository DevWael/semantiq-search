<?php

declare(strict_types=1);

namespace SemantiQ\CLI;

use SemantiQ\Sync\SyncManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync CLI Command
 */
class SyncCommand {

    /**
     * Synchronize posts to Vector Database
     *
     * ## OPTIONS
     *
     * [--force]
     * : Force re-sync of all posts even if already synced.
     *
     * ## EXAMPLES
     *
     *     wp vector-search sync
     */
    public function sync($args, $assoc_args): void {
        \WP_CLI::line('Starting Vector Synchronization...');
        
        $sync_manager = SyncManager::get_instance();
        $sync_manager->start_bulk_sync();
        
        $state = $sync_manager->get_progress();
        $total = $state['total'];
        
        $progress = \WP_CLI\Utils\make_progress_bar('Syncing posts', $total);

        while (true) {
            $result = $sync_manager->process_batch();
            
            if (isset($result['error'])) {
                \WP_CLI::error($result['error']);
            }

            $current_processed = $result['processed'];
            for ($i = 0; $i < $current_processed; $i++) {
                $progress->tick();
            }

            if ($result['is_complete']) {
                break;
            }
        }

        $progress->finish();
        \WP_CLI::success("Successfully synced {$total} items!");
    }
}
