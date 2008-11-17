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
            $user_id = $logged_info->user_id;
            $user_group = $logged_info->group_list;
            $grant->is_admin = false;

            $oModuleModel = &getModel('module');

            // 로그인되어 있다면 관리자 여부를 확인
            if($is_logged) {
                /* 로그인 사용자에 대한 관리자 여부는 다양한 방법으로 체크가 됨 */
                // 1. 최고관리자일 경우
                if($logged_info->is_admin == 'Y') {
                    $grant->is_admin = true;

                // 2. 사이트 관리자일 경우 사이트 관리 권한을 줌
                } elseif($oModuleModel->isSiteAdmin()) {
                    $grant->is_admin = true;


                // 3. 최고 관리자는 아니지만 모듈 object가 있고 admin_id 컬럼에 로그인 사용자의 아이디가 있을 경우
                } elseif($this->module_info->admin_id) {
                    if(is_array($this->module_info->admin_id) && in_array($user_id, $this->module_info->admin_id)) $grant->is_admin = true;

                // 4. 1/2/3번이 아닐 경우 그룹을 체크하고 직접 모듈에 요청을 하여 체크를 함. (모듈.class.php에 정의)
                } else {
                    $manager_group = $this->module_info->grants['manager'];
                    if(count($user_group) && count($manager_group)) {
                        foreach($user_group as $group_srl => $group_info) {
                            if(in_array($group_srl, $manager_group)) $grant->is_admin = true;
                        }
                    }

                    if(!$grant->is_admin && $module_info->module) {
                        $oClass = &getClass($module_info->module);
                        if($oClass && method_exists($oClass, 'isAdmin')) $grant->is_admin = $oClass->isAdmin();
                    }
                }
            }

            // 권한 설정
            if($xml_info->grant) {

                // 이 모듈에 action.xml에서 선언된 권한 목록을 루프
                foreach($xml_info->grant as $grant_name => $grant_item) {

                    // 제목과 기타 설정 없을 경우의 기본 권한(guest, member, root)에 대한 변수 설정
                    $title = $grant_item->title;
                    $default = $grant_item->default;

                    // 최고 관리자이면 모든 권한에 대해 true 설정
                    if($grant->is_admin) {
                        $grant->{$grant_name} = true;
                        continue;
                    }

                    // 일단 현재 권한에 대해 false 지정
                    $grant->{$grant_name} = false;

                    // 모듈의 개별 설정에서 이 권한에 대한 그룹 지정이 있으면 체크
                    if(count($this->module_info->grants[$grant_name])) {
                        $group_srls = $this->module_info->grants[$grant_name];
                        if(!is_array($group_srls)) $group_srls = array($group_srls);

                        if(count($user_group)) {
                            foreach($user_group as $group_srl => $group_title) {
                                if(in_array($group_srl, $group_srls)) {
                                    $grant->{$grant_name} = true;
                                    break;
                                }
                            }
                        } 

                    // 별도의 지정이 없으면 default값으로 권한 체크
                    } else {
                        switch($default) {
                            case 'member' :
                                    if($is_logged) $grant->{$grant_name} = true;
                                break;
                            case 'root' :
                                    if($logged_info->is_admin == 'Y') $grant->{$grant_name} = true;
                                break;
                            default :
                                    $grant->{$grant_name} = true;
                                break;
                        }

                    }
                }
            }

            // 현재 action값에 따른 최고 관리 권한 부여
            if($this->act && $xml_info->permission) {
                $permission_target = $xml_info->permission->{$this->act};
                if($permission_target && $grant->{$permission_target}) {
                    foreach($grant as $key => $val) $grant->{$key} = true;
                }
            }

            // act값에 admin이 들어 있는데 관리자가 아닌 경우 해당 모듈의 관리자 체크 
            if(substr_count($this->act, 'Admin')) {
                if(!$is_logged) $this->setAct("dispMemberLoginForm");
                else if($logged_info->is_admin != 'Y' && (!method_exists($this, 'checkAdminActionGrant') || !$this->checkAdminActionGrant())) {
                    $this->stop('msg_not_permitted_act');
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
            if($this->stop_proc==true) return false;

            // addon 실행(called_position 를 before_module_proc로 하여 호출)
            $called_position = 'before_module_proc';
            @include(_XE_PATH_."files/cache/activated_addons.cache.php");

            // 지금까지 이상이 없었다면 action 실행
            if(!$this->stop_proc) {
                // 현재 모듈에 act값이 있으면 해당 act를 실행
                if(method_exists($this, $this->act)) {
                    $output = $this->{$this->act}();

                // act가 없으면 action_forward에서 해당하는 act가 있는지 찾아서 대신 실행
                } else if(Context::isInstalled()) {

                    $oModuleModel = &getModel('module');
                    $forward = $oModuleModel->getActionForward($this->act);
                    if($forward->module && $forward->type && $forward->act) {

                        $kind = strpos(strtolower($forward->act),'admin')!==false?'admin':'';
                        $oModule = &getModule($forward->module, $forward->type, $kind);
                        $xml_info = $oModuleModel->getModuleActionXml($forward->module);
                        $oModule->setAct($forward->act);
                        $oModule->init();
                        $oModule->setModuleInfo($this->module_info, $xml_info);

                        $output = $oModule->{$forward->act}();

                        $this->setTemplatePath($oModule->getTemplatePath());
                        $this->setTemplateFile($oModule->getTemplateFile());

                    } else {
                        if($this->xml_info->default_index_act) {
                            if(method_exists($this, $this->xml_info->default_index_act)) {
                                $output = $this->{$this->xml_info->default_index_act}();
                            }
                        } else {
                            return false;
                        }
                    }
                    
                } else {
                    return false;
                }
            }

            // addon 실행(called_position 를 after_module_proc로 하여 호출)
            $called_position = 'after_module_proc';
            @include(_XE_PATH_."files/cache/activated_addons.cache.php");

            if(is_a($output, 'Object') || is_subclass_of($output, 'Object')) {
                $this->setError($output->getError());
                $this->setMessage($output->getMessage());
                return false;
            }

            // view action이고 결과 출력이 XMLRPC일 경우 해당 모듈의 api method를 실행
            if((Context::getResponseMethod() == 'XMLRPC' || Context::getResponseMethod() == 'JSON') && $this->module_info->module_type == 'view') {
                $oAPI = getAPI($this->module_info->module, 'api');
                if(method_exists($oAPI, $this->act)) {
                    $oAPI->{$this->act}($this);
                }
            }

            return true;
        }
    }
?>
