{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($availableFinancialSolutions)}
    <form method="post" action="{$redirectControllerLink|default:'#'}">
        <ul class="list-group list-group-flush">
            {foreach $availableFinancialSolutions as $availableSolution}
                {if !empty($availableSolution)}
                    {assign var="md5Id" value={$availableSolution.solutionCode|default:''}|cat:$availableSolution.buyerBillingCountry|default:'':$availableSolution.type|default:''|md5}

                    <li class="list-group-item sep_financialSolution">
                        {if !empty($availableSolution.visualLogo)}
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
                                 alt="{l s='Information' d='Modules.Scalexpertplugin.Shop'}"
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
                            {l s='Payer' d='Modules.Scalexpertplugin.Shop'}
                        </button>

                        {include file='module:scalexpertplugin/views/templates/hook/contentModal.tpl' md5Id=$md5Id|default:'' financialSolution=$availableSolution}
                    </li>
                {/if}
            {/foreach}
        </ul>
    </form>
{/if}
