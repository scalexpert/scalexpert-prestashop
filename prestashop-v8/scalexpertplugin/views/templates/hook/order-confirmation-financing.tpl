{if !empty($financialSubscriptions)}
    {foreach $financialSubscriptions as $financialSubscription}
        <p>
            {l s='Your order on %s is complete.' sprintf=[$shop.name|default:''] d='Modules.Scalexpertplugin.Shop'}
            <br />{l s='Your informations payment:' d='Modules.Scalexpertplugin.Shop'}
        </p>
        <ul>
            <li>- {l s='Amount' d='Modules.Scalexpertplugin.Shop'} : <span class="price"><strong>{$financialSubscription.buyerFinancedAmount|escape:'htmlall':'UTF-8'}</strong></span></li>
            <li>- {l s='Order reference' d='Modules.Scalexpertplugin.Shop'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span></li>
            <li>- {l s='Financial status' d='Modules.Scalexpertplugin.Shop'} : <span class="subscription_status"><strong>{$financialSubscription.consolidatedStatus|escape:'html':'UTF-8'}</strong></span></li>
        </ul>
        <p>
            {l s='An email has been sent with this information.' d='Modules.Scalexpertplugin.Shop'}
            <br /><br />{l s='If you have questions, comments or concerns, please contact our [1]expert customer support team[/1].' d='Modules.Scalexpertplugin.Shop' sprintf=['[1]' => "<a href='{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}'>", '[/1]' => '</a>']}
        </p>
    {/foreach}
{/if}
