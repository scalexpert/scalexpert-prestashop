{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{if !empty($insurancesData)}
    <div id="{$md5Id|default:''}"
         hidden
         class="container sep_contentModal"
    >
        <h3>
            <img src="/modules/{$nameModule|default:''}/views/img/borrow.svg"
                 alt="{l s='Emprunter' mod='scalexpertplugin'}"
            >
            {$insurancesData.visualTitle|default:''|strip_tags}&nbsp;{$insuranceSolution.relatedProduct.name|default:''|strip_tags}
        </h3>
        <div>
            {$insurancesData.visualAdditionalInformation|default:''}

            {if !empty($insurancesData.visualInformationNoticeURL) || !empty($insurancesData.visualProductTermsURL)}
                <div class="sep_contentModal-listlink">
                    {if !empty($insurancesData.visualProductTermsURL)}
                        <a href="{$insurancesData.visualProductTermsURL|default:''}">{l s='Fiche d\'information produit (IPID)' mod='scalexpertplugin'}</a>
                    {/if}
                    {if !empty($insurancesData.visualInformationNoticeURL)}
                        <a href="{$insurancesData.visualInformationNoticeURL|default:''}">{l s='Notice d\'information (NI)' mod='scalexpertplugin'}</a><br>
                    {/if}
                </div>
            {/if}
        </div>
    </div>
{/if}
