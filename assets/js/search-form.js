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
                    
                    // Check for success flag
                    if (!response.success) {
                        $results.append('<div class="semantiq-no-results">Search failed. Please try again.</div>');
                        return;
                    }
                    
                    renderResults(response);
                },
                error: function(xhr, status, error) {
                    $loader.hide();
                    console.error('Search error:', xhr.responseText);
                    $results.append('<div class="semantiq-no-results">Error performing search. Please try again.</div>');
                },
                statusCode: {
                    400: function(xhr) {
                        $loader.hide();
                        console.error('Bad request:', xhr.responseText);
                        $results.append('<div class="semantiq-no-results">Invalid search request.</div>');
                    },
                    500: function(xhr) {
                        $loader.hide();
                        console.error('Server error:', xhr.responseText);
                        $results.append('<div class="semantiq-no-results">Server error. Please try again later.</div>');
                    }
                }
            });
        });

        function renderResults(response) {
            // Extract actual results from response object
            var data = response.results;

            // Validate data structure
            if (!data || typeof data !== 'object' || Object.keys(data).length === 0) {
                $results.append('<div class="semantiq-no-results">No relevant matches found. Try a different query.</div>');
                return;
            }

            var hasResults = false;

            for (var type in data) {
                // Skip if not an array or empty
                if (!Array.isArray(data[type]) || data[type].length === 0) {
                    continue;
                }

                hasResults = true;

                var group = $('<div class="semantiq-result-group"></div>');
                group.append('<h3>' + capitalizeType(type) + 's</h3>');

                data[type].forEach(function(item) {
                    var html = '<div class="semantiq-result-item">';
                    
                    // Add featured image if available
                    if (item.featured_image) {
                        html += '<div class="semantiq-result-image">';
                        html += '<img src="' + item.featured_image + '" alt="' + item.title + '">';
                        html += '</div>';
                    }
                    
                    html += '<div class="semantiq-result-content">';
                    html += '<a href="' + item.url + '" class="semantiq-result-title">' + item.title + '</a>';
                    
                    if (item.excerpt) {
                        html += '<div class="semantiq-result-excerpt">' + item.excerpt + '</div>';
                    }
                    
                    // Display relevance score if available
                    if (item.score) {
                        var percentage = Math.round(item.score * 100);
                        html += '<div class="semantiq-result-score">Relevance: ' + percentage + '%</div>';
                    }
                    
                    html += '</div>';
                    html += '</div>';
                    
                    group.append(html);
                });

                $results.append(group);
            }

            // Show message if no valid groups rendered
            if (!hasResults) {
                $results.append('<div class="semantiq-no-results">No relevant matches found. Try a different query.</div>');
            }
        }

        function capitalizeType(type) {
            return type.charAt(0).toUpperCase() + type.slice(1);
        }
    });

})(jQuery);
