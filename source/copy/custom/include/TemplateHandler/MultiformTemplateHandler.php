<?php
require_once 'include/TemplateHandler/TemplateHandler.php';

class MultiformTemplateHandler extends TemplateHandler {

    public $cacheSubDir = 'multiform';
    public $dirSuffix;

    public function __construct($dirSuffix = null)
    {
        parent::__construct();
        $this->dirSuffix = $dirSuffix;
    }

    function checkTemplate($module, $view, $checkFormName = false, $formName='')
    {
        if(empty($this->cacheSubDir) || !empty($this->inDisplayTemplate)) {
            return parent::checkTemplate($module, $view, $checkFormName, $formName);
        }
        $cacheSubDir = $this->cacheSubDir;
        if($this->dirSuffix === null) {
            $cacheSubDir .= '_'.md5(serialize($metaDataDefs));
        }
        elseif(!empty($this->dirSuffix)) {
            $cacheSubDir .= '_'.$this->dirSuffix;
        }
        $oldGroupLayout = !empty($_SESSION['groupLayout']) ? $_SESSION['groupLayout'] : null;
        $newGroupLayout = !empty($_SESSION['groupLayout']) ? $_SESSION['groupLayout'].'/'.$cacheSubDir : $cacheSubDir;
        $_SESSION['groupLayout'] = $newGroupLayout;
        $check = parent::checkTemplate($module, $view, $checkFormName, $formName);
        $_SESSION['groupLayout'] = $oldGroupLayout;
        return $check;
    }

    function displayTemplate($module, $view, $tpl, $ajaxSave = false, $metaDataDefs = null)
    {
        if(empty($this->cacheSubDir)) {
            return parent::displayTemplate($module, $view, $tpl, $ajaxSave, $metaDataDefs);
        }
        $this->inDisplayTemplate = true;
        $cacheSubDir = $this->cacheSubDir;
        if($this->dirSuffix === null) {
            $cacheSubDir .= '_'.md5(serialize($metaDataDefs));
        }
        elseif(!empty($this->dirSuffix)) {
            $cacheSubDir .= '_'.$this->dirSuffix;
        }
        $oldGroupLayout = !empty($_SESSION['groupLayout']) ? $_SESSION['groupLayout'] : null;
        $newGroupLayout = !empty($_SESSION['groupLayout']) ? $_SESSION['groupLayout'].'/'.$cacheSubDir : $cacheSubDir;
        $_SESSION['groupLayout'] = $newGroupLayout;
        $template = parent::displayTemplate($module, $view, $tpl, $ajaxSave, $metaDataDefs);
        $_SESSION['groupLayout'] = $oldGroupLayout;
        $this->inDisplayTemplate = false;
        return $template;
    }
}
