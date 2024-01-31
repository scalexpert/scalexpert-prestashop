{if !empty($insuranceSubscriptionsByProduct)}
<div id="customerCard" class="customer card">
    <div class="card-header">
        <h3 class="card-header-title">
            {l s='Insurance subscriptions' mod='scalexpertplugin'}
        </h3>
    </div>

    <div class="card-body">
        {foreach from=$insuranceSubscriptionsByProduct item=insuranceSubscriptionsByProductElement}

        <p><b>{$insuranceSubscriptionsByProductElement.insuranceName}</b></p>

        {if count($insuranceSubscriptionsByProductElement.subscriptions)}
        {foreach from=$insuranceSubscriptionsByProductElement.subscriptions item=insuranceSubscription}
        <div class="mt-2 info-block">
            <div class="row">
                {l s='Subscription ID' mod='scalexpertplugin'}
                : {$insuranceSubscription.subscriptionId}
            </div>
            <div class="row">
                {l s='Insurance price' mod='scalexpertplugin'}
                : {$insuranceSubscription.producerQuoteInsurancePrice}
            </div>
            <div class="row">
                {l s='Insurance duration' mod='scalexpertplugin'}
                : {$insuranceSubscription.duration}
            </div>
            <div class="row">
                {l s='Status' mod='scalexpertplugin'}
                : {$insuranceSubscription.consolidatedStatus}
            </div>
        </div>
        {/foreach}
        {else}
        <div class="mt-2 info-block">
            <div class="row">
                {l s='En attente du paiement' mod='scalexpertplugin'}
            </div>
        </div>
        {/if}
        {/foreach}
    </div>
</div>
{/if}


{if !empty($financialSubscriptions)}
<div id="customerCard" class="customer card">
    <div class="card-header">
        <h3 class="card-header-title">
            {l s='Financing subscriptions' mod='scalexpertplugin'}
        </h3>
    </div>

    <div class="card-body">
        {foreach from=$financialSubscriptions item=financialSubscription}
        <div class="mt-2 info-block">
            <div class="row">
                {l s='Subscription ID' mod='scalexpertplugin'}
                : {$financialSubscription.creditSubscriptionId}
            </div>
            <div class="row">
                {l s='Consolidated status' mod='scalexpertplugin'}
                : {$financialSubscription.consolidatedStatusDisplay}
            </div>
            <div class="row">
                {l s='Buyer financed amount' mod='scalexpertplugin'}
                : {$financialSubscription.buyerFinancedAmountDisplay}
            </div>
            <div class="row">
                {l s='Registration timestamp' mod='scalexpertplugin'}
                : {$financialSubscription.registrationTimestamp|date_format:"d/m/Y H:i:s"}
            </div>
            {if "ACCEPTED" === $financialSubscription.consolidatedStatus}
                <br/>
                <div class="row">
                    <form action="" method="post" onsubmit="return confirm('{l s='Do you confirm sending a cancel request?' mod='scalexpertplugin'}');">
                        <div id="message" class="form-horizontal">
                            <input type="hidden" name="creditSubscriptionId" value="{$financialSubscription.creditSubscriptionId}">

                            <label for="scalexpert_cancel_financial_subscription_amount">
                                {l s='Cancellation amount' mod='scalexpertplugin'} ({l s='format : 1234.56' mod='scalexpertplugin'}) :
                            </label>
                            <input type="text" id="scalexpert_cancel_financial_subscription_amount" name="buyerFinancedAmount" value="{$financialSubscription.buyerFinancedAmount}" class="form-control">
                            <br/>

                            <button type="submit" id="submitSubscriptionCancelRequest" class="btn btn-primary pull-right" name="submitSubscriptionCancelRequest">
                                {l s='Cancel subscription' mod='scalexpertplugin'}
                            </button>
                        </div>
                    </form>
                </div>
            {/if}
        </div>
        {/foreach}
    </div>
</div>
{/if}
