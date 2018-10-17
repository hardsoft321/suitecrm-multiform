/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

function initEditForm(items_module, formname) {
    var templatePanelId = items_module + "_template";
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
    var beanId = item.children('.item_record').val();
    if(!beanId) {
        item.remove();
        return;
    }
    var fullName = buildMultiformName(item, '['+beanId+']');
    item
    .find('.item-fields, .item-buttons').remove().end()
    .append($('<input type="hidden" class="item_deleted" name="'+fullName+'[item_deleted]" value="1">'))
    .append($('<p class="del-message">').text('Запись будет удалена при сохранении формы.'))
}

//TODO: code duplication
function updateNames(items_module, formname) {
    formname = $('#'+items_module + '_template').closest('form').attr('name') || formname;
    $('.multiform.'+items_module+' > .editlistitem, .multiform.'+items_module+' > .item_template > .editlistitem').each(function() {
        var self = this;
        var beanId = $(this).attr('data-itemkey') || 'template';
        var hasLocalCurrencySelect = $(this).find('#currency_id_span').length > 0;
        var localCurrencyFields = [];
        $(this).find('[name]').not('[data-nameupdated]').each(function() {
            $(this).attr('data-nameupdated', 'true')
            var isMultiselect = this.name.slice(-2) == '[]';
            var name = isMultiselect ? this.name.slice(0, this.name.length - 2) : this.name;
            var fullName = buildMultiformName($(this), '['+name+']');
            this.name = fullName + (isMultiselect ? '[]' : '');
            this.id = this.name;

            var form1 = 'EditView_'+items_module;
            var isTemplate2 = $(this).closest('.item_template').length > 0;
            copyValidators(form1, name, (isTemplate2 ? '_template--' : '') + formname, fullName);

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
        })
        $(this)
        .find('[data-relate]').each(function(){ //для поля SumInWords
            $(this).attr('data-relate', items_module+'['+beanId+']['+$(this).attr('data-relate')+']');
        }).end()
        .data('currencyfields', localCurrencyFields)
        updateDateFields($(this));
    })

    $('.multiform.'+items_module+' > .multiform_validation > input[type="hidden"]').not('[data-nameupdated]').each(function() {
        $(this).attr('data-nameupdated', 'true');
        var name = this.name;
        var fullName = buildMultiformName($(this).closest('.multiform'), '');
        fullName = fullName ? fullName + '[' + name + ']' : name;
        this.name = fullName;
        var isTemplate2 = $(this).closest('.item_template').length > 0;
        copyValidators(formname, name, (isTemplate2 ? '_template--' : '') + formname, fullName);
        if (formname != 'EditView') {
            copyValidators('EditView', name, (isTemplate2 ? '_template--' : '') + formname, fullName);
        }
    });
}

function addToValidateMultiformRequired(items_module, formname) {
    addToValidateCallback(formname, items_module + '_multiform_validation', '', false, 'Необходимо добавить хотя бы одну запись', function(formname, name) {
        return $(document.forms[formname].elements[name]).closest('.multiform').children('.editlistitem').not('.item_template').filter(function(i, v) {
            return $(v).children('.item_deleted').length == 0
        }).length;
    });
}

function buildMultiformName(elem, name) {
    var names = elem.parents('.editlistitem, .multiform').map(function() {
        var el = $(this);
        if (el.hasClass('editlistitem')) {
            return el.attr('data-itemkey') || 'template';
        }
        if (el.hasClass('multiform')) {
            return el.attr('data-itemsmodule');
        }
        return '';
    }).get().reverse();
    return (names[0] || '') + $.map(names.slice(1), function(name) {return '['+name+']'}).join('') + name;
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
    var func_for_add_ch_f = '';//
    var add_ch_f = '';//Переменные используется для определения поля с обработчиком события при создании нового экземпляра тимплейта
    var items_module = item.closest('.multiform').attr('data-itemsmodule');
    var newId = newItemsCount++;
    var localCurrencyFields = [];
    var formname = $(item).closest('form').attr('name') || 'EditView';
    var newItem = $(item.html());
    newItem.removeClass('item_template')
    .attr('data-itemkey', 'new'+newId)
    .find('[name]').each(function() {
        var name = this.name;
        this.name = this.name.replace(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]'), items_module+'[new'+newId+']');
        this.name = this.name.replace(new RegExp('\\['+items_module+'\\]'+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]'), '['+items_module+']'+'[new'+newId+']');
        var fieldName = '';
        var matches = name.match(new RegExp(items_module+'\\[((new[0-9]+)|(template)|([a-f0-9\-]{36}))\\]\\[([^\\[\\]]+)\\]'));
        if(matches) {
            fieldName = matches[5];
        }
        var isTemplate2 = $(this).closest('.item_template').length > 0;
        copyValidators(formname, name, (isTemplate2 ? '_template--' : '') + formname, this.name);
        copyValidators('_template--' + formname, name, (isTemplate2 ? '_template--' : '') + formname, this.name);

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
            //При клонировании тимплейта, при наличии у элемента данного атрибута, дальше по коду навешивается change с функцией, указанной в атрибуте
            var addLisen = $(this).attr('addtochangeevent');
            if(addLisen){
                add_ch_f = $(this).attr('name');
                func_for_add_ch_f = addLisen;
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
    .hide().slideDown({duration:0});
    updateDateFields(newItem);
    if(typeof lab321 != "undefined" && typeof lab321.sumInWords != "undefined") {
        newItem.find('.sumInWords').removeClass('init');
        lab321.sumInWords.setup();
    }
    if(typeof select2_options != 'undefined') {
        newItem.find('select[multiple]:visible').select2(select2_options)
    }
    if(add_ch_f){
        function fn(e) {var func_name = $(e).attr('name'); eval(func_for_add_ch_f+'(func_name);')}
        YAHOO.util.Event.addListener(add_ch_f.toString(), 'change', fn, $('[name="'+add_ch_f+'"]'), false);
    }
    updateSorting(items_module);
}

function updateSorting(items_module) {
    var sorting = 0;
    var sortingField = $('.multiform.'+items_module).attr('data-sortingfield');
    if(!sortingField)
        return;
    $('.multiform.'+items_module+' > .editlistitem').each(function() {
        sorting += 10;
        var field = $(this).find('[name$=\\['+sortingField+'\\]]');
        if(!field.length) {
            var beanId = $(this).attr('data-itemkey') || 'template';
            var fullName = buildMultiformName($(this), '['+beanId+']['+sortingField+']');
            field = $('<input type="hidden" name="'+fullName+'" data-nameupdated="true">').appendTo(this)
        }
        field.val(sorting);
    })
}

function copyValidators(form1, name1, form2, name2) {
    var validators = findValidators(form1, name1);
    for(var i in validators) {
        var newValidator = $.extend({}, validators[i]);
        newValidator[nameIndex] = name2;
        if (!validate[form2]) {
            validate[form2] = [];
        }
        validate[form2][validate[form2].length] = newValidator;
    }
}

function findValidators(formname, field) {
    if(typeof validate[formname] == 'undefined')
        return [];
    var validators = [];
    for (var i = 0; i < validate[formname].length; i++)
        if (validate[formname][i][nameIndex] == field || validate[formname][i][nameIndex] == field + '[]')
            validators.push(validate[formname][i]);
    return validators;
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
