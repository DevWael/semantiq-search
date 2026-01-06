<?php

declare(strict_types=1);

namespace SemantiQ\Sync;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sync Manager Interface
 */
interface SyncManagerInterface {
    public function start_bulk_sync(): void;
    public function process_batch(): array;
    public function sync_single_post(int $post_id): void;
    public function get_progress(): ?array;
    public function cancel_sync(): void;
}
