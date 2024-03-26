{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{if !empty($insuranceSolution) && !empty($md5Id)}
    <div id="{$md5Id|default:''}"
         class="sep_contentModal modal fade product-comment-modal"
         role="dialog"
         aria-hidden="true"
    >

        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <p class="h2 modal-header-title">
                        <img src="/modules/scalexpertplugin/views/img/insurance.svg"
                             alt="{$insuranceSolution.visualTitle|default:''|strip_tags}"
                             width="32"
                             height="32"
                        >
                        {$insuranceSolution.visualTitle|default:''|strip_tags}&nbsp;{$insuranceSolution.relatedProduct.name|default:''}
                    </p>
                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="{l s='Close' mod='scalexpertplugin'}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$insuranceSolution.visualAdditionalInformation|default:'' nofilter}

                    {if !empty($insuranceSolution.visualInformationNoticeURL) && !empty($insuranceSolution.visualProductTermsURL)}
                        <div class="sep_contentModal-listlink">
                            {if !empty($insuranceSolution.visualProductTermsURL)}
                                <a href="{$insuranceSolution.visualProductTermsURL|default:''}">{l s='Fiche d\'information produit (IPID)' mod='scalexpertplugin'}</a>
                            {/if}

                            {if !empty($insuranceSolution.visualInformationNoticeURL)}
                                <a href="{$insuranceSolution.visualInformationNoticeURL|default:''}">{l s='Notice d\'information (NI)' mod='scalexpertplugin'}</a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}

