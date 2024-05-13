{if !empty($solutionSimulations)}
    <div class="sep-Simulations">
        {include file='module:scalexpertplugin/views/templates/hook/ps17/_simulation/product.tpl' solutionSimulations=$solutionSimulations|default:''}
    </div>
{/if}
