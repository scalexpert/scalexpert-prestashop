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
        productId = 0,
        modalSelector = '.sep_financialSolution [data-modal="sep_openModal"],.sep-Simulations .sep-Simulations-solution [data-modal="sep_openModal"]',
        xhr = '',
        solutionSelector = '.sep-Simulations-solution [data-js="selectSolutionSimulation"]';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displayProductAdditionalInfo');
        if (domDisplay.length && $('#product_page_product_id').length) {
            productId = parseInt($('#product_page_product_id').val());
            callAjax();
            eventPrestaShopUpdateProduct();
        }
    });

    function callAjax() {
        if (typeof getFinancialInsertsOnProductAjaxURL !== 'undefined' &&
            !isNaN(productId)
        ) {
            let id_product = parseInt($('#product_page_product_id').val());
            abortAjax();
            xhr = $.ajax({
                method: "POST",
                headers: {"cache-control": "no-cache"},
                url: getFinancialInsertsOnProductAjaxURL,
                async: true,
                cache: false,
                dataType: 'json',
                data: {id_product: id_product},
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
        let htmlFinancial = '';
        let htmlSimulation = '';
        if (typeof jsonData !== 'undefined') {
            if (typeof jsonData.financialInserts !== 'undefined') { // Classic financial solution
                jsonData.financialInserts.forEach(function (elm) {
                    if (typeof elm !== 'undefined' && typeof elm.formattedInsert !== 'undefined') {
                        htmlFinancial += elm.formattedInsert;
                    }
                });

                domDisplay.html(htmlFinancial);
                addEventOpenModal();
            }
            if (typeof jsonData.simulationInsert !== 'undefined') { // Simulation on product page
                htmlSimulation = jsonData.simulationInsert;
                domDisplay.html(htmlSimulation);
                addEventChangeSimulation();
                addEventOpenModal();
            }
        }
    }

    function addEventOpenModal() {
        if ($(modalSelector).length) {
            $(modalSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {
                    let attrModal = $(elm).attr('href');

                    if(typeof attrModal === 'undefined') {
                        attrModal = $(elm).attr('data-idmodal');
                    }

                    if(typeof attrModal !== 'undefined' && attrModal) {
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

    function eventPrestaShopUpdateProduct() {
        prestashop.on('updatedProduct', (event) => {
            domDisplay = $('#scalexpertplugin-displayProductAdditionalInfo');
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
