<?php

declare(strict_types=1);

namespace SemantiQ\Admin;

use SemantiQ\Sync\SyncManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk Sync Page
 */
class SyncPage {

    /**
     * Render Page
     */
    public function render(): void {
        $sync_manager = SyncManager::get_instance();
        $state = $sync_manager->get_progress();
        $is_running = $state['is_running'] ?? false;
        ?>
        <div class="wrap" id="semantiq-sync-page">
            <h1><?php _e('Bulk Synchronization', 'semantiq-search'); ?></h1>

            <div class="semantiq-sync-container card">
                <div class="sync-status-header">
                    <h2><?php _e('Sync Progress', 'semantiq-search'); ?></h2>
                    <span class="status-badge <?php echo $is_running ? 'running' : 'ready'; ?>">
                        <?php echo $is_running ? __('In Progress', 'semantiq-search') : __('Ready', 'semantiq-search'); ?>
                    </span>
                </div>

                <div class="sync-progress-bar-wrapper">
                    <div class="sync-progress-bar">
                        <div class="sync-progress-fill" style="width: 0%;"></div>
                    </div>
                    <div class="sync-progress-text">0%</div>
                </div>

                <div class="sync-stats">
                    <div class="stat-item">
                        <span class="label"><?php _e('Processed:', 'semantiq-search'); ?></span>
                        <span class="value" id="synced-count">0</span> / <span class="value" id="total-count">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="label"><?php _e('Errors:', 'semantiq-search'); ?></span>
                        <span class="value" id="error-count">0</span>
                    </div>
                </div>

                <div class="sync-actions">
                    <button id="start-sync" class="button button-primary" <?php disabled($is_running); ?>>
                        <?php _e('Start Sync', 'semantiq-search'); ?>
                    </button>
                    <button id="cancel-sync" class="button" <?php disabled(!$is_running); ?>>
                        <?php _e('Cancel', 'semantiq-search'); ?>
                    </button>
                </div>

                <div id="sync-log" class="sync-log" style="display: none;">
                    <h3><?php _e('Sync Errors', 'semantiq-search'); ?></h3>
                    <ul></ul>
                </div>
            </div>
        </div>
        <?php
    }
}
