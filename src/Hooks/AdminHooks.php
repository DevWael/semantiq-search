<?php

declare(strict_types=1);

namespace SemantiQ\Hooks;

use SemantiQ\Admin\SettingsPage;
use SemantiQ\Admin\SyncPage;
use SemantiQ\Admin\PostListColumns;
use SemantiQ\Admin\PostMetaBox;
use SemantiQ\Admin\AdminNotices;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Hooks
 */
class AdminHooks {

    /**
     * @var array
     */
    private $pages = [];

    /**
     * Register Hooks
     */
    public function register(): void {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Initialize admin components
        $this->pages['settings'] = new SettingsPage();
        $this->pages['sync']     = new SyncPage();

        $this->pages['settings']->register();
        (new PostMetaBox())->register();
        (new PostListColumns())->register();
        (new AdminNotices())->register();
    }

    /**
     * Add Menu Pages
     */
    public function add_menu_pages(): void {
        // Main Menu
        add_menu_page(
            __('SemantiQ Search', 'semantiq-search'),
            __('SemantiQ Search', 'semantiq-search'),
            'manage_options',
            'semantiq-search',
            [$this->pages['settings'], 'render'],
            'dashicons-search'
        );

        // Submenus
        // Note: The first submenu slug must match the parent slug to avoid duplicates
        add_submenu_page(
            'semantiq-search',
            __('Settings', 'semantiq-search'),
            __('Settings', 'semantiq-search'),
            'manage_options',
            'semantiq-search',
            [$this->pages['settings'], 'render']
        );

        add_submenu_page(
            'semantiq-search',
            __('Bulk Sync', 'semantiq-search'),
            __('Bulk Sync', 'semantiq-search'),
            'manage_options',
            'semantiq-sync',
            [$this->pages['sync'], 'render']
        );
    }

    /**
     * Enqueue Assets
     */
    public function enqueue_assets($hook): void {
        if (strpos($hook, 'semantiq-search') === false && strpos($hook, 'semantiq-sync') === false) {
            return;
        }

        wp_enqueue_style(
            'semantiq-admin',
            SEMANTIQ_ASSETS_URL . 'css/admin.css',
            [],
            SEMANTIQ_VERSION
        );

        wp_enqueue_script(
            'semantiq-admin',
            SEMANTIQ_ASSETS_URL . 'js/admin-settings.js',
            ['jquery'],
            SEMANTIQ_VERSION,
            true
        );

        wp_localize_script('semantiq-admin', 'semantiq_sync', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'rest_url' => get_rest_url(null, 'semantiq/v1'),
        ]);

        if (strpos($hook, 'semantiq-sync') !== false) {
            wp_enqueue_script(
                'semantiq-sync',
                SEMANTIQ_ASSETS_URL . 'js/admin-sync.js',
                ['jquery'],
                SEMANTIQ_VERSION,
                true
            );
        }
    }
}
