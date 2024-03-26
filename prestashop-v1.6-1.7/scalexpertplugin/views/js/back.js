/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(document).ready(function () {
    $('input.password').each(function () {
        $(this).attr('type', 'password');
    });

    productAutocompleteInput.onReady();
});

productAutocompleteInput = new function(){
    var self = this;
    this.initAccessoriesAutocomplete = function (){
        $('.AdminScalexpertCustomize input[name="product_autocomplete_input"]').each(function (i, elm) {

            var $parent = $(elm).parents('.form-group');

            $(elm).autocomplete('../modules/scalexpertplugin/ajax/ajax_products_list.php?exclude_packs=0&excludeVirtuals=1&token=' + token, {
                minChars: 1,
                autoFill: true,
                max: 20,
                matchContains: true,
                mustMatch: false,
                scroll: false,
                cacheLength: 0,
                formatItem: function (item) {
                    if(typeof item !== 'undefined' && item.length && typeof item[1] !== 'undefined' && typeof item[0] !== 'undefined') {
                        return item[1] + ' - ' + item[0];
                    }
                    return '';
                },
            }).result(self.addAccessory);

            $(elm).setOptions({
                extraParams: {
                    excludeIds: self.getAccessoriesIds($parent)
                }
            });
        });
    };

    this.getAccessoriesIds = function($parent)
    {
        $inputAccessories = $parent.find('.inputAccessories');
        if ($inputAccessories.val() === undefined)
            return '';
        return 0 + ',' + $inputAccessories.val().replace(/\-/g,',');
    }

    this.addAccessory = function(event, data, formatted)
    {
        var $parent = $(this).parents('.form-group');
        if (data == null) {
            return false;
        }

        var productId = data[1];
        var productName = data[0];

        var $divAccessories = $parent.find('.divAccessories');
        var $inputAccessories = $parent.find('.inputAccessories');
        var $nameAccessories = $parent.find('.nameAccessories');

        /* delete product from select + add product line to the div, input_name, input_ids elements */
        $divAccessories.html($divAccessories.html() + '<div class="form-control-static"><button type="button" class="delAccessory btn btn-default" name="' + productId + '"><i class="icon-remove text-danger"></i></button>&nbsp;'+ productName +'</div>');
        $nameAccessories.val($nameAccessories.val() + productName + '¤');
        $inputAccessories.val($inputAccessories.val() + productId + ',');
        $('.AdminScalexpertCustomize input[name="product_autocomplete_input"]').val('');
        $('.AdminScalexpertCustomize input[name="product_autocomplete_input"]').setOptions({
            extraParams: {excludeIds : self.getAccessoriesIds($parent)}
        });
    };

    this.delAccessory = function($this)
    {
        var id = $this.attr('name');
        var $parent = $this.parents('.form-group');

        var $divAccessories = $parent.find('.divAccessories');
        var $inputAccessories = $parent.find('.inputAccessories');
        var $nameAccessories = $parent.find('.nameAccessories');

        // Cut hidden fields in array
        var inputCut = $inputAccessories.val().split(',');
        var nameCut = $nameAccessories.val().split('¤');

        // if (inputCut.length != nameCut.length)
        if (!inputCut.length)
            return jAlert('Bad size');

        // Reset all hidden fields
        $inputAccessories.val('');
        $nameAccessories.val('');

        $divAccessories.html('');

        for (i in inputCut)
        {
            // If empty, error, next
            if (!inputCut[i] || !nameCut[i])
                continue ;

            // Add to hidden fields no selected products OR add to select field selected product
            if (inputCut[i] != id)
            {
                $inputAccessories.value += inputCut[i] + ',';
                $nameAccessories.value += nameCut[i] + '¤';
                $divAccessories.append('<div class="form-control-static"><button type="button" class="delAccessory btn btn-default" name="' + inputCut[i] +'"><i class="icon-remove text-danger"></i></button>&nbsp;' + nameCut[i] + '</div>');
            }
        }

        $parent.find('input[name="product_autocomplete_input"]').setOptions({
            extraParams: {excludeIds : self.getAccessoriesIds($parent)}
        });
    };

    /**
     * Update the manufacturer select element with the list of existing manufacturers
     */
    this.getManufacturers = function(){
        $.ajax({
            url: 'ajax-tab.php',
            cache: false,
            dataType: 'json',
            data: {
                ajaxProductManufacturers:"1",
                ajax : '1',
                token : token,
                controller : 'AdminProducts',
                action : 'productManufacturers'
            },
            success: function(j) {
                var options;
                if (j) {
                    for (var i = 0; i < j.length; i++) {
                        options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
                    }
                }
                $('select#id_manufacturer').chosen({width: '250px'}).append(options).trigger("chosen:updated");
            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
                $("select#id_manufacturer").replaceWith("<p id=\"id_manufacturer\">[TECHNICAL ERROR] ajaxProductManufacturers: "+textStatus+"</p>");
            }
        });
    };

    this.onReady = function(){
        self.initAccessoriesAutocomplete();
        self.getManufacturers();
        $('.divAccessories').delegate('.delAccessory', 'click', function(){
                self.delAccessory($(this));
        });
        // if (display_multishop_checkboxes)
        //     ProductMultishop.checkAllAssociations();
    };
}


function viewPassword(name) {
    if ($('input[name="' + name + '"]').attr('type') == 'text') {
        $('input[name="' + name + '"]').attr('type', 'password');
    } else {
        $('input[name="' + name + '"]').attr('type', 'text');
    }
}

function checkKeys() {
    var mode = $('select[name="mode"] > option:selected').val();
    if (mode == 'production') {
        var apiIdentifier = encodeURIComponent($('input[name="apiProductionIdentifier"]').val());
        var apiKey = encodeURIComponent($('input[name="apiProductionKey"]').val());
    } else {
        var apiIdentifier = encodeURIComponent($('input[name="apiTestIdentifier"]').val());
        var apiKey = encodeURIComponent($('input[name="apiTestKey"]').val());
    }

    if (!apiIdentifier || !apiKey) {
        alert('Some fields are empty for ' + mode + ' API Credentials.');
        return;
    }

    $.ajax({
        type: 'POST',
        headers: {"cache-control": "no-cache"},
        async: true,
        cache: false,
        dataType: 'json',
        url: currentIndex + '&token=' + token + '&' + 'rand=' + new Date().getTime(),
        data: 'ajax=true&action=checkKeys&mode='+mode+'&apiIdentifier=' + apiIdentifier + '&apiKey=' + apiKey,
        success: function (data) {
            if (data.hasError) {
                alert(mode + ' API Credentials are invalid!'); //data.error
            } else {
                alert(mode + ' API Credentials are correct!');
            }
        }
    });
}
