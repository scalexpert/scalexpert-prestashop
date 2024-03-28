{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{if (isset($status) == true) && ($status == 'ok')}
	<div class="box">
		{l s='Your informations payment:' mod='scalexpertplugin'}
		<br />- {l s='Amount' mod='scalexpertplugin'} : <span class="price"><strong>{$total|escape:'htmlall':'UTF-8'}</strong></span>
		<br />- {l s='Order reference' mod='scalexpertplugin'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
		<br />- {l s='Financial status' mod='scalexpertplugin'} : <span class="subscription_status" {if !empty($subscription_status_error)}style="color:red;" {/if}><strong>{$subscription_status|escape:'html':'UTF-8'}</strong></span>
		<br /><br />{l s='An email has been sent with this information.' mod='scalexpertplugin'}
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='scalexpertplugin'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='scalexpertplugin'}</a>
	</div>
{else}
	<p class="alert alert-warning">{l s='Your order on %s has not been accepted.' sprintf=[$shop_name] mod='scalexpertplugin'}</p>
	<p>
		<br />- {l s='Reference' mod='scalexpertplugin'} <span class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
		<br /><br />{l s='Please, try to order again.' mod='scalexpertplugin'}
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='scalexpertplugin'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='scalexpertplugin'}</a>
	</p>
{/if}
