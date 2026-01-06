<?php

declare(strict_types=1);

namespace SemantiQ\Sync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync State Manager
 */
class SyncStateManager {

    private const TRANSIENT_KEY = 'semantiq_sync_progress';

    /**
     * Start/Initialize sync state
     */
    public function start(int $total): void {
        $state = [
            'total'        => $total,
            'processed'    => 0,
            'offset'       => 0,
            'errors'       => [],
            'status'       => 'starting',
            'is_running'   => true,
            'started_at'   => current_time('timestamp'),
            'last_updated' => current_time('timestamp'),
        ];
        set_transient(self::TRANSIENT_KEY, $state, HOUR_IN_SECONDS);
    }

    /**
     * Update sync state
     */
    public function update(array $updates): void {
        $state = $this->get_state();
        if (!$state) {
            return;
        }

        $state = array_merge($state, $updates);
        $state['last_updated'] = current_time('timestamp');
        
        set_transient(self::TRANSIENT_KEY, $state, HOUR_IN_SECONDS);
    }

    /**
     * Complete sync
     */
    public function complete(): void {
        $state = $this->get_state();
        if ($state) {
            $state['is_running'] = false;
            $state['status'] = 'completed';
            $state['last_updated'] = current_time('timestamp');
            set_transient(self::TRANSIENT_KEY, $state, HOUR_IN_SECONDS);
        }
    }

    /**
     * Cancel/Reset sync
     */
    public function reset(): void {
        delete_transient(self::TRANSIENT_KEY);
    }

    /**
     * Get current state
     */
    public function get_state(): ?array {
        return get_transient(self::TRANSIENT_KEY) ?: null;
    }
}
