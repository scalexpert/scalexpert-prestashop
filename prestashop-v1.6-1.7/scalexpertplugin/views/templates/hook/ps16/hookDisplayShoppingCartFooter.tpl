{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


<script>
    var scalexpertpluginFrontUrl = '{$scalexpertpluginFrontUrl|default:''}';
</script>

<div id="scalexpertplugin-displaySimulationShoppingCartFooter"></div>
<div id="scalexpertplugin-displayShoppingCartFooter"></div>

<template id="scalexpertpluginTemplateShoppingCartFooter">
    <form method="post">
        <div class="scalexpertplugin-content"></div>
        <div class="text-center">
            <button class="button btn btn-default button-medium" type="submit" name="confirm">
                <span>
                    {l s='Confirmer' mod='scalexpertplugin'}
                </span>
            </button>
        </div>
    </form>
</template>
