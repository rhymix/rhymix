<?php
    class smartphoneXE {
        var $module_info = null;
        var $content = null;
        var $oModule = null;
        var $menuList = null;

        function isFromSmartPhone()
        {
           $userAgent = $_SERVER['HTTP_USER_AGENT']; 
           $iphoneForce = Context::get('iphone');
           return $iphoneForce || $userAgent && (strpos($userAgent, 'iPod') || strpos($userAgent, 'iPhone') || strpos($userAgent, 'PPC')) ;
        }

        function &getInstance()
        {
            static $instance = null;
            if($instnace  == null)
            {
                $instance = new smartphoneXE();
                return $instance;
            }
        }

        function setModuleInstance(&$oModule) {
            if($this->oModule) return;
            $this->oModule = $oModule;
        }

        function setContent($content) {
            $this->content = $content;
        }

        function setModuleInfo(&$module_info)
        {
            if($this->module_info) return;
            $this->module_info = $module_info;
        }

        function procSmartPhone()
        {
            if(!$this->module_info) return;
            $oModule =& getModule($this->module_info->module, 'smartphone');
            if(!$oModule || !method_exists($oModule, 'procSmartPhone') ) return;
            $vars = get_object_vars($this->oModule);
            if(count($vars)) foreach($vars as $key => $val) $oModule->{$key}  = $val;
            return $oModule->procSmartPhone($this);
        }

        function getAllItems(&$menu_list, $node_srl = 0, $node_text= "Main Menu")
        {
            if($node_srl == 0) $this->menuList = array();

            $obj = null;
            $obj->text = $node_text;
            $obj->list = array();
            foreach($menu_list as $menu_node_srl => $menu_item)
            {
                $it = null;
                if(!preg_match('/^([a-zA-Z0-9\_\-]+)$/', $menu_item['url'])) { continue; }
                if($menu_item["list"] && count($menu_item["list"]) > 0)
                {
                    $this->getAllItems($menu_item["list"], $menu_node_srl, $menu_item["text"]);
                }
                $it->text = $menu_item["text"];
                $it->url = $menu_item["url"];
                $obj->list[$menu_node_srl] = $it;
            }
            $this->menuList[$node_srl] = $obj;
        }

        function setMenu()
        {
            $menu_cache_file = sprintf(_XE_PATH_.'files/cache/menu/%d.php', $this->module_info->menu_srl);
            if(!file_exists($menu_cache_file)) return;

            include $menu_cache_file;

            $this->getAllItems($menu->list);
            Context::set('menus', $this->menuList);
        }

        function display()
        {
            Context::set('layout', 'none');
            $act = Context::get('act');
            if($act)
            {
                $content = $this->procSmartPhone();
            }
            else
            {
                Context::set('module_info', $this->module_info);
                $this->setMenu();
                $oModule =& getModule($this->module_info->module, 'smartphone');
                if($oModule && method_exists($oModule, 'procSmartPhone') ) Context::set('bHavePhoneMethod', true); 
                $oTemplate = new TemplateHandler();
                $oContext = &Context::getInstance();
                $content = $oTemplate->compile(_XE_PATH_."addons/smartphone/tpl", "layout");
            }
            print $content;
            
            exit();
        }
    }
?>
