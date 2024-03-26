{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
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
                            aria-label="{l s='Close' d='Shop.Theme.Global'}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$insuranceSolution.visualAdditionalInformation|default:'' nofilter}

                    {if !empty($insuranceSolution.visualInformationNoticeURL) && !empty($insuranceSolution.visualProductTermsURL)}
                        <div class="sep_contentModal-listlink">
                            {if !empty($insuranceSolution.visualProductTermsURL)}
                                <a href="{$insuranceSolution.visualProductTermsURL|default:''}">{l s='Fiche d\'information produit (IPID)' d='Modules.Scalexpertplugin.Shop'}</a>
                            {/if}

                            {if !empty($insuranceSolution.visualInformationNoticeURL)}
                                <a href="{$insuranceSolution.visualInformationNoticeURL|default:''}">{l s='Notice d\'information (NI)' d='Modules.Scalexpertplugin.Shop'}</a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}
