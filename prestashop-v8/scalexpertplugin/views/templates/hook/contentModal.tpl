{**
* Copyright © DATASOLUTION.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    DATASOLUTION (https://www.datasolution.fr/)
* @copyright DATASOLUTION
* @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

{if !empty($financialSolution) && !empty($md5Id)}
    <div id="{$md5Id|default:''}"
         class="sep_contentModal modal fade product-comment-modal"
         role="dialog"
         aria-hidden="true"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="h2 modal-header-title">
                        <img src="/modules/scalexpertplugin/views/img/borrow.svg"
                             alt="{l s='Emprunter' d='Modules.Scalexpertplugin.Shop'}"
                        >
                        {$financialSolution.visualTitle|default:''|strip_tags}
                    </div>
                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="{l s='Close' d='Shop.Theme.Global'}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$financialSolution.visualAdditionalInformation|default:'' nofilter}

                    {if !empty($financialSolution.visualTableImage)}
                        <img class="sep_contentModal-img"
                             src="{$financialSolution.visualTableImage|default:''}"
                             alt="{$financialSolution.visualTitle|default:''|strip_tags}"
                        >
                    {/if}

                    {$financialSolution.visualLegalText|default:'' nofilter}

                    {if !empty($financialSolution.visualInformationNoticeURL) && !empty($financialSolution.visualProductTermsURL)}
                        <div class="sep_contentModal-listlink">
                            {if !empty($financialSolution.visualInformationNoticeURL)}
                                <a href="{$financialSolution.visualInformationNoticeURL|default:''}">{l s='Notice' d='Modules.Scalexpertplugin.Shop'}</a>
                            {/if}

                            {if !empty($financialSolution.visualProductTermsURL)}
                                <a href="{$financialSolution.visualProductTermsURL|default:''}">{l s='Terms' d='Modules.Scalexpertplugin.Shop'}</a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}
