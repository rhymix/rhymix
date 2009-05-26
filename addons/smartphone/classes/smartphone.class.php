<?php
    class smartphoneXE {
        var $module_info = null;
        var $oModule = null;
        var $output = null;

        var $parent_url = null;
        var $prev_url = null;
        var $next_url = null;

        var $content = null;

        function isFromSmartPhone() {
           return Context::get('smartphone') || preg_match('/(iPopd|iPhone|PPC)/',$_SERVER['HTTP_USER_AGENT']);
        }

        function haveSmartphoneModule($module) {
            return $oModule =& getModule($module, 'smartphone') && method_exists($oModule,'procSmartPhone');
        }

        function smartphoneXE($oModule, $module_info, $output) {

            $this->oModule = $oModule;
            $this->module_info = $module_info;

            if(!$this->module_info->menu_srl) {
                $oMenuModel = &getAdminModel('menu');
                $menus = $oMenuModel->getMenus($this->module_info->site_srl);
                if($menus[0]) $this->module_info->menu_srl = $menus[0]->menu_srl;
            }

            if($this->module_info->menu_srl) {
                $menu_cache_file = sprintf(_XE_PATH_.'files/cache/menu/%d.php', $this->module_info->menu_srl);
                if(!file_exists($menu_cache_file)) return;
                @include $menu_cache_file;
                Context::addHtmlHeader(sprintf('<script type="text/javascript"> var xeMenus = { %s } </script>', $this->_getAllItems($menu->list)));
                $this->_setParentUrl($menu->list);
            }
        }

        function _setParentUrl($menu_list) {
            if(!count($menu_list)) return;
            foreach($menu_list as $key => $val) {
                if(!$val['text']) continue;
                if($val['list'] && $this->_setParentUrl($val['list'])) {
                    $href = $val['href'];
                    if(preg_match('/^[a-z0-9_]+$/i',$val['url'])) $href = getUrl('','mid',$val['url'],'smartphone','true');
                    else $href = $val['href'];
                    $this->setParentUrl($href);
                    return false;
                } 
                if($val['url']==Context::get('mid')) return true;
            }
            return false;
        }

        function _getAllItems($menu_list, $depth=0) {
            if(!count($menu_list)) return;
            $output = '';

            foreach($menu_list as $menu_item)
            {
                if($output) $output .= ",";
                $key = $menu_item['text'];
                $val = $menu_item['url'];
                if($menu_item['list']) {
                    $childs = '{'.$this->_getAllItems($menu_item['list'], $depth+1).'}';
                } else {
                    $childs = 'null';
                }

                $output .= sprintf('"%s" : { "url" : "%s", "childs" : %s } ',str_replace('"','\"',$key), str_replace('"','\"',$val), $childs); 
            }
            return $output;
        }

        function procSmartPhone($msg = null) {
            if(preg_match('/(iPopd|iPhone)/',$_SERVER['HTTP_USER_AGENT'])) {
                Context::addHtmlHeader('<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>');
            } else if(preg_match('/PPC/',$_SERVER['HTTP_USER_AGENT'])) {
                Context::addHtmlHeader('<meta name="viewport" content="width=240; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>');
            }

            if(is_a($this->output, 'Object') || is_subclass_of($this->output, 'Object') || $msg) {
                if($msg) $this->setContent(Context::getLang($msg));
                else $this->setContent($this->output->getMessage());
                return;
            }

            if($this->haveSmartphoneModule($this->module_info->module)) {
                $oSmartPhoneModule =& getModule($this->module_info->module, 'smartphone');
                $vars = get_object_vars($this->oModule);
                if(count($vars)) foreach($vars as $key => $val) $oSmartPhoneModule->{$key}  = $val;
                $oSmartPhoneModule->procSmartPhone($this);
            } else {
                switch(Context::getLangType()) {
                    case 'ko' :
                            $msg = '스마트폰을 지원하지 않는 모듈입니다';
                        break;
                    case 'jp' :
                            $msg = 'このモジュールをサポートしていません。';
                        break;
                    case 'zh-TW' :
                            $msg = '該模塊不支持。';
                        break;
                    case 'zh-CN' :
                            $msg = '该模块不支持。';
                        break;
                    default :
                            $msg = 'This module is not supported.';
                        break;
                }
                $this->setContent($msg);
            }
        }

        function setContent($content) {
            $this->content = $content;
        }

        function setParentUrl($url) {
            $this->parent_url = $url;
        }

        function setPrevUrl($url) {
            $this->prev_url = $url;
        }

        function setNextUrl($url) {
            $this->next_url = $url;
        }

    }
?>
