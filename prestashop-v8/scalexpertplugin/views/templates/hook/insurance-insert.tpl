{**
* Copyright © DATASOLUTION.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    DATASOLUTION (https://www.datasolution.fr/)
* @copyright DATASOLUTION
* @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

{if !empty($insuranceSolution)}

    {assign var="md5Id" value="{$insuranceSolution.solutionCode|default:''|cat:$insuranceSolution.buyerBillingCountry|default:'':$insuranceSolution.type|default:'':$insuranceSolution.productID|default:''|md5}"}
    {assign var="isCart" value=false}

    {if !empty($insuranceSolution.relatedProduct)}
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

                {$insuranceSolution.visualTitle|default:''|strip_tags}&nbsp;{$insuranceSolution.relatedProduct.name|default:''}

                {if !empty($insuranceSolution.visualInformationIcon)}
                    <img class="sep_insuranceSolution-i"
                         src="{$insuranceSolution.visualInformationIcon|default:''}"
                         alt="{l s='Information' d='Modules.Scalexpertplugin.Shop'}"
                         width="16"
                         height="16"
                         data-modal="sep_openModal"
                         data-idmodal="#{$md5Id|default:''}"
                    >
                {/if}
            </span>

            <img class="sep_insuranceSolution-logo"
                 src="{$insuranceSolution.visualLogo|default:''}"
                 alt="Logo {$insuranceSolution.visualTitle|default:''|strip_tags}"
            >
        </div>

        {if !empty($insuranceSolution.insurances)}
            <div class="sep_insuranceSolution-choices">
                <div class="sep_insuranceSolution-choices-item">

                    {assign var="idRadio" value="{$insuranceSolution.solutionCode|default:''}"}
                    {assign var="nameRadio" value="insurances[{$insuranceSolution.id_product}|{$insuranceSolution.id_product_attribute}]"}
                    {if !empty($isCart)}
                        {$idRadio = "{$insuranceSolution.solutionCode|default:''|cat:'_':$insuranceSolution.relatedProduct.id_product|default:'':$insuranceSolution.relatedProduct.id_product_attribute|default:''}"}
                        {$nameRadio = "insurances[{$insuranceSolution.relatedProduct.id_product|default:''}|{$insuranceSolution.relatedProduct.id_product_attribute|default:''}]"}
                    {/if}

                    {strip}
                        <label for="noinsurance_{$idRadio|default:''}">
                            <span class="sep_insuranceSolution-choices-item-content">
                                <span class="custom-radio">
                                  <input
                                          class="ps-shown-by-js"
                                          id="noinsurance_{$idRadio|default:''}"
                                          name="{$nameRadio|default:''}"
                                          type="radio"
                                          value="{$insuranceSolution.solutionCode}|{$insuranceSolution.itemId|default:''}|"
                                          required="required"
                                          checked="checked"
                                  >
                                  <span></span>
                                </span>
                                {l s='Pas de garantie' d='Modules.Scalexpertplugin.Shop'}
                            </span>
                        </label>

                    {/strip}
                </div>

                {foreach $insuranceSolution.insurances as $insurance}
                    {if !empty($insurance)}
                        <div class="sep_insuranceSolution-choices-item">
                            {strip}
                                <label for="{$idRadio|default:''|cat:$insurance.id|default:''}">
                                    <span class="sep_insuranceSolution-choices-item-content">
                                        <span class="custom-radio">
                                          <input
                                                  class="ps-shown-by-js"
                                                  id="{$idRadio|default:''|cat:$insurance.id|default:''}"
                                                  name="{$nameRadio|default:''}"
                                                  type="radio"
                                                  value="{$insuranceSolution.solutionCode}|{$insurance.itemId|default:''}|{$insurance.id|default:''}"
                                                  required="required"
                                                  {if !empty($insurance.selected)} checked{/if}
                                          >
                                          <span></span>
                                        </span>

                                        {$insurance.description|default:''}
                                        {if !empty($isCart)}*{/if}
                                    </span>

                                    <span class="sep_insuranceSolution-choices-item-price">
                                        {if empty($isCart)}&nbsp;({/if}{$insurance.formattedPrice|default:''}{if empty($isCart)}){/if}
                                    </span>
                                </label>

                            {/strip}
                        </div>
                    {/if}
                {/foreach}
            </div>
        {/if}

        {if !empty($isCart)}
            *{$insuranceSolution.visualLegalText|default:''}
            {if !empty($insuranceSolution.visualInformationNoticeURL)}
                (<a href="{$insuranceSolution.visualInformationNoticeURL|default:''}">{l s='Notice d\'Information' d='Modules.Scalexpertplugin.Shop'}</a>)
            {/if}
        {else}
            <div class="sep_insuranceSolution-condition">
                {$insuranceSolution.visualDescription|default:''}
            </div>

            {if !empty($insuranceSolution.visualInformationNoticeURL) &&
            !empty($insuranceSolution.visualProductTermsURL)}
                <div class="sep_insuranceSolution-link">
                    {if !empty($insuranceSolution.visualInformationNoticeURL)}
                        <a href="{$insuranceSolution.visualInformationNoticeURL|default:''}">{l s='Fiche d\'information produit (IPID)' d='Modules.Scalexpertplugin.Shop'}</a>
                    {/if}

                    {if !empty($insuranceSolution.visualProductTermsURL)}
                        <a href="{$insuranceSolution.visualProductTermsURL|default:''}">{l s='Notice d\'information (NI)' d='Modules.Scalexpertplugin.Shop'}</a>
                    {/if}
                </div>
            {/if}
        {/if}
    </div>

    {include file='module:scalexpertplugin/views/templates/hook/contentModalInsurance.tpl' md5Id=$md5Id|default:'' insuranceSolution=$insuranceSolution}
{/if}
