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

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displayProductButtons-insurance');

        if (domDisplay.length) {
            callAjax();
        }
    });

    function callAjax() {
        var idProductAttribute = getIdProductAttribute();

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
                    idProductAttribute: idProductAttribute,
                    type: 'insurance'
                },
                beforeSend: clearResult()
            }).success(function (jsonData) {
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
            addEventChangeRadioInsurance();
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

    function addEventChangeRadioInsurance() {
        var $radios = getRadioInsurance();

        if($radios !== null && $radios.length) {
            if (typeof bindUniform !=='undefined') {
                bindUniform();
            }

            $radios.off('change').on('change', function() {
                if(typeof $(this).val() !== 'undefined' && $(this).val()) {
                    callAjaxChangeRadioInsurance($(this).val());
                }
            });
        }
    }

    function removeEventChangeRadioInsurance() {
        var $radios = getRadioInsurance();
        if($radios !== null && $radios.length) {
            $radios.off('change');
        }
    }

    function callAjaxChangeRadioInsurance(valueRadio) {
        if (typeof scalexpertpluginAddToCartUrl !== 'undefined' &&
            typeof valueRadio !== 'undefined'
        ) {
            var idProductAttribute = getIdProductAttribute();

            $.ajax({
                type: 'POST',
                headers: {"cache-control": "no-cache"},
                url: scalexpertpluginAddToCartUrl,
                async: true,
                cache: false,
                dataType: 'json',
                data: {
                    ajax: true,
                    action: 'addToCart',
                    id_cart: scalexpertpluginCartId,
                    id_product: scalexpertpluginProductId,
                    id_product_attribute: idProductAttribute,
                    insurance: valueRadio
                }
            }).success(function (jsonData) {
                /** success ajax select insurance **/
            }).error(function (jsonData) {
                /** error ajax select insurance **/
            });
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

    function getIdProductAttribute() {
        var idProductAttribute = 0;
        if($('#idCombination').length && $('#idCombination').val()) {
            idProductAttribute = parseInt($('#idCombination').val());
            if(!isNaN(idProductAttribute)) {
                return idProductAttribute;
            }
        }
        return 0;
    }
});
