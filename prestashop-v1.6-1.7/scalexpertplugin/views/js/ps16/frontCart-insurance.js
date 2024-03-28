/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(function () {
    var domDisplay = '';
    var modalSelector = '.sep_insuranceSolution [data-modal="sep_openModal"]';
    var selectorContentRadioInsurance = '.sep_insuranceSolution-choices';
    var xhr = '';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displayShoppingCartFooter');

        if (domDisplay.length) {
            callAjax();
            eventUpdateQuantity();
        }
    });

    function callAjax() {
        if (typeof scalexpertpluginFrontUrl !== 'undefined') {
            abortAjax();
            xhr = $.ajax({
                type: 'POST',
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
            }).success(function (jsonData) {
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
            initCartInsuranceModals();
            initRadio();
        }
    }

    function initCartInsuranceModals() {
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

    function initRadio() {
        var $radios = getRadioInsurance();

        if($radios !== null && $radios.length && typeof bindUniform !== 'undefined') {
            bindUniform();
        }
    }


    function getRadioInsurance() {
        if( domDisplay.length &&
            typeof selectorContentRadioInsurance !== 'undefined' &&
            domDisplay.find(selectorContentRadioInsurance).find('input[type="radio"]').length ) {
            return domDisplay.find(selectorContentRadioInsurance).find('input[type="radio"]');
        }
        return null;
    }

    function eventUpdateQuantity() {
        $('.cart_quantity_up, .cart_quantity_down, .cart_quantity_delete').on('click', function () {
            reloadInsurance();
        });
        $('.cart_quantity_input').on('change',  function () {
            reloadInsurance();
        });
    }

    function reloadInsurance() {
        setTimeout(function() {
            domDisplay = $('#scalexpertplugin-displayShoppingCartFooter');
            if (domDisplay.length) {
                callAjax();
            }
        }, 250);
    }
});
