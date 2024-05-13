{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($solution.installments.0.amount)}
    <div class="sep-Simulations-solution-infoMonthlyPayment">
        {strip}
            {if !empty($solution.hasFeesOnFirstInstallment)}
                {l s='soit un [1]1er prélèvement[/1] de [2]%amountFirstStep%[/2]' mod='scalexpertplugin'
                    sprintf=[
                        '%amountFirstStep%' => $solution.installments.0.amountFormatted|default:''
                    ]
                    tags=[
                        '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label">',
                        '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value">'
                    ]
                }
                {if !empty($solution.totalCost)}
                    &nbsp;{l s='(frais inclus)' mod='scalexpertplugin'}&nbsp;
                {/if}
                {l s='puis [1]%nbStepStay% prélèvements[/1] de [2]%amountStepStay%[/2]' mod='scalexpertplugin'
                    sprintf=[
                        '%nbStepStay%' => sizeof($solution.installments) - 1,
                        '%amountStepStay%' => $solution.installments.1.amountFormatted|default:''
                    ]
                    tags=[
                        '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label">',
                        '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value">'
                    ]
                }
            {else}
                {if !empty($isModal)}
                    {l s='soit [1]%nbSteps% prélèvements[/1] de [2]%amountStep%[/2]' mod='scalexpertplugin'
                        sprintf=[
                            '%nbSteps%' => $solution.duration|default:'',
                            '%amountStep%' => $solution.installments.0.amountFormatted|default:''
                        ]
                        tags=[
                            '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label">',
                            '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value">'
                        ]
                    }
                {else}
                    {l s='soit [1]%amountStep% / mois[/1]' mod='scalexpertplugin'
                        sprintf=[
                            '%amountStep%' => $solution.installments.0.amountFormatted|default:''
                        ]
                        tags=[
                            '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value">'
                        ]
                    }
                {/if}
            {/if}
        {/strip}
    </div>
{/if}
