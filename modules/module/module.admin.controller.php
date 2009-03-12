<?php
    /**
     * @class  moduleAdminController
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 admin controller class
     **/

    class moduleAdminController extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모듈 카테고리 추가
         **/
        function procModuleAdminInsertCategory() {
            $args->title = Context::get('title');
            $output = executeQuery('module.insertModuleCategory', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage("success_registed");
        }

        /**
         * @brief 카테고리의 내용 수정
         **/
        function procModuleAdminUpdateCategory() {
            $mode = Context::get('mode');

            switch($mode) {
                case 'delete' :
                        $output = $this->doDeleteModuleCategory();
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                        $output = $this->doUpdateModuleCategory();
                        $msg_code = 'success_updated';
                    break;
            }
            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
        }

        /**
         * @brief 모듈 카테고리의 제목 변경
         **/
        function doUpdateModuleCategory() {
            $args->title = Context::get('title');
            $args->module_category_srl = Context::get('module_category_srl');
            return executeQuery('module.updateModuleCategory', $args);
        }

        /**
         * @brief 모듈 카테고리 삭제
         **/
        function doDeleteModuleCategory() {
            $args->module_category_srl = Context::get('module_category_srl');
            return executeQuery('module.deleteModuleCategory', $args);
        }

        /**
         * @brief 모듈 복사
         **/
        function procModuleAdminCopyModule() {
            // 복사하려는 대상 모듈의 정보를 구함
            $module_srl = Context::get('module_srl');
            if(!$module_srl) return;

            // 새로 생성하려는 모듈들의 이름/브라우저 제목을 구함
            $clones = array();
            $args = Context::getAll();
            for($i=1;$i<=10;$i++) {
                $mid = trim($args->{"mid_".$i});
                if(!$mid) continue;
                if(!preg_match("/^[a-zA-Z]([a-zA-Z0-9_]*)$/i", $mid)) return new Object(-1, 'msg_limit_mid');
                $browser_title = $args->{"browser_title_".$i};
                if(!$mid) continue;
                if($mid && !$browser_title) $browser_title = $mid;
                $clones[$mid] = $browser_title;
            }
            if(!count($clones)) return;

            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 모듈 정보 가져옴
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

            // 권한 정보 가져옴
            $module_args->module_srl = $module_srl;
            $output = executeQueryArray('module.getModuleGrants', $module_args);
            $grant = array();
            if($output->data) {
                foreach($output->data as $key => $val) $grant[$val->name][] = $val->group_srl;
            }


            $oDB = &DB::getInstance();
            $oDB->begin();

            // 모듈 복사
            foreach($clones as $mid => $browser_title) {
                $clone_args = null;
                $clone_args = clone($module_info);
                $clone_args->module_srl = null;
                $clone_args->content = null;
                $clone_args->mid = $mid;
                $clone_args->browser_title = $browser_title;
                $clone_args->is_default = 'N';

                // 모듈 생성
                $output = $oModuleController->insertModule($clone_args);
                $module_srl = $output->get('module_srl');

                // 권한 정보 등록
                if(count($grant)) $oModuleController->insertModuleGrants($module_srl, $grant);
            }

            $oDB->commit();
            $this->setMessage('success_registed');
        }

        /**
         * @brief 모듈 권한 저장
         **/
        function procModuleAdminInsertGrant() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // 모듈 번호 구함
            $module_srl = Context::get('module_srl');

            // 해당 모듈의 정보를 구함
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info) return new Object(-1,'msg_invalid_request');

            // 관리자 아이디 등록
            $oModuleController->deleteAdminId($module_srl);
            $admin_member = Context::get('admin_member');
            if($admin_member) {
                $admin_members = explode(',',$admin_member);
                for($i=0;$i<count($admin_members);$i++) {
                    $admin_id = trim($admin_members[$i]);
                    if(!$admin_id) continue;
                    $oModuleController->insertAdminId($module_srl, $admin_id);

                }
            }

            // 권한 정리
            $xml_info = $oModuleModel->getModuleActionXML($module_info->module);

            $grant_list = $xml_info->grant;

            $grant_list->access->default = 'guest';
            $grant_list->manager->default = 'manager';

            foreach($grant_list as $grant_name => $grant_info) {
                // default값을 구함
                $default = Context::get($grant_name.'_default');

                // -1 = 로그인 사용자만, -2 = 사이트 가입자만, 0 = 모든 사용자
                if(strlen($default)){
                    $grant->{$grant_name}[] = $default;
                    continue;

                // 특정 그룹 사용자
                } else {
                    $group_srls = Context::get($grant_name);
                    if($group_srls) {
                        if(strpos($group_srls,'|@|')!==false) $group_srls = explode('|@|',$group_srls);
                        elseif(strpos($group_srls,',')!==false) $group_srls = explode(',',$group_srls);
                        else $group_srls = array($group_srls);
                        $grant->{$grant_name} = $group_srls;
                    }
                    continue;
                }
                $grant->{$group_srls} = array();
            }
            
            // DB에 저장
            $args->module_srl = $module_srl;
            $output = executeQuery('module.deleteModuleGrants', $args);
            if(!$output->toBool()) return $output;

            // DB에 권한 저장 
            foreach($grant as $grant_name => $group_srls) {
                foreach($group_srls as $key => $val) {
                    $args = null;
                    $args->module_srl = $module_srl;
                    $args->name = $grant_name;
                    $args->group_srl = $val;
                    $output = executeQuery('module.insertModuleGrant', $args);
                    if(!$output->toBool()) return $output;
                }
            }
            $this->setMessage('success_registed');
        }

        /**
         * @brief 스킨 정보 업데이트
         **/
        function procModuleAdminUpdateSkinInfo() {
            // module_srl에 해당하는 정보들을 가져오기
            $module_srl = Context::get('module_srl');

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if($module_info->module_srl) {
                $skin = $module_info->skin;

                // 스킨의 정보를 구해옴 (extra_vars를 체크하기 위해서)
                $module_path = './modules/'.$module_info->module;
                $skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);

                // 입력받은 변수들을 체크 (mo, act, module_srl, page등 기본적인 변수들 없앰)
                $obj = Context::getRequestVars();
                unset($obj->act);
                unset($obj->module_srl);
                unset($obj->page);
                unset($obj->mid);
                unset($obj->module);

                // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
                if($skin_info->extra_vars) {
                    foreach($skin_info->extra_vars as $vars) {
                        if($vars->type!='image') continue;

                        $image_obj = $obj->{$vars->name};

                        // 삭제 요청에 대한 변수를 구함
                        $del_var = $obj->{"del_".$vars->name};
                        unset($obj->{"del_".$vars->name});
                        if($del_var == 'Y') {
                            FileHandler::removeFile($module_info->{$vars->name});
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

                // 해당 모듈의 전체 스킨 불러와서 이미지는 제거
                $skin_vars = $oModuleModel->getModuleSkinVars($module_srl);

                if($skin_info->extra_vars) {
                    foreach($skin_info->extra_vars as $vars) {
                        if($vars->type!='image') continue;
                        $value = $skin_vars[$vars->name];
                        if(file_exists($value)) @unlink($value);
                    }
                }
                $oModuleController = &getController('module');
                $oModuleController->deleteModuleSkinVars($module_srl);

                // 등록
                $oModuleController->insertModuleSkinVars($module_srl, $obj);
            }

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath('./modules/module/tpl');
            $this->setTemplateFile("top_refresh.html");
        }

        /**
         * @brief 모듈 일괄 정리
         **/
        function procModuleAdminModuleSetup() {
            $vars = Context::getRequestVars();

            if(!$vars->module_srls) return new Object(-1,'msg_invalid_request');

            $module_srls = explode(',',$vars->module_srls);
            if(!count($module_srls)) return new Object(-1,'msg_invalid_request');

            $oModuleModel = &getModel('module');
            $oModuleController= &getController('module');
            foreach($module_srls as $module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                $module_info->module_category_srl = $vars->module_category_srl;
                $module_info->layout_srl = $vars->layout_srl;
                $module_info->skin = $vars->skin;
                $module_info->description = $vars->description;
                $module_info->header_text = $vars->header_text;
                $module_info->footer_text = $vars->footer_text;
                $oModuleController->updateModule($module_info);
            }

            $this->setMessage('success_registed');
        }

        /**
         * @brief 모듈 권한 일괄 정리
         **/
        function procModuleAdminModuleGrantSetup() {
            $module_srls = Context::get('module_srls');
            if(!$module_srls) return new Object(-1,'msg_invalid_request');

            $modules = explode(',',$module_srls);
            if(!count($modules)) return new Object(-1,'msg_invalid_request');

            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            $module_info = $oModuleModel->getModuleInfoByModuleSrl($modules[0]);
            $xml_info = $oModuleModel->getModuleActionXml($module_info->module);
            $grant_list = $xml_info->grant;

            $grant_list->access->default = 'guest';
            $grant_list->manager->default = 'manager';

            foreach($grant_list as $grant_name => $grant_info) {
                // default값을 구함
                $default = Context::get($grant_name.'_default');

                // -1 = 로그인 사용자만, 0 = 모든 사용자
                if(strlen($default)){
                    $grant->{$grant_name}[] = $default;
                    continue;

                // 특정 그룹 사용자
                } else {
                    $group_srls = Context::get($grant_name);
                    if($group_srls) {
                        if(strpos($group_srls,'|@|')!==false) $group_srls = explode('|@|',$group_srls);
                        elseif(strpos($group_srls,',')!==false) $group_srls = explode(',',$group_srls);
                        else $group_srls = array($group_srls);
                        $grant->{$grant_name} = $group_srls;
                    }
                    continue;
                }
                $grant->{$group_srls} = array();
            }

            
            // DB에 저장
            foreach($modules as $module_srl) {
                $args = null;
                $args->module_srl = $module_srl;
                $output = executeQuery('module.deleteModuleGrants', $args);
                if(!$output->toBool()) continue;

                // DB에 권한 저장 
                foreach($grant as $grant_name => $group_srls) {
                    foreach($group_srls as $key => $val) {
                        $args = null;
                        $args->module_srl = $module_srl;
                        $args->name = $grant_name;
                        $args->group_srl = $val;
                        $output = executeQuery('module.insertModuleGrant', $args);
                        if(!$output->toBool()) return $output;
                    }
                }
            }
            $this->setMessage('success_registed');
        }

        /**
         * @brief 언어 추가/ 업데이트
         **/
        function procModuleAdminInsertLang() {
            // 언어코드명 가져옴 
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = (int)$site_module_info->site_srl;
            $args->name = str_replace(' ','_',Context::get('lang_code'));
            if(!$args->name) return new Object(-1,'msg_invalid_request');

            // 언어코드가 있는지 조사
            $output = executeQueryArray('module.getLang', $args);
            if(!$output->toBool()) return $output;

            // 있으면 업데이트를 위해 기존 값들을 지움
            if($output->data) $output = executeQuery('module.deleteLang', $args);
            if(!$output->toBool()) return $output;

            // 입력
            $lang_supported = Context::get('lang_supported');
            foreach($lang_supported as $key => $val) {
                $args->lang_code = $key;
                $args->value = trim(Context::get($key));
                if(!$args->value) {
                    $args->value = Context::get(strtolower($key));
                    if(!$args->value) $args->value = $args->name;
                }
                $output = executeQuery('module.insertLang', $args);
                if(!$output->toBool()) return $output;
            }
            $this->makeCacheDefinedLangCode($args->site_srl);

            $this->add('name', $args->name);
        }

        /**
         * @brief 언어 제거
         **/
        function procModuleAdminDeleteLang() {
            // 언어코드명 가져옴 
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = (int)$site_module_info->site_srl;
            $args->name = str_replace(' ','_',Context::get('name'));
            if(!$args->name) return new Object(-1,'msg_invalid_request');

            $output = executeQuery('module.deleteLang', $args);
            if(!$output->toBool()) return $output;
            $this->makeCacheDefinedLangCode($args->site_srl);
        }

        /**
         * @brief 사용자 정이 언어코드 파일 저장
         **/
        function makeCacheDefinedLangCode($site_srl = 0) {
            // 현재 사이트의 언어파일 가져오기
            if(!$site_srl) {
                $site_module_info = Context::get('site_module_info');
                $args->site_srl = (int)$site_module_info->site_srl;
            } else {
                $args->site_srl = $site_srl;
            }
            $output = executeQueryArray('module.getLang', $args);
            if(!$output->toBool() || !$output->data) return;

            // 캐시 디렉토리 설정
            $cache_path = _XE_PATH_.'files/cache/lang_defined/';
            if(!is_dir($cache_path)) FileHandler::makeDir($cache_path);

            $lang_supported = Context::get('lang_supported');
            foreach($lang_supported as $key => $val) {
                $fp[$key] = fopen( sprintf('%s/%d.%s.php', $cache_path, $args->site_srl, $key), 'w' );
                if(!$fp[$key]) return;
                fwrite($fp[$key],"<?php if(!defined('__ZBXE__')) exit(); \r\n");
            }

            foreach($output->data as $key => $val) {
                if($fp[$val->lang_code]) fwrite($fp[$val->lang_code], sprintf('$lang["%s"] = "%s";'."\r\n", $val->name, str_replace('"','\\"',$val->value)));
            }

            foreach($lang_supported as $key => $val) {
                if(!$fp[$key]) continue;
                fwrite($fp[$key],"?>");
                fclose($fp[$key]);
            }
        }

    }
?>
