{**
* Copyright © DATASOLUTION.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    DATASOLUTION (https://www.datasolution.fr/)
* @copyright DATASOLUTION
* @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
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
                            {if !empty($insuranceSolution.visualInformationNoticeURL)}
                                <a href="{$insuranceSolution.visualInformationNoticeURL|default:''}">{l s='Notice' d='Modules.Scalexpertplugin.Shop'}</a>
                            {/if}

                            {if !empty($insuranceSolution.visualProductTermsURL)}
                                <a href="{$insuranceSolution.visualProductTermsURL|default:''}">{l s='Terms' d='Modules.Scalexpertplugin.Shop'}</a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}
