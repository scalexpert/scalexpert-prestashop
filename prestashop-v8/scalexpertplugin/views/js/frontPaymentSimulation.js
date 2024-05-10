/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(function () {
    let solutionSelector = '.sep-Simulations-solution [data-js="selectSolutionSimulation"]',
        modalSelector = '.payment-options [data-modal="sep_openModal"]';

    $(document).ready(function () {
        addEventChangeSimulation();
        addEventOpenModal();
    });


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

    function addEventOpenModal() {
        if ($(modalSelector).length) {
            $(modalSelector).each(function (i, elm) {
                if (typeof elm !== 'undefined' && $(elm).length) {
                    let idSolution = $(elm).attr('data-idsolution');

                    if(typeof idSolution !== 'undefined' && idSolution) {
                        $(elm).on('click', function (e) {
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
});
