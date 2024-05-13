{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($solutions)}
    <div id="{$md5GroupSolution|default:''}"
         class="sep_contentModal sep-SimulationsModal modal fade"
         role="dialog"
         aria-hidden="true"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="sep-Simulations-groupSolution"
                     data-id="{$md5GroupSolution|default:''}">

                    {foreach $solutions as $key => $solution}
                        {if !empty($solution)}
                            {$titleSolution = $solution.designConfiguration.visualTitle|default:''|strip_tags}
                            {$md5Solution = ($solution.designConfiguration.solutionCode|default:''|cat:$solution.duration|default:'')|md5}

                            {include file='module:scalexpertplugin/views/templates/hook/ps17/_simulation/modalSimulationContent.tpl'
                                     solution=$solution|default:''
                                     titleSolution=$titleSolution|default:''
                                     key=$key|default:0
                                     md5Solution=$md5Solution|default:''
                            }
                        {/if}
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
{/if}
