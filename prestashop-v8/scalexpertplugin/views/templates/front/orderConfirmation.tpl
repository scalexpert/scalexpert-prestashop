{**
* Copyright © Scalexpert.
* This file is part of Scalexpert plugin for PrestaShop.
*
* @author    Société Générale
* @copyright Scalexpert
*}
{extends file='page.tpl'}

{block name='page_content_container' prepend}
    <section id="content-hook_order_confirmation" class="card">
        <div class="card-block">
            <div class="row">
                <div class="col-md-12">

                    {block name='order_confirmation_header'}
                        <h3 class="h1 card-title">
                            {$subscriptionStatusTitle|escape:'html':'UTF-8'|default:''}
                        </h3>
                    {/block}

                    <p>
                        {$subscriptionStatusSubtitle|escape:'html':'UTF-8'|default:''}
                    </p>

                    {block name='hook_order_confirmation'}
                        {if !empty($HOOK_ORDER_CONFIRMATION)}
                            {$HOOK_ORDER_CONFIRMATION nofilter}
                        {/if}
                    {/block}

                    {if !empty($subscriptionStatus) && $subscriptionStatus == 'REJECTED' &&
                        !empty($displayReorder) && $reorderUrl
                    }
                        <div class="button-reorder">
                            <a class="btn btn-primary"
                               href="{$order.details.reorder_url}"
                               title="{l s='Reorder' d='Shop.Theme.Actions'}">
                                {l s='Reorder' d='Shop.Theme.Actions'}
                            </a>
                        </div>
                    {/if}

                </div>
            </div>
        </div>
    </section>
{/block}

{block name='page_content_container'}
    <section id="content" class="page-content page-order-confirmation card">
        <div class="card-block">
            <div class="row">

                {block name='order_details'}
                    <div id="order-details">
                        <h3 class="h3 card-title">{l s='Order details' d='Shop.Theme.Checkout'}:</h3>
                        <ul>
                            <li id="order-reference-value">{l s='Order reference: %reference%' d='Shop.Theme.Checkout' sprintf=['%reference%' => $order.details.reference]}</li>
                            <li>{l s='Payment method: %method%' d='Shop.Theme.Checkout' sprintf=['%method%' => $order.details.payment]}</li>
                            {if !$order.details.is_virtual}
                                <li>
                                    {l s='Shipping method: %method%' d='Shop.Theme.Checkout' sprintf=['%method%' => $order.carrier.name]}<br>
                                    <em>{$order.carrier.delay}</em>
                                </li>
                            {/if}
                            {if $order.details.recyclable}
                                <li>
                                    <em>{l s='You have given permission to receive your order in recycled packaging.' d="Shop.Theme.Customeraccount"}</em>
                                </li>
                            {/if}
                        </ul>
                    </div>
                {/block}

                {block name='order_confirmation_table'}
                    {include
                    file='checkout/_partials/order-confirmation-table.tpl'
                    products=$order.products
                    subtotals=$order.subtotals
                    totals=$order.totals
                    labels=$order.labels
                    add_product_link=false
                    }
                {/block}

            </div>
        </div>
    </section>

    {block name='hook_payment_return'}
        {if ! empty($HOOK_PAYMENT_RETURN)}
            <section id="content-hook_payment_return" class="card definition-list">
                <div class="card-block">
                    <div class="row">
                        <div class="col-md-12">
                            {$HOOK_PAYMENT_RETURN nofilter}
                        </div>
                    </div>
                </div>
            </section>
        {/if}
    {/block}

    {if $is_guest}
        {block name='account_transformation_form'}
            <div class="card">
                <div class="card-block">
                    {include file='customer/_partials/account-transformation-form.tpl'}
                </div>
            </div>
        {/block}
    {/if}

    {block name='hook_order_confirmation_1'}
        {hook h='displayOrderConfirmation1'}
    {/block}

    {block name='hook_order_confirmation_2'}
        <section id="content-hook-order-confirmation-footer">
            {hook h='displayOrderConfirmation2'}
        </section>
    {/block}
{/block}
