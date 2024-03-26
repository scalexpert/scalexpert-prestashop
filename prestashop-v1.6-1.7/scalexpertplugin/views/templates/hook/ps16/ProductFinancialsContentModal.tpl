{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{if !empty($availableSolution)}
    <div id="{$md5Id|default:''}"
         hidden
         class="container sep_contentModal"
    >
        <h3>
            <img src="/modules/{$nameModule|default:''}/views/img/borrow.svg"
                 alt="{l s='Emprunter' mod='scalexpertplugin'}"
            >
            {$availableSolution.visualTitle|default:''|strip_tags}
        </h3>
        <div>
            {$availableSolution.visualAdditionalInformation|default:''}

            {if !empty($availableSolution.visualTableImage)}
                <img class="sep_contentModal-img"
                     src="{$availableSolution.visualTableImage|default:''}"
                     alt="{$availableSolution.visualTitle|default:''|strip_tags}"
                />
            {/if}

            {$availableSolution.visualLegalText|default:''}

            {if !empty($availableSolution.visualInformationNoticeURL) || !empty($availableSolution.visualProductTermsURL)}
                <ul>
                    {if !empty($availableSolution.visualProductTermsURL)}
                        <li>
                            <a href="{$availableSolution.visualProductTermsURL|default:''}">{l s='Fiche d\'information produit (IPID)' mod='scalexpertplugin'}</a>
                        </li>
                    {/if}
                    {if !empty($availableSolution.visualInformationNoticeURL)}
                        <li>
                            <a href="{$availableSolution.visualInformationNoticeURL|default:''}">{l s='Notice d\'information (NI)' mod='scalexpertplugin'}</a><br>
                        </li>
                    {/if}
                </ul>
            {/if}
        </div>
    </div>
{/if}
