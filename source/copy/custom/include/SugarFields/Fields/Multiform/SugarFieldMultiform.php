<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package multiform
 */
require_once 'include/Sugar_Smarty.php';

/**
 * Поле для добавления нескольких кредитных продуктов заявки.
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
            return self::getEditHtml($itemBeans, $formName, $parentBean->field_defs[$field]['module']);
        }
        if($view == 'DetailView') {
            $formName = $view == 'EditView' ? 'EditView' : 'form_SubpanelQuickCreate_'.$parentBean->module_name;
            $itemBeans = self::getBeans($parentBean, $field);
            return self::getDetailHtml($itemBeans);
        }
        return '';
    }

    protected static function getBeans($parentBean, $field)
    {
        if(empty($parentBean->field_defs[$field]['link']) || ! $parentBean->load_relationship($parentBean->field_defs[$field]['link'])) {
            $GLOBALS['log']->error("SugarFieldMultiform: No link for {$parentBean->module_dir} {$field}");
            return array();
        }
        $beans = $parentBean->{$parentBean->field_defs[$field]['link']}->getBeans();
        usort($beans, 'SugarFieldMultiform::compareBeansByDateEntered');
        foreach($beans as $bean) {
            $bean->parent_beans = array($parentBean);
        }
        return $beans;
    }

    public static function compareBeansByDateEntered($bean1, $bean2)
    {
        return strcmp($bean1->fetched_row['date_entered'], $bean2->fetched_row['date_entered']);
    }

    protected static function getEditHtml($itemBeans, $formName, $itemsModule)
    {
        $ss = new Sugar_Smarty();
        $forms = array();
        $isDuplicate = isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == "true";
        $count = 0;
        //существующие записи
        foreach($itemBeans as $bean) {
            $view = ViewFactory::loadView('editpartial', $bean->module_name, $bean);
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

        $origPost = $_POST;
        $origRequest = $_REQUEST;
        // Заполнение при переходе в полную форму из формы быстрого создания
        if(!empty($_POST[$itemsModule])) {
            $itemsPost = $_POST[$itemsModule];
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
                $controller->pre_save();
                $controller->bean->id = '';

                $view = ViewFactory::loadView('editpartial', $controller->bean->module_name, $controller->bean);
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
        $view = ViewFactory::loadView('editpartial', $bean->module_name, $bean);
        $view->init($bean);
        $view->module = $bean->module_name;
        $view->preDisplay();
        ob_start();
        $view->display();
        $template_html = ob_get_clean();

        $_POST = $origPost;
        $_REQUEST = $origRequest;

        if(empty($forms)) {
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
        return $ss->fetch('custom/include/SugarFields/Fields/Multiform/EditView.tpl');
    }

    public function save($parentBean, $field)
    {
        if(empty($parentBean->field_defs[$field]['module'])) {
            $GLOBALS['log']->error("SugarFieldMultiform: No module for {$parentBean->module_dir} {$field}");
            return array();
        }
        $itemsModule = $parentBean->field_defs[$field]['module'];
        $controller = ControllerFactory::getController($itemsModule);
        $controller->parentBean = $parentBean;

        $origPost = $_POST;
        $itemsPost = $_POST[$itemsModule];
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
                if($controller->bean->parent_id != $parentBean->id) {
                    sugar_die('Невозможно закрепить чужую запись');
                }
            }
            $_POST = array_merge($origPost, $itemPost);
            $_POST['parent_id'] = $parentBean->id;
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

                if($parentBean->parent_type == 'Accounts') {
                    $controller->bean->account_id = $parentBean->parent_id;
                    $controller->bean->account_rating = $parentBean->rating;
                }
                $controller->action_save();
                $beans[] = $controller->bean;
            }
        }
        $_POST = $origPost;
        return $beans;
    }

    public static function getDetailHtml($itemBeans)
    {
        $str = '';
        $str .= '<style>.view tr td table tr td {padding: 5px 6px 5px 6px}</style>';
        foreach($itemBeans as $bean) {
            $view = ViewFactory::loadView('detailpartial', $bean->module_name, $bean);
            $view->init($bean);
            $view->module = $bean->module_name;
            $view->preDisplay();
            ob_start();
            $view->display();
            $str .= ob_get_clean();
        }
        return $str;
    }
}
