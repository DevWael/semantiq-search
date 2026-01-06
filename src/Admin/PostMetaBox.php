<?php

declare(strict_types=1);

namespace SemantiQ\Admin;

use SemantiQ\Database\PostMetaRepository;
use SemantiQ\Core\Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Meta Box
 */
class PostMetaBox {

    private $meta;

    public function __construct() {
        $this->meta = new PostMetaRepository();
    }

    /**
     * Register Hooks
     */
    public function register(): void {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
    }

    /**
     * Register Meta Box
     */
    public function register_meta_box(): void {
        $config = Config::get_instance();
        $screens = $config->get_enabled_post_types();

        foreach ($screens as $screen) {
            add_meta_box(
                'semantiq_post_sync',
                __('Vector Search Sync', 'semantiq-search'),
                [$this, 'render_meta_box'],
                $screen,
                'side',
                'default'
            );
        }
    }

    /**
     * Render Meta Box
     */
    public function render_meta_box(\WP_Post $post): void {
        $last_synced = $this->meta->get_sync_timestamp($post->ID);
        $error = $this->meta->get_sync_error($post->ID);

        wp_nonce_field('semantiq_post_nonce', 'semantiq_meta_nonce');
        ?>
        <div class="semantiq-meta-box">
            <p>
                <strong><?php _e('Last Synced:', 'semantiq-search'); ?></strong><br>
                <?php echo $last_synced ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_synced) : __('Never', 'semantiq-search'); ?>
            </p>

            <?php if ($error) : ?>
                <div class="notice notice-error notice-alt" style="margin: 10px 0;">
                    <p><?php echo esc_html($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="semantiq-sync-actions" style="margin-top: 15px;">
                <button type="button" 
                        class="button semantiq-manual-sync" 
                        data-post-id="<?php echo $post->ID; ?>">
                    <?php _e('Sync to Vector DB', 'semantiq-search'); ?>
                </button>
                <span class="spinner" style="float: none; margin: 0 5px;"></span>
                <span class="sync-status-msg"></span>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.semantiq-manual-sync').on('click', function() {
                var $btn = $(this);
                var $spinner = $btn.siblings('.spinner');
                var $msg = $btn.siblings('.sync-status-msg');
                var postId = $btn.data('post-id');

                $btn.prop('disabled', true);
                $spinner.addClass('is-active');
                $msg.text('');

                $.ajax({
                    url: semantiq_sync.rest_url + '/sync/post/' + postId,
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', semantiq_sync.nonce);
                    },
                    success: function(response) {
                        $msg.css('color', 'green').text('✓');
                        setTimeout(function() { location.reload(); }, 1000);
                    },
                    error: function(err) {
                        $msg.css('color', 'red').text('✗');
                        alert('Sync failed: ' + (err.responseJSON?.message || 'Unknown error'));
                    },
                    complete: function() {
                        $spinner.removeClass('is-active');
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
