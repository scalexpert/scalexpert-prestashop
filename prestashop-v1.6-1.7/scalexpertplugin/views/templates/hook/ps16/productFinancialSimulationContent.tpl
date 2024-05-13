{if !empty($solutionSimulations)}
    {*{$solutionSimulations|p}*}
    <div class="sep-Simulations">
        {include file='./_simulation/product.tpl' solutionSimulations=$solutionSimulations|default:''}
    </div>
{/if}
