{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{if !empty($availableFinancialSolutions)}
    {$arrayModalContent=[]}

    <form method="post" action="{$redirectControllerLink|default:'#'}">
        <ul class="list-group list-group-flush">
            {foreach $availableFinancialSolutions as $availableSolution}
                {if !empty($availableSolution)}
                    {assign var="md5Id" value={$availableSolution.solutionCode|default:''}|cat:$availableSolution.marketCode|default:'':$availableSolution.conditions|default:''|md5}

                    <li class="list-group-item sep_financialSolution">
                        {if !empty($availableSolution.useLogo)}
                            <img src="{$availableSolution.visualLogo|default:''}"
                                 alt="{$availableSolution.visualTitle|default:''|strip_tags}"
                            >
                        {/if}

                        <div class="sep_financialSolution-title">
                            {$availableSolution.visualTitle|default:''|strip_tags}

                            {if !empty($availableSolution.simulation)}
                                {if !empty($availableSolution.simulation.hasFeesOnFirstInstallment)}
                                    <div>
                                {/if}
                                {if !$availableSolution.simulation.isLongFinancingSolution}
                                    {include file='module:scalexpertplugin/views/templates/hook/ps17/_simulation/infoMonthlyPayment.tpl'
                                    solution=$availableSolution.simulation|default:'' }
                                {/if}
                                {if !empty($availableSolution.simulation.hasFeesOnFirstInstallment)}
                                    </div>
                                {/if}
                            {/if}
                        </div>

                        <div>
                            {if !empty($availableSolution.visualInformationIcon)}
                                <img class="sep_financialSolution-i"
                                     src="{$availableSolution.visualInformationIcon|default:''}"
                                     alt="{l s='Information' mod='scalexpertplugin'}"
                                     width="16"
                                     height="16"
                                     data-modal="sep_openModal"
                                    {if !empty($availableSolution.simulation)}
                                        {$md5Solution = ($availableSolution.solutionCode|default:''|cat:$availableSolution.simulation.duration|default:'')|md5}
                                        data-idmodal="#modal-simulation"
                                        data-idsolution="{$md5Solution}"
                                    {else}
                                        data-idmodal="#{$md5Id|default:''}"
                                    {/if}
                                >
                            {/if}
                        </div>

                        <button class="btn btn-primary disabled"
                                type="submit"
                                name="solutionCode"
                                value="{$availableSolution.solutionCode|default:''}"
                                disabled
                        >
                            {l s='Payer' mod='scalexpertplugin'}
                        </button>

                        {if !empty($availableSolution.simulation)}
                            {foreach $availableSolution.simulationPopinData as $key => $solution}
                                {capture name='modal'}{/capture}
                                {if !empty($solution)}
                                    {$titleSolution = $solution.designConfiguration.visualTitle|default:''|strip_tags}
                                    {$md5Solution = ($solution.designConfiguration.solutionCode|default:''|cat:$solution.duration|default:'')|md5}

                                    {capture name='modal'}
                                        {include file='module:scalexpertplugin/views/templates/hook/ps17/_simulation/modalSimulationContent.tpl'
                                                 solutions=$availableSolution.simulationPopinData|default:''
                                                 solution=$solution|default:''
                                                 titleSolution=$titleSolution|default:''
                                                 key=$key|default:0
                                                 md5Solution=$md5Solution|default:''
                                        }
                                    {/capture}

                                    {if !empty($smarty.capture.modal)}
                                        {$arrayModalContent[$md5Solution] = $smarty.capture.modal|default:''}
                                    {/if}
                                {/if}
                            {/foreach}
                        {else}
                            {include file="module:scalexpertplugin/views/templates/hook/ps17/ProductFinancialsContentModal.tpl" md5Id=$md5Id|default:'' nameModule=$scalexpertplugin_global.nameModule|default:''}
                        {/if}
                    </li>
                {/if}
            {/foreach}
        </ul>

        {if !empty($arrayModalContent)}
            <div id="modal-simulation"
                 class="sep_contentModal sep-SimulationsModal modal fade"
                 role="dialog"
                 aria-hidden="true"
            >
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="sep-Simulations-groupSolution">
                            <div class="sep-Simulations-modal">
                                {foreach $arrayModalContent as $content}
                                    {if !empty($content)}
                                        {$content nofilter}
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
    </form>
{/if}
