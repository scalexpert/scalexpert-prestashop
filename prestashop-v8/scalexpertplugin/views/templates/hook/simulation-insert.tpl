{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($solutionSimulations)}
    <div class="sep-Simulations">
        {include file='module:scalexpertplugin/views/templates/hook/_simulation/product.tpl' solutionSimulations=$solutionSimulations|default:''}
    </div>
{/if}
