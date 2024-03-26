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
         class="sep_contentModal modal fade product-comment-modal"
         role="dialog"
         aria-hidden="true"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="h2 modal-header-title">
                        <img src="/modules/{$nameModule|default:''}/views/img/borrow.svg"
                             alt="{l s='Emprunter' mod='scalexpertplugin'}"
                        >
                        {$availableSolution.visualTitle|default:''|strip_tags}
                    </div>
                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="{l s='Close' mod='scalexpertplugin'}">
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
                            {if !empty($availableSolution.visualProductTermsURL)}
                                <a href="{$availableSolution.visualProductTermsURL|default:''}">{l s='Fiche d\'information produit (IPID)' mod='scalexpertplugin'}</a>
                            {/if}
                            {if !empty($availableSolution.visualInformationNoticeURL)}
                                <a href="{$availableSolution.visualInformationNoticeURL|default:''}">{l s='Notice d\'information (NI)' mod='scalexpertplugin'}</a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}
