{**
* Copyright © DATASOLUTION.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    DATASOLUTION (https://www.datasolution.fr/)
* @copyright DATASOLUTION
* @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

{if (isset($status) == true) && ($status == 'ok')}
	<p>
		{l s='Your order on %s is complete.' sprintf=[$shop_name] d='scalexpertplugin'}
		<br />{l s='Your informations payment:' mod='scalexpertplugin'}
	</p>

	<section>
		<ul>
			<li>- {l s='Amount' mod='scalexpertplugin'} : <span class="price"><strong>{$total|escape:'htmlall':'UTF-8'}</strong></span></li>
			<li>- {l s='Order reference' mod='scalexpertplugin'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span></li>
			<li>- {l s='Financial status' mod='scalexpertplugin'} : <span class="subscription_status"><strong>{$subscription_status|escape:'html':'UTF-8'}</strong></span></li>
		</ul>
		<p>
			{l s='An email has been sent with this information.' mod='scalexpertplugin'}
			<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='scalexpertplugin'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='scalexpertplugin'}</a>
		</p>
	</section>
{else}
	<p class="alert alert-warning">{l s='Your order on %s has not been accepted.' sprintf=[$shop_name] mod='scalexpertplugin'}</p>
	<p>
		<br />- {l s='Reference' mod='scalexpertplugin'} <span class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
		<br /><br />{l s='Please, try to order again.' mod='scalexpertplugin'}
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='scalexpertplugin'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='scalexpertplugin'}</a>
	</p>
{/if}
