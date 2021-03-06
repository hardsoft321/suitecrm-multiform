<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package multiform
 */
require_once 'include/Sugar_Smarty.php';

/**
 * Поле для добавления нескольких записей в формах
 * редактирования/просмотра родительской записи.
 */
class SugarFieldMultiform
{
    public static function getFieldHtml($parentBean, $field, $value, $view)
    {
        if($view == 'EditView' || $view == 'QuickCreate') {
            $formName = $view == 'EditView' ? 'EditView' : 'form_SubpanelQuickCreate_'.$parentBean->module_name;
            $itemBeans = self::getBeans($parentBean, $field);
            if(empty($parentBean->field_defs[$field]['module'])) {
                $GLOBALS['log']->error("SugarFieldMultiform: No module for {$parentBean->module_dir} {$field}");
                return '';
            }
            if(empty($parentBean->field_defs[$field]['editview'])) {
                $parentBean->field_defs[$field]['editview'] = 'editpartial';
            }
            return self::getEditHtml($itemBeans, $formName, $parentBean->field_defs[$field]);
        }
        if($view == 'DetailView') {
            $formName = 'DetailView';
            $itemBeans = self::getBeans($parentBean, $field);
            return self::getDetailHtml($itemBeans, $formName, $parentBean->field_defs[$field]);
        }
        return '';
    }

    protected static function getBeans($parentBean, $field)
    {
        if(empty($parentBean->field_defs[$field]['link']) || ! $parentBean->load_relationship($parentBean->field_defs[$field]['link'])) {
            if(!isset($parentBean->field_defs[$field]['link_manual'])) {
                $GLOBALS['log']->error("SugarFieldMultiform: No link for {$parentBean->module_dir} {$field}");
            }
            return array();
        }
        static $cache = array();
        if(isset($cache[$parentBean->module_name][$parentBean->id][$parentBean->field_defs[$field]['link']])) {
            return $cache[$parentBean->module_name][$parentBean->id][$parentBean->field_defs[$field]['link']];
        }
        $beans = $parentBean->{$parentBean->field_defs[$field]['link']}->getBeans();
        if(!empty($beans)) {
            $b = reset($beans);
            if(!empty($parentBean->field_defs[$field]['sortingField'])) {
                $sortingField = $parentBean->field_defs[$field]['sortingField'];
                usort($beans, function($bean1, $bean2) use ($sortingField) {
                    return strnatcmp($bean1->$sortingField, $bean2->$sortingField);
                });
            }
            elseif(!empty($b->date_entered)) {
                usort($beans, 'SugarFieldMultiform::compareBeansByDateEntered');
            }
            elseif(!empty($b->date_modified)) {
                usort($beans, 'SugarFieldMultiform::compareBeansByDateModified');
            }
        }
        foreach($beans as $bean) {
            $bean->parent_beans = array($parentBean);
        }
        $cache[$parentBean->module_name][$parentBean->id][$parentBean->field_defs[$field]['link']] = $beans;
        return $beans;
    }

    public static function compareBeansByDateEntered($bean1, $bean2)
    {
        $sort1 = strcmp($bean1->fetched_row['date_entered'], $bean2->fetched_row['date_entered']);
        if ($sort1 || empty($bean1->fetched_row['id'])) {
            return $sort1;
        }
        return strcmp($bean1->fetched_row['id'], $bean2->fetched_row['id']);
    }

    public static function compareBeansByDateModified($bean1, $bean2)
    {
        return strcmp($bean1->fetched_row['date_modified'], $bean2->fetched_row['date_modified']);
    }

    protected static function getEditHtml($itemBeans, $formName, $field_defs)
    {
        $itemsModule = $field_defs['module'];
        $formItemsModule = !empty($field_defs['form_module']) ? $field_defs['form_module'] : $field_defs['module'];
        $ss = new Sugar_Smarty();
        $forms = array();
        $isDuplicate = isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == "true";
        $count = 0;
        //существующие записи
        foreach($itemBeans as $bean) {
            $view = ViewFactory::loadView($field_defs['editview'], $bean->module_name, $bean);
            $view->init($bean);
            $view->module = $bean->module_name;
            $view->preDisplay();
            ob_start();
            $view->display();
            $forms[] = array(
                'key' => $isDuplicate ? 'new'.(++$count) : $bean->id,
                'bean_id' => $isDuplicate ? '' : $bean->id,
                'form' => ob_get_clean(),
            );
        }

        $origPost = isset($_POST) ? $_POST : array();
        $origRequest = isset($_REQUEST) ? $_REQUEST : array();
        // Заполнение при переходе в полную форму из формы быстрого создания
        if(!empty($_REQUEST[$formItemsModule])) {
            $itemsPost = $_REQUEST[$formItemsModule];
            $controller = ControllerFactory::getController($itemsModule);
            foreach($itemsPost as $id => $itemPost) {
                if($id == 'template') {
                    continue;
                }
                if(!preg_match('/^new[0-9]+$/', $id)) {
                    continue;
                }
                $_POST = array_merge($origPost, $itemPost);
                $_REQUEST = array_merge($origRequest, $itemPost);

                $controller->bean = BeanFactory::newBean($itemsModule);
                $controller->bean->parentAclChecked = true;
                $controller->bean->edit_view_pre_save = true;
                $controller->pre_save();
                $controller->bean->id = '';

                $view = ViewFactory::loadView($field_defs['editview'], $controller->bean->module_name, $controller->bean);
                $view->init($controller->bean);
                $view->module = $controller->bean->module_name;
                $view->preDisplay();
                ob_start();
                $view->display();
                $html = ob_get_clean();
                $forms[] = array(
                    'key' => 'new'.(++$count),
                    'form' => $html,
                );
            }
            $_POST = $origPost;
        }

        unset($_POST);
        unset($_REQUEST);
        //шаблон
        $bean = BeanFactory::newBean($itemsModule);
        $view = ViewFactory::loadView($field_defs['editview'], $bean->module_name, $bean);
        $view->init($bean);
        $view->module = $bean->module_name;
        $view->preDisplay();
        ob_start();
        $view->display();
        $template_html = ob_get_clean();

        $_POST = $origPost;
        $_REQUEST = $origRequest;

        if(empty($forms) && !empty($field_defs['required'])) {
            $forms[] = array(
                'key' => 'new1',
                'form' => $template_html,
            );
        }

        $ss->assign('form_name', $formName);
        $ss->assign('forms', $forms);
        $ss->assign('form_template', $template_html);
        $ss->assign('is_admin', $GLOBALS['current_user']->isAdmin());
        $ss->assign('items_module', $itemsModule);
        $ss->assign('form_items_module', $formItemsModule);
        $ss->assign('field_defs', $field_defs);
        $ss->assign('isAuditEnabled', $bean->is_AuditEnabled());
        return $ss->fetch('custom/include/SugarFields/Fields/Multiform/EditView.tpl');
    }

    public function save($parentBean, $field)
    {
        if(empty($parentBean->field_defs[$field]['module'])) {
            $GLOBALS['log']->error("SugarFieldMultiform: No module for {$parentBean->module_dir} {$field}");
            return array();
        }
        $itemsModule = $parentBean->field_defs[$field]['module'];
        $formItemsModule = !empty($parentBean->field_defs[$field]['form_module'])
            ? $parentBean->field_defs[$field]['form_module']
            : $parentBean->field_defs[$field]['module'];

        if (!isset($_POST[$formItemsModule])) {
            return array();
        }

        $object = BeanFactory::getObjectName($parentBean->module_name);
        $linkName = $parentBean->field_defs[$field]['link'];
        $relName = $parentBean->field_defs[$linkName]['relationship'];
        if(empty($GLOBALS['dictionary'][$object]['relationships'][$relName]['rhs_key'])
            || $GLOBALS['dictionary'][$object]['relationships'][$relName]['rhs_module'] != $itemsModule)
        {
            $GLOBALS['log']->error("SugarFieldMultiform: No parent key for {$parentBean->module_dir} {$field}");
            return array();
        }
        $itemParentIdKey = $GLOBALS['dictionary'][$object]['relationships'][$relName]['rhs_key'];
        $itemParentTypeKey = (isset($GLOBALS['dictionary'][$object]['relationships'][$relName]['relationship_role_column']) &&
                             isset($GLOBALS['dictionary'][$object]['relationships'][$relName]['relationship_role_column_value'])) ?
                                   $GLOBALS['dictionary'][$object]['relationships'][$relName]['relationship_role_column'] : '';

        $controller = ControllerFactory::getController($itemsModule);
        $controller->parentBean = $parentBean;

        $itemsPost = $_POST[$formItemsModule];
        $_POST[$formItemsModule] = array();
        $origPost = $_POST;
        $beans = array();

        foreach($itemsPost as $id => $itemPost) {
            if($id == 'template') {
                continue;
            }
            $controller->bean = BeanFactory::newBean($itemsModule);
            if(!preg_match('/^new[0-9]+$/', $id)) {
                $controller->bean = $controller->bean->retrieve($id);
                if(!$controller->bean) {
                    sugar_die('Запись для обновления не найдена');
                }
                if($controller->bean->$itemParentIdKey != $parentBean->id) {
                    sugar_die('Невозможно закрепить чужую запись');
                }
            }
            $_POST = array_merge($origPost, $itemPost);
            $_POST[$itemParentIdKey] = $parentBean->id;
            if (!empty($itemParentTypeKey))
                $_POST[$itemParentTypeKey] = $GLOBALS['dictionary'][$object]['relationships'][$relName]['relationship_role_column_value'];
            $controller->bean->parent_beans = array($parentBean);
            $controller->bean->parentAclChecked = true;

            if(!empty($itemPost['item_deleted'])) {
                if(!empty($controller->bean->id)) {
                    //$_REQUEST['record'] = $controller->bean->id;
                    //$controller->action_delete(); //action_delete is protected method
                    if(!$controller->bean->ACLAccess('Delete')){
                        ACLController::displayNoAccess(true);
                        sugar_cleanup(true);
                    }
                    $controller->bean->mark_deleted($controller->bean->id);
                    //$_REQUEST['record'] = '';
                }
            }
            else {
                $controller->pre_save();
                $controller->bean->notify_on_save = false;
                //TODO: $_REQUEST['module'] = $itemsModule; //для наследования групп в SecurityGroups/AssignGroups.php
                $controller->action_save();
                $beans[] = $controller->bean;
            }
        }
        $_POST = $origPost;
        $_POST[$formItemsModule.'--processed'] = $itemsPost;
        return $beans;
    }

    public function mark_deleted($parentBean, $field)
    {
        $itemBeans = self::getBeans($parentBean, $field);
        foreach ($itemBeans as $bean) {
            $bean->mark_deleted($bean->id);
        }
    }

    function ACLAccessLikeParent($itemBean, $parentLink, $view)
    {
        if($GLOBALS['current_user']->isAdmin()) {
            return true;
        }
        if(!empty($itemBean->parentAclChecked)) {
            return true;
        }

        $view = strtolower($view);
        $isUpdate = false;
        $isDelete = false;
        switch ($view)
        {
            case 'list':
            case 'index':
            case 'listview':
                return false; //в дочерних записях скорее всего не настроена связь с группами
            case 'edit':
            case 'save':
            case 'popupeditview':
            case 'editview':
                $isUpdate = true;
                break;
            case 'view':
            case 'detail':
            case 'detailview':
                break;
            case 'delete':
                $isDelete = true;
                break;
            case 'export':
                return false;
            case 'import':
                return false;
        }

        $relName = $itemBean->field_defs[$parentLink]['relationship'];
        if(empty($GLOBALS['dictionary'][$itemBean->object_name]['relationships'][$relName]['rhs_key'])
            || $GLOBALS['dictionary'][$itemBean->object_name]['relationships'][$relName]['rhs_module'] != $itemBean->module_name
            || empty($GLOBALS['dictionary'][$itemBean->object_name]['relationships'][$relName]['lhs_module'])
            )
        {
            $GLOBALS['log']->error("SugarFieldMultiform: No parent key for {$parentBean->module_dir} {$field}.");
            return false;
        }
        $itemParentIdKey = $GLOBALS['dictionary'][$itemBean->object_name]['relationships'][$relName]['rhs_key'];

        if(!isset($itemBean->link_parent_bean[$parentLink])) {
            if(!empty($_REQUEST['action']) && $_REQUEST['action'] == 'SubpanelCreates'
                && !empty($_REQUEST['module']) && $_REQUEST['module'] == 'Home'
                && !empty($_REQUEST['target_module']) && $_REQUEST['target_module'] == $itemBean->module_name
                && !empty($_REQUEST['parent_type']) && $GLOBALS['dictionary'][$itemBean->object_name]['relationships'][$relName]['lhs_module']
                && !empty($_REQUEST['parent_id'])
                )
            {
                $itemBean->link_parent_bean[$parentLink]['bean'] = BeanFactory::getBean($_REQUEST['parent_type'], $_REQUEST['parent_id']);
            }
            else {
                if(!$itemBean->load_relationship($parentLink)) {
                    $GLOBALS['log']->error("SugarFieldMultiform: No link {$parentLink} in {$itemBean->module_name}");
                    return false;
                }
                $parentBeans = $itemBean->$parentLink->getBeans();
                $parentBean = $itemBean->link_parent_bean[$parentLink]['bean'] = reset($parentBeans);
            }
            if($parentBean) {
                $itemBean->link_parent_bean[$parentLink]['is_owner'] = $parentBean->isOwner($GLOBALS['current_user']->id);
                if(file_exists("modules/SecurityGroups/SecurityGroup.php"))
                {
                    require_once("modules/SecurityGroups/SecurityGroup.php");
                    /* from SugarBean::ACLAccess */
                    switch ($view)
                    {
                        case 'list':
                        case 'index':
                        case 'listview':
                            $action = "list";
                        break;
                        case 'edit':
                        case 'save':
                        case 'popupeditview':
                    case 'editview':
                        $action = "edit";
                        break;
                    case 'view':
                    case 'detail':
                    case 'detailview':
                        $action = "view";
                        break;
                    case 'delete':
                        $action = "delete" ;
                        break;
                    case 'export':
                        $action = "export";
                        break;
                    case 'import':
                        $action = "import";
                        break;
                    default:
                        $action = "";
                        break;
                    }
                    $itemBean->link_parent_bean[$parentLink]['in_group'] = SecurityGroup::groupHasAccess($parentBean->module_dir,$parentBean->id, $action); 
                }
            }
        }
        $parentBean = $itemBean->link_parent_bean[$parentLink]['bean'];
        $is_owner = $itemBean->link_parent_bean[$parentLink]['is_owner'];
        $in_group = $itemBean->link_parent_bean[$parentLink]['in_group'];
        if(!$parentBean) {
            return false;
        }

        // проверяем соответствие родителя и дочерней записи
        if(!empty($itemBean->fetched_row[$itemParentIdKey]) && $itemBean->fetched_row[$itemParentIdKey] != $parentBean->id) {
            return false;
        }
        // запрещаем смену родителя на другую запись
        if(!empty($itemBean->fetched_row[$itemParentIdKey]) && $itemBean->$itemParentIdKey != $parentBean->id) {
            return false;
        }
        return $parentBean->ACLAccess($view, $is_owner, $in_group);
    }

    public static function getDetailHtml($itemBeans, $formName, $field_defs)
    {
        $str = '';
        $str .= '<style>
.view tr td table tr td {padding: 5px 6px 5px 6px}
.detaillistitem .view {margin: 0; border-bottom-width: 0}
</style>';
        foreach($itemBeans as $bean) {
            $viewType = !empty($field_defs['detailview']) ? $field_defs['detailview'] : 'detailpartial';
            $view = ViewFactory::loadView($viewType, $bean->module_name, $bean);
            $view->init($bean);
            $view->module = $bean->module_name;
            $view->preDisplay();
            ob_start();
            echo '<div class="detaillistitem">';
            $view->display();
            echo '</div>';
            $str .= ob_get_clean();
        }
        return $str;
    }
}
