$(() => {

    $('input[data-password="yes"]').each(function () {
        $(this).attr('type', 'password');
    });

    // initiate the search on button click
    $(document).on('click', '#form_scalexpert_keys_check', () => checkKeys());

    $(document).on('click', '#scalexpert_keys_test_hide', function(e) {
        e.preventDefault();
        var apiSecretField = $("#form_scalexpert_keys_secret_test");
        if (apiSecretField.attr('type') === 'password') {
            apiSecretField.attr('type', 'text');
        } else {
            apiSecretField.attr('type', 'password');
        }
    });

    $(document).on('click', '#scalexpert_keys_prod_hide', function(e) {
        e.preventDefault();
        var apiSecretField = $("#form_scalexpert_keys_secret_prod");
        if (apiSecretField.attr('type') === 'password') {
            apiSecretField.attr('type', 'text');
        } else {
            apiSecretField.attr('type', 'password');
        }
    });

    function checkKeys() {
        var route = $('#form_scalexpert_keys_check').data('url');

        var type = $("#form_scalexpert_keys_type").val();
        var apiKey;
        var apiSecret;
        if ('production' === type) {
            apiKey = $("#form_scalexpert_keys_id_prod").val();
            apiSecret = $("#form_scalexpert_keys_secret_prod").val();
        } else {
            apiKey = $("#form_scalexpert_keys_id_test").val();
            apiSecret = $("#form_scalexpert_keys_secret_test").val();
        }

        var getParams = {
            'apiKey': encodeURIComponent(apiKey),
            'apiSecret': encodeURIComponent(apiSecret),
            'type': encodeURIComponent(type)
        };

        // use the ajax request to get customers
        $.get(route, getParams, function (data) {
            if (data) {
                alert(type + " API Credentials are correct !")
            } else {
                alert(type + " API Credentials are invalid !")
            }
        }, 'json');
    }
});