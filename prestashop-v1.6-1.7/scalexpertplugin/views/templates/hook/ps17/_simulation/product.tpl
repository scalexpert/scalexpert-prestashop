{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($solutionSimulations)}
    <div class="sep-Simulations-Product">
        {capture name='modal'}{/capture}
        {foreach $solutionSimulations as $solutionGroupKey => $solutions}

            {if !empty($solutions)}

                {$random = rand(0,100)}
                {$nbSolution = $solutions|@count}
                {$md5GroupSolution = $solutionGroupKey|cat:$nbSolution:$random|md5}

                {include file='module:scalexpertplugin/views/templates/hook/ps17/_simulation/solution.tpl'
                         solutions=$solutions|default:''
                         md5GroupSolution=$md5GroupSolution|default:''
                }

                {capture name='modal'}
                    {if !empty($smarty.capture.modal)}
                        {$smarty.capture.modal|default:'' nofilter}
                    {/if}
                    {include file='module:scalexpertplugin/views/templates/hook/ps17/_simulation/modalSimulation.tpl'
                             solutions=$solutions|default:''
                             md5GroupSolution=$md5GroupSolution|default:''
                    }
                {/capture}
            {/if}
        {/foreach}

        {if !empty($smarty.capture.modal)}
            <div class="sep-Simulations-modal">
                {$smarty.capture.modal|default:'' nofilter}
            </div>
        {/if}
    </div>
{/if}
