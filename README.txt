Несколько форм в форме

Требуется секьюритигрупповская доработка с $_SESSION['groupLayout'].

Пример vardefs:

$dictionary['Opportunity']['fields']['OpportunityProductsField'] = array(
    'name' => 'OpportunityProductsField',
    'vname' => 'LBL_OPPORTUNITY_PRODUCTS_FIELD',
    'vname_add' => 'LBL_ADD_OPPORTUNITY_PRODUCTS', //<- перевод "Добавить продукт";
                                                   //  если такого нет, используется "Добавить LBL_OBJECT_NAME"
    'link' => 'products', //<-required; если не надо автоматически загружать/сохранять записи можно вместо этого параметра добавить 'link_manual'
    'module' => 'OpportunityProducts', //<-required
    'required' => true, //<-хотя бы одну запись нужно добавить
    // 'mode' => 'single', //<-скрыть кнопку добавления удаления
    // 'editview' => 'quickcreatepartial', //<- кастомный эдит вью
                                           // По умолчанию, editpartialviewdefs.php
                                           // Если его нет, берется editviewdefs.php и убирается шапка и футер.
    // 'sortingField' => 'sorting', // поле для заполнения сортировки (тип int)
    'type' => 'function',
    'source' => 'non-db',
    'massupdate' => 0,
    'studio' => 'visible',
    'importable' => 'false',
    'duplicate_merge' => 'disabled',
    'duplicate_merge_dom_value' => 0,
    'audited' => false,
    'reportable' => false,
    'function' => array(
        'name' => 'SugarFieldMultiform::getFieldHtml',
        'returns' => 'html',
        'include' => 'custom/include/SugarFields/Fields/Multiform/SugarFieldMultiform.php',
    ),
);


Пример сохранения:

$itemsModule = 'OpportunityProducts';
$fieldName = 'OpportunityProductsField';
if(isset($_POST[$itemsModule])) {
    require_once 'custom/include/SugarFields/Fields/Multiform/SugarFieldMultiform.php';
    $field = new SugarFieldMultiform;
    $itemBeans = $field->save($opp, $fieldName);
}

Пример удаления:
public function mark_deleted($id)
{
    $link = 'products';
    $bean = BeanFactory::getBean($this->module_name, $id);
    $bean->load_relationship($link);
    $children = $bean->$link->getBeans();
    parent::mark_deleted($id);
    foreach($children as $child) {
        $child->mark_deleted($child->id);
    }
}

Пример ACLAccess:

public function ACLAccess($view, $is_owner = 'not_set')
{
    $linkName = 'opportunities';
    require_once 'custom/include/SugarFields/Fields/Multiform/SugarFieldMultiform.php';
    return SugarFieldMultiform::ACLAccessLikeParent($this, $linkName, $view);
}
