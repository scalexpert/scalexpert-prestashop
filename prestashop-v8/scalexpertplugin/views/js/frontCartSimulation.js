/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(function () {
    let domDisplay = '',
        modalSelector = '.sep-Simulations [data-modal="sep_openModal"]',
        xhr = '',
        solutionSelector = '.sep-Simulations-solution [data-js="selectSolutionSimulation"]';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displaySimulationShoppingCartFooter');
        if (domDisplay.length) {
            callAjax();
            eventPrestaShopUpdateCart();
        }
    });

    function callAjax() {
        if (typeof getFinancialInsertsOnCartAjaxURL !== 'undefined') {
            abortAjax();
            xhr = $.ajax({
                method: "POST",
                headers: {"cache-control": "no-cache"},
                url: getFinancialInsertsOnCartAjaxURL,
                async: true,
                cache: false,
                dataType: 'json',
                beforeSend: clearResult()
            })
            .done(function (jsonData) {
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
        if (typeof jsonData !== 'undefined') {
            if (typeof jsonData.simulationInsert !== 'undefined' && jsonData.simulationInsert.length) {
                domDisplay.html(jsonData.simulationInsert);
                addEventChangeSimulation();
                addEventOpenModal();
            }
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

    function addEventChangeSimulation() {
        if($(solutionSelector).length) {
            $(solutionSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {
                    let idSolution = $(elm).attr('data-id');
                    if (typeof idSolution !== 'undefined' && idSolution) {
                        $(elm).off().on('click', function (e) {
                            e.preventDefault();

                            let idGroupSolutionSelect = '.sep-Simulations-groupSolution';
                            $(idGroupSolutionSelect + ' .sep-Simulations-solution').hide();
                            $(idGroupSolutionSelect + ' .sep-Simulations-solution[data-id="' + idSolution + '"]').show();

                            return;
                        });
                    }

                }
            });
        }
    }

    function eventPrestaShopUpdateCart() {
        prestashop.on('updateCart', (event) => {
            domDisplay = $('#scalexpertplugin-displaySimulationShoppingCartFooter');
            if (domDisplay.length) {
                callAjax();
            }
        });
    }
});
