<?php

declare(strict_types=1);

namespace SemantiQ\Hooks;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Hooks
 */
class FrontendHooks {

    /**
     * Register Hooks
     */
    public function register(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('semantiq_search', [$this, 'render_search_form']);
    }

    /**
     * Enqueue Assets
     */
    public function enqueue_assets(): void {
        wp_enqueue_style(
            'semantiq-frontend',
            SEMANTIQ_ASSETS_URL . 'css/search-form.css',
            [],
            SEMANTIQ_VERSION
        );

        wp_enqueue_script(
            'semantiq-frontend',
            SEMANTIQ_ASSETS_URL . 'js/search-form.js',
            ['jquery'],
            SEMANTIQ_VERSION,
            true
        );

        wp_localize_script('semantiq-frontend', 'semantiq_search', [
            'rest_url' => get_rest_url(null, 'semantiq/v1'),
            'nonce'    => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Render Search Form
     */
    public function render_search_form(array $atts = []): string {
        ob_start();
        include SEMANTIQ_PATH . 'views/frontend/search-form.php';
        return ob_get_clean();
    }
}
