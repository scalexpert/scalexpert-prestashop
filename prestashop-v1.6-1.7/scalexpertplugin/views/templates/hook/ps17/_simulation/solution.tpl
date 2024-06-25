{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($solutions)}
    <div class="sep-Simulations-Product-groupSolution sep-Simulations-groupSolution"
         data-id="{$md5GroupSolution|default:''}">
            {foreach $solutions as $key => $solution}
                {$titleSolution = $solution.designConfiguration.visualTitle|default:''|strip_tags}
                {$md5Solution = ($solution.designConfiguration.solutionCode|cat:$solution.duration|default:'')|md5}

                <div class="sep-Simulations-Product-solution sep-Simulations-solution"
                     data-id="{$md5Solution}"
                >
                    <div class="sep-Simulations-solution-top">
                        {$titleSolution|default:''}

                        {if !empty($solution.designConfiguration.visualInformationIcon)}
                            <img class="sep-Simulations-solution-i"
                                 src="{$solution.designConfiguration.visualInformationIcon|default:''}"
                                 alt="{l s='Information' mod='scalexpertplugin'}"
                                 width="16"
                                 height="16"
                                 data-modal="sep_openModal"
                                 data-idmodal="#{$md5GroupSolution|default:''}"
                            >
                        {/if}
                        {if !empty($solution.designConfiguration.displayLogo)}
                            <img class="sep-Simulations-solution-logo"
                                 src="{$solution.designConfiguration.visualLogo|default:''}"
                                 alt="Logo {$titleSolution|default:''}"
                            >
                        {/if}
                    </div>
                    <div class="sep-Simulations-solution-infoSimulation">
                        {include file='module:scalexpertplugin/views/templates/hook/ps17/_simulation/listSimulations.tpl'
                                 solutions=$solutions|default:''
                                 current=$key
                                 md5GroupSolution=$md5GroupSolution|default:''
                        }

                        {include file='module:scalexpertplugin/views/templates/hook/ps17/_simulation/infoMonthlyPayment.tpl'
                                 solution=$solution|default:''
                        }
                    </div>

                    {if !empty($solution.designConfiguration.visualDescription)}
                        <div class="sep-Simulations-solution-visualDescription">
                            {$solution.designConfiguration.visualDescription|default:'' nofilter}
                        </div>
                    {/if}
                </div>
            {/foreach}
        </div>
{/if}
