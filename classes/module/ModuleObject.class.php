<?php
    /**
    * @class ModuleObject
    * @author zero (zero@nzeo.com)
    * @brief module의 abstract class
    *
    * @todo 모듈에서 mid가 꼭 필요한지를 체크하는 단계가 필요 (admin은 mid불필요, board는 필요.., 미설정시 필요함으로.. 기본)
    *    
    **/

    class ModuleObject extends Object {

        var $mid = NULL; ///< module로 생성한 instance(관리상)의 값 
        var $module = NULL; ///< mid로 찾아서 생성한 모듈 class 이름
        var $module_srl = NULL; ///< 모듈 객체의 고유값인 module_srl 
        var $module_info = NULL; ///< 모듈의 정보 

        var $module_path = NULL; ///< 모듈 class file의 실행 위치

        var $act = NULL; ///< act 값

        var $template_path = NULL; ///< template 경로
        var $template_file = NULL; ///< template 파일

        var $layout_path = './common/tpl/'; ///< 레이아웃 경로
        var $layout_file = 'default_layout.html'; ///< 레이아웃 파일

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
         * @brief 모듈의 정보 세팅
         **/
        function setModuleInfo($module_info, $xml_info) {
            // 기본 변수 설정
            $this->mid = $module_info->mid;
            $this->module = $module_info->module;
            $this->module_srl = $module_info->module_srl;
            $this->module_info = $module_info;
            $this->xml_info = $xml_info;

            // 웹서비스에서 꼭 필요한 인증 정보와 권한 설정 체크
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');
            $user_id = $logged_info->user_id;
            $user_group = $logged_info->group_list;

            // 로그인되어 있다면 admin 체크
            if($is_logged && ($logged_info->is_admin == 'Y' || in_array($user_id, $this->module_info->admin_id) )) {
                $grant->is_admin = true;
            } else {
                $grant->is_admin = false;
            }

            // 권한 설정
            if($xml_info->grant) {
                foreach($xml_info->grant as $grant_name => $grant_item) {
                    $title = $grant_item->title;
                    $default = $grant_item->default;

                    $grant->{$grant_name} = false;

                    if($grant->is_admin) {
                        $grant->{$grant_name} = true;
                        continue;
                    }

                    if(count($user_group)) {
                        foreach($user_group as $group_srl) {
                            if(in_array($group_srl, $this->module_info->grants[$grant_name])) {
                                $grant->{$grant_name} = true;
                                break;
                            }
                        }
                    } else {
                        switch($default) {
                            case 'member' :
                                    if($is_logged) $grant->{$grant_name} = true;
                                break;
                            case 'root' :
                                    if($grant->is_admin) $grant->{$grant_name} = true;
                                break;
                            default :
                                    $grant->{$grant_name} = true;
                                break;
                        }
                    }
                }
            }

            // 권한변수 설정
            $this->grant = $grant;
            Context::set('grant', $grant);

            // 모듈의 init method 실행
            $this->init();
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
            if(substr($path,-1)!='/') $path .= '/';
            if(substr($path,0,2)!='./') $path = './'.$path;
            $this->template_path = $path;
        }

        /**
         * @brief template 경로 return
         **/
        function getTemplatePath() {
            return $this->template_path;
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
            if(substr($path,-1)!='/') $path .= '/';
            if(substr($path,0,2)!='./') $path = './'.$path;
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
        function proc($act) {

            // 기본 act조차 없으면 return
            if(!method_exists($this, $act)) return false;

            // act값으로 method 실행
            $output = call_user_method($act, $this);

            if(is_a($output, 'Object') || is_subclass_of($output, 'Object')) {
                $this->setError($output->getError());
                $this->setMessage($output->getMessage());
            }
        }
    }
?>
