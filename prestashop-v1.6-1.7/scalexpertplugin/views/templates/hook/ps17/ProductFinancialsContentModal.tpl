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
         class="sep_contentModal modal fade product-comment-modal"
         role="dialog"
         aria-hidden="true"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="h2 modal-header-title">
                        <img src="/modules/{$nameModule|default:''}/views/img/borrow.svg"
                             alt="{l s='Emprunter' d='Modules.Scalexpertplugin.Shop'}"
                        >
                        {$availableSolution.visualTitle|default:''|strip_tags}
                    </div>
                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="{l s='Close' d='Shop.Theme.Global'}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$availableSolution.visualAdditionalInformation|default:'' nofilter}

                    {if !empty($availableSolution.visualTableImage)}
                        <img class="sep_contentModal-img"
                             src="{$availableSolution.visualTableImage|default:''}"
                             alt="{$availableSolution.visualTitle|default:''|strip_tags}"
                        >
                    {/if}

                   {$availableSolution.visualLegalText|default:'' nofilter}

                    {if !empty($availableSolution.visualInformationNoticeURL) && !empty($availableSolution.visualProductTermsURL)}
                        <div class="sep_contentModal-listlink">
                            {if !empty($availableSolution.visualInformationNoticeURL)}
                                <a href="{$availableSolution.visualInformationNoticeURL|default:''}">{l s='Notice' mod='scalexpertplugin'}</a>
                            {/if}
                            {if !empty($availableSolution.visualProductTermsURL)}
                                <a href="{$availableSolution.visualProductTermsURL|default:''}">{l s='Terms' mod='scalexpertplugin'}</a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}
