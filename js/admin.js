(function($){
    $(document).ready(function() {
        $('.order-status-select').change(function(elem) {
            var select = $(elem.target);
            $.post('/wp-admin/admin-ajax.php', {
                action: 'snipcart_update_status',
                token: select.data('token'),
                value: select.val()
            }, function() {
                alert('Status updated!');
                location.reload();
            })
                .fail(function(data) {
                    alert('Error: ' + data.responseText);
                });
        });
    });
}(jQuery));
