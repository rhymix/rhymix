<?php
    /**
    * @class ModuleObject
    * @author zero (zero@nzeo.com)
    * @brief base class of ModuleHandler
    **/

    class ModuleObject extends Object {

        var $mid = NULL; ///< string to represent run-time instance of Module (XE Module)
        var $module = NULL; ///< Class name of Xe Module that is identified by mid
        var $module_srl = NULL; ///< integer value to represent a run-time instance of Module (XE Module)
        var $module_info = NULL; ///< an object containing the module information 
        var $xml_info = NULL; ///< an object containing the module description extracted from XML file

        var $module_path = NULL; ///< a path to directory where module source code resides

        var $act = NULL; ///< a string value to contain the action name

        var $template_path = NULL; ///< a path of directory where template files reside
        var $template_file = NULL; ///< name of template file

        var $layout_path = ''; ///< a path of directory where layout files reside
        var $layout_file = ''; ///< name of layout file
        var $edited_layout_file = ''; ///< name of temporary layout files that is modified in an admin mode

        var $stop_proc = false; ///< a flag to indicating whether to stop the execution of code.

        /**
         * @brief setter to set the name of module
         * @param name of module
         **/
        function setModule($module) {
            $this->module = $module;
        }

        /**
         * @brief setter to set the name of module path
         * @param the directory path to a module directory
         **/
        function setModulePath($path) {
            if(substr($path,-1)!='/') $path.='/';
            $this->module_path = $path;
        }

        /**
         * @brief setter to set an url for redirection
         * @param $url url for redirection
         * @remark redirect_url is used only for ajax requests
         **/
        function setRedirectUrl($url='./') {
            $this->add('redirect_url', $url);
        }

        /**
         * @brief sett to set the template path for refresh.html
         * @remark refresh.html is executed as a result of method execution
         * 공통 tpl중 refresh.html을 실행할 뿐..
         **/
        function setRefreshPage() {
            $this->setTemplatePath('./common/tpl');
            $this->setTemplateFile('refresh');
        }


        /**
         * @brief sett to set the action name
         **/
        function setAct($act) {
            $this->act = $act;
        }

        /**
         * @brief sett to set module information
         * @param[in] $module_info object containing module information
         * @param[in] $xml_info object containing module description
        **/
        function setModuleInfo($module_info, $xml_info) {
            // 기본 변수 설정
            $this->mid = $module_info->mid;
            $this->module_srl = $module_info->module_srl;
            $this->module_info = $module_info;
            $this->xml_info = $xml_info;
            $this->skin_vars = $module_info->skin_vars;

            // 웹서비스에서 꼭 필요한 인증 정보와 권한 설정 체크
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');

            // module model 객체 생성
            $oModuleModel = &getModel('module');

            // XE에서 access, manager (== is_admin) 는 고정된 권한명이며 이와 관련된 권한 설정
            $module_srl = Context::get('module_srl');
            if(!$module_info->mid && preg_match('/^([0-9]+)$/',$module_srl)) {
                $request_module = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if($request_module->module_srl == $module_srl) {
                    $grant = $oModuleModel->getGrant($request_module, $logged_info);
                }
            } else {
                $grant = $oModuleModel->getGrant($module_info, $logged_info, $xml_info);
            }

            // 현재 모듈의 access 권한이 없으면 권한 없음 표시
            //if(!$grant->access) return $this->stop("msg_not_permitted");

            // 관리 권한이 없으면 permision, action 확인
            if(!$grant->manager) {
                // 현재 요청된 action의 퍼미션 type(guest, member, manager, root)를 구함
                $permission_target = $xml_info->permission->{$this->act};

                // module.xml에 명시된 퍼미션이 없을때 action명에 Admin이 있으면 manager로 체크
                if(!$permission_target && substr_count($this->act, 'Admin')) $permission_target = 'manager';

                // 권한 체크
                switch($permission_target) {
                    case 'root' :
                            $this->stop('msg_not_permitted_act');
                        break;
                    case 'manager' :
                            if(!$grant->manager) $this->stop('msg_not_permitted_act');
                        break;
                    case 'member' :
                            if(!$is_logged) $this->stop('msg_not_permitted_act');
                        break;
                }
            }

            // 권한변수 설정
            $this->grant = $grant;
            Context::set('grant', $grant);

            if(method_exists($this, 'init')) $this->init();
        }

        /**
         * @brief set the stop_proc and approprate message for msg_code
         * @param $msg_code an error code 
         **/
        function stop($msg_code) {
            // proc 수행을 중지 시키기 위한 플래그 세팅
            $this->stop_proc = true;

            // 에러 처리
            $this->setError(-1);
            $this->setMessage($msg_code);

            // message 모듈의 에러 표시
            $oMessageView = &getView('message');
            $oMessageView->setError(-1);
            $oMessageView->setMessage($msg_code);
            $oMessageView->dispMessage();

            $this->setTemplatePath($oMessageView->getTemplatePath());
            $this->setTemplateFile($oMessageView->getTemplateFile());

            return $this;
        }

        /**
         * @brief set the file name of the template file
         **/
        function setTemplateFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->template_file = $filename;
        }

        /**
         * @brief retrieve the directory path of the template directory
         **/
        function getTemplateFile() {
            return $this->template_file;
        }

        /**
         * @brief set the directory path of the template directory
         **/
        function setTemplatePath($path) {
            if(substr($path,0,1)!='/' && substr($path,0,2)!='./') $path = './'.$path;
            if(substr($path,-1)!='/') $path .= '/';
            $this->template_path = $path;
        }

        /**

         * @brief retrieve the directory path of the template directory
         **/
        function getTemplatePath() {
            return $this->template_path;
        }

        /**
         * @brief set the file name of the temporarily modified by admin
         **/
        function setEditedLayoutFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->edited_layout_file = $filename;
        }

        /**
         * @brief retreived the file name of edited_layout_file
         **/
        function getEditedLayoutFile() {
            return $this->edited_layout_file;
        }

        /**
         * @brief set the file name of the layout file
         **/
        function setLayoutFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->layout_file = $filename;
        }

        /**
         * @brief get the file name of the layout file
         **/
        function getLayoutFile() {
            return $this->layout_file;
        }

        /**
         * @brief set the directory path of the layout directory
         **/
        function setLayoutPath($path) {
            if(substr($path,0,1)!='/' && substr($path,0,2)!='./') $path = './'.$path;
            if(substr($path,-1)!='/') $path .= '/';
            $this->layout_path = $path;
        }

        /**
         * @brief set the directory path of the layout directory
         **/
        function getLayoutPath() {
            return $this->layout_path;
        }

        /**
         * @brief excute the member method specified by $act variable 
         *
         **/
        function proc() {
            // stop_proc==true이면 그냥 패스
            if($this->stop_proc) return false;

            // addon 실행(called_position 를 before_module_proc로 하여 호출)
            $called_position = 'before_module_proc';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone()?"mobile":"pc");
            @include($addon_file);

            if(isset($this->xml_info->action->{$this->act}) && method_exists($this, $this->act)) {

                // 권한 체크
                if(!$this->grant->access) return $this->stop("msg_not_permitted_act");

                // 모듈의 스킨 정보를 연동 (스킨 정보의 테이블 분리로 동작대상 모듈에만 스킨 정보를 싱크시키도록 변경)
                $oModuleModel = &getModel('module');
                $oModuleModel->syncSkinInfoToModuleInfo($this->module_info);
                Context::set('module_info', $this->module_info);

                // 실행
                $output = $this->{$this->act}();
            } 
			else {
				return false;
			}
			

            // addon 실행(called_position 를 after_module_proc로 하여 호출)
            $called_position = 'after_module_proc';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone()?"mobile":"pc");
            @include($addon_file);

            if(is_a($output, 'Object') || is_subclass_of($output, 'Object')) {
                $this->setError($output->getError());
                $this->setMessage($output->getMessage());
                return false;
            }

            // view action이고 결과 출력이 XMLRPC 또는 JSON일 경우 해당 모듈의 api method를 실행
            if($this->module_info->module_type == 'view'){
                if(Context::getResponseMethod() == 'XMLRPC' || Context::getResponseMethod() == 'JSON') {
                    $oAPI = getAPI($this->module_info->module, 'api');
                    if(method_exists($oAPI, $this->act)) {
                        $oAPI->{$this->act}($this);
                    }
                }
            }

            return true;
        }
    }
?>
