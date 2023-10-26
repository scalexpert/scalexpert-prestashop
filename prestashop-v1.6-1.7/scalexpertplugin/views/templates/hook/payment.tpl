{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($eligibleSolutions)}
    {foreach $eligibleSolutions as $eligibleSolution}
        {if !empty($eligibleSolution)}
            <div class="row">
                <div class="col-xs-12">
                    <p class="payment_module"
                       id="scalexpertplugin_payment_button_{$eligibleSolution.solutionCode|default:''}"
                    >
                        <a class="scalexpertplugin"
{*                           href="{$link->getModuleLink('scalexpertplugin', 'redirect', ['t' =>  $eligibleSolution.solutionCode|default:''], true)|escape:'htmlall':'UTF-8'}"*}
                           href="{$link->getModuleLink('scalexpertplugin', 'validation', ['solutionCode' =>  $eligibleSolution.solutionCode|default:''], true)|escape:'htmlall':'UTF-8'}"
                           title="{$eligibleSolution.communicationKit.visualTitle|default:''|strip_tags}"
                           {if !empty($eligibleSolution.communicationKit.displayLogo)}style="background-image: url('{$eligibleSolution.communicationKit.visualLogo|default:''}')"{/if}
                        >
                            {strip}
                                <span class="scalexpertplugin_subtitle">
                                    {$eligibleSolution.communicationKit.visualTitle|default:''|strip_tags}
                                    {if !empty($eligibleSolution.communicationKit.visualDescription)}
                                        &nbsp;<span>({$eligibleSolution.communicationKit.visualDescription|default:''|strip_tags})</span>
                                    {/if}
                                </span>
                                <br>
                                <span class="scalexpertplugin_content">
                                    {$eligibleSolution.communicationKit.visualLegalText|default:''|strip_tags}
                                </span>
                            {/strip}
                        </a>
                    </p>
                </div>
            </div>
        {/if}
    {/foreach}
{/if}
