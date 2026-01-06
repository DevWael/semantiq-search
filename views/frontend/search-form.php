<?php
/**
 * Frontend Search Form Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="semantiq-search-container">
    <form class="semantiq-search-form" id="semantiq-search-form">
        <div class="semantiq-input-wrapper">
            <input type="text" 
                   name="q" 
                   id="semantiq-query" 
                   placeholder="<?php esc_attr_e('Search with semantic power...', 'semantiq-search'); ?>" 
                   required>
            <button type="submit">
                <span class="dashicons dashicons-search"></span>
            </button>
        </div>
    </form>

    <div id="semantiq-results" class="semantiq-results-container">
        <!-- Results will be injected here -->
        <div class="semantiq-loader" style="display: none;">
            <div class="semantiq-spinner"></div>
            <p><?php _e('Searching vectors...', 'semantiq-search'); ?></p>
        </div>
    </div>
</div>
