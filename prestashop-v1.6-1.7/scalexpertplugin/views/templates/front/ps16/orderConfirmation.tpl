{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{capture name=path}{l s='Récapitulatif de commande' mod='scalexpertplugin'}{/capture}

<h1 class="page-heading">{l s='Récapitulatif de commande' mod='scalexpertplugin'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{include file="$tpl_dir./errors.tpl"}

{$HOOK_ORDER_CONFIRMATION}

<div class="box order-confirmation">
	<h3 class="page-subheading">{$subscription_status_title|escape:'html':'UTF-8'|default:''}</h3>
	<p>
		{$subscription_status_subtitle|escape:'html':'UTF-8'|default:''}
	</p>

	{if !empty($subscription_status) && $subscription_status == 'REJECTED'}
		<div class="button-reorder">
			{if isset($opc) && $opc}
				<a class="link-button button btn btn-default button-medium"
				   href="{$link->getPageLink('order-opc', true, NULL, "submitReorder&id_order={$id_order|intval}")|escape:'html':'UTF-8'}"
				   title="{l s='Commander à nouveau' mod='scalexpertplugin'}">
					<span>
						{l s='Commander à nouveau' mod='scalexpertplugin'}
					</span>
				</a>
			{else}
				<a class="link-button button btn btn-default button-medium"
				   href="{$link->getPageLink('order', true, NULL, "submitReorder&id_order={$id_order|intval}")|escape:'html':'UTF-8'}"
				   title="{l s='Commander à nouveau' mod='scalexpertplugin'}">
					<span>
						{l s='Commander à nouveau' mod='scalexpertplugin'}
					</span>
				</a>
			{/if}
		</div>
	{/if}

	<div>
		{l s='Your informations payment:' mod='scalexpertplugin'}
		<br />- {l s='Amount' mod='scalexpertplugin'} : <span class="price"><strong>{$total|escape:'htmlall':'UTF-8'|default:''}</strong></span>
		<br />- {l s='Order reference' mod='scalexpertplugin'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'|default:$id_order|default:''}</strong></span>
		<br />- {l s='Financial status' mod='scalexpertplugin'} : <span class="subscription_status"><strong>{$subscription_status_formatted|escape:'html':'UTF-8'|default:''}</strong></span>
		<br /><br />{l s='An email has been sent with this information.' mod='scalexpertplugin'}
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='scalexpertplugin'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='scalexpertplugin'}</a>
	</div>
</div>

{if $is_guest}
	<p>{l s='Your order ID is:'} <span class="bold">{$id_order_formatted}</span> . {l s='Your order ID has been sent via email.'}</p>
    <p class="cart_navigation exclusive">
	<a class="button-exclusive btn btn-default" href="{$link->getPageLink('guest-tracking', true, NULL, "id_order={$reference_order|urlencode}&email={$email|urlencode}")|escape:'html':'UTF-8'}" title="{l s='Follow my order'}"><i class="icon-chevron-left"></i>{l s='Follow my order'}</a>
    </p>
{else}
	<p class="cart_navigation exclusive">
		<a class="button-exclusive btn btn-default" href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}" title="{l s='Go to your order history page'}"><i class="icon-chevron-left"></i>{l s='View your order history'}</a>
	</p>
{/if}
