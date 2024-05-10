{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($availableSolution)}
    <div class="sep_financialSolutionContent">
        {$availableSolution.visualDescription|default:'' nofilter}
        {$availableSolution.visualLegalText|default:'' nofilter}

        {if !empty($availableSolution.simulation) && !empty($availableSolution.simulationPopinData)}
            {$random = rand(0,100)}
            {$nbSolution = $availableSolution.simulationPopinData|@count}
            {$md5GroupSolution = $availableSolution.solutionCode|cat:$nbSolution:$random|md5}

            {$md5Solution = ($availableSolution.simulation.designConfiguration.solutionCode|default:''|cat:$availableSolution.simulation.duration|default:'')|md5}

            <button class="btn btn-primary center-block"
                    data-modal="sep_openModal"
                    data-idmodal="#{$md5GroupSolution|default:''}"
                    data-idsolution="{$md5Solution|default:''}"
            >
                {l s='Plus d\'informations' d='Modules.Scalexpertplugin.Shop'}
            </button>

            {include file='module:scalexpertplugin/views/templates/hook/_simulation/modalSimulation.tpl'
                     solutions=$availableSolution.simulationPopinData|default:''
                     md5GroupSolution=$md5GroupSolution|default:''
            }
        {/if}
    </div>
{/if}
