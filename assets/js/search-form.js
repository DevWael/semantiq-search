(function($) {
    'use strict';

    $(document).ready(function() {
        var $form = $('#semantiq-search-form');
        var $results = $('#semantiq-results');
        var $loader = $('.semantiq-loader');

        if (!$form.length) return;

        $form.on('submit', function(e) {
            e.preventDefault();

            var query = $('#semantiq-query').val();
            if (!query) return;

            // Clear previous results and show loader
            $results.find('.semantiq-result-group, .semantiq-no-results').remove();
            $loader.show();

            $.ajax({
                url: semantiq_search.rest_url + '/search',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', semantiq_search.nonce);
                },
                data: {
                    query: query
                },
                success: function(response) {
                    $loader.hide();
                    renderResults(response);
                },
                error: function(err) {
                    $loader.hide();
                    $results.append('<div class="semantiq-no-results">Error performing search. Please try again.</div>');
                }
            });
        });

        function renderResults(data) {
            if (Object.keys(data).length === 0) {
                $results.append('<div class="semantiq-no-results">No relevant matches found. Try a different query.</div>');
                return;
            }

            for (var type in data) {
                var group = $('<div class="semantiq-result-group"></div>');
                group.append('<h3>' + type + 's</h3>');

                data[type].forEach(function(item) {
                    var html = '<div class="semantiq-result-item">';
                    html += '<a href="' + item.url + '">' + item.title + '</a>';
                    if (item.excerpt) {
                        html += '<div class="semantiq-result-excerpt">' + item.excerpt + '</div>';
                    }
                    html += '</div>';
                    group.append(html);
                });

                $results.append(group);
            }
        }
    });

})(jQuery);
