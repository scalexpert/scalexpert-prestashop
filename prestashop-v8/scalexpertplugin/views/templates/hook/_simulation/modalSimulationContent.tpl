{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($solution)}


    <div class="sep-Simulations-solution"
         data-id="{$md5Solution|default:''}"
    >
        <div class="modal-header">
            <div class="h2 modal-header-title">
                {$titleSolution|default:''}
            </div>
            <button type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="{l s='Close' d='Shop.Theme.Global'}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="modal-body">

            <div class="sep-SimulationsModal-mainContent">
                <div class="sep-SimulationsModal-mainContent-left">
                    {$solution.designConfiguration.visualAdditionalInformation|default:'' nofilter}
                </div>
                <div class="sep-SimulationsModal-mainContent-right">
                    <div class="sep-SimulationsModal-mainContent-right-content">
                        <div class="sep-SimulationsModal-mainContent-right-top">
                            {l s='Simulez votre paiement' d='Modules.Scalexpertplugin.Shop'}

                            {if !empty($solution.designConfiguration.visualLogo)}
                                <img class="-logo"
                                     src="{$solution.designConfiguration.visualLogo|default:''}"
                                     alt="Logo {$titleSolution|default:''}"
                                >
                            {/if}
                        </div>

                        {include file='module:scalexpertplugin/views/templates/hook/_simulation/listSimulations.tpl'
                            current=$key
                            md5Solution=$md5Solution
                        }

                        {include file='module:scalexpertplugin/views/templates/hook/_simulation/infoMonthlyPayment.tpl'
                            simulation=$solution|default:''
                            isModal=true
                        }

                        {if !empty($solution.installments)}
                            {include file='module:scalexpertplugin/views/templates/hook/_simulation/listInstallments.tpl'
                                solution=$solution|default:''
                            }
                        {/if}
                    </div>
                </div>
            </div>

            <div class="part_bottom">
                {$solution.designConfiguration.visualLegalText|default:'' nofilter}
            </div>
        </div>
    </div>
{/if}
