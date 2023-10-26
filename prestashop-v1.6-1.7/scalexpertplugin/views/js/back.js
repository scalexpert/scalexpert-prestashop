/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

$(document).ready(function () {
    $('input.password').each(function () {
        $(this).attr('type', 'password');
    });
});

function viewPassword(name) {
    if ($('input[name="' + name + '"]').attr('type') == 'text') {
        $('input[name="' + name + '"]').attr('type', 'password');
    } else {
        $('input[name="' + name + '"]').attr('type', 'text');
    }
}

function checkKeys() {
    var mode = $('select[name="mode"] > option:selected').val();
    if (mode == 'production') {
        var apiIdentifier = encodeURIComponent($('input[name="apiProductionIdentifier"]').val());
        var apiKey = encodeURIComponent($('input[name="apiProductionKey"]').val());
    } else {
        var apiIdentifier = encodeURIComponent($('input[name="apiTestIdentifier"]').val());
        var apiKey = encodeURIComponent($('input[name="apiTestKey"]').val());
    }

    if (!apiIdentifier || !apiKey) {
        alert('Some fields are empty for ' + mode + ' API Credentials.');
        return;
    }

    $.ajax({
        type: 'POST',
        headers: {"cache-control": "no-cache"},
        async: true,
        cache: false,
        dataType: 'json',
        url: currentIndex + '&token=' + token + '&' + 'rand=' + new Date().getTime(),
        data: 'ajax=true&action=checkKeys&mode='+mode+'&apiIdentifier=' + apiIdentifier + '&apiKey=' + apiKey,
        success: function (data) {
            if (data.hasError) {
                alert(mode + ' API Credentials are invalid!'); //data.error
            } else {
                alert(mode + ' API Credentials are correct!');
            }
        }
    });
}