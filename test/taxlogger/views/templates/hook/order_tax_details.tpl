{*
 * /modules/taxlogger/views/templates/hook/order_tax_details.tpl
 * 用于在客户订单详情页显示税率组信息
 *}

<div class="box">
    <div class="row">
        <div class="col-sm-12">
            <h3 class="page-subheading">
                <i class="material-icons">local_offer</i>
                {$tax_info_title|escape:'htmlall':'UTF-8'}
            </h3>

            <table class="table table-bordered">
                <thead>
                <tr>
                    <th class="first_item">{l s='Tax Rules Group' mod='taxlogger'}</th>
                    <th class="item">{l s='Tax Name' mod='taxlogger'}</th>
                    <th class="last_item">{l s='Rate' mod='taxlogger'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $tax_rules_groups as $group}
                    {if $group.rates}
                        {foreach $group.rates as $rate_info}
                            <tr>
                                <td>
                                    <strong>{$group.group_name|escape:'htmlall':'UTF-8'}</strong>
                                    <small class="text-muted">(ID: {$group.group_id})</small>
                                </td>
                                <td>{$rate_info.name|escape:'htmlall':'UTF-8'}</td>
                                <td>
                                        <span class="label label-info">
                                            {$rate_info.rate|floatval|string_format:"%.2f"}%
                                        </span>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td><strong>{$group.group_name|escape:'htmlall':'UTF-8'}</strong></td>
                            <td colspan="2">{l s='No specific tax rate applied or available.' mod='taxlogger'}</td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>

            <p class="text-muted small">
                {l s='This table displays the tax rules group used by the products in this order and the specific tax rates applied based on the delivery address.' mod='taxlogger'}
            </p>
        </div>
    </div>
</div>