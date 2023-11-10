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
								{l s='Insurance' mod='scalexpertplugin'} {$smarty.foreach.productSubscriptionForeach.iteration}
								({l s='Status:' mod='scalexpertplugin'} {$insuredProductSubscription.consolidatedStatus})
							</li>
						{else}
							<li>
								{l s='Status:' mod='scalexpertplugin'} {$insuredProductSubscription.consolidatedStatus}
							</li>
						{/if}
					{/foreach}
				{else}
					{l s='Waiting for payment' mod='scalexpertplugin'}
				{/if}
			</ul>
		{/foreach}
	</div>
{/if}
