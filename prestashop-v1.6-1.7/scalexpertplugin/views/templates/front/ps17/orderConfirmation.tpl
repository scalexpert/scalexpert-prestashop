{**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}


{extends file='page.tpl'}

{block name='page_content_container' prepend}
    <section id="content-hook_order_confirmation" class="card">
        <div class="card-block">
            <div class="row">
                <div class="col-md-12">

                    {block name='order_confirmation_header'}
                        <h3 class="h1 card-title">
                            {$subscription_status_title|escape:'html':'UTF-8'|default:''}
                        </h3>
                    {/block}

                    <p>
                        {$subscription_status_subtitle|escape:'html':'UTF-8'|default:''}
                    </p>

                    {block name='hook_order_confirmation'}
                        {if !empty($HOOK_ORDER_CONFIRMATION)}
                            {$HOOK_ORDER_CONFIRMATION nofilter}
                        {/if}
                    {/block}

                    {if
                        !empty($subscription_status)
                        && in_array($subscription_status, ['REJECTED', 'ABORTED'])
                        && !empty($order.details.reorder_url)
                        && !$customer.is_guest
                    }
                        <div class="button-reorder">
                            <a class="btn btn-primary"
                               href="{$order.details.reorder_url}"
                               title="{l s='Reorder' mod='scalexpertplugin'}">
                                {l s='Reorder' mod='scalexpertplugin'}
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
                        <h3 class="h3 card-title">{l s='Order details' mod='scalexpertplugin'}:</h3>
                        <ul>
                            <li id="order-reference-value">{l s='Order reference :' mod='scalexpertplugin'} {$order.details.reference}</li>
                            <li>{l s='Payment method :' mod='scalexpertplugin'} {$order.details.payment}</li>
                            {if !$order.details.is_virtual}
                                <li>
                                    {l s='Shipping method :' mod='scalexpertplugin'} {$order.carrier.name}<br>
                                    <em>{$order.carrier.delay}</em>
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
        <section class="card definition-list">
            <div class="card-block">
                <div class="row">
                    <div class="col-md-12">
                        <p>
                            {l s='Your informations payment:' mod='scalexpertplugin'}
                        </p>

                        <section>
                            <ul>
                                <li>- {l s='Amount' mod='scalexpertplugin'} : <span class="price"><strong>{$total|escape:'htmlall':'UTF-8'|default:''}</strong></span></li>
                                <li>- {l s='Order reference' mod='scalexpertplugin'} : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'|default:''}</strong></span></li>
                                <li>- {l s='Financial status' mod='scalexpertplugin'} : <span class="subscription_status"><strong>{$subscription_status_formatted|escape:'html':'UTF-8'|default:''}</strong></span></li>
                            </ul>
                            <p>
                                {l s='An email has been sent with this information.' mod='scalexpertplugin'}
                                <br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='scalexpertplugin'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='scalexpertplugin'}</a>
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </section>

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

    {block name='customer_registration_form'}
        {if $customer.is_guest}
            <div id="registration-form" class="card">
                <div class="card-block">
                    <h4 class="h4">{l s='Save time on your next order, sign up now' mod='scalexpertplugin'}</h4>
                    {render file='customer/_partials/customer-form.tpl' ui=$register_form}
                </div>
            </div>
        {/if}
    {/block}

    {block name='hook_order_confirmation_1'}
        {hook h='displayOrderConfirmation1'}
    {/block}

    {block name='hook_order_confirmation_2'}
        <section id="content-hook-order-confirmation-footer">
            {hook h='displayOrderConfirmation2'}
        </section>
    {/block}
{/block}
