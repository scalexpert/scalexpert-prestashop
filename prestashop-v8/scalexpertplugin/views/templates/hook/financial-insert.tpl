{**
* Copyright © DATASOLUTION.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    DATASOLUTION (https://www.datasolution.fr/)
* @copyright DATASOLUTION
* @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

{if !empty($financialSolution)}
    {assign var="md5Id" value={$financialSolution.solutionCode|default:''}|cat:$financialSolution.buyerBillingCountry|default:'':$financialSolution.type|default:''|md5}

    <div class="sep_financialSolution">
        <a class="sep_financialSolution-content"
           data-modal="sep_openModal"
           href="#{$md5Id|default:''}"
           title="{$financialSolution.visualAdditionalInformation|default:$financialSolution.visualTitle|default:''|strip_tags}"
        >
            <span class="sep_financialSolution-title">
                {$financialSolution.visualTitle|default:''|strip_tags}
                {if !empty($financialSolution.visualInformationIcon)}
                    <img class="sep_financialSolution-i"
                         src="{$financialSolution.visualInformationIcon|default:''}"
                         alt="{l s='Information' d='Modules.Scalexpertplugin.Shop'}"
                         width="16"
                         height="16"
                    >
                {/if}
            </span>
            {if !empty($financialSolution.displayLogo)}
                <img class="sep_financialSolution-logo"
                     src="{$financialSolution.visualLogo|default:''}"
                     alt="Logo {$financialSolution.visualTitle|default:''|strip_tags}"
                >
            {/if}
        </a>
    </div>

    {include file='module:scalexpertplugin/views/templates/hook/contentModal.tpl' md5Id=$md5Id|default:'' financialSolution=$financialSolution}
{/if}
