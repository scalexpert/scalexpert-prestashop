<div class="panel product-tab">
    <div class="row">
        <div class="col-md-12">
            <h2>{l s='Scalepert - Specific Product Fields' mod='scalexpertplugin'}</h2>
            <br/>
        </div>

        <hr/>
        {foreach $languages as $lang}
            <div class="form-group">
                <label class="control-label col-lg-2" for="scalexpertplugin_model_{$lang.id_lang}">
                    {l s='Model' mod='scalexpertplugin'} {$lang.iso_code|upper}
                </label>
                <div class="col-lg-7">
                    <input type="text" name="scalexpertplugin_model[{$lang.id_lang}]"
                           id="scalexpertplugin_model_{$lang.id_lang}" value="{if !empty($scalexpertplugin_model_values[$lang.id_lang])}{$scalexpertplugin_model_values[$lang.id_lang]}{/if}">
                </div>
            </div>
        {/foreach}

        {foreach $languages as $lang}
            <div class="form-group">
                <label class="control-label col-lg-2" for="scalexpertplugin_characteristics_{$lang.id_lang}">
                    {l s='Characteristics' mod='scalexpertplugin'} {$lang.iso_code|upper}
                </label>
                <div class="col-lg-7">
                    <input type="text" name="scalexpertplugin_characteristics[{$lang.id_lang}]"
                           id="scalexpertplugin_characteristics_{$lang.id_lang}" value="{if !empty($scalexpertplugin_characteristics_values[$lang.id_lang])}{$scalexpertplugin_characteristics_values[$lang.id_lang]}{/if}">
                </div>
            </div>
        {/foreach}


    </div>
    <div class="panel-footer">
        <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i
                    class="process-icon-save"></i> {l s='Save' mod='scalexpertplugin'}</button>
        <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i
                    class="process-icon-save"></i> {l s='Save and stay' mod='scalexpertplugin'}</button>
    </div>
</div>