{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{if !empty($availableSolution)}
    {$availableSolution.visualDescription|default:'' nofilter}
    {$availableSolution.visualLegalText|default:'' nofilter}
{/if}
