{if !empty($insuranceSubscriptionsByProduct)}
    <hr>
    <div>
        {foreach $insuranceSubscriptionsByProduct as $insuredProduct}
            <p><b>{$insuredProduct.insuranceName}</b></p>
            <ul>
                {if !empty($insuredProduct.subscriptions)}
                        {foreach name=productSubscriptionForeach from=$insuredProduct.subscriptions item=$insuredProductSubscription}
                            {if count($insuredProduct.subscriptions) > 1}
                                <li>
                                    {l s='Insurance' d='Modules.Scalexpertplugin.Shop'} {$smarty.foreach.productSubscriptionForeach.iteration}
                                    ({l s='Status:' d='Modules.Scalexpertplugin.Shop'} {$insuredProductSubscription.consolidatedStatus})
                                </li>
                            {else}
                                <li>
                                    {l s='Status:' d='Modules.Scalexpertplugin.Shop'} {$insuredProductSubscription.consolidatedStatus}
                                </li>
                            {/if}
                        {/foreach}
                {else}
                    {l s='Waiting for payment' d='Modules.Scalexpertplugin.Shop'}
                {/if}
            </ul>
        {/foreach}
    </div>
{/if}