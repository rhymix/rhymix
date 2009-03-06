<?php
    /**
     * @class  moduleController
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 controller class
     **/

    class moduleController extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief action forward 추가
         * action foward는 등록된 action이 요청된 모듈에 없을 경우 찾아서 포워딩을 하는 구조이다
         * 모듈의 설치시에 사용된다.
         **/
        function insertActionForward($module, $type, $act) {
            $args->module = $module;
            $args->type = $type;
            $args->act = $act;

            $output = executeQuery('module.insertActionFoward', $args);
            return $output;
        }

        /**
         * @brief action forward 삭제
         **/
        function deleteActionForward($module, $type, $act) {
            $args->module = $module;
            $args->type = $type;
            $args->act = $act;

            $output = executeQuery('module.deleteActionFoward', $args);
            return $output;
        }

        /**
         * @brief module trigger 추가
         * module trigger는 trigger 대상이 등록된 대상을 호출하는 방법이다.
         *
         **/
        function insertTrigger($trigger_name, $module, $type, $called_method, $called_position) {
            $args->trigger_name = $trigger_name;
            $args->module = $module;
            $args->type = $type;
            $args->called_method = $called_method;
            $args->called_position = $called_position;

            $output = executeQuery('module.insertTrigger', $args);

            // 트리거 정보가 있는 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/triggers");

            return $output;
        }

        /**
         * @brief module trigger 삭제
         *
         **/
        function deleteTrigger($trigger_name, $module, $type, $called_method, $called_position) {
            $args->trigger_name = $trigger_name;
            $args->module = $module;
            $args->type = $type;
            $args->called_method = $called_method;
            $args->called_position = $called_position;

            $output = executeQuery('module.deleteTrigger', $args);

            // 트리거 캐시 삭제
            FileHandler::removeFilesInDir('./files/cache/triggers');

            return $output;
        }

        /**
         * @brief 특정 모듈의 설정 입력
         * board, member등 특정 모듈의 global config 관리용
         **/
        function insertModuleConfig($module, $config) {
            $args->module = $module;
            $args->config = serialize($config);

            $output = executeQuery('module.deleteModuleConfig', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('module.insertModuleConfig', $args);
            return $output;
        }

        /**
         * @brief 특정 mid의 모듈 설정 정보 저장
         * mid의 모듈 의존적인 설정을 관리
         **/
        function insertModulePartConfig($module, $module_srl, $config) {
            $args->module = $module;
            $args->module_srl = $module_srl;
            $args->config = serialize($config);

            $output = executeQuery('module.deleteModulePartConfig', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('module.insertModulePartConfig', $args);
            return $output;
        }

        /**
         * @brief virtual site 생성
         **/
        function insertSite($domain, $index_module_srl) {
            $args->site_srl = getNextSequence();
            $args->domain = preg_replace('/\/$/','',$domain);
            $args->index_module_srl = $index_module_srl;
            $output = executeQuery('module.insertSite', $args);
            if(!$output->toBool()) return null;

            return $args->site_srl;
        }

        /**
         * @brief virtual site 수정
         **/
        function updateSite($args) {
            return executeQuery('module.updateSite', $args);
        }

        /**
         * @brief 모듈 정보 정리
         **/
        function arrangeModuleInfo(&$args, &$extra_vars) {
            // 불필요한 내용 제거
            unset($args->body);
            unset($args->act);
            unset($args->page);

            // mid값 검사
            if(!ereg("^[a-zA-Z][a-zA-Z0-9_]+", $args->mid)) return new Object(-1, 'msg_limit_mid');

            // 변수를 검사 (modules의 기본 변수와 그렇지 않은 변수로 분리)
            $extra_vars = clone($args);
            unset($extra_vars->module_srl);
            unset($extra_vars->module);
            unset($extra_vars->module_category_srl);
            unset($extra_vars->layout_srl);
            unset($extra_vars->menu_srl);
            unset($extra_vars->site_srl);
            unset($extra_vars->mid);
            unset($extra_vars->skin);
            unset($extra_vars->browser_title);
            unset($extra_vars->description);
            unset($extra_vars->is_default);
            unset($extra_vars->content);
            unset($extra_vars->open_rss);
            unset($extra_vars->header_text);
            unset($extra_vars->footer_text);
            $args = delObjectVars($args, $extra_vars);

            return new Object();
        }

        /**
         * @brief 모듈 입력
         **/
        function insertModule($args) {
            $output = $this->arrangeModuleInfo($args, $extra_vars);
            if(!$output->toBool()) return $output;

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            // 이미 존재하는 모듈 이름인지 체크
            if(!$args->site_srl) $args->site_srl = 0;
            $output = executeQuery('module.isExistsModuleName', $args);
            if(!$output->toBool() || $output->data->count) {
                $oDB->rollback();
                return new Object(-1, 'msg_module_name_exists');
            }

            // is_default 의 값에 따라서 처리
            if($args->site_srl!=0) $args->is_default = 'N';
            else {
                if($args->is_default!='Y') $args->is_default = 'N';
                else $this->clearDefaultModule();
            }

            // 선택된 스킨정보에서 colorset을 구함
            $oModuleModel = &getModel('module');
            $module_path = ModuleHandler::getModulePath($args->module);
            $skin_info = $oModuleModel->loadSkinInfo($module_path, $args->skin);
            $skin_vars->colorset = $skin_info->colorset[0]->name;

            // 변수 정리후 query 실행
            if(!$args->module_srl) $args->module_srl = getNextSequence();

            // 모듈 등록
            $output = executeQuery('module.insertModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 모듈 추가 변수 등록
            $this->insertModuleExtraVars($args->module_srl, $extra_vars);

            // commit
            $oDB->commit();

            $output->add('module_srl',$args->module_srl);
            return $output;
        }

        /**
         * @brief 모듈의 정보를 수정
         **/
        function updateModule($args) {
            $output = $this->arrangeModuleInfo($args, $extra_vars);
            if(!$output->toBool()) return $output;

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

            $args->site_srl = (int)$module_info->site_srl;
            if(!$args->browser_title) $args->browser_title = $module_info->browser_title;

            $output = executeQuery('module.isExistsModuleName', $args);
            if(!$output->toBool() || $output->data->count) {
                $oDB->rollback();
                return new Object(-1, 'msg_module_name_exists');
            }

            // is_default 의 값에 따라서 처리
            if($args->site_srl!=0) $args->is_default = 'N';
            else {
                if($args->is_default!='Y') $args->is_default = 'N';
                else $this->clearDefaultModule();
            }

            $output = executeQuery('module.updateModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 모듈 추가 변수 등록
            $this->insertModuleExtraVars($args->module_srl, $extra_vars);

            $oDB->commit();

            $output->add('module_srl',$args->module_srl);
            return $output;
        }

        /**
         * @brief 모듈을 삭제
         *
         * 모듈 삭제시는 관련 정보들을 모두 삭제 시도한다.
         **/
        function deleteModule($module_srl) {

            // trigger 호출 (before)
            $trigger_obj->module_srl = $module_srl;
            $output = ModuleHandler::triggerCall('module.deleteModule', 'before', $trigger_obj);
            if(!$output->toBool()) return $output;

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            $args->module_srl = $module_srl;

            // module 정보를 DB에서 삭제
            $output = executeQuery('module.deleteModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 권한 정보 삭제
            $this->deleteModuleGrants($module_srl);

            // 스킨 정보 삭제
            $this->deleteModuleSkinVars($module_srl);

            // 모듈 추가 변수 삭제
            $this->deleteModuleExtraVars($module_srl);

            // 모듈 관리자 제거
            $this->deleteAdminId($module_srl);

            // trigger 호출 (after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('module.deleteModule', 'after', $trigger_obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

            return $output;
        }

        /**
         * @brief 모듈의 기타 정보를 변경
         **/
        function updateModuleSkinVars($module_srl, $skin_vars) {
            // skin_vars 정보 세팅
            $args->module_srl = $module_srl;
            $args->skin_vars = $skin_vars;
            $output = executeQuery('module.updateModuleSkinVars', $args);
            if(!$output->toBool()) return $output;

            return $output;
        }

        /**
         * @brief 모든 모듈의 is_default값을 N 으로 세팅 (기본 모듈 해제)
         **/
        function clearDefaultModule() {
            $output = executeQuery('module.clearDefaultModule');
            if(!$output->toBool()) return $output;

            return $output;
        }

        /**
         * @brief 지정된 menu_srl에 속한 mid 의 menu_srl 을 변경
         **/
        function updateModuleMenu($args) {
            return executeQuery('module.updateModuleMenu', $args);
        }

        /**
         * @brief 지정된 menu_srl에 속한 mid 의 layout_srl을 변경
         **/
        function updateModuleLayout($layout_srl, $menu_srl_list) {
            if(!count($menu_srl_list)) return;

            $args->layout_srl = $layout_srl;
            $args->menu_srls = implode(',',$menu_srl_list);
            $output = executeQuery('module.updateModuleLayout', $args);
            return $output;
        }

        /**
         * @brief 사이트의 관리를 변경
         **/
        function insertSiteAdmin($site_srl, $arr_admins) {
            // 사이트 관리자 제거
            $args->site_srl = $site_srl;
            $output = executeQuery('module.deleteSiteAdmin', $args);
            if(!$output->toBool()) return $output;

            // 관리자 대상 멤버 번호를 구함
            if(!is_array($arr_admins) || !count($arr_admins)) return new Object();
            foreach($arr_admins as $key => $user_id) {
                if(!trim($user_id)) continue;
                $admins[] = trim($user_id);
            }
            if(!count($admins)) return new Object();

            $args->user_ids = '\''.implode('\',\'',$admins).'\'';
            $output = executeQueryArray('module.getAdminSrls', $args);
            if(!$output->toBool()||!$output->data) return $output;

            foreach($output->data as $key => $val) {
                unset($args);
                $args->site_srl = $site_srl;
                $args->member_srl = $val->member_srl;
                $output = executeQueryArray('module.insertSiteAdmin', $args);
                if(!$output->toBool()) return $output;
            }
            return new Object();
        }

        /**
         * @brief 특정 모듈에 관리자 아이디 지정
         **/
        function insertAdminId($module_srl, $admin_id) {
            $oMemberModel = &getModel('member');
            $member_info = $oMemberModel->getMemberInfoByUserID($admin_id);
            if(!$member_info->member_srl) return;
            $args->module_srl = $module_srl;
            $args->member_srl = $member_info->member_srl;
            return executeQuery('module.insertAdminId', $args);
        }

        /**
         * @brief 특정 모듈의 관리자 아이디 제거
         **/
        function deleteAdminId($module_srl, $admin_id = '') {
            $args->module_srl = $module_srl;

            if($admin_id) {
                $oMemberModel = &getModel('member');
                $member_info = $oMemberModel->getMemberInfoByUserID($admin_id);
                if($member_info->member_srl) $args->member_srl = $member_info->member_srl;
            }
            return executeQuery('module.deleteAdminId', $args);
        }

        /**
         * @brief 특정 모듈에 스킨 변수 등록
         **/
        function insertModuleSkinVars($module_srl, $obj) {
            $this->deleteModuleSkinVars($module_srl);
            if(!$obj || !count($obj)) return;

            $args->module_srl = $module_srl;
            foreach($obj as $key => $val) {
                $args->name = trim($key);
                $args->value = trim($val);
                if(!$args->name || !$args->value) continue;
                executeQuery('module.insertModuleSkinVars', $args);
            }
        }

        /**
         * @brief 특정 모듈의 스킨 변수 제거
         **/
        function deleteModuleSkinVars($module_srl) {
            $args->module_srl = $module_srl;
            return executeQuery('module.deleteModuleSkinVars', $args);
        }

        /**
         * @brief 특정 모듈에 확장 변수 등록
         **/
        function insertModuleExtraVars($module_srl, $obj) {
            $this->deleteModuleExtraVars($module_srl);
            if(!$obj || !count($obj)) return;

            foreach($obj as $key => $val) {
                $args = null;
                $args->module_srl = $module_srl;
                $args->name = trim($key);
                $args->value = trim($val);
                if(!$args->name || !$args->value) continue;
                executeQuery('module.insertModuleExtraVars', $args);
            }
        }

        /**
         * @brief 특정 모듈의 확장 변수 제거
         **/
        function deleteModuleExtraVars($module_srl) {
            $args->module_srl = $module_srl;
            return executeQuery('module.deleteModuleExtraVars', $args);
        }

        /**
         * @brief 특정 모듈에 권한 등록
         **/
        function insertModuleGrants($module_srl, $obj) {
            $this->deleteModuleGrants($module_srl);
            if(!$obj || !count($obj)) return;

            foreach($obj as $name => $val) {
                if(!$val || !count($val)) continue;

                foreach($val as $group_srl) {
                    $args = null;
                    $args->module_srl = $module_srl;
                    $args->name = $name;
                    $args->group_srl = trim($group_srl);
                    if(!$args->name || !$args->group_srl) continue;
                    executeQuery('module.insertModuleGrant', $args);

                }
            }
        }

        /**
         * @brief 특정 모듈의 권한 제거
         **/
        function deleteModuleGrants($module_srl) {
            $args->module_srl = $module_srl;
            return executeQuery('module.deleteModuleGrants', $args);
        }

        /**
         * @brief 사용자 정의 언어 변경
         **/
        function replaceDefinedLangCode(&$output) {
            $output = preg_replace_callback('!\$user_lang->([a-z0-9\_]+)!is', array($this,'_replaceLangCode'), $output);
        }
        function _replaceLangCode($matches) {
            static $lang = null;
            if(is_null($lang)) {
                $site_module_info = Context::get('site_module_info');
                $cache_file = sprintf('%sfiles/cache/lang_defined/%d.%s.php', _XE_PATH_, $site_module_info->site_srl, Context::getLangType());
                if(!file_exists($cache_file)) {
                    $lang = array();
                    return;
                }
                require_once($cache_file);
            }
            if(!Context::get($matches[1]) && $lang[$matches[1]]) return $lang[$matches[1]];

            return $matches[0];
        }



        /**
         * @brief 파일박스에 파일 추가 및 업데이트
         **/
        function procModuleFileBoxAdd(){

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return new Object(-1, 'msg_not_permitted');

            $vars = Context::gets('comment','addfile','filter');
            $module_filebox_srl = Context::get('module_filebox_srl');

            $ext = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
            $vars->ext = $ext;
            if($vars->filter) $filter = explode(',',$vars->filter);
            if(!in_array($ext,$filter)) return new Object(-1, 'msg_error_occured');

            $vars->member_srl = $logged_info->member_srl;

            // update
            if($module_filebox_srl > 0){
                $vars->module_filebox_srl = $module_filebox_srl;
                $output = $this->updateModuleFileBox($vars);

            // insert
            }else{
                if(!Context::isUploaded()) return new Object(-1, 'msg_error_occured');
                $addfile = Context::get('addfile');
                if(!is_uploaded_file($addfile['tmp_name'])) return new Object(-1, 'msg_error_occured');
                if($vars->addfile['error'] != 0) return new Object(-1, 'msg_error_occured');
                $output = $this->insertModuleFileBox($vars);
            }

            $url  = getUrl('','module','module','act','dispModuleFileBox','input',Context::get('input'),'filter',$vars->filter);
            $url = html_entity_decode($url);
            $vars = Context::set('url',$url);
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('move_filebox_list');
        }


        /**
         * @brief 파일박스에 파일 업데이트
         **/
        function updateModuleFileBox($vars){

            // have file
            if($vars->addfile['tmp_name'] && is_uploaded_file($vars->addfile['tmp_name'])){
                $oModuleModel = &getModel('module');
                $output = $oModuleModel->getModuleFileBox($vars->module_filebox_srl);
                FileHandler::removeFile($output->data->filename);

                $path = $oModuleModel->getModuleFileBoxPath($vars->module_filebox_srl);
                FileHandler::makeDir($path);

                $save_filename = sprintf('%s%s.%s',$path, $vars->module_filebox_srl, $ext);
                $tmp = $vars->addfile['tmp_name'];

                if(!@move_uploaded_file($tmp, $save_filename)) {
                    return false;
                }

                $args->fileextension = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
                $args->filename = $save_filename;
                $args->filesize = $vars->addfile['size'];

            }

            $args->module_filebox_srl = $vars->module_filebox_srl;
            $args->comment = $vars->comment;

            return executeQuery('module.updateModuleFileBox', $vars);
        }


        /**
         * @brief 파일박스에 파일 추가
         **/
        function insertModuleFileBox($vars){
            // set module_filebox_srl
            $vars->module_filebox_srl = getNextSequence();

            // get file path
            $oModuleModel = &getModel('module');
            $path = $oModuleModel->getModuleFileBoxPath($vars->module_filebox_srl);
            FileHandler::makeDir($path);
            $save_filename = sprintf('%s%s.%s',$path, $vars->module_filebox_srl, $vars->ext);
            $tmp = $vars->addfile['tmp_name'];

            // upload
            if(!@move_uploaded_file($tmp, $save_filename)) {
                return false;
            }


            // insert
            $args->module_filebox_srl = $vars->module_filebox_srl;
            $args->member_srl = $vars->member_srl;
            $args->comment = $vars->comment;
            $args->filename = $save_filename;
            $args->fileextension = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
            $args->filesize = $vars->addfile['size'];

            $output = executeQuery('module.insertModuleFileBox', $args);
            return $output;
        }


        /**
         * @brief 파일박스에 파일 삭제
         **/

        function procModuleFileBoxDelete(){
            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin !='Y' && !$logged_info->is_site_admin) return new Object(-1, 'msg_not_permitted');

            $module_filebox_srl = Context::get('module_filebox_srl');
            if(!$module_filebox_srl) return new Object(-1, 'msg_invalid_request');
            $vars->module_filebox_srl = $module_filebox_srl;
            $output = $this->deleteModuleFileBox($vars);
            if(!$output->toBool()) return $output;
        }

        function deleteModuleFileBox($vars){

            // delete real file
            $oModuleModel = &getModel('module');
            $output = $oModuleModel->getModuleFileBox($vars->module_filebox_srl);
            FileHandler::removeFile($output->data->filename);

            $args->module_filebox_srl = $vars->module_filebox_srl;
            return executeQuery('module.deleteModuleFileBox', $args);
        }
    }
?>
