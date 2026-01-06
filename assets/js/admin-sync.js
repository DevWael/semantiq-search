(function($) {
    'use strict';

    $(document).ready(function() {
        const $startBtn = $('#start-sync');
        const $cancelBtn = $('#cancel-sync');
        const $progressFill = $('.sync-progress-fill');
        const $progressText = $('.sync-progress-text');
        const $syncedCount = $('#synced-count');
        const $totalCount = $('#total-count');
        const $errorCount = $('#error-count');
        const $logList = $('#sync-log ul');

        let isSyncing = false;

        $startBtn.on('click', function() {
            startSync();
        });

        $cancelBtn.on('click', function() {
            cancelSync();
        });

        function startSync() {
            isSyncing = true;
            $startBtn.prop('disabled', true);
            $cancelBtn.prop('disabled', false);
            $('.status-badge').text('In Progress').removeClass('ready').addClass('running');
            
            // Initialization
            $.ajax({
                url: semantiq_sync.rest_url + '/sync/start',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', semantiq_sync.nonce);
                },
                success: function(response) {
                    $totalCount.text(response.total);
                    processBatch();
                },
                error: function(err) {
                    alert('Failed to start sync: ' + (err.responseJSON?.message || 'Unknown error'));
                    resetUI();
                }
            });
        }

        function processBatch() {
            if (!isSyncing) return;

            $.ajax({
                url: semantiq_sync.rest_url + '/sync/process',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', semantiq_sync.nonce);
                },
                success: function(response) {
                    updateProgress(response);

                    if (!response.is_complete) {
                        processBatch();
                    } else {
                        finishSync();
                    }
                },
                error: function(err) {
                    console.error('Batch error:', err);
                    // Add to log but continue?
                    setTimeout(processBatch, 2000); // Retry after delay
                }
            });
        }

        function updateProgress(data) {
            $syncedCount.text(data.processed);
            $errorCount.text(data.errors.length);

            const total = parseInt($totalCount.text()) || 1;
            const percentage = Math.round((data.processed / total) * 100);
            
            $progressFill.css('width', percentage + '%');
            $progressText.text(percentage + '%');

            if (data.errors.length > 0) {
                $('#sync-log').show();
                data.errors.forEach(err => {
                    $logList.append(`<li>ID ${err.post_id}: ${err.error}</li>`);
                });
            }
        }

        function finishSync() {
            isSyncing = false;
            resetUI();
            alert('Synchronization completed!');
        }

        function cancelSync() {
            isSyncing = false;
            resetUI();
        }

        function resetUI() {
            $startBtn.prop('disabled', false);
            $cancelBtn.prop('disabled', true);
            $('.status-badge').text('Ready').removeClass('running').addClass('ready');
        }
    });

})(jQuery);
