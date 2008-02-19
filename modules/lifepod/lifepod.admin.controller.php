<?php
    /**
     * @class  lifepodAdminController
     * @author haneul (haneul0318@nzeo.com)
     * @brief  lifepod 모듈의 admin controller class
     * 관리자 기능을 담당하게 된다.
     * 보통 모듈의 관리자 기능은 해당 모듈의 생성이나 정보/권한/스킨정보의 수정등을 담당하게 된다.
     **/

    class lifepodAdminController extends lifepod {

        /**
         * @brief 초기화
         **/
        function init() { }

        /**
         * @brief lifepod 추가
         * lifepod_name은 mid의 값이 되고 나머지 모듈 공통 값을 받아서 저장을 하게 된다.
         **/
        function procLifepodAdminInsertLifepod($args = null) {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            if(!$args) $args = Context::gets('module_srl','module_category_srl','lifepod_name','layout_srl','skin','browser_title','description','is_default','header_text','footer_text','admin_id','open_rss');

            // lifepod모듈임을 명시적으로 지정한다.
            $args->module = 'lifepod';

            // mid값을 직접 받지 않고 lifepod_name으로 받는 이유는 mid는 특별히 약속된 변수명이라 오동작이 발생할 수 있어서 다른 이름으로 전달을 받은후 다시 바꾸어준다.
            $args->mid = $args->lifepod_name;
            unset($args->lifepod_name);

            // is_default일 경우 별다른 요청이 없는 index페이지의 경우 바로 호출이 되는데 이 값을 설정을 하게 된다.
            if($args->is_default!='Y') $args->is_default = 'N';

            // 기본 값외의 것들을 정리
            $extra_var = delObjectVars(Context::getRequestVars(), $args);
            unset($extra_var->act);
            unset($extra_var->page);
            unset($extra_var->lifepod_name);

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

                // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // $extra_var를 serialize
            $args->extra_vars = serialize($extra_var);

            // module 모듈의 controller 객체 생성
            $oModuleController = &getController('module');

            // is_default=='Y' 이면
            if($args->is_default=='Y') $oModuleController->clearDefaultModule();

            /**
             * module_srl값이 없다면 신규 등록으로 처리를 한다.
             **/
            if(!$args->module_srl) {
                // module controller를 이용하여 모듈을 생성한다.
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';

                // 권한의 경우 기본으로 설정을 해주는 것이 좋으며 lifepod 모듈의 경우 manager권한을 관리 그룹으로 설정을 한다.
                if($output->toBool()) {
                    // 관리그룹을 member model객체에서 구할 수 있다.
                    $oMemberModel = &getModel('member');
                    $admin_group = $oMemberModel->getAdminGroup();
                    $admin_group_srl = $admin_group->group_srl;

                    $module_srl = $output->get('module_srl');
                    $grants = serialize(array('manager'=>array($admin_group_srl)));

                    // module controller의 module 권한 설정 method를 이용하여 기본 권한을 적용한다.
                    $oModuleController->updateModuleGrant($module_srl, $grants);
                }
            /**
             * module_srl이 있다면 모듈의 정보를 수정한다
             **/
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            // 결과값에 오류가 있을 경우 그대로 객체 리턴.
            if(!$output->toBool()) return $output;

            // 등록후 페이지 이동을 위해 변수 설정 및 메세지를 설정한다.
            $this->add('page',Context::get('page'));
            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief lifepod 삭제
         **/
        function procLifepodAdminDeleteLifepod() {
            // 삭제할 대상 lifepod의 module_srl을 구한다.
            $module_srl = Context::get('module_srl');

            // 원본을 구해온다
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);

            // 삭제 처리시 오류가 발생하면 결과 객체를 바로 리턴한다.
            if(!$output->toBool()) return $output;

            // 등록후 페이지 이동을 위해 변수 설정 및 메세지를 설정한다.
            $this->add('module','lifepod');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }
 
        /**
         * @brief 권한 설정
         * 생성된 lifepod에 ./conf/module.xml에 정의된 권한과 관리자가 선택한 그룹의 값을 연동하여 권한을 설정하게 된다.
         **/
        function procLifepodAdminInsertGrant() {
            // 대상 lifepod(모듈)의 고유값인 module_srl을 체크한다.
            $module_srl = Context::get('module_srl');

            /**
             * 현 모듈의 권한 목록을 가져옴
             * xml_info 는 lifepod모듈이 요청되었다고 판단될때 ModuleObject에서 이미 세팅해 놓은 상태이다.
             **/
            $grant_list = $this->xml_info->grant;

            /**
             * 권한의 목록을 loop로 돌면서 권한 설정을 한다.
             * zbxe의 경우 가능한 간단한 xmlrpc사용을 위해서 배열의 경우 |@|를 pipe로 하여 하나의 string으로 전달한다.
             * 요청받은 권한의 대상 그룹과 권한을 배열로 한 후 serialize하여 modules테이블에 module_srl을 키로 한 rows에 데이터를 적용한다.
             **/
            if(count($grant_list)) {
                foreach($grant_list as $key => $val) {
                    $group_srls = Context::get($key);
                    if($group_srls) $arr_grant[$key] = explode('|@|',$group_srls);
                }
                $grants = serialize($arr_grant);
            }

            // 권한 설정은 모듈 공통이라 module 모듈의 controller을 생성하여 저장하도록 한다.
            $oModuleController = &getController('module');
            $oModuleController->updateModuleGrant($module_srl, $grants);

            // 권한 설정후 돌아갈 페이지를 위하여 module_srl값을 세팅하고 성공 메세지 역시 세팅한다.
            $this->add('module_srl',Context::get('module_srl'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 스킨 정보 업데이트
         * 스킨 정보는 skin.xml파일의 extra_vars와 입력된 변수값을 조합하여 serialize하여 modules 테이블에 module_srl을 키로 하여 저장을 하게 된다.
         **/
        function procLifepodAdminUpdateSkinInfo() {
            // module_srl에 해당하는 정보들을 가져오기
            $module_srl = Context::get('module_srl');

            // 어떤 스킨이 사용중인지 확인하기 위해서 module_srl을 이용하여 모듈의 정보를 구하고 스킨을 구한다.
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            $skin = $module_info->skin;

            // 스킨의 정보르 구해옴 (extra_vars를 체크하기 위해서)
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            // 입력받은 변수들을 체크 (mo, act, module_srl, page등 기본적인 변수들 없앰)
            $obj = Context::getRequestVars();
            unset($obj->act);
            unset($obj->module_srl);
            unset($obj->page);

            // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
            if($skin_info->extra_vars) {
                foreach($skin_info->extra_vars as $vars) {
                    if($vars->type!='image') continue;

                    $image_obj = $obj->{$vars->name};

                    // 삭제 요청에 대한 변수를 구함
                    $del_var = $obj->{"del_".$vars->name};
                    unset($obj->{"del_".$vars->name});
                    if($del_var == 'Y') {
                        @unlink($module_info->{$vars->name});
                        continue;
                    }

                    // 업로드 되지 않았다면 이전 데이터를 그대로 사용
                    if(!$image_obj['tmp_name']) {
                        $obj->{$vars->name} = $module_info->{$vars->name};
                        continue;
                    }

                    // 정상적으로 업로드된 파일이 아니면 무시
                    if(!is_uploaded_file($image_obj['tmp_name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 이미지 파일이 아니어도 무시
                    if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 경로를 정해서 업로드
                    $path = sprintf("./files/attach/images/%s/", $module_srl);

                    // 디렉토리 생성
                    if(!FileHandler::makeDir($path)) return false;

                    $filename = $path.$image_obj['name'];

                    // 파일 이동
                    if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 변수를 바꿈
                    unset($obj->{$vars->name});
                    $obj->{$vars->name} = $filename;
                }
            }

            // serialize하여 저장
            $skin_vars = serialize($obj);

            // module controller객체를 생성하여 module_srl을 키로 한 rows에 serialize한 스킨 정보를 적용한다.
            $oModuleController = &getController('module');
            $oModuleController->updateModuleSkinVars($module_srl, $skin_vars);

            /** 
             * 스킨 정보는 첨부파일때문에 xml로 전달이 되지 않고 POST로 전송이 되어 왔으므로 템플릿을 이용하여 프레임을 refresh시키도록 한다.
             * 스킨 정보를 수정할때 숨어 있는 iframe을 target으로 삼기에 기본 레이아웃을 이용하면 되므로 직접 레이아웃 경로와 파일을 기본으로 지정한다.
             **/
            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }
    }
?>
