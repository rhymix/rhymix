<?php
    /**
    * @class Module
    * @author zero (zero@nzeo.com)
    * @brief modules 의 abstract class
    **/

    class Module extends Output {

        var $module_path = NULL; ///< 현재 모듈의 실행 위치

        var $skin = 'default'; ///< skin 설정 (없을 경우도 있음)

        var $module_srl = NULL; ///< 모듈 객체의 고유값인 module_srl 
        var $module_info = NULL; ///< 현재 모듈 생성시 주어진 설정 정보들

        var $act = NULL; ///< act 값
        var $act_type = 'disp'; ///< act_type (disp, proc, lib, admin 4가지 존재, act에서 type을 찾음)

        var $layout_path = "./common/tpl/"; ///< 레이아웃 파일의 path
        var $layout_tpl = "default_layout"; ///< 레이아웃 파일

        /**
         * @brief 모듈의 정보 세팅
         **/
        function moduleInit($module_info) {
            // 브라우저 타이틀 지정
            Context::setBrowserTitle($module_info->browser_title?$module_info->browser_title:$module_info->mid);

            // 기본 변수 설정
            $this->module_info = $module_info;
            context::set('module_info', &$this->module_info);

            $this->module_srl = $module_info->module_srl;

            // skin 설정 (기본으로는 default)
            if($this->module_info->skin) $this->skin = $this->module_info->skin;
            else $this->skin = 'default';

            // 템플릿 위치 설정
            if(!$this->template_path) {
                $template_path = $this->module_path.'skins/'.$this->skin;
                $this->setTemplatePath($template_path);
            }

            $oMember = getModule('member');
            $user_id = $oMember->getUserID();
            $logged_info = $oMember->getLoggedInfo();
            $user_group = $logged_info->group_list;
            $user_group_count = count($user_group);

            // 로그인되어 있다면 admin 체크
            if($oMember->isLogged() && ($logged_info->is_admin == 'Y' || in_array($user_id, $this->module_info->admin_id) )) {
                $grant->is_admin = true;
            } else {
                $grant->is_admin = false;
            }

            // 권한 설정
            if($this->grant_list) {

                foreach($this->grant_list as $grant_name) {
                    $grant->{$grant_name} = false;

                    if($grant->is_admin || !$this->module_info->grant[$grant_name]) {
                        $grant->{$grant_name} = true;
                        continue;
                    }

                    if($user_group_count) {
                        foreach($user_group as $group_srl) {
                            if(in_array($group_srl, $this->module_info->grant[$grant_name])) {
                                $grant->{$grant_name} = true;
                                break;
                            }
                        }
                    }
                }
            }

            // 권한변수 설정
            Context::set('grant',$grant);
            $this->grant = $grant;

            // 모듈의 init method 실행
            $this->init();
        }

        /**
         * @brief 현재 모듈에 $act에 해당하는 method가 있는지 체크
         **/
        function isExistsAct($act) {
            return method_exists($this, $act);
        }

        /**
         * @brief 현재 acT_type의 return (disp/proc)
         **/
        function getActType() {
            return $this->act_type;
        }

        /**
         * @brief 현재 모듈의 path를 지정
         **/
        function setModulePath($path) {
            if(substr($path,-1)!='/') $path.='/';
            $this->module_path = $path;
        }

        /**
         * @brief 에러 유발. 에러시 message module을 바로 호출하고 현재 모듈은 exit
         **/
        function doError($msg_code) {
            $this->setError(-1);
            if(!Context::getLang($msg_code)) $this->setMessage($msg_code);
            else $this->setMessage(Context::getLang($msg_code));
            return false;
        }

        /**
         * @brief 레이아웃 경로를 지정
         **/
        function setLayoutPath($path) {
            $this->layout_path = $path;
        }

        /**
         * @brief 레이아웃 tpl 파일을 지정
         **/
        function setLayoutTpl($tpl) {
            $this->layout_tpl = $tpl;
        }

        /**
         * @brief 모듈의 action에 해당하는 method를 실행
         *
         * $act값에 의해서 $action_list에 선언된 것들을 실행한다
         **/
        function proc($act = null) {

            // 별도로 요청한 act가 없으면 주어진 act를 이용
            if($act) $this->act = $act;
            else $this->act = Context::get('act');

            // act의 종류가 disp/proc인지에 대한 확인
            if($this->act&&strtolower(substr($this->act,0,4)) != 'disp') $this->act_type = 'proc';

            // act값이 없거나 존재하지 않는 method를 호출시에 default_act를 지정
            if(!$this->act || !$this->isExistsAct($this->act)) $this->act = $this->default_act;

            // module의 *init 호출 (기본 init과 proc/disp init 2가지 있음)
            if($this->act_type == 'proc') {
                $output = $this->procInit();
                if((is_a($output, 'Output') || is_subclass_of($output, 'Output')) && !$output->toBool() ) {
                    $this->setError($output->getError());
                    $this->setMessage($output->getMessage());
                    return;
                } elseif(!$output) {
                    $this->setError(-1);
                    $this->setMessage('fail');
                    return;
                }
            } else $this->dispInit();

            // 기본 act조차 없으면 return
            if(!$this->isExistsAct($this->act)) return false;

            // act값으로 method 실행
            $output = call_user_method($this->act, $this);

            if(is_a($output, 'Output') || is_subclass_of($output, 'Output')) {
                $this->setError($output->getError());
                $this->setMessage($output->getMessage());
            }

            return true;
        }
    }
?>
