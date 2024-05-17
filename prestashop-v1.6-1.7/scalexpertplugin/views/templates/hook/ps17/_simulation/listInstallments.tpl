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
                {l s='Montant total dû' mod='scalexpertplugin'}
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
                            {l s='Payer en %s fois' mod='scalexpertplugin' sprintf=[$solution.duration|default:'']}
                        {else}
                            {if $installment@first}
                                {l s='Aujourd\'hui' mod='scalexpertplugin'}
                            {else}
                                {l s='%sème prélèvement' mod='scalexpertplugin' sprintf=[$installment@iteration]}
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
                <span>{l s='Montant du financement :' mod='scalexpertplugin'}&nbsp;{$financedAmountFormatted|default:'NC'}.</span>&nbsp;
                <span>{l s='TAEG FIXE :' mod='scalexpertplugin'}&nbsp;{$solution.effectiveAnnualPercentageRateFormatted|default:'NC'}.</span>&nbsp;

                {if !empty($solution.isLongFinancingSolution)}
                    <span>{l s='Taux débiteur fixe :' mod='scalexpertplugin'}&nbsp;{$solution.nominalPercentageRateFormatted|default:'NC'}.</span>&nbsp;
                    {l s='Coût du crédit :' mod='scalexpertplugin'}&nbsp;{$solution.totalCostFormatted|default:'NC'}.<br/>
                {/if}

                {if !empty($solution.isLongFinancingSolution)}
                    <span>{l s='Frais de dossier :' mod='scalexpertplugin'}&nbsp;{$solution.feesAmountFormatted|default:'NC'}.</span>&nbsp;
                {else}
                    <span>{l s='Frais :' mod='scalexpertplugin'}&nbsp;{$solution.feesAmountFormatted|default:'NC'}.</span>&nbsp;
                {/if}

                <span>{l s='Montant total dû :' mod='scalexpertplugin'}&nbsp;{$solution.dueTotalAmountFormatted|default:'NC'}.</span>&nbsp;
            {/strip}
        </div>
    </div>
{/if}
