Несколько форм в форме


Пример vardefs:

$dictionary['Opportunity']['fields']['OpportunityProductsField'] = array(
    'required' => false,
    'name' => 'OpportunityProductsField',
    'vname' => 'LBL_OPPORTUNITY_PRODUCTS_FIELD',
    'link' => 'products', //<-required
    'module' => 'OpportunityProducts', //<-required
    'required' => true, //<-хотя бы одну запись нужно добавить
    // 'mode' => 'single', //<-скрыть кнопку добавления удаления
    // 'editview' => 'quickcreatepartial', //<- кастомный эдит вью
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

if(isset($_POST['OpportunityProducts'])) {
    require_once 'custom/include/SugarFields/Fields/Multiform/SugarFieldMultiform.php';
    $field = new SugarFieldMultiform;
    $products = $field->save($opp, 'OpportunityProductsField');
}


Пример ACLAccess:

public function ACLAccess($view, $is_owner = 'not_set')
{
    require_once 'custom/include/SugarFields/Fields/Multiform/SugarFieldMultiform.php';
    return SugarFieldMultiform::ACLAccessLikeParent($this, 'opportunities', $view);
}
