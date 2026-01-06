(function($) {
    'use strict';

    $(document).ready(function() {
        // Shared Manual Sync Handler
        $(document).on('click', '.semantiq-manual-sync', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var $row = $btn.closest('tr');
            var $statusDot = $row.find('.semantiq-status-dot');

            $btn.css('opacity', '0.5').text('Syncing...');

            $.ajax({
                url: semantiq_sync.rest_url + '/sync/post/' + postId,
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', semantiq_sync.nonce);
                },
                success: function(response) {
                    $btn.text('Synced!').css('color', 'green');
                    $statusDot.css('background', '#00a32a');
                    setTimeout(function() {
                        $btn.text('Sync Now').css('color', '');
                        $btn.css('opacity', '1');
                    }, 2000);
                },
                error: function(err) {
                    alert('Sync failed: ' + (err.responseJSON?.message || 'Unknown error'));
                    $btn.text('Failed').css('color', 'red');
                    setTimeout(function() {
                        $btn.text('Sync Now').css('color', '');
                        $btn.css('opacity', '1');
                    }, 2000);
                }
            });
        });
    });

})(jQuery);
