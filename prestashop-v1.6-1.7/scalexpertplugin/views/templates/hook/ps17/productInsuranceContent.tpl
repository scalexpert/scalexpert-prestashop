{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{if !empty($insurancesData)}
    {assign var="isCart" value=false}

    {if !empty($insurancesData.relatedProduct)}
        {$isCart = true}
    {/if}
    <div class="sep_insuranceSolution" data-type="{if !empty($isCart)}cart{else}product{/if}">
        <div class="sep_insuranceSolution-top">
            <span class="sep_insuranceSolution-top-title">
            {if !empty($isCart)}
                <img class="sep_insuranceSolution-top-title-illus"
                     src="/modules/scalexpertplugin/views/img/insurance.svg"
                     width="24"
                     height="24"
                >
            {/if}

            {$insurancesData.visualTitle|default:''|strip_tags}&nbsp;{$insurancesData.relatedProduct.name|default:''}

            {if !empty($insurancesData.visualInformationIcon)}
                <img class="sep_insuranceSolution-i"
                     src="{$insurancesData.visualInformationIcon|default:''}"
                     alt="{l s='Information' mod='scalexpertplugin'}"
                     width="16"
                     height="16"
                     data-modal="sep_openModal"
                     data-idmodal="#{$scalexpertplugin_productButtons.idModal|default:''}"
                >
            {/if}
        </span>

            {if !empty($insurancesData.visualLogo)}
                <img class="sep_insuranceSolution-logo"
                     src="{$insurancesData.visualLogo|default:''}"
                     alt="Logo {$insurancesData.visualTitle|default:''|strip_tags}"
                >
            {/if}
        </div>

        {if !empty($insurancesData.insurances)}
            <div class="sep_insuranceSolution-choices">
                <div class="sep_insuranceSolution-choices-item">

                    {assign var="idRadio" value="{$insurancesData.solutionCode|default:''}"}
                    {assign var="nameRadio" value="insurances[{$insurancesData.id_product}|{$insurancesData.id_product_attribute}]"}
                    {if !empty($isCart)}
                        {$idRadio = "{$insurancesData.solutionCode|default:''|cat:'_':$insurancesData.relatedProduct.id_product|default:'':$insurancesData.relatedProduct.id_product_attribute|default:''}"}
                        {$nameRadio = "insurances[{$insurancesData.relatedProduct.id_product|default:''}|{$insurancesData.relatedProduct.id_product_attribute|default:''}]"}
                    {/if}

                    {strip}
                        <label for="noinsurance_{$idRadio|default:''}">
                            <span class="sep_insuranceSolution-choices-item-content">
                                <span class="custom-radio">
                                  <input class="ps-shown-by-js"
                                         id="noinsurance_{$idRadio|default:''}"
                                         name="{$nameRadio|default:''}"
                                         type="radio"
                                         value="{$insurancesData.solutionCode}|{$insurancesData.itemId|default:''}|"
                                         required="required"
                                         checked="checked"
                                  >
                                  <span></span>
                                </span>
                                {l s='Pas de garantie' mod='scalexpertplugin'}
                            </span>
                        </label>

                    {/strip}
                </div>

                {foreach $insurancesData.insurances as $insurance}
                    {if !empty($insurance)}
                        <div class="sep_insuranceSolution-choices-item">
                            {strip}
                                <label for="{$idRadio|default:''|cat:$insurance.id|default:''}">
                                    <span class="sep_insuranceSolution-choices-item-content">
                                        <span class="custom-radio">
                                          <input class="ps-shown-by-js"
                                                 id="{$idRadio|default:''|cat:$insurance.id|default:''}"
                                                 name="{$nameRadio|default:''}"
                                                 type="radio"
                                                 value="{$insurancesData.solutionCode}|{$insurance.itemId|default:''}|{$insurance.id|default:''}"
                                                 required="required"
                                                  {if !empty($insurance.selected)} checked{/if}
                                          >
                                          <span></span>
                                        </span>

                                        {$insurance.description|default:''}
                                    </span>

                                    <span class="sep_insuranceSolution-choices-item-price">({$insurance.formattedPrice|default:''})</span>
                                </label>
                            {/strip}
                        </div>
                    {/if}
                {/foreach}
            </div>
        {/if}

        {if !empty($isCart)}
            *{$insurancesData.visualLegalText|default:''}
            {if !empty($insurancesData.visualInformationNoticeURL)}
                {l s='By subscribing to the insurance, I declare that I have been able to download and print the' mod='scalexpertplugin'}
                <a target="_blank" href="{$insurancesData.visualInformationNoticeURL|default:''}">{l s='Information notice (IN)' mod='scalexpertplugin'}</a>
                {l s='of the insurance contract.' mod='scalexpertplugin'}
            {/if}
        {else}
            <div class="sep_insuranceSolution-condition">
                {$insurancesData.visualDescription|default:'' nofilter}
            </div>

            {if !empty($insurancesData.visualInformationNoticeURL) ||
            !empty($insurancesData.visualProductTermsURL)}
                <div class="sep_insuranceSolution-link">
                    {if !empty($insurancesData.visualProductTermsURL)}
                        <a href="{$insurancesData.visualProductTermsURL|default:''}">{l s='Fiche d\'information produit (IPID)' mod='scalexpertplugin'}</a>
                    {/if}

                    {if !empty($insurancesData.visualInformationNoticeURL)}
                        <a href="{$insurancesData.visualInformationNoticeURL|default:''}">{l s='Notice d\'information (NI)' mod='scalexpertplugin'}</a>
                    {/if}
                </div>
            {/if}
        {/if}
    </div>

    {include file='module:scalexpertplugin/views/templates/hook/ps17/contentModalInsurance.tpl' md5Id=$scalexpertplugin_productButtons.idModal|default:'' insuranceSolution=$insurancesData}
{/if}
