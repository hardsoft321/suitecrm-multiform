{**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 *}

<div class="multiform {$items_module} {if $field_defs.mode == 'single'}mode-single{/if}"
  data-itemsmodule="{$items_module}"
  data-sortingfield="{$field_defs.sortingField}"
>

{literal}
<style>
.item_template {display: none;}
.item-buttons{text-align:right; padding: 3px 0;float:right;
    top: 20px; position: relative;}
.del-message {color: red; margin-bottom: 7px;}
.bottom-links {margin-top: 13px;}
.edit tr td .multiform table.edit.view tr td {padding: 3px !important;}
.edit tr td .multiform table.edit.view {padding-top: 20px !important; padding-bottom: 20px !important;}
.editlistitem > .item-fields, .editlistitem > #EditView_tabs {margin-top: -24px;}
.multiform_validation > .required {margin-bottom: 7px;}
.edit tr td .multiform.mode-single .bean-new table.edit.view {padding-top: 3px !important;}
.multiform {top: -5px; position: relative;}
</style>
{/literal}

{* Для отображения ошибок валидации *}
<div class="multiform_validation"><input type="hidden" name="{$items_module}_multiform_validation" value="1" /></div>

{foreach from=$forms key="bean_id" item="item"}
<div class="editlistitem {if !empty($item.bean_id)}bean-id{else}bean-new{/if}" data-itemkey="{$item.key}">
    {if !empty($item.bean_id)}
    <input type="hidden" name="item_record" class="item_record" value="{$item.bean_id}">
    {/if}
    <div class="item-buttons">
        {if !empty($item.bean_id)}
        {if $is_admin}
        <a href='index.php?module={$items_module}&action=DetailView&record={$item.bean_id}'>Просмотр</a>
        {/if}
        {if $isAuditEnabled}
        <a href='javascript:void(0)' onclick='open_popup("Audit", "600", "400", "&record={$item.bean_id}&module_name={$items_module}", true, false, {ldelim}call_back_function: set_return, form_name: "EditView", field_to_name_array: []{rdelim});'>{sugar_translate label="LNK_VIEW_CHANGE_LOG"}</a>
        {/if}
        {/if}
        {if $field_defs.mode != 'single'}
        <a href="#" onclick="deleteItem($(this).closest('.editlistitem'));return false;" class="abutton remove_item_button">&times; Удалить</a>
        {/if}
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

{if $field_defs.mode != 'single'}
<div class="bottom-links editform">
    <input type="button" class="add_item" onclick="cloneItem($('#{$items_module}_template'))" value=
        "{if !empty($field_defs.vname_add)}{sugar_translate label=$field_defs.vname_add module=$items_module}{else}Добавить {sugar_translate label="LBL_OBJECT_NAME" module=$items_module}{/if}">
</div>
{/if}

{sugar_getscript file="custom/include/SugarFields/Fields/Multiform/editview.js"}
<script>
var items_module = "{$items_module}";
var formname = "{$form_name}";
{literal}
validate[formname+'_'+items_module] = validate['EditView'];
validate[formname] = [];
if(!lab321) var lab321 = {};
if(!lab321.multiform) lab321.multiform = {};
lab321.multiform[items_module] = {};

if(!lab321.multiform.set_return_orig) {
    lab321.multiform.set_return_orig = set_return; //TODO: выводить item форму с правильным form_name в open_popup
    set_return = function(popup_reply_data) {
        if(popup_reply_data.form_name == 'EditView' && !document["EditView"]) {
            popup_reply_data.form_name = formname;
        }
        lab321.multiform.set_return_orig(popup_reply_data);
    }
}

{/literal}
SUGAR.util.doWhen("document.readyState == \'complete\' && typeof initEditForm != \'undefined\' && typeof validate['"+formname+"'] != \'undefined\' && validate['"+formname+"'].length > 0", function() {ldelim}
    updateNames("{$items_module}");

    initEditForm("{$items_module}");

{if $field_defs.required}
    addToValidateCallback(formname, '{$items_module}_multiform_validation', '', false, 'Необходимо добавить хотя бы одну запись', function(formname, name) {ldelim}
        return $('.multiform.'+"{$items_module}"+' .editlistitem').not('.item_template').filter(function(i, v) {ldelim}
            return $(v).find('.item_deleted').length == 0
        {rdelim}).length;
    });
{/if}

{if $field_defs.sortingField}
    {* sortable перезапускает скрипты - валидация ломается - убираем их *}
    $('.multiform.{$items_module} .editlistitem script').remove()

    $('.multiform.{$items_module}').sortable({ldelim}
        items: ".editlistitem"
        , cursor: "move"
        , axis: "y"
    {rdelim})
    .disableSelection()
    .on('sortstop', function(event, ui) {ldelim}
        updateSorting('{$items_module}');
    {rdelim})

    updateSorting('{$items_module}');
{/if}

    lab321.multiform["{$items_module}"].ready = true;
{rdelim});
</script>

</div>
