{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{if !empty($availableFinancialSolutions)}
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
                        </div>

                        {if !empty($availableSolution.visualInformationIcon)}
                            <img class="sep_financialSolution-i"
                                 src="{$availableSolution.visualInformationIcon|default:''}"
                                 alt="{l s='Information' mod='scalexpertplugin'}"
                                 width="16"
                                 height="16"
                                 data-modal="sep_openModal"
                                 data-idmodal="#{$md5Id|default:''}"
                            >
                        {/if}

                        <button class="btn btn-primary disabled"
                                type="submit"
                                name="solutionCode"
                                value="{$availableSolution.solutionCode|default:''}"
                                disabled
                        >
                            {l s='Payer' mod='scalexpertplugin'}
                        </button>
                        {include file="module:scalexpertplugin/views/templates/hook/ps17/ProductFinancialsContentModal.tpl" md5Id=$md5Id|default:'' nameModule=$scalexpertplugin_global.nameModule|default:''}
                    </li>
                {/if}
            {/foreach}
        </ul>
    </form>
{/if}
