<?PHP
    if(!defined("__ZBXE__")) exit();

    if(Context::get('module')=='admin') return;

    if($called_position != 'before_module_proc' && $called_position != 'after_module_proc' ) return;

    require_once(_XE_PATH_.'addons/iphone/classes/iphone.class.php');
    if(!iphoneXE::isFromIPhone())
    {
        return;
    }

    $iphoneXE = &iphoneXE::getInstance();
    $iphoneXE->setModuleInfo($this->module_info);
    $iphoneXE->setModuleInstance($this);
    $iphoneXE->display();
?>
