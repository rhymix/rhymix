<?php
    /**
     * @class  boardAdminController
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 admin controller class
     **/

    class boardAdminController extends board {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 권한 추가
         **/
        function procBoardAdminInsertGrant() {
            $module_srl = Context::get('module_srl');

            // 현 모듈의 권한 목록을 가져옴
            $grant_list = $this->xml_info->grant;

            if(count($grant_list)) {
                foreach($grant_list as $key => $val) {
                    $group_srls = Context::get($key);
                    if($group_srls) $arr_grant[$key] = explode('|@|',$group_srls);
                }
                $grants = serialize($arr_grant);
            }

            $oModuleController = &getController('module');
            $oModuleController->updateModuleGrant($module_srl, $grants);

            $this->add('module_srl',Context::get('module_srl'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 스킨 정보 업데이트
         **/
        function procBoardAdminUpdateSkinInfo() {
            // module_srl에 해당하는 정보들을 가져오기
            $module_srl = Context::get('module_srl');
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            $skin = $module_info->skin;

            // 스킨의 정보를 구해옴 (extra_vars를 체크하기 위해서)
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

            $oModuleController = &getController('module');
            $oModuleController->updateModuleSkinVars($module_srl, $skin_vars);

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }

        /**
         * @brief 게시판 추가
         **/
        function procBoardAdminInsertBoard($args = null) {
            // module 모듈의 model/controller 객체 생성
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // 만약 module_srl이 , 로 연결되어 있다면 일괄 정보 수정으로 처리
            if(strpos(Context::get('module_srl'),',')!==false) {
                // 대상 모듈들을 구해옴
                $modules = $oModuleModel->getModulesInfo(Context::get('module_srl'));
                $args = Context::getRequestVars();

                for($i=0;$i<count($modules);$i++) {
                    $obj = $extra_vars = null;

                    $obj = $modules[$i];
                    $extra_vars = unserialize($obj->extra_vars);

                    $obj->module_category_srl = $args->module_category_srl;
                    $obj->layout_srl = $args->layout_srl;
                    $obj->skin = $args->skin;
                    $obj->description = $args->description;
                    $obj->header_text = $args->header_text;
                    $obj->footer_text = $args->footer_text;
                    $obj->admin_id = $args->admin_id;

                    $extra_vars->use_category = $args->use_category=='Y'?'Y':'N';
                    $extra_vars->list_count = $args->list_count;
                    $extra_vars->search_list_count = $args->search_list_count;
                    $extra_vars->except_notice = $args->except_notice!='Y'?'N':'Y';
                    $extra_vars->page_count = $args->page_count;

                    $obj->extra_vars = serialize($extra_vars);

                    $output = $oModuleController->updateModule($obj);
                    if(!$output->toBool()) return $output;
                }

                return new Object(0,'success_updated');
            }

            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            if(!$args) {
                $args = Context::gets('module_srl','module_category_srl','board_name','layout_srl','skin','browser_title','description','is_default','header_text','footer_text','admin_id');
            }

            $args->module = 'board';
            $args->mid = $args->board_name;
            unset($args->board_name);
            if($args->is_default!='Y') $args->is_default = 'N';

            // 기본 값외의 것들을 정리
            $extra_var = delObjectVars(Context::getRequestVars(), $args);
            if($extra_var->use_category!='Y') $extra_var->use_category = 'N';
            if($extra_var->except_notice!='Y') $extra_var->except_notice = 'N';
            unset($extra_var->act);
            unset($extra_var->page);
            unset($extra_var->board_name);
            unset($extra_var->module_srl);

            // 확장변수(20개로 제한된 고정 변수) 체크
            $user_defined_extra_vars = array();
            foreach($extra_var as $key => $val) {
                if(substr($key,0,11)!='extra_vars_') continue;
                preg_match('/^extra_vars_([0-9]+)_(.*)$/i', $key, $matches);
                if(!$matches[1] || !$matches[2]) continue;

                $user_defined_extra_vars[$matches[1]]->{$matches[2]} = $val;
                unset($extra_var->{$key});
            }
            for($i=1;$i<=20;$i++) if(!$user_defined_extra_vars[$i]->name) unset($user_defined_extra_vars[$i]);
            $extra_var->extra_vars = $user_defined_extra_vars;

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

                // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // $extra_var를 serialize
            $args->extra_vars = serialize($extra_var);

            // is_default=='Y' 이면
            if($args->is_default=='Y') $oModuleController->clearDefaultModule();

            // module_srl의 값에 따라 insert/update
            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';

                // 파일업로드, 댓글 파일업로드, 관리에 대한 권한 지정
                if($output->toBool()) {
                    $oMemberModel = &getModel('member');
                    $admin_group = $oMemberModel->getAdminGroup();
                    $admin_group_srl = $admin_group->group_srl;

                    $module_srl = $output->get('module_srl');
                    $grants = serialize(array('manager'=>array($admin_group_srl)));

                    $oModuleController->updateModuleGrant($module_srl, $grants);
                }
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 게시판 삭제
         **/
        function procBoardAdminDeleteBoard() {
            $module_srl = Context::get('module_srl');

            // 원본을 구해온다
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->add('module','board');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 카테고리 추가
         **/
        function procBoardAdminInsertCategory() {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $module_srl = Context::get('module_srl');
            $category_title = Context::get('category_title');

            // module_srl이 있으면 원본을 구해온다
            $oDocumentController = &getController('document');
            $args->module_srl = $module_srl;
            $args->title = $category_title;
            $output = $oDocumentController->insertCategory($args);
            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$module_srl);
            $this->setMessage('success_registed');
        }

        /**
         * @brief 카테고리의 내용 수정
         **/
        function procBoardAdminUpdateCategory() {
            $module_srl = Context::get('module_srl');
            $category_srl = Context::get('category_srl');
            $mode = Context::get('mode');
            $title = Context::get('category_title');

            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');

            switch($mode) {
                case 'up' :
                        $output = $oDocumentController->moveCategoryUp($category_srl);
                        $msg_code = 'success_moved';
                    break;
                case 'down' :
                        $output = $oDocumentController->moveCategoryDown($category_srl);
                        $msg_code = 'success_moved';
                    break;
                case 'delete' :
                        $output = $oDocumentController->deleteCategory($category_srl);
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                        $selected_category = $oDocumentModel->getCategory($category_srl);
                        $selected_category->title = $title;
                        $output = $oDocumentController->updateCategory($selected_category);
                        $msg_code = 'success_updated';
                    break;
            }
            if(!$output->toBool()) return $output;

            $this->add('module_srl', $module_srl);
            $this->setMessage($msg_code);
        }
    }
?>
