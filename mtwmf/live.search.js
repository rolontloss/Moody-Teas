jQuery(document).ready(function($) {
    var searchTimer;

    $('#wmf-product-search').on('keyup', function() {
        var searchQuery = $(this).val();

        clearTimeout(searchTimer);

        if (searchQuery.length >= 3) {
            searchTimer = setTimeout(function() {
                $.ajax({
                    url: wmf_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wmf_live_search',
                        search_query: searchQuery
                    },
                    success: function(response) {
                        // Display search results
                        // Replace '#search-results-container' with the ID or class of the element where you want to display the results
                        $('#search-results-container').html(response);
                    }
                });
            }, 300);
        } else {
            // Clear search results
            $('#search-results-container').html('');
        }
    });
});
