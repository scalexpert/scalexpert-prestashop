{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
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
                <ul>
                    {if !empty($insurancesData.visualInformationNoticeURL)}
                        <li>
                            <a href="{$insurancesData.visualInformationNoticeURL|default:''}">{l s='Notice' mod='scalexpertplugin'}</a><br>
                        </li>
                    {/if}
                    {if !empty($insurancesData.visualProductTermsURL)}
                        <li>
                            <a href="{$insurancesData.visualProductTermsURL|default:''}">{l s='Terms' mod='scalexpertplugin'}</a>
                        </li>
                    {/if}
                </ul>
            {/if}
        </div>
    </div>
{/if}
