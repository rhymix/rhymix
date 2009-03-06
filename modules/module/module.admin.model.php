<?php
    /**
     * @class  moduleAdminModel
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief  module 모듈의 AdminModel class
     **/

    class moduleAdminModel extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief module_srl (,콤마로 연결된)로 대상 모듈들의 목록을 return)
         * 모듈 선택기(ModuleSelector)에서 사용됨
         **/
        function getModuleAdminModuleList() {
            $args->module_srls = Context::get('module_srls');
            $output = executeQueryArray('module.getModulesInfo', $args);
            if(!$output->toBool() || !$output->data) return new Object();

            foreach($output->data as $key => $val) {
                $list[$val->module_srl] = array('module_srl'=>$val->module_srl,'mid'=>$val->mid,'browser_title'=>$val->browser_title);
            }
            $modules = explode(',',$args->module_srls);
            for($i=0;$i<count($modules);$i++) {
                $module_list[$modules[$i]] = $list[$modules[$i]];
            }

            $this->add('id', Context::get('id'));
            $this->add('module_list', $module_list);
        }

        /**
         * @brief 공통 :: 모듈의 모듈 권한 출력 페이지
         * 모듈의 모듈 권한 출력은 모든 모듈에서 module instance를 이용할때 사용할 수 있음
         **/
        function getModuleGrantHTML($module_srl, $source_grant_list) {

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

            // access, manager 권한은 가상 권한으로 설정
            $grant_list->access->title = Context::getLang('grant_access');
            $grant_list->access->default = 'guest';
            if(count($source_grant_list)) {
                foreach($source_grant_list as $key => $val) {
                    if(!$val->default) $val->default = 'guest';
                    if($val->default == 'root') $val->default = 'manager';
                    $grant_list->{$key} = $val;
                }
            }
            $grant_list->manager->title = Context::getLang('grant_manager');
            $grant_list->manager->default = 'manager';
            Context::set('grant_list', $grant_list);

            // 현재 모듈에 설정된 권한 그룹을 가져옴
            $default_grant = array();
            $args->module_srl = $module_srl;
            $output = executeQueryArray('module.getModuleGrants', $args);
            if($output->data) {
                foreach($output->data as $val) {
                    if($val->group_srl == 0) $default_grant[$val->name] = 'all';
                    else if($val->group_srl == -1) $default_grant[$val->name] = 'member';
                    else if($val->group_srl == -2) $default_grant[$val->name] = 'site';
                    else {
                        $selected_group[$val->name][] = $val->group_srl;
                        $default_grant[$val->name] = 'group';
                    }
                }
            }
            Context::set('selected_group', $selected_group);
            Context::set('default_grant', $default_grant);

            // 현재 모듈에 설정된 관리자 아이디를 추출
            $admin_member = $oModuleModel->getAdminId($module_srl);
            Context::set('admin_member', $admin_member);

            // 그룹을 가져옴
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups($module_info->site_srl);
            Context::set('group_list', $group_list);

            // grant 정보를 추출
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($this->module_path.'tpl', 'module_grants');
        }

        /**
         * @brief 공통 :: 모듈의 스킨 설정 출력 페이지
         **/
        function getModuleSkinHTML($module_srl) {
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info) return;

            $skin = $module_info->skin;
            $module_path = './modules/'.$module_info->module;

            // 스킨의 XML 정보를 구함
            $skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);

            // DB에 설정된 스킨 정보를 구함
            $skin_vars = $oModuleModel->getModuleSkinVars($module_srl);

            if(count($skin_info->extra_vars)) {
                foreach($skin_info->extra_vars as $key => $val) {
                    $group = $val->group;
                    $name = $val->name;
                    $type = $val->type;
                    if($skin_vars[$name]) $value = $skin_vars[$name]->value;
                    else $value = '';
                    if($type=="checkbox" && !$value) $value = array();
                    $skin_info->extra_vars[$key]->value= $value;
                }
            }

            Context::set('module_info', $module_info);
            Context::set('mid', $module_info->mid);
            Context::set('skin_info', $skin_info);
            Context::set('skin_vars', $skin_vars);

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($this->module_path.'tpl', 'skin_config');
        }

        /**
         * @brief 특정 언어 코드에 대한 값들을 가져오기
         * lang_code를 직접 기입하면 해당 언어코드에 대해서만 가져오고 값이 없으면 $name을 그대로 return
         **/
        function getLangCode($site_srl, $name) {
            $lang_supported = Context::get('lang_supported');

            if(substr($name,0,12)=='$user_lang->') {
                $args->site_srl = (int)$site_srl;
                $args->name = substr($name,12);
                $output = executeQueryArray('module.getLang', $args);
                if($output->data) {
                    foreach($output->data as $key => $val) {
                        $selected_lang[$val->lang_code] = $val->value;
                    }
                }
            } else {
                $tmp = unserialize($name);
                if($tmp) {
                    $selected_lang = array();
                    $rand_name = $tmp[Context::getLangType()];
                    if(!$rand_name) $rand_name = array_shift(unserialize($name));
                    foreach($tmp as $key => $val) $selected_lang[$key] = $tmp[$key]?$tmp[$key]:$rand_name;
                }
            }

            $output = array();
            foreach($lang_supported as $key => $val) {
                $output[$key] = $selected_lang[$key]?$selected_lang[$key]:$name;
            }
            return $output;
        }

        /**
         * @brief 모듈 언어를 ajax로 요청시 return
         **/
        function getModuleAdminLangCode() {
            $name = Context::get('name');
            if(!$name) return new Object(-1,'msg_invalid_request');
            $site_module_info = Context::get('site_module_info');
            $this->add('name', $name);
            $output = $this->getLangCode($site_module_info->site_srl, '$user_lang->'.$name);
            $this->add('langs', $output);
        }
    }
?>
