{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

<div>
	<h3>{l s='An error occurred' mod='scalexpertplugin'}:</h3>
	{if isset($errors)}
		<ul class="alert alert-danger">
			{foreach from=$errors item='error'}
				<li>{$error|escape:'htmlall':'UTF-8'}.</li>
			{/foreach}
		</ul>
	{/if}
</div>
