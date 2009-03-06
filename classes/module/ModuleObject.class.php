<?php
    /**
    * @class ModuleObject
    * @author zero (zero@nzeo.com)
    * @brief module의 상위 클래스
    **/

    class ModuleObject extends Object {

        var $mid = NULL; ///< module로 생성한 instance(관리상)의 값 
        var $module = NULL; ///< mid로 찾아서 생성한 모듈 class 이름
        var $module_srl = NULL; ///< 모듈 객체의 고유값인 module_srl 
        var $module_info = NULL; ///< 모듈의 설정 정보 
        var $xml_info = NULL; ///< 모듈 자체 정보

        var $module_path = NULL; ///< 모듈 class file의 실행 위치

        var $act = NULL; ///< act 값

        var $template_path = NULL; ///< template 경로
        var $template_file = NULL; ///< template 파일

        var $layout_path = ''; ///< 레이아웃 경로
        var $layout_file = ''; ///< 레이아웃 파일
        var $edited_layout_file = ''; ///< 관리자 모드에서 수정된 레이아웃 파일

        var $stop_proc = false; ///< action 수행중 stop()를 호출하면 ModuleObject::proc()를 수행하지 않음

        /**
         * @brief 현재 모듈의 이름을 지정
         **/
        function setModule($module) {
            $this->module = $module;
        }

        /**
         * @brief 현재 모듈의 path를 지정
         **/
        function setModulePath($path) {
            if(substr($path,-1)!='/') $path.='/';
            $this->module_path = $path;
        }

        /**
         * @brief redirect_url을 정함 
         *
         * redirect_url의 경우 ajax로 request를 받았을 경우에 사용하면 됨...
         **/
        function setRedirectUrl($url='./') {
            $this->add('redirect_url', $url);
        }

        /**
         * @brief 현재 페이지를 refresh시킴
         *
         * 공통 tpl중 refresh.html을 실행할 뿐..
         **/
        function setRefreshPage() {
            $this->setTemplatePath('./common/tpl');
            $this->setTemplateFile('refresh');
        }


        /**
         * @brief act값 지정
         **/
        function setAct($act) {
            $this->act = $act;
        }

        /**
         * @brief 모듈의 정보 세팅
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

            // 사이트 관리자이면 로그인 정보의 is_admin 에 'Y'로 세팅
            //if($oModuleModel->isSiteAdmin($logged_info)) $logged_info->is_admin = 'Y';

            // XE에서 access, manager (== is_admin) 는 고정된 권한명이며 이와 관련된 권한 설정
            $grant = $oModuleModel->getGrant($module_info, $logged_info, $xml_info);

            // 현재 모듈의 access 권한이 없으면 권한 없음 표시
            //if(!$grant->access) return $this->stop("msg_not_permitted");

            // 관리 권한이 없으면 permision, action 확인
            if(!$grant->manager) {
                // 현재 요청된 action의 퍼미션 type(guest, member, manager, root)를 구함
                $permission_target = $xml_info->permission->{$this->act};

                // module.xml에 명시된 퍼미션이 없을때 ation명에 Admin이 있으면 manager로 체크
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
         * @brief 메세지 출력
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
         * @brief template 파일 지정
         **/
        function setTemplateFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->template_file = $filename;
        }

        /**
         * @brief template 파일 return
         **/
        function getTemplateFile() {
            return $this->template_file;
        }

        /**
         * @brief template 경로 지정
         **/
        function setTemplatePath($path) {
            if(substr($path,0,1)!='/' && substr($path,0,2)!='./') $path = './'.$path;
            if(substr($path,-1)!='/') $path .= '/';
            $this->template_path = $path;
        }

        /**
         * @brief template 경로 return
         **/
        function getTemplatePath() {
            return $this->template_path;
        }

        /**
         * @brief edited layout 파일 지정
         **/
        function setEditedLayoutFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->edited_layout_file = $filename;
        }

        /**
         * @brief layout 파일 return
         **/
        function getEditedLayoutFile() {
            return $this->edited_layout_file;
        }

        /**
         * @brief layout 파일 지정
         **/
        function setLayoutFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->layout_file = $filename;
        }

        /**
         * @brief layout 파일 return
         **/
        function getLayoutFile() {
            return $this->layout_file;
        }

        /**
         * @brief layout 경로 지정
         **/
        function setLayoutPath($path) {
            if(substr($path,0,1)!='/' && substr($path,0,2)!='./') $path = './'.$path;
            if(substr($path,-1)!='/') $path .= '/';
            $this->layout_path = $path;
        }

        /**
         * @brief layout 경로 return
         **/
        function getLayoutPath() {
            return $this->layout_path;
        }

        /**
         * @brief 모듈의 action에 해당하는 method를 실행
         *
         * $act값에 의해서 $action_list에 선언된 것들을 실행한다
         **/
        function proc() {
            // stop_proc==true이면 그냥 패스
            if($this->stop_proc) return false;

            // addon 실행(called_position 를 before_module_proc로 하여 호출)
            $called_position = 'before_module_proc';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath();
            if(file_exists($addon_file)) @include($addon_file);

            // action 실행
            if(method_exists($this, $this->act)) {

                // 권한 체크
                if(!$this->grant->access) return $this->stop("msg_not_permitted_act");

                // 모듈의 스킨 정보를 연동 (스킨 정보의 테이블 분리로 동작대상 모듈에만 스킨 정보를 싱크시키도록 변경)
                $oModuleModel = &getModel('module');
                $oModuleModel->syncSkinInfoToModuleInfo($this->module_info);
                Context::set('module_info', $this->module_info);

                // 실행
                $output = $this->{$this->act}();

            // act이 없으면 action_forward에서 해당하는 act가 있는지 찾아서 대신 실행
            } else if(Context::isInstalled()) {
                $oModuleModel = &getModel('module');

                $forward = null;

                // 현재 요청된 action의 대상 모듈을 찾음
                // 1. action이름으로 검색 (DB검색 없이 하기 위함)
                if(preg_match('/^([a-z]+)([A-Z])([a-z0-9\_]+)(.*)$/', $this->act, $matches)) {
                    $module = strtolower($matches[2].$matches[3]);
                    $xml_info = $oModuleModel->getModuleActionXml($module);
                    if($xml_info->action->{$this->act}) {
                        $forward->module = $module;
                        $forward->type = $xml_info->action->{$this->act}->type;
                        $forward->act = $this->act;
                    }
                }

                // 2. 1번에서 찾지 못하면 action forward를 검색
                if(!$forward) $forward = $oModuleModel->getActionForward($this->act);

                // 찾아진 forward 모듈이 있으면 실행
                if($forward->module && $forward->type && $forward->act) {

                    $kind = strpos(strtolower($forward->act),'admin')!==false?'admin':'';

                    $oModule = &getModule($forward->module, $forward->type, $kind);
                    $xml_info = $oModuleModel->getModuleActionXml($forward->module);

                    $oModule->setAct($forward->act);
                    $oModule->init();
                    if($oModule->stop_proc) return $this->stop($oModule->getMessage());

                    $oModule->setModuleInfo($this->module_info, $xml_info);

                    if(method_exists($oModule, $forward->act)) {
                        $output = $oModule->{$forward->act}();
                    } else {
                        return $this->stop("msg_module_is_not_exists");
                    }

                    // forward 모듈의 실행 결과 검사
                    if($oModule->stop_proc) return $this->stop($oModule->getMessage());

                    $this->setTemplatePath($oModule->getTemplatePath());
                    $this->setTemplateFile($oModule->getTemplateFile());

                // forward 모듈을 찾지 못했다면 원 모듈의 default index action을 실행
                } else if($this->xml_info->default_index_act && method_exists($this, $this->xml_info->default_index_act)) {
                    $output = $this->{$this->xml_info->default_index_act}();
                } else {
                    return false;
                }
            } else {
                return false;
            }

            // addon 실행(called_position 를 after_module_proc로 하여 호출)
            $called_position = 'after_module_proc';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath();
            if(file_exists($addon_file)) @include($addon_file);

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
