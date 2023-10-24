/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

$(function () {
    var domDisplay = '';
    var modalSelector = '.sep_main_productsButtons [data-modal="sep_openModal"]';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displayProductButtons');

        if (domDisplay.length) {
            callAjax();
        }
    });

    function callAjax() {
        if (typeof scalexpertpluginFrontUrl !== 'undefined' &&
            !isNaN(scalexpertpluginProductId)) {
            $.ajax({
                type: 'POST',
                headers: {"cache-control": "no-cache"},
                url: scalexpertpluginFrontUrl,
                async: true,
                cache: false,
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'product',
                    idProduct: scalexpertpluginProductId,
                    type: 'financial'
                },
                beforeSend: clearResult()
            }).done(function (jsonData) {
                successAjax(jsonData);
            });
        }
    }

    function clearResult() {
        removeEventOpenModal();
        domDisplay.html('');
    }

    function successAjax(jsonData) {
        if (!jsonData.hasError && typeof jsonData.content != 'undefined') {
            domDisplay.html(jsonData.content);
            initProductModals();
        }
    }

    function initProductModals() {
        if (typeof $.fancybox !== 'undefined') {
            if ($(modalSelector).length) {
                $(modalSelector).each(function (i, elm) {
                    if (typeof elm !== 'undefined' && $(elm).length) {
                        $(elm).fancybox({
                            'hideOnContentClick': false
                        });
                    }
                });
            }
        }
    }

    function removeEventOpenModal() {
        if ($(modalSelector).length) {
            $(modalSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {
                    $(elm).off();
                }
            });
        }
    }
});
