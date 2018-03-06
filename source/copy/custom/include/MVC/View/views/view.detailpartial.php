<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package multiform
 */
require_once('include/DetailView/DetailView2.php');
require_once('custom/include/TemplateHandler/MultiformTemplateHandler.php');

class ViewDetailPartial extends SugarView
{
    public $type = 'detailpartial';
    public $showTitle = false;
    public $view = 'DetailPartial';
    private $th;

    function preDisplay()
    {
        $this->view = 'DetailView';
        $this->showTitle = false;

        $metadataFile = $this->getMetaDataFile();

        $this->dv = new DetailView2();
        $this->dv->ss =&  $this->ss;
        $this->dv->setup($this->module, $this->bean, $metadataFile, get_custom_file_if_exists('include/DetailView/DetailView.tpl'));
        $this->dv->th = new MultiformTemplateHandler($this->type);
        $this->dv->th->ss = $this->dv->ss;
    }

    function display()
    {
        if(empty($this->bean->id)){
            sugar_die($GLOBALS['app_strings']['ERROR_NO_RECORD']);
        }
        $this->dv->process();
        echo $this->dv->display($this->showTitle);
    }
}
