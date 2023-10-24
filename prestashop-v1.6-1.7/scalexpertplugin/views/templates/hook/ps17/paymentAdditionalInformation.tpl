{**
* Copyright © DATASOLUTION.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    DATASOLUTION (https://www.datasolution.fr/)
* @copyright DATASOLUTION
* @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

{if !empty($availableSolution)}
    {$availableSolution.visualDescription|default:'' nofilter}
    {$availableSolution.visualLegalText|default:'' nofilter}
{/if}
