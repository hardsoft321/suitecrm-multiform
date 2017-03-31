<?php
require_once 'include/TemplateHandler/TemplateHandler.php';

class MultiformTemplateHandler extends TemplateHandler {

    public $cacheSubDir = 'multiform';

    // function checkTemplate($module, $view, $checkFormName = false, $formName='')
    // {
    //      TODO: checkTemplate used in EditView2
    // }

    function displayTemplate($module, $view, $tpl, $ajaxSave = false, $metaDataDefs = null)
    {
        if(empty($this->cacheSubDir)) {
            return parent::displayTemplate($module, $view, $tpl, $ajaxSave, $metaDataDefs);
        }
        $oldGroupLayout = !empty($_SESSION['groupLayout']) ? $_SESSION['groupLayout'] : null;
        $newGroupLayout = !empty($_SESSION['groupLayout']) ? $_SESSION['groupLayout'].'/'.$this->cacheSubDir : $this->cacheSubDir;
        $_SESSION['groupLayout'] = $newGroupLayout;
        $template = parent::displayTemplate($module, $view, $tpl, $ajaxSave, $metaDataDefs);
        $_SESSION['groupLayout'] = $oldGroupLayout;
        return $template;
    }
}
