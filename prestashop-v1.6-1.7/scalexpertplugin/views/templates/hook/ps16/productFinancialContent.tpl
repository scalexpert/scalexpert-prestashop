{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($availableSolution)}
    <div class="sep_main_productsButtons product_attributes">
        <a class="sep_main_productsButtons-content"
           data-modal="sep_openModal"
           href="#{$scalexpertplugin_productButtons.idModal|default:''}"
        >
            <span class="sep_main_productsButtons-title">
                {$availableSolution.visualTitle|default:''|strip_tags}

                {if !empty($availableSolution.visualInformationIcon)}
                    <img class="sep_main_productsButtons-i"
                         src="{$availableSolution.visualInformationIcon|default:''}"
                         alt="{l s='more information' mod='scalexpertplugin'}"
                         width="16"
                         height="16"
                    >
                {/if}
            </span>
            {if !empty($availableSolution.displayLogo)}
                <img class="sep_main_productsButtons-logo"
                     src="{$availableSolution.visualLogo|default:''}"
                     alt="{$availableSolution.visualTitle|default:''|strip_tags}"
                >
            {/if}
        </a>
    </div>
    {include file="./ProductFinancialsContentModal.tpl" md5Id=$scalexpertplugin_productButtons.idModal|default:'' nameModule=$scalexpertplugin_productButtons.nameModule|default:''}
{/if}
