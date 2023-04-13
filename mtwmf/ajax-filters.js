jQuery(document).ready(function ($) {
    $('#wmf-filter-form').on('submit', function (e) {
        e.preventDefault();

        // Serialize the form data
        var formData = $(this).serialize();

        // Make an AJAX request to the server to update the product list
        $.ajax({
            url: wmf_ajax_object.ajax_url,
            method: 'GET',
            data: formData + '&action=wmf_filter_products',
            success: function (response) {
                // Update the product list HTML with the filtered results
                $('.products').html(response);
            },
            error: function (xhr, status, error) {
                console.log('Error:', error);
            },
        });
    });
});
