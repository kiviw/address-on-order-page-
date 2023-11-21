jQuery(document).ready(function($) {
    // Make an AJAX request to generate Monero subaddress
    $.ajax({
        url: monero_subaddress_vars.ajaxurl,
        type: 'GET',
        data: {
            action: 'generate_monero_subaddress'
        },
        success: function(response) {
            // Update the result container with the generated subaddress
            $('#monero-subaddress-container').html('<strong>Monero Subaddress:</strong> ' + response);
        },
        error: function(error) {
            console.error('Error generating Monero subaddress:', error);
        }
    });
});
