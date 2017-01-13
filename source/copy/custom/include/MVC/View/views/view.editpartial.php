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
    public $view = 'EditPartial';

    function preDisplay()
    {
        $metadataFile = $this->getMetaDataFile();

        $this->ev = new EditView();
        $this->ev->view = 'EditView';
        $this->ev->ss =& $this->ss;
        $this->ev->setup($this->module, $this->bean, $metadataFile, get_custom_file_if_exists('include/EditView/EditView.tpl'));
    }

    function display()
    {
        $this->ev->process();
        echo $this->ev->display($this->showTitle);
    }
}
