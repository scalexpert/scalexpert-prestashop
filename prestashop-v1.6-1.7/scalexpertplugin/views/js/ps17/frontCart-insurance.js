/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

$(function () {
    let domDisplay = '';
    let modalSelector = '.sep_insuranceSolution [data-modal="sep_openModal"]';
    let xhr = '';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displayShoppingCartFooter');
        if (domDisplay.length) {
            callAjax();
            eventPrestaShopUpdateCart();
        }
    });

    function callAjax() {
        if (typeof scalexpertpluginFrontUrl !== 'undefined') {
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
                    action: 'cart'
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
            domDisplay.html(scalexpertpluginTemplateShoppingCartFooter.content.cloneNode(true));
            domDisplay.find('.scalexpertplugin-content').html(jsonData.content);
            addEventOpenModal();
        }
    }

    function addEventOpenModal() {
        if ($(modalSelector).length) {
            $(modalSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {
                    let attrModal = $(elm).attr('data-idmodal');
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
                if (typeof elm !== 'undefined') {
                    $(elm).off();
                }
            });
        }
    }

    function eventPrestaShopUpdateCart() {
        prestashop.on('updateCart', (event) => {
            domDisplay = $('#scalexpertplugin-displayShoppingCartFooter');
            if (domDisplay.length) {
                callAjax();
            }
        });
    }
});
