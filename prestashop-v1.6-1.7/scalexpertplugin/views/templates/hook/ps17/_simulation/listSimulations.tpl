{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($solutions)}
    <ul class="sep-Simulations-solution-listSimulation">
        {foreach $solutions as $key => $simulation}
            {$md5Solution = ($simulation.designConfiguration.solutionCode|default:''|cat:$simulation.duration|default:'')|md5}

            {if !empty($simulation)}
                <li class="sep-Simulations-solution-itemSimulation"
                    data-js="selectSolutionSimulation"
                    {if $current === $key} data-current="true"{/if}
                    data-id="{$md5Solution|default:''}"
                    data-groupid="{$md5GroupSolution|default:''}"
                >
                    x{$simulation.duration|default:''}
                </li>
            {/if}
        {/foreach}
    </ul>
{/if}
