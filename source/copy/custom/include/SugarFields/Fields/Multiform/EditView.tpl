{**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 *}

<div class="multiform {$items_module}">

{literal}
<style>
.item_template {display: none;}
.item-buttons{text-align:right; padding: 3px 0;float:right;
    top: 20px; position: relative;}
.del-message {color: red;}
.edit tr td .multiform table.edit.view tr td {padding: 3px !important;}
.edit tr td .multiform table.edit.view {padding-top: 20px !important;}
</style>
{/literal}

{* Для отображения ошибок валидации *}
<div class="multiform_validation"><input type="hidden" name="multiform_validation" value="1" /></div>

{foreach from=$forms key="bean_id" item="item"}
<div class="editlistitem">
    {if !empty($item.bean_id)}
    <input type="hidden" name="item_record" class="item_record" value="{$item.bean_id}">
    {/if}
    <input type="hidden" name="item_key" class="item_key" value="{$item.key}">
    <div class="item-buttons">
        {if !empty($item.bean_id)}
        {if $is_admin}
        <a href='index.php?module={$items_module}&action=DetailView&record={$item.bean_id}'>Просмотр</a>
        {/if}
        <a href='javascript:void(0)' onclick='open_popup("Audit", "600", "400", "&record={$item.bean_id}&module_name={$items_module}", true, false, {ldelim}call_back_function: set_return, form_name: "EditView", field_to_name_array: []{rdelim});'>{sugar_translate label="LNK_VIEW_CHANGE_LOG"}</a>
        {/if}
        <a href="#" onclick="deleteItem($(this).closest('.editlistitem'));return false;" class="abutton remove_item_button">&times; Удалить</a>
    </div>
    <div class="item-fields">
        {$item.form}
    </div>
</div>
{/foreach}

<div id="{$items_module}_template" class="item_template" type="text/template">
<div class="item_template editlistitem">
    <div class="item-buttons">
        <a href="#" onclick="deleteItem($(this).closest('.editlistitem'));return false;" class="abutton">&times; Удалить</a>
    </div>

    {$form_template}

</div>
</div>

<div class="bottom-links edit">
    <input type="button" class="add_item" onclick="cloneItem($('#{$items_module}_template'))" value="+ Добавить Кредитный продукт">
</div>

{sugar_getscript file="custom/include/SugarFields/Fields/Multiform/editview.js"}
<script>
var module = "{$items_module}";
var formname = "{$form_name}";
{literal}
validate[formname+'_'+module] = validate['EditView'];
validate[formname] = [];
if(!lab321) var lab321 = {};
if(!lab321.multiform) lab321.multiform = {};
lab321.multiform[module] = {};

var templatePanelId = module + "_template";
SUGAR.util.doWhen("document.readyState == \'complete\' && typeof initEditForm != \'undefined\' && typeof validate['"+formname+"'] != \'undefined\' && validate['"+formname+"'].length > 0", function() {
    updateNames();

    initEditForm();

    addToValidateCallback(formname, 'multiform_validation', '', false, 'Необходимо добавить хотя бы одну запись', function(formname, name) {
        return $('.multiform.'+module+' .editlistitem').not('.item_template').length;
    });

    lab321.multiform[module].ready = true;
});
{/literal}
</script>

</div>
