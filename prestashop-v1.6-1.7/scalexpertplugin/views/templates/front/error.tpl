{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
