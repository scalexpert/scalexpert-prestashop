{if !empty($insuranceSubscriptionsByProduct)}
    <hr>
    <div>
        {foreach $insuranceSubscriptionsByProduct as $insuredProduct}
            <p><b>{$insuredProduct.insuranceName}</b></p>
            {if !empty($insuredProduct.subscriptions)}
                <ul>
                    {foreach $insuredProduct.subscriptions as $insuredProductSubscription}
                        <li> - {l s='Status' d='Modules.Scalexpertplugin.Shop'}
                            : {$insuredProductSubscription.consolidatedStatus}</li>
                    {/foreach}
                </ul>
            {/if}
            <br/>
        {/foreach}
    </div>
{/if}