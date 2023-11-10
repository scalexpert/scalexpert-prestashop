{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

<script>
    var scalexpertpluginFrontUrl = '{$scalexpertpluginFrontUrl|default:''}';
</script>
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
