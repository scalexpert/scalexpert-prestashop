/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

$(function () {
    let domDisplay = '';
    let productId = 0;
    let modalSelector = '.sep_insuranceSolution [data-modal="sep_openModal"]';
    let xhr = '';

    $(document).ready(function () {
        domDisplay = $('#scalexpertplugin-displayProductAdditionalInfo-Insurance');
        if (domDisplay.length && $('#product_page_product_id').length) {
            productId = parseInt($('#product_page_product_id').val());
            callAjax();
            eventPrestaShopUpdateProduct();
        }
    });

    function callAjax() {
        if (typeof getInsuranceInsertsAjaxURL !== 'undefined' &&
            !isNaN(productId)
        ) {
            var combinationGroupsData = getIdProductAttribute();

            let id_product = parseInt($('#product_page_product_id').val());
            abortAjax();
            xhr = $.ajax({
                method: "POST",
                headers: {"cache-control": "no-cache"},
                url: getInsuranceInsertsAjaxURL,
                async: true,
                cache: false,
                dataType: 'json',
                data: {
                    id_product: id_product,
                    combination_groups: combinationGroupsData
                },
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
        let html = ''
        if (typeof jsonData !== 'undefined') {
            if (typeof jsonData.insuranceInserts !== 'undefined') {
                jsonData.insuranceInserts.forEach(function (elm) {
                    if (typeof elm !== 'undefined' && typeof elm.formattedInsert !== 'undefined') {
                        html += elm.formattedInsert;
                    }
                });
            }
        }

        domDisplay.html(html);
        addEventOpenModal();
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

    function eventPrestaShopUpdateProduct() {
        prestashop.on('updatedProduct', (event) => {
            domDisplay = $('#scalexpertplugin-displayProductAdditionalInfo-Insurance');
            if (domDisplay.length) {
                callAjax();
            }
        });
    }

    function getIdProductAttribute() {
        var idProductAttribute = {};
        if ($('#add-to-cart-or-refresh').length) {
            var addToCartOrRefreshForm = $('#add-to-cart-or-refresh');
            const formData = new FormData(addToCartOrRefreshForm[0]);
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('group[')) {
                    var customKey = key.replace('group[', '').replace(']', '');
                    idProductAttribute[customKey] = value;
                }
            }

            return idProductAttribute;
        }
        return 0
    }
});
