<?php
    /**
    * @class ModuleHandler
    * @author zero (zero@nzeo.com)
    * @brief mid의 값으로 모듈을 찾아 객체 생성 & 모듈 정보 세팅
    *
    * ModuleHandler는 RequestArgument중 $mid 값을 이용하여\n
    * 모듈을 찾아서 객체를 생성한다.\n
    * 단 act 값을 이용하여 actType(view, controller)을 판단하여\n
    * 객체를 생성해야 한다.\n
    * 그리고 $mid값을 이용 해당 모듈의 config를 읽어와 생성된\n
    * 모듈 객체에 전달하고 실행까지 진행을 한다.
    **/

    class ModuleHandler { 

        var $oModule = NULL; ///< 모듈 객체

        /**
         * @brief constructor
         *
         * Request Argument에서 $mid, $act값으로 객체를 찾는다.\n
         * 단 유연한 처리를 위해 $document_srl 을 이용하기도 한다.
         **/
        function ModuleHandler() {
            $module = Context::get('module');
            $act = Context::get('act');

            // 설치가 안되어 있다면 설치를 위한 준비를 한다
            if(!Context::isInstalled()) {
                // install 모듈로 강제 지정
                $module = 'install';
                $mid = NULL;

            // Request Argument의 mid값으로 모듈 객체 생성
            // mid가 없이 document_srl만 있다면 document_srl로 mid를 찾음
            } elseif(!$module) {
                $mid = Context::get('mid');
                $document_srl = Context::get('document_srl');

                // document_srl만 있다면 mid를 구해옴
                if(!$mid && $document_srl) $module_info = $this->getModuleInfoByDocumentSrl($document_srl);

                // mid 값에 대한 모듈 정보를 추출
                if(!$module_info) $module_info = $this->getModuleInfoByMid($mid);

                // 모듈 정보에서 module 이름을 구해움
                $module = $module_info->module;
            }

            // 만약 모듈이 없다면 잘못된 모듈 호출에 대한 오류를 message 모듈을 통해 호출
            if(!$module) {
                $module = 'message';
                Context::set('message', Context::getLang('msg_mid_not_exists'));
            }

            // 해당 모듈의 conf/action.xml 을 분석하여 action 정보를 얻어옴
            $action_info = $this->getActionInfo($module);

            // 현재 요청된 act가 있으면 $action_info에서 type을 찾음, 없다면 기본 action을 이용
            if(!$act || !$action_info->{$act}) $act = $action_info->default_action;

            // type, grant 값 구함
            $type = $action_info->{$act}->type;
            $grant = $action_info->{$act}->grant;

            // act값을 Context에 세팅
            Context::set('act', $act, true);

            // 모듈 객체 생성
            $oModule = &$this->getModuleInstance($module, $type, $module_info);

            if(!is_object($oModule)) return;

            $oModule->proc($act);

            $this->oModule = $oModule;
        }

        /**
         * @brief constructor에서 생성한 oModule를 return
         **/
        function getModule() {
            return $this->oModule;
        }

        /**
         * @brief document_srl로 모듈의 정보르 구함
         **/
        function getModuleInfoByDocumentSrl($document_srl) {
            // DB 객체 생성후 데이터를 DB에서 가져옴
            $oDB = &DB::getInstance();
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('module_manager.getModuleInfoByDocument', $args);

            // extra_vars의 정리
            $module_info = module_manager::extractExtraVar($output->data);
            return $module_info;
        }

        /**
         * @brief mid로 모듈의 정보를 구함
         **/
        function getModuleInfo($mid='') {
            // DB 객체 생성후 데이터를 DB에서 가져옴
            $oDB = &DB::getInstance();

            // $mid값이 인자로 주어질 경우 $mid로 모듈의 정보를 구함
            if($mid) {
                $args->mid = $mid;
                $output = $oDB->executeQuery('module_manager.getMidInfo', $args);
            }

            // 모듈의 정보가 없다면($mid가 잘못이거나 없었을 경우) 기본 모듈을 가져옴
            if(!$output->data) {
                $output = $oDB->executeQuery('module_manager.getDefaultMidInfo');
            }

            // extra_vars의 정리
            $module_info = module_manager::extractExtraVar($output->data);
            return $module_info;
        }

        /**
         * @brief module의 위치를 찾아서 return
         **/
        function getModulePath($module) {
            $class_path = sprintf('./files/modules/%s/', $module);
            if(is_dir($class_path)) return $class_path;

            $class_path = sprintf('./modules/%s/', $module);
            if(is_dir($class_path)) return $class_path;

            return "";
        }

        /**
         * @brief module의 conf/action.xml 을 통해 act값에 해당하는 action type을 return
         **/
        function getActionInfo($module) {
            $class_path = $this->getModulePath($module);
            if(!$class_path) return;

            $action_xml_file = sprintf("%sconf/action.xml", $class_path);
            if(!file_exists($action_xml_file)) return;

            $xml_obj = XmlParser::loadXmlFile($action_xml_file);
            if(!count($xml_obj->module)) return;

            $output->default_action = $xml_obj->module->attrs->default_action;
            $output->manage_action = $xml_obj->module->attrs->manage_action;

            if(is_array($xml_obj->module->action)) $action_list = $xml_obj->module->action;
            else $action_list[] = $xml_obj->module->action;

            foreach($action_list as $action) {
                $name = $action->attrs->name;
                $type = $action->attrs->type;
                $grant = $action->attrs->grant;
                $output->{$name}->type = $type;
                $output->{$name}->grant = $grant;
            }

            return $output;
        }

        /**
         * @brief 모듈 객체를 생성함
         **/
        function getModuleInstance($module, $type = 'view', $module_info = NULL) {
            $class_path = ModuleHandler::getModulePath($module);
            if(!$class_path) return NULL;

            // global 변수에 미리 생성해 둔 객체가 없으면 새로 생성
            if(!$GLOBALS['_loaded_module'][$module][$type]) {

                /**
                 * 모듈의 위치를 파악 
                 * 기본적으로는 ./modules/* 에 있지만 웹업데이트나 웹설치시 ./files/modules/* 에 있음
                 * ./files/modules/* 의 클래스 파일을 우선으로 처리해야 함
                 **/

                // 객체의 이름을 구함
                switch($type) {
                    case 'controller' :
                            $instance_name = sprintf("%s%s",$module,"Controller");
                            $class_file = sprintf('%s%s.%s.php', $class_path, $module, $type);
                        break;
                    case 'model' :
                            $instance_name = sprintf("%s%s",$module,"Model");
                            $class_file = sprintf('%s%s.%s.php', $class_path, $module, $type);
                        break;
                    default :
                            $type = 'view';
                            $instance_name = sprintf("%s%s",$module,"View");
                            $class_file = sprintf('%s%s.view.php', $class_path, $module, $type);
                        break;
                }

                // 클래스 파일의 이름을 구함
                if(!file_exists($class_file)) return NULL;

                // eval로 객체 생성
                require_once($class_file);
                $eval_str = sprintf('$oModule = new %s();', $instance_name);
                @eval($eval_str);
                if(!is_object($oModule)) return NULL;

                // 생성된 객체에 자신이 호출된 위치를 세팅해줌
                $oModule->setModulePath($class_path);

                // 모듈 정보 세팅
                $oModule->setModuleInfo($module_info);

                // 해당 위치에 속한 lang 파일을 읽음
                Context::loadLang($class_path.'lang');

                // GLOBALS 변수에 생성된 객체 저장
                $GLOBALS['_loaded_module'][$module][$type] = $oModule;
            }

            // 객체 리턴
            return $GLOBALS['_loaded_module'][$module][$type];
        }
    }
?>
