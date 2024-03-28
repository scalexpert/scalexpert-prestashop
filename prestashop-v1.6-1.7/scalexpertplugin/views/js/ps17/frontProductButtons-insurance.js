/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(function () {
    let domDisplay = '';
    let modalSelector = '.sep_insuranceSolution [data-modal="sep_openModal"]';
    let xhr = '';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displayProductButtons-insurance');

        if (domDisplay.length) {
            callAjax();
            eventPrestaShopUpdateProduct();
        }
    });

    function callAjax() {
        var idProductAttribute = getIdProductAttribute();

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
                    idProductAttribute: idProductAttribute,
                    type: 'insurance'
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
                if (typeof elm !== 'undefined' && $(elm).length) {
                    $(elm).off();
                }
            });
        }
    }

    function eventPrestaShopUpdateProduct() {
        prestashop.on('updatedProduct', (event) => {
            domDisplay = $('#scalexpertplugin-displayProductButtons-insurance');
            if (domDisplay.length) {
                callAjax();
            }
        });
    }

    function getIdProductAttribute() {
        var idProductAttribute = {};
        if($('#add-to-cart-or-refresh').length) {
            addToCartOrRefreshForm = $('#add-to-cart-or-refresh');
            const formData = new FormData(addToCartOrRefreshForm[0]);
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('group[')) {
                    var customKey = key.replace('group[', '').replace(']', '');
                    idProductAttribute[customKey] = value;
                }
            }

            return idProductAttribute;
        }
        return 0;
    }
});
