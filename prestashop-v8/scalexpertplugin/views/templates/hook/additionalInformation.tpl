{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}

{if !empty($availableSolution)}
    {$availableSolution.visualDescription|default:'' nofilter}
    {$availableSolution.visualLegalText|default:'' nofilter}
{/if}
