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
        <h3>
            {$titleSolution|default:''}
        </h3>
        <div>
            <div class="sep-SimulationsModal-mainContent">
                <div class="sep-SimulationsModal-mainContent-left">
                    {$solution.designConfiguration.visualAdditionalInformation|default:'' nofilter}
                </div>
                <div class="sep-SimulationsModal-mainContent-right">
                    <div class="sep-SimulationsModal-mainContent-right-content">
                        <div class="sep-SimulationsModal-mainContent-right-top">
                            {l s='Simulez votre paiement' mod='scalexpertplugin'}

                            {if !empty($solution.designConfiguration.displayLogo)}
                                <img class="-logo"
                                     src="{$solution.designConfiguration.visualLogo|default:''}"
                                     alt="Logo {$titleSolution|default:''}"
                                >
                            {/if}
                        </div>

                        {include file='./listSimulations.tpl'
                            current=$key
                            md5Solution=$md5Solution
                        }

                        {include file='./infoMonthlyPayment.tpl'
                            simulation=$solution|default:''
                            isModal=true
                        }

                        {if !empty($solution.installments)}
                            {include file='./listInstallments.tpl'
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
