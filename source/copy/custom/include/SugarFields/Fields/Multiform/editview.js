/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

function initEditForm(items_module) {
    var templatePanelId = items_module + "_template";
    if(!$('form[name="'+formname+'"]').length) {
        formname = $('#'+templatePanelId).closest('form').attr('name') || 'EditView';
    }
    $('#'+templatePanelId).find('script').remove();
    var init = false;
    var initEditView_orig = initEditView;
    newItemsCount = $('.editlistitem').length+1;
    initEditView = function(form_name) {
        if(!init) {
            //updateNames();
            init = true;
        }
        initEditView_orig(form_name);
    }
    initEditView(document.forms[formname]);
}

function deleteItem(item) {
    var items_module = item.closest('.multiform').attr('data-itemsmodule');
    var beanId = item.find('.item_record').val();
    if(!beanId) {
        item.remove();
        return;
    }
    item
    .find('.item-fields, .item-buttons').remove().end()
    .append($('<input type="hidden" class="item_deleted" name="'+items_module+'['+beanId+'][item_deleted]" value="1">'))
    .append($('<p class="del-message">').text('Запись будет удалена при сохранении формы.'))
}

//TODO: code duplication
function updateNames(items_module) {
    $('.editlistitem', '.multiform.'+items_module).each(function() {
        var self = this;
        var beanId = $(this).find('[name="item_key"]').val() || 'template';
        var hasLocalCurrencySelect = $(this).find('#currency_id_span').length > 0;
        var localCurrencyFields = [];
        $(this).find('[name]').each(function() {
            var isMultiselect = this.name.slice(-2) == '[]';
            var name = isMultiselect ? this.name.slice(0, this.name.length - 2) : this.name;
            this.name = items_module+'['+beanId+']['+name+']' + (isMultiselect ? '[]' : '');
            this.id = this.name;

            if(beanId != 'template') {
                var validator = findValidator(formname+'_'+items_module, name);
                if(validator) {
                    var newValidator = $.extend({}, validator);
                    newValidator[nameIndex] = this.name;
                    validate[formname][validate[formname].length] = newValidator;
                }
            }

            if ( $(self).find('[id="'+name+'_trigger"]').length > 0 ) {
                $(self).find('[id="'+name+'_trigger"]')[0].id =
                    this.name+'[trigger]';
            }
            var onclick = $(this).attr('onclick');
            if(onclick && name.match(/btn_clr_.*/)) {
                $(this).attr('onclick', onclick.replace(/SUGAR\.clearRelateField\(this\.form,\s*'([^']+)',\s*'([^']+)'\)/, function(str, name, id) {
                    return "SUGAR.clearRelateField(this.form, '"+items_module+'['+beanId+']['+name+']'+"', '"+items_module+'['+beanId+']['+id+']'+"')";
                }));
            }
            else if(onclick && name.match(/btn_.*/)) {
                $(this).attr('onclick', onclick.replace(/"field_to_name_array":{"id":"([^"]+)","name":"([^"]+)"}/, function(str, id, name) {
                    return '"field_to_name_array":{"id":"'+items_module+'['+beanId+']['+id+']'+'","name":"'+items_module+'['+beanId+']['+name+']"}';
                }));
            }

            if(beanId != 'template') {
                if(hasLocalCurrencySelect) {
                    if(name == 'currency_id') {
                        $(this).attr('onchange', 'CurrencyConvertLocal(this.form, "'+this.name+'", $(this).closest(".editlistitem").data("currencyfields")); $(this).data("lastvalue", this.value)')
                        .data('lastvalue', this.value);
                    }
                    if(typeof currencyFields !== 'undefined' && currencyFields.indexOf(name) >= 0) {
                        localCurrencyFields.push(this.name);
                    }
                }
                else {
                    if(typeof currencyFields !== 'undefined' && currencyFields.indexOf(name) >= 0) {
                        currencyFields.push(this.name);
                    }
                }
            }
        }).end()
        .find('[data-relate]').each(function(){ //для поля SumInWords
            $(this).attr('data-relate', items_module+'['+beanId+']['+$(this).attr('data-relate')+']');
        }).end()
        .data('currencyfields', localCurrencyFields)
        updateDateFields($(this));
    })
}

function updateDateFields(item) {
    var formname = item.closest('form').attr('name');
    item.find('.date_input').each(function() {
    var a = document.getElementById(this.id);
      if (a) {
        Calendar.setup ({
            inputField : this.id,
            form : formname,
            ifFormat : cal_date_format,
            daFormat : cal_date_format,
            button : $(this).next('img').attr('id'),
            singleClick : true,
            dateStr : $(this).val(),
            startWeekday: 1,
            step : 1,
            weekNumbers:false
        });
      }
    });
}

function cloneItem(item) {
    var items_module = item.closest('.multiform').attr('data-itemsmodule');
    var newId = newItemsCount++;
    var localCurrencyFields = [];
    var newItem = $(item.html());
    newItem.removeClass('item_template')
    .find('[name]').each(function() {
        var name = this.name;
        this.name = this.name.replace(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]'), items_module+'[new'+newId+']');

        var fieldName = '';
        var matches = name.match(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]\\[([^\\[\\]]+)\\]'));
        if(matches) {
            fieldName = matches[5];
            var validator = findValidator(formname+'_'+items_module, fieldName);
            if(validator) {
                var newValidator = $.extend({}, validator);
                newValidator[nameIndex] = this.name;
                validate[formname][validate[formname].length] = newValidator;
            }
        }

        this.id = this.name;
        if ( newItem.find('[id="'+name+'[trigger]"]').length > 0 ) {
            newItem.find('[id="'+name+'[trigger]"]')[0].id =
                this.name+'[trigger]';
        }

        var onclick = $(this).attr('onclick');
        if(onclick && name.match(/\[btn_clr_.*\]/)) {
            $(this).attr('onclick', onclick.replace(/SUGAR\.clearRelateField\(this\.form,\s*'([^']+)',\s*'([^']+)'\)/, function(str, name, id) {
                name = name.replace(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]'), items_module+'[new'+newId+']');
                id = id.replace(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]'), items_module+'[new'+newId+']');
                return "SUGAR.clearRelateField(this.form, '"+name+"', '"+id+"')";
            }));
        }
        else if(onclick && name.match(/\[btn_.*\]/)) {
            $(this).attr('onclick', onclick.replace(/"field_to_name_array":{"id":"([^"]+)","name":"([^"]+)"}/, function(str, id, name) {
                name = name.replace(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]'), items_module+'[new'+newId+']');
                id = id.replace(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]'), items_module+'[new'+newId+']');
                return '"field_to_name_array":{"id":"'+id+'","name":"'+name+'"}';
            }));
        }

        //if(beanId != 'template') {
            if(/* hasCurrencySelect && */ fieldName == 'currency_id') {
                $(this).attr('onchange', 'CurrencyConvertLocal(this.form, "'+this.name+'", $(this).closest(".editlistitem").data("currencyfields")); $(this).data("lastvalue", this.value)');
            }
            if(/*!hasCurrencySelect && */typeof currencyFields !== 'undefined' && currencyFields.indexOf(fieldName) >= 0) {
                //currencyFields.push(this.name);
                localCurrencyFields.push(this.name);
            }
        //}
        //if(typeof currencyFields !== 'undefined' && currencyFields.indexOf(name) >= 0) {
        //    currencyFields.push(this.name);
        //}
    })
    .end()
    .find('[data-relate]').each(function(){ //для поля SumInWords
        $(this).attr('data-relate', $(this).attr('data-relate').replace(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]'), items_module+'[new'+newId+']'));
    })
    .end()
    .find('div[id^="detailpanel_"]').attr('id', 'detailpanel_'+newId)
        .find('a.collapseLink').attr('onclick', 'collapsePanel('+newId+');').end()
        .find('a.expandLink').attr('onclick', 'expandPanel('+newId+');').end()
    .end()
    .data('currencyfields', localCurrencyFields)
    .insertBefore(item)
    .hide().slideDown({duration:200});
    updateDateFields(newItem);
    if(typeof lab321 != "undefined" && typeof lab321.sumInWords != "undefined") {
        newItem.find('.sumInWords').removeClass('init');
        lab321.sumInWords.setup();
    }
    if(typeof select2_options != 'undefined') {
        newItem.find('select[multiple]:visible').select2(select2_options)
    }
}

function findValidator(formname, field) {
    if(typeof validate[formname] == 'undefined')
        return false;
    for (var i = 0; i < validate[formname].length; i++)
        if (validate[formname][i][nameIndex] == field || validate[formname][i][nameIndex] == field + '[]')
            return validate[formname][i];
    //TODO: массив валидаторов
    return false;
}

function CurrencyConvertLocal(form, currencyField, localCurrencyFields){
    try {
        var id = form[currencyField].options[form[currencyField].selectedIndex].value;
        var fields = new Array();
        for(i in localCurrencyFields){
            var field = localCurrencyFields[i];
            if(typeof(form[field]) != 'undefined'){
                form[field].value = unformatNumber(form[field].value, num_grp_sep, dec_sep);
                fields.push(form[field]);
            }
        }

        var lastRateLocal = ConversionRates[$(form[currencyField]).data('lastvalue') || -99];
        ConvertRateLocal(id, fields, lastRateLocal);
        for(i in fields){
            fields[i].value = formatNumber(fields[i].value, num_grp_sep, dec_sep);
            $(fields[i]).change();
        }
    } catch (err) {
    // Do nothing, if we can't find the currency_id field we will just not attempt to convert currencies
    // This typically only happens in lead conversion and quick creates, where the currency_id field may be named somethnig else or hidden deep inside a sub-form.
    }
}

function ConvertRateLocal(id,fields, lastRateLocal){
    for(var i = 0; i < fields.length; i++){
        fields[i].value = toDecimal(ConvertFromDollar(toDecimal(ConvertToDollar(toDecimal(fields[i].value), lastRateLocal)), ConversionRates[id]));
    }
}
