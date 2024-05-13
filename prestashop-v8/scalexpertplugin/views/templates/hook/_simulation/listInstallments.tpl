{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($solution) && !empty($solution.installments)}
    <div class="sep-Simulations-installments">
        <div class="sep-Simulations-installments-total sep-Simulations-installments-item">
            <span class="sep-Simulations-installments-item-label">
                {if !empty($solution.isLongFinancingSolution)}
                    {l s='Montant total dû' d='Modules.Scalexpertplugin.Shop'}
                {else}
                    {l s='Montant total dû' d='Modules.Scalexpertplugin.Shop'}
                    {if !empty($solution.totalCost)}
                        {l s='(dont frais)' d='Modules.Scalexpertplugin.Shop'}
                    {/if}
                {/if}
            </span>
            <span class="sep-Simulations-installments-item-value">
                {$solution.dueTotalAmountFormatted|default:''}
            </span>
        </div>

        {foreach $solution.installments as $installment}
            {if !empty($installment)}
                <div class="sep-Simulations-installments-item">
                    <span class="sep-Simulations-installments-item-label">
                        {if !empty($solution.isLongFinancingSolution)}
                            {l s='Payer en %nbSteps% fois' d='Modules.Scalexpertplugin.Shop' sprintf=['%nbSteps%' => $solution.duration|default:'']}
                        {else}
                            {if $installment@first}
                                {l s='Aujourd\'hui' d='Modules.Scalexpertplugin.Shop'}
                            {else}
                                {l s='%step%ème prélèvement' d='Modules.Scalexpertplugin.Shop' sprintf=['%step%' => $installment@iteration]}
                            {/if}
                        {/if}
                    </span>
                    <span class="sep-Simulations-installments-item-value">
                        {$installment.amountFormatted|default:''}
                    </span>
                </div>
            {/if}
        {/foreach}

        <div class="sep-Simulations-installments-mentions">
            {strip}
                <span>{l s='Montant du financement :' d='Modules.Scalexpertplugin.Shop'}&nbsp;{$financedAmountFormatted|default:'NC'}.</span>&nbsp;
                <span>{l s='TAEG FIXE :' d='Modules.Scalexpertplugin.Shop'}&nbsp;{$solution.effectiveAnnualPercentageRateFormatted|default:'NC'}.</span>&nbsp;

                {if !empty($solution.isLongFinancingSolution)}
                    <span>{l s='Taux débiteur fixe :' d='Modules.Scalexpertplugin.Shop'}&nbsp;{$solution.nominalPercentageRateFormatted|default:'NC'}.</span>&nbsp;
                    {l s='Coût du crédit :' d='Modules.Scalexpertplugin.Shop'}&nbsp;{$solution.totalCostFormatted|default:'NC'}.<br/>
                {/if}

                {if !empty($solution.isLongFinancingSolution)}
                    <span>{l s='Frais de dossier :' d='Modules.Scalexpertplugin.Shop'}&nbsp;{$solution.feesAmountFormatted|default:'NC'}.</span>&nbsp;
                {else}
                    <span>{l s='Frais :' d='Modules.Scalexpertplugin.Shop'}&nbsp;{$solution.feesAmountFormatted|default:'NC'}.</span>&nbsp;
                {/if}

                <span>{l s='Montant total dû :' d='Modules.Scalexpertplugin.Shop'}&nbsp;{$solution.dueTotalAmountFormatted|default:'NC'}.</span>&nbsp;
            {/strip}
        </div>
    </div>
{/if}
