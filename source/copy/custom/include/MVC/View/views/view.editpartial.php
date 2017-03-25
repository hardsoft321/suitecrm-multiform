<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package multiform
 */
require_once('include/EditView/EditView2.php');

class ViewEditPartial extends SugarView
{
    public $type = 'editpartial';
    public $showTitle = false;
    public $view = 'EditView';

    function preDisplay()
    {
        $metadataFile = $this->getMetaDataFile();

        $this->ev = new EditView();
        $this->ev->view = $this->view;
        $this->ev->ss =& $this->ss;
        $this->ev->setup($this->module, $this->bean, $metadataFile, get_custom_file_if_exists('include/EditView/EditView.tpl'));

        if(!$metadataFile) {
            $this->ev->defs['templateMeta']['form']['headerTpl'] = 'custom/include/SugarFields/Fields/Multiform/empty.tpl';
            $this->ev->defs['templateMeta']['form']['footerTpl'] = 'custom/include/SugarFields/Fields/Multiform/empty.tpl';
        }
    }

    function display()
    {
        $this->ev->process();
        echo $this->ev->display($this->showTitle);
    }
}
