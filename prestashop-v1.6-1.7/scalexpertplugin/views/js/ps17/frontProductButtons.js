/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

$(function () {
    let domDisplay = '';
    let modalSelector = '.sep_main_productsButtons [data-modal="sep_openModal"]';
    let xhr = '';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displayProductButtons');

        if (domDisplay.length) {
            callAjax();
            eventPrestaShopUpdateProduct();
        }
    });

    function callAjax() {
        if (typeof scalexpertpluginFrontUrl !== 'undefined' &&
            !isNaN(scalexpertpluginProductId)) {
            abortAjax();
            xhr = $.ajax({
                method: "POST",
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

    function abortAjax() {
        if (typeof xhr !== 'undefined' &&
            xhr != 'cancel_duplicate' &&
            xhr.readyState < 4)
        {
            xhr.abort();
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
        if ($(modalSelector).length) {
            $(modalSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {
                    let attrModal = $(elm).attr('href');
                    if (attrModal) {
                        $(elm).off().on('click', function (e) {
                            e.preventDefault();
                            $(attrModal).modal('show');
                            return;
                        });
                    }
                }
            });
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

    function eventPrestaShopUpdateProduct() {
        prestashop.on('updatedProduct', (event) => {
            domDisplay = $('#scalexpertplugin-displayProductButtons');
            if (domDisplay.length) {
                callAjax();
            }
        });
    }
});
