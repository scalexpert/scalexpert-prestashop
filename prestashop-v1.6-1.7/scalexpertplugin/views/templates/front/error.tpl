{**
* Copyright © DATASOLUTION.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    DATASOLUTION (https://www.datasolution.fr/)
* @copyright DATASOLUTION
* @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
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
