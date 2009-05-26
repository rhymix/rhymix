<?PHP
    if(!defined("__ZBXE__")) exit();

    if(Context::get('module')=='admin') return;

    require_once(_XE_PATH_.'addons/smartphone/classes/smartphone.class.php');
    if(!smartphoneXE::isFromSmartPhone()) return;

    if($called_position == 'after_module_proc' ) {
        $oSmartphoneXE = new smartphoneXE($this, $this->module_info, $output);
        $oSmartphoneXE->procSmartPhone();
        Context::set('layout', 'none');
        Context::set('smart_content', $oSmartphoneXE->content);
        Context::set('parent_url', $oSmartphoneXE->parent_url);
        Context::set('prev_url', $oSmartphoneXE->prev_url);
        Context::set('next_url', $oSmartphoneXE->next_url);
        $this->setTemplatePath('addons/smartphone/tpl');
        $this->setTemplateFile('layout');

    } elseif($called_position == 'before_module_proc' && !$this->grant->access) {
        $oSmartphoneXE = new smartphoneXE($this, $this->module_info, $output);
        $oSmartphoneXE->procSmartPhone('msg_not_permitted_act');
        Context::set('layout', 'none');
        Context::set('smart_content', $oSmartphoneXE->content);
        Context::set('parent_url', $oSmartphoneXE->parent_url);
        Context::set('prev_url', $oSmartphoneXE->prev_url);
        Context::set('next_url', $oSmartphoneXE->next_url);
        $this->setTemplatePath('addons/smartphone/tpl');
        $this->setTemplateFile('layout');
    }
?>
