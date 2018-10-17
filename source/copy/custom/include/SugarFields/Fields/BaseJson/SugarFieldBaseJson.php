<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'include/SugarFields/Fields/Base/SugarFieldBase.php';

class SugarFieldBaseJson extends SugarFieldBase
{
    public static function getFieldHtml($parentBean, $field, $value, $view)
    {
        $encodedValue = !empty($parentBean->$field) ? $parentBean->$field : '';
        $vardef = $parentBean->field_defs[$field];
        if ($view == 'EditView' || $view == 'QuickCreate') {
            $formName = $view == 'EditView' ? 'EditView' : 'form_SubpanelQuickCreate_' . $parentBean->module_name;
            return self::getEditHtml($encodedValue, $formName, $vardef);
        }
        if ($view == 'DetailView') {
            $formName = 'DetailView';
            return self::getDetailHtml($encodedValue, $formName, $vardef);
        }
        return '';
    }

    protected static function getDetailHtml($encodedValue, $formName, $vardef)
    {
        $str = '';
        if (!empty($encodedValue)) {
            $values = json_decode(from_html($encodedValue), true);
            if ($values === null) {
                $str .= "Decode error: " . htmlspecialchars(from_html($encodedValue));
            } else {
                foreach ($values as $value) {
                    $str .= self::getDetailPartialHtml($value, $formName, $vardef);
                }
            }
        }
        return $str;
    }

    protected static function getEditHtml($encodedValue, $formName, $vardef)
    {
        $formItemsModule = $vardef['name'];
        $isDuplicate = isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == "true";
        $forms = array();
        $count = 0;
        $str = '';
        //существующие записи
        if (!empty($encodedValue)) {
            $values = json_decode(from_html($encodedValue), true);
            if ($values === null) {
                $str .= "Decode error: " . htmlspecialchars(from_html($encodedValue));
            } else {
                foreach ($values as $value) {
                    $count++;
                    $form = self::getEditPartialHtml($value, $formName, $vardef);
                    $forms[] = array(
                        'key' => $isDuplicate ? 'new' . $count : $count,
                        'bean_id' => $isDuplicate ? '' : $count,
                        'form' => $form,
                    );
                }
            }
        }
        // Заполнение при переходе в полную форму из формы быстрого создания
        if (!empty($_REQUEST[$formItemsModule])) {
            foreach ($_REQUEST[$formItemsModule] as $id => $value) {
                if ($id == 'template') {
                    continue;
                }
                if (!preg_match('/^new[0-9]+$/', $id)) {
                    continue;
                }
                $count++;
                $form = self::getEditPartialHtml($value, $formName, $vardef);
                $forms[] = array(
                    'key' => 'new' . $count,
                    'form' => $form,
                );
            }
        }
        //шаблон
        $form = self::getEditPartialHtml(array(), $formName, $vardef);

        if(empty($forms) && !empty($vardef['required'])) {
            $forms[] = array(
                'key' => 'new1',
                'form' => $form,
            );
        }

        $ss = new Sugar_Smarty();
        $ss->assign('form_name', $formName);
        $ss->assign('form_items_module', $formItemsModule);
        $ss->assign('forms', $forms);
        $ss->assign('form_template', $form);
        $ss->assign('items_module', $vardef['module']);
        $ss->assign('field_defs', $vardef);
        $str .= '<div class="jsonmultiform">';
        $str .= $ss->fetch('custom/include/SugarFields/Fields/Multiform/EditView.tpl');
        $str .= '</div>';

        return $str;
    }

    protected static function getDetailPartialHtml($value, $formName, $vardef)
    {
        $tpl_path = $vardef['function']['detailpartialtpl'];
        $ss = new Sugar_Smarty();
        $ss->assign('value', $value);
        $ss->assign('form_name', $formName);
        $ss->assign('vardef', $vardef);
        return $ss->fetch($tpl_path);
    }

    protected static function getEditPartialHtml($value, $formName, $vardef)
    {
        $tpl_path = $vardef['function']['editpartialtpl'];
        $ss = new Sugar_Smarty();
        $ss->assign('value', $value);
        $ss->assign('form_name', $formName);
        $ss->assign('vardef', $vardef);
        return $ss->fetch($tpl_path);
    }

    public function save(&$bean, $params, $field, $properties, $prefix = '')
    {
        if (!empty($bean->edit_view_pre_save)) {
            return;
        }
        $formItemsModule = $properties['name'];
        if (!isset($params[$formItemsModule])) {
            return;
        }
        $data = array();
        foreach ($params[$formItemsModule] as $id => $itemPost) {
            if ($id == 'template') {
                continue;
            }
            if (!empty($itemPost['item_deleted'])) {
                continue;
            }
            $this->filterItemPost($itemPost, $bean, $properties);
            if (!empty($itemPost)) {
                $data[] = $itemPost;
            }
        }
        array_walk_recursive($data, function (&$item, $key) {
            $item = from_html($item);
        });
        $bean->$field = json_encode($data);

        //against audit false positive
        //TODO: strings containing &<> are always written to audit, see SugarBean::cleanBean
        if (!empty($properties['audited']) && !empty($bean->fetched_row[$field])) {
            $fetchedValues = json_decode(from_html($bean->fetched_row[$field]), true);
            if (is_array($fetchedValues)) {
                $bean->fetched_row[$field] = json_encode($fetchedValues);
            }
        }
    }

    protected function filterItemPost(&$itemPost, $bean, $properties)
    {
        if (!empty($properties['sortingField']) && isset($itemPost[$properties['sortingField']])) {
            unset($itemPost[$properties['sortingField']]);
        }
    }
}
