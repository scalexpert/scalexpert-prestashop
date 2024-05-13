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
                {l s='soit un [1]1er prélèvement[/1] de [2]%amountFirstStep%[/2]' d='Modules.Scalexpertplugin.Shop'
                    sprintf=[
                        '%amountFirstStep%' => $solution.installments.0.amountFormatted|default:'',
                        '[1]' => '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label">',
                        '[/1]' => '</span>',
                        '[2]' => '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value">',
                        '[/2]' => '</span>'
                    ]
                }
                {if !empty($solution.totalCost)}
                    &nbsp;{l s='(frais inclus)' d='Modules.Scalexpertplugin.Shop'}&nbsp;
                {/if}
                {l s='puis [1]%nbStepStay% prélèvements[/1] de [2]%amountStepStay%[/2]' d='Modules.Scalexpertplugin.Shop'
                    sprintf=[
                        '%nbStepStay%' => sizeof($solution.installments) - 1,
                        '%amountStepStay%' => $solution.installments.1.amountFormatted|default:'',
                        '[1]' => '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label">',
                        '[/1]' => '</span>',
                        '[2]' => '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value">',
                        '[/2]' => '</span>'
                    ]
                }
            {else}
                {if !empty($isModal)}
                    {l s='soit [1]%nbSteps% prélèvements[/1] de [2]%amountStep%[/2]' d='Modules.Scalexpertplugin.Shop'
                        sprintf=[
                            '%nbSteps%' => $solution.duration|default:'',
                            '%amountStep%' => $solution.installments.0.amountFormatted|default:'',
                            '[1]' => '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label">',
                            '[/1]' => '</span>',
                            '[2]' => '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value">',
                            '[/2]' => '</span>'
                        ]
                    }
                {else}
                    {l s='soit [1]%amountStep% / mois[/1]' d='Modules.Scalexpertplugin.Shop'
                        sprintf=[
                            '%amountStep%' => $solution.installments.0.amountFormatted|default:'',
                            '[1]' => '<span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value">',
                            '[/1]' => '</span>'
                        ]
                    }
                {/if}
            {/if}
        {/strip}
    </div>
{/if}
