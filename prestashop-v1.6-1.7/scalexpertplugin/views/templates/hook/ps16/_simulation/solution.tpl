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
                {$md5Solution = ($solution.designConfiguration.solutionCode|cat:$titleSolution:$solution.duration|default:'')|md5}

                <div class="sep-Simulations-Product-solution sep-Simulations-solution"
                     data-id="{$md5Solution}"
                >
                    <a class="sep-Simulations-solution-top"
                         data-modal="sep_openModal"
                         href="#{$md5GroupSolution|default:''}"
                    >
                        {$titleSolution|default:''}

                        {if !empty($solution.designConfiguration.visualInformationIcon)}
                            <img class="sep-Simulations-solution-i"
                                 src="{$solution.designConfiguration.visualInformationIcon|default:''}"
                                 alt="{l s='Information' mod='scalexpertplugin'}"
                                 width="16"
                                 height="16"
                            >
                        {/if}
                        {if !empty($solution.designConfiguration.displayLogo)}
                            <img class="sep-Simulations-solution-logo"
                                 src="{$solution.designConfiguration.visualLogo|default:''}"
                                 alt="Logo {$titleSolution|default:''}"
                            >
                        {/if}
                    </a>
                    <div class="sep-Simulations-solution-infoSimulation">
                        {include file='./listSimulations.tpl'
                                 solutions=$solutions|default:''
                                 current=$key
                                 md5GroupSolution=$md5GroupSolution|default:''
                        }

                        {include file='./infoMonthlyPayment.tpl'
                                 solution=$solution|default:''
                        }
                    </div>

                    {if !empty($solution.designConfiguration.visualDescription)}
                        <div class="sep-Simulations-solution-visualDescription">
                            {$solution.designConfiguration.visualDescription|default:''}
                        </div>
                    {/if}
                </div>
            {/foreach}
        </div>
{/if}
