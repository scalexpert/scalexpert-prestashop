/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(function () {
    var domDisplay = '',
        modalSelector = '.sep-Simulations [data-modal="sep_openModal"]',
        xhr = '',
        solutionSelector = '.sep-Simulations-solution [data-js="selectSolutionSimulation"]';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displaySimulationShoppingCartFooter');

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
                    action: 'cart',
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
            initCartSimulationModals();
            addEventChangeSimulation();
        }
    }

    function initCartSimulationModals() {
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

    function eventUpdateQuantity() {
        $('.cart_quantity_up, .cart_quantity_down, .cart_quantity_delete').on('click', function () {
            reloadSimulation();
        });
        $('.cart_quantity_input').on('change',  function () {
            reloadSimulation();
        });
    }

    function reloadSimulation() {
        setTimeout(function() {
            domDisplay = $('#scalexpertplugin-displaySimulationShoppingCartFooter');
            if (domDisplay.length) {
                callAjax();
            }
        }, 250);
    }

    function addEventChangeSimulation() {
        if($(solutionSelector).length) {
            $(solutionSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {

                    var idSolution = $(elm).attr('data-id');
                    if (typeof idSolution !== 'undefined' && idSolution) {
                        $(elm).off().on('click', function (e) {
                            e.preventDefault();

                            var idGroupSolutionSelect = '.sep-Simulations-groupSolution';
                            $(idGroupSolutionSelect + ' .sep-Simulations-solution').hide();
                            $(idGroupSolutionSelect + ' .sep-Simulations-solution[data-id="' + idSolution + '"]').show();

                            return;
                        });
                    }
                }
            });
        }
    }
});
