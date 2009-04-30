<?PHP
    if(!defined("__ZBXE__")) exit();

    if(Context::get('module')=='admin') return;

    if($called_position != 'before_module_proc' && $called_position != 'after_module_proc' ) return;

    require_once(_XE_PATH_.'addons/smartphone/classes/smartphone.class.php');
    debugPrint("here");
    if(!smartphoneXE::isFromSmartPhone())
    {
    debugPrint("here");
        return;
    }
    debugPrint("here");


    $oSmartphoneXE = &smartphoneXE::getInstance();
    $oSmartphoneXE->setModuleInfo($this->module_info);
    $oSmartphoneXE->setModuleInstance($this);
    $oSmartphoneXE->display();
?>
