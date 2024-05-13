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
        modalSelector = '.sep_main_productsButtons [data-modal="sep_openModal"],.sep-Simulations .sep-Simulations-solution [data-modal="sep_openModal"]',
        xhr = '',
        solutionSelector = '.sep-Simulations-solution [data-js="selectSolutionSimulation"]';

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
            addEventChangeSimulation();
        }
    }

    function initProductModals() {
        if ($(modalSelector).length) {
            $(modalSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {
                    let attrModal = $(elm).attr('href');

                    if(typeof attrModal === 'undefined') {
                        attrModal = $(elm).attr('data-idmodal');
                    }

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

    function addEventChangeSimulation() {
        if($(solutionSelector).length) {
            $(solutionSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {
                    let idSolution = $(elm).attr('data-id');
                    if(typeof idSolution !== 'undefined' && idSolution) {
                        let idGroupSolution = $(elm).attr('data-groupid');
                        $(elm).off().on('click', function (e) {
                            e.preventDefault();

                            let idGroupSolutionSelect = '.sep-Simulations-groupSolution[data-id="' + idGroupSolution + '"]';
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
