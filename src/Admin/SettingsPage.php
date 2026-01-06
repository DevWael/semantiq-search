<?php

declare(strict_types=1);

namespace SemantiQ\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Page
 */
class SettingsPage {

    /**
     * Register Hooks
     */
    public function register(): void {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register Settings
     */
    public function register_settings(): void {
        register_setting('semantiq_settings_group', 'semantiq_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);

        // Qdrant Section
        add_settings_section(
            'semantiq_qdrant_section',
            __('Qdrant Configuration', 'semantiq-search'),
            null,
            'semantiq-search'
        );

        add_settings_field(
            'qdrant_host',
            __('Host URL', 'semantiq-search'),
            [$this, 'render_text_field'],
            'semantiq-search',
            'semantiq_qdrant_section',
            ['label_for' => 'qdrant_host', 'default' => 'http://localhost']
        );

        add_settings_field(
            'qdrant_port',
            __('Port', 'semantiq-search'),
            [$this, 'render_number_field'],
            'semantiq-search',
            'semantiq_qdrant_section',
            ['label_for' => 'qdrant_port', 'default' => 6333]
        );

        add_settings_field(
            'qdrant_api_key',
            __('API Key', 'semantiq-search'),
            [$this, 'render_password_field'],
            'semantiq-search',
            'semantiq_qdrant_section',
            ['label_for' => 'qdrant_api_key']
        );

        add_settings_field(
            'qdrant_collection',
            __('Collection Name', 'semantiq-search'),
            [$this, 'render_text_field'],
            'semantiq-search',
            'semantiq_qdrant_section',
            ['label_for' => 'qdrant_collection', 'default' => 'wordpress_posts']
        );

        // Embedding Section
        add_settings_section(
            'semantiq_embedding_section',
            __('Embedding API Configuration', 'semantiq-search'),
            null,
            'semantiq-search'
        );

        add_settings_field(
            'embedding_url',
            __('Endpoint URL', 'semantiq-search'),
            [$this, 'render_text_field'],
            'semantiq-search',
            'semantiq_embedding_section',
            ['label_for' => 'embedding_url', 'default' => 'http://localhost:8000/embed']
        );

        add_settings_field(
            'embedding_api_key',
            __('API Key', 'semantiq-search'),
            [$this, 'render_password_field'],
            'semantiq-search',
            'semantiq_embedding_section',
            ['label_for' => 'embedding_api_key']
        );

        // Content Section
        add_settings_section(
            'semantiq_content_section',
            __('Content Configuration', 'semantiq-search'),
            null,
            'semantiq-search'
        );

        add_settings_field(
            'enabled_post_types',
            __('Enabled Post Types', 'semantiq-search'),
            [$this, 'render_post_types_field'],
            'semantiq-search',
            'semantiq_content_section'
        );
    }

    /**
     * Sanitize Settings
     */
    public function sanitize_settings(array $input): array {
        $sanitized = [];
        $sanitized['qdrant_host'] = esc_url_raw($input['qdrant_host'] ?? 'http://localhost');
        $sanitized['qdrant_port'] = absint($input['qdrant_port'] ?? 6333);
        $sanitized['qdrant_api_key'] = sanitize_text_field($input['qdrant_api_key'] ?? '');
        $sanitized['qdrant_collection'] = sanitize_text_field($input['qdrant_collection'] ?? 'wordpress_posts');
        $sanitized['embedding_url'] = esc_url_raw($input['embedding_url'] ?? 'http://localhost:8000/embed');
        $sanitized['embedding_api_key'] = sanitize_text_field($input['embedding_api_key'] ?? '');
        $sanitized['enabled_post_types'] = array_map('sanitize_text_field', (array) ($input['enabled_post_types'] ?? []));
        
        return $sanitized;
    }

    /**
     * Render Text Field
     */
    public function render_text_field(array $args): void {
        $options = get_option('semantiq_settings');
        $value = $options[$args['label_for']] ?? $args['default'] ?? '';
        $datalist_id = "datalist_{$args['label_for']}";
        
        echo "<input type='text' 
                    id='{$args['label_for']}' 
                    name='semantiq_settings[{$args['label_for']}]' 
                    value='" . esc_attr($value) . "' 
                    class='regular-text' 
                    list='{$datalist_id}'>";

        if ($args['label_for'] === 'qdrant_collection') {
            try {
                $qdrant = new \SemantiQ\Database\QdrantClient();
                $collections = $qdrant->get_collections();
                if (!empty($collections)) {
                    echo "<datalist id='{$datalist_id}'>";
                    foreach ($collections as $col) {
                        echo "<option value='" . esc_attr($col) . "'>";
                    }
                    echo "</datalist>";
                    echo "<p class='description'>" . __('You can select from existing collections or enter a new one.', 'semantiq-search') . "</p>";
                }
            } catch (\Exception $e) {
                // Silently fail, just show text field
            }
        }
    }

    /**
     * Render Number Field
     */
    public function render_number_field(array $args): void {
        $options = get_option('semantiq_settings');
        $value = $options[$args['label_for']] ?? $args['default'] ?? '';
        echo "<input type='number' id='{$args['label_for']}' name='semantiq_settings[{$args['label_for']}]' value='" . esc_attr((string)$value) . "' class='small-text'>";
    }

    /**
     * Render Password Field
     */
    public function render_password_field(array $args): void {
        $options = get_option('semantiq_settings');
        $value = $options[$args['label_for']] ?? '';
        echo "<input type='password' id='{$args['label_for']}' name='semantiq_settings[{$args['label_for']}]' value='" . esc_attr($value) . "' class='regular-text'>";
    }

    /**
     * Render Post Types Field
     */
    public function render_post_types_field(): void {
        $options = get_option('semantiq_settings');
        $enabled = $options['enabled_post_types'] ?? ['post', 'page'];
        $post_types = get_post_types(['public' => true], 'objects');

        foreach ($post_types as $type) {
            $checked = in_array($type->name, $enabled) ? 'checked' : '';
            echo "<label><input type='checkbox' name='semantiq_settings[enabled_post_types][]' value='{$type->name}' {$checked}> " . esc_html($type->label) . "</label><br>";
        }
    }

    /**
     * Render Page
     */
    public function render(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('semantiq_settings_group');
                do_settings_sections('semantiq-search');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
