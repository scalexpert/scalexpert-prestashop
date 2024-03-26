/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(function () {
    let modalSelector = '.payment-options [data-modal="sep_openModal"]';
    let conditionsSelector;

    $(document).ready(function () {
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

        // Condition for chekout button (PrestaShop)
        conditionsSelector = '#conditions-to-approve';
        conditionsSelector = $(`${conditionsSelector} input[type="checkbox"]`);

        // Event when change payment option or condition
        $('body').on('change', conditionsSelector, toggleOrderButton);
        $('body').on('change', 'input[name="payment-option"]', toggleOrderButton);
    });

    function toggleOrderButton() {
        // Event change
        let show = true;

        // If condition not check, disable can checkout
        conditionsSelector.each((_, checkbox) => {
            if (!checkbox.checked) {
                show = false;
            }
        });

        prestashop.emit('termsUpdated', {
            isChecked: show,
        });

        const selectedOption = getSelectedOption();

        if (!selectedOption) {
            show = false;
        }

        let $selectedOption = $(`#${selectedOption}`);

        // Check if payment is module scalexpertplugin and grouped payment "binary"
        if($selectedOption.length && $selectedOption.attr('data-module-name') === 'scalexpertplugin' && $selectedOption.hasClass('binary')) {
            const paymentOption = getPaymentOptionSelector(selectedOption);

            // Enable or disable buttons if rules not valid
            let elements = document.querySelectorAll(`${paymentOption} button[type="submit"], ${paymentOption} input[type="submit"]`);
            if(elements.length) {
                elements.forEach((element) => {
                    if (show) {
                        element.removeAttribute('disabled');
                        $(element).removeClass('disabled');
                    } else {
                        element.setAttribute('disabled', !show);
                        $(element).addClass('disabled');
                    }
                });
            }
        }
    }

    function getSelectedOption() {
        return $('input[name="payment-option"]:checked').attr('id');
    }

    function getPaymentOptionSelector(option) {
        return `#${getSelectedOption()}-additional-information`;
    }
});
