jQuery(document).ready(function($) {
    $('#search_city').on('keyup', function() {
        var searchQuery = $(this).val();

        $.ajax({
            url: ajaxurl, 
            type: 'POST',
            data: {
                action: 'search_cities',
                search: searchQuery 
            },
            success: function(response) {
                $('#cities_table_body').html(response);
            }
        });
    });
});