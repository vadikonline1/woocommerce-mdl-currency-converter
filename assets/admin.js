jQuery(document).ready(function($) {
    $('#wc_mdl_force_update').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $spinner = $('#wc_mdl_update_spinner');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wc_mdl_force_update_rate',
                nonce: wc_mdl_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Eroare la comunicarea cu serverul.');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});
