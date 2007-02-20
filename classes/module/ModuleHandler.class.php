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

    class ModuleHandler extends Handler {

        var $oModule = NULL; ///< 모듈 객체

        /**
         * @brief constructor
         *
         * Request Argument에서 $mid, $act값으로 객체를 찾는다.\n
         * 단 유연한 처리를 위해 $document_srl 을 이용하기도 한다.
         **/
        function ModuleHandler() {
            // Request Argument중 모듈을 찾을 수 있는 변수를 구함
            $module = Context::get('module');
            $act = Context::get('act');
            $mid = Context::get('mid');
            $document_srl = Context::get('document_srl');

            // ModuleModel 객체 생성
            $oModuleModel = getModel('module');

            // 설치가 안되어 있다면 install module을 지정
            if(!Context::isInstalled()) {
                $module = 'install';
                $mid = NULL;

            // 설치가 되어 있을시에 요청받은 모듈을 확인 (없으면 기본 모듈, 기본 모듈도 없으면 에러 출력)
            } else {

                // document_srl만 있다면 mid를 구해옴
                if(!$mid && $document_srl) $module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);

                // document_srl에 의한 모듈 찾기가 안되었거나 document_srl이 없을시 처리
                if(!$module_info) {
                    // mid 값이 있으면 모듈을 찾기
                    if($mid) $module_info = $oModuleModel->getModuleInfoByMid($mid);

                    // mid값이 없고 module지정이 없을시
                    elseif(!$module) $module_info = $oModuleModel->getModuleInfoByMid($mid);
                }

                // 모듈 정보에서 module 이름을 구해움
                $module = $module_info->module;
                $mid = $module_info->mid;
            }

            // 만약 모듈이 없다면 잘못된 모듈 호출에 대한 오류를 message 모듈을 통해 호출
            if(!$module) {
                $module = 'message';
                Context::set('message', Context::getLang('msg_mid_not_exists'));
            }

            // 해당 모듈의 conf/action.xml 을 분석하여 action 정보를 얻어옴
            $xml_info = $oModuleModel->getModuleXmlInfo($module);

            // 현재 요청된 act가 있으면 $xml_info에서 type을 찾음, 없다면 기본 action을 이용
            if(!$act || !$xml_info->action->{$act}) $act = $xml_info->default_action;

            // type, grant 값 구함
            $type = $xml_info->action->{$act}->type;
            $grant = $xml_info->action->{$act}->grant;

            // module, act, mid값을 Context에 세팅
            Context::set('module', $module);
            Context::set('mid', $mid, true);
            Context::set('act', $act, true);

            // 모듈 객체 생성
            $oModule = &$this->getModuleInstance($module, $type);


            // 모듈 정보 세팅
            $oModule->setModuleInfo($module_info, $xml_info);

            if(!is_object($oModule)) return;

            $act = Context::get('act');
            $oModule->proc($act);

            $this->oModule = $oModule;
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
         * @brief 모듈 객체를 생성함
         **/
        function getModuleInstance($module, $type = 'view') {
            $class_path = ModuleHandler::getModulePath($module);
            if(!$class_path) return NULL;

            // global 변수에 미리 생성해 둔 객체가 없으면 새로 생성
            if(!$GLOBALS['_loaded_module'][$module][$type]) {

                /**
                 * 모듈의 위치를 파악
                 * 기본적으로는 ./modules/* 에 있지만 웹업데이트나 웹설치시 ./files/modules/* 에 있음
                 * ./files/modules/* 의 클래스 파일을 우선으로 처리해야 함
                 **/

                // 상위 클래스명 구함
                $high_class_file = sprintf('%s%s.class.php', $class_path, $module);
                if(!file_exists($high_class_file)) return NULL;
                require_once($high_class_file);

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

                // 해당 위치에 속한 lang 파일을 읽음
                Context::loadLang($class_path.'lang');

                // 생성된 객체에 자신이 호출된 위치를 세팅해줌
                $oModule->setModulePath($class_path);

                // GLOBALS 변수에 생성된 객체 저장
                $GLOBALS['_loaded_module'][$module][$type] = $oModule;
            }

            // 객체 리턴
            return $GLOBALS['_loaded_module'][$module][$type];
        }

        /**
         * @brief constructor에서 생성한 oModule를 return
         **/
        function getModule() {
            return $this->oModule;
        }
    }
?>
