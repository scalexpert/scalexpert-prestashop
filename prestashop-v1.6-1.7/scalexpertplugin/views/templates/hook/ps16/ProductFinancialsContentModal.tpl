{**
* Copyright © DATASOLUTION.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    DATASOLUTION (https://www.datasolution.fr/)
* @copyright DATASOLUTION
* @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
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

            {if !empty($availableSolution.visualInformationNoticeURL) && !empty($availableSolution.visualProductTermsURL)}
                <ul class="sep_contentModal-img">
                    {if !empty($availableSolution.visualInformationNoticeURL)}
                        <li>
                            <a href="{$availableSolution.visualInformationNoticeURL|default:''}">{l s='Notice' mod='scalexpertplugin'}</a><br>
                        </li>
                    {/if}
                    {if !empty($availableSolution.visualProductTermsURL)}
                        <li>
                            <a href="{$availableSolution.visualProductTermsURL|default:''}">{l s='Terms' mod='scalexpertplugin'}</a>
                        </li>
                    {/if}
                </ul>
            {/if}
        </div>
    </div>
{/if}
