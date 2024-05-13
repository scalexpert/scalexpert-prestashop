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
        modalSelector = '.sep_main_productsButtons [data-modal="sep_openModal"], .sep-Simulations [data-modal="sep_openModal"]',
        solutionSelector = '.sep-Simulations-solution [data-js="selectSolutionSimulation"]';

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
            addEventChangeSimulation();
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
