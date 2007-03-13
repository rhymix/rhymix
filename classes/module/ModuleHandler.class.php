<?php
    /**
    * @class ModuleHandler
    * @author zero (zero@nzeo.com)
    * @brief 모듈 핸들링을 위한 Handler
    *
    * 모듈을 실행시키기 위한 클래스.
    * constructor에 아무 인자 없이 객체를 생성하면 현재 요청받은 
    * 상태를 바탕으로 적절한 모듈을 찾게 되고,
    * 별도의 인자 값을 줄 경우 그에 맞는 모듈을 찾아서 실행한다.
    **/

    class ModuleHandler extends Handler {

        var $oModule = NULL; ///< 모듈 객체

        var $module = NULL; ///< 모듈
        var $act = NULL; ///< action
        var $mid = NULL; ///< 모듈의 객체명
        var $document_srl = NULL; ///< 문서 번호

        var $module_info = NULL; ///< 모듈의 정보

        var $error = NULL; ///< 진행 도중 에러 발생시 에러 코드를 정의, message 모듈을 호출시 사용

        /**
         * @brief constructor
         *
         * ModuleHandler에서 사용할 변수를 미리 세팅
         * 인자를 넘겨주지 않으면 현 페이지 요청받은 Request Arguments를 이용하여
         * 변수를 세팅한다.
         **/
        function ModuleHandler($module = '', $act = '', $mid = '', $document_srl = '') {
            // 설치가 안되어 있다면 install module을 지정
            if(!Context::isInstalled()) {
                $this->module = 'install';
                $this->act = Context::get('act');
                return;
            }

            // Request Argument중 모듈을 찾을 수 있는 변수를 구함
            if(!$module) $this->module = Context::get('module');
            else $this->module = $module;

            if(!$act) $this->act = Context::get('act');
            else $this->act = $act;

            if(!$mid) $this->mid = Context::get('mid');
            else $this->mid = $mid;

            if(!$document_srl) $this->document_srl = Context::get('document_srl');
            else $this->document_srl = $document_srl;
        }

        /**
         * @brief module, mid, document_srl을 이용하여 모듈을 찾고 act를 실행하기 위한 준비를 함
         **/
        function init() {
            // ModuleModel 객체 생성
            $oModuleModel = &getModel('module');

            // document_srl이 있으면 document_srl로 모듈과 모듈 정보를 구함
            if($this->document_srl) $module_info = $oModuleModel->getModuleInfoByDocumentSrl($this->document_srl);

            // 아직 모듈을 못 찾았고 $mid값이 있으면 $mid로 모듈을 구함
            if(!$module_info && $this->mid) $module_info = $oModuleModel->getModuleInfoByMid($this->mid);

            // 역시 모듈을 못 찾았고 $module이 없다면 기본 모듈을 찾아봄
            if(!$module_info && !$this->module) $module_info = $oModuleModel->getModuleInfoByMid();

            // 모듈 정보가 찾아졌을 경우 모듈 정보에서 기본 변수들을 구함
            // 모듈 정보에서 module 이름을 구해움
            if($module_info) {
                $this->module = $module_info->module;
                $this->mid = $module_info->mid;
                $this->module_info = $module_info;
            }

            // 여기까지도 모듈 정보를 찾지 못했다면 깔끔하게 시스템 오류 표시
            if(!$this->module) $this->error = 'msg_module_is_not_exists';

            // mid값이 있을 경우 mid값을 세팅
            if($this->mid) Context::set('mid', $this->mid, true);
        }

        /**
         * @brief 모듈과 관련된 정보를 이용하여 객체를 구하고 act 실행까지 진행시킴
         **/
        function procModule() {
            // 에러가 있으면 return
            if($this->error) return;

            // ModuleModel 객체 생성
            $oModuleModel = &getModel('module');

            // 해당 모듈의 conf/action.xml 을 분석하여 action 정보를 얻어옴
            $xml_info = $oModuleModel->getModuleActionXml($this->module);

            // 현재 요청된 act가 있으면 $xml_info에서 type을 찾음, 없다면 기본 action을 이용
            if(!$this->act || !$xml_info->action->{$this->act}) $this->act = $xml_info->default_index_act;

            // act값이 지정이 안되어 있으면 오류 표시
            if(!$this->act) {
                $this->error = 'msg_module_is_not_exists';
                return;
            }

            // 설정된 mid가 없을 경우 요청된 act의 standalone 여부 체크
            if(!$this->mid && !$xml_info->action->{$this->act}->standalone) {
                $this->error = 'msg_module_is_not_standalone';
                return;
            }

            // type, grant 값 구함
            $type = $xml_info->action->{$this->act}->type;
            $grant = $xml_info->action->{$this->act}->grant;

            // 모듈 객체 생성
            $oModule = &$this->getModuleInstance($this->module, $type);
            if(!is_object($oModule)) {
                $this->error = 'msg_module_is_not_exists';
                return;
            }

            // 모듈에 act값을 세팅
            $oModule->setAct($this->act);

            // 모듈 정보 세팅
            $oModule->setModuleInfo($this->module_info, $xml_info);

            // 모듈을 수행하고 결과가 false이면 message 모듈 호출 지정
            if(!$oModule->proc()) $this->error = $oModule->getMessage();

            return $oModule;
        }

        /**
         * @ 실행된 모듈의 컨텐츠를 출력
         **/
        function displayContent(&$oModule = NULL) {
            // 설정된 모듈이 정상이지 않을 경우 message 모듈 객체 생성
            if(!$oModule || !is_object($oModule)) {
                $this->error = 'msg_module_is_not_exists';
            }

            // 에러가 발생하였을시 처리
            if($this->error) {
                // message 모듈 객체를 생성해서 컨텐츠 생성
                $oMessageView = &getView('message');
                $oMessageView->setError(-1);
                $oMessageView->setMessage($this->error);
                $oMessageView->dispContent();

                // 정상적으로 호출된 객체가 있을 경우 해당 객체의 template를 변경
                if($oModule) {
                    $oModule->setTemplatePath($oMessageView->getTemplatePath());
                    $oModule->setTemplateFile($oMessageView->getTemplateFile());

                // 그렇지 않으면 message 객체를 호출된 객체로 지정
                } else {
                    $oModule = $oMessageView;
                }
            }

            // 현재 페이지의 정보를 $zbxe_url이라는 변수로 등록 (common/tpl/common_header.tpl에서 javascript 변수로 설정함)
            if($_REQUEST['mid']) $zbxe_url = sprintf('mid=%s',$_REQUEST['mid']);
            elseif($_REQUEST['module']) $zbxe_url = sprintf('module=%s',$_REQUEST['module']);
            else $zbxe_url = Context::getRequestUri();
            Context::set('zbxe_url', $zbxe_url);

            // 해당 모듈에 layout_srl이 있는지 확인
            if($oModule->module_info->layout_srl) {
                // layout_srl이 있으면 해당 레이아웃 정보를 가져와 layout_path/ layout_file 위치 변경
                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($oModule->module_info->layout_srl);

                if($layout_info) {

                    // 레이아웃 정보중 extra_vars의 이름과 값을 $layout_info에 입력
                    if($layout_info->extra_var_count) {
                        foreach($layout_info->extra_var as $var_id => $val) {
                            $layout_info->{$var_id} = $val->value;
                        }
                        unset($layout_info->extra_var);
                    }
                    
                    // 레이아웃 정보중 menu를 Context::set
                    if($layout_info->menu_count) {
                        foreach($layout_info->menu as $menu_id => $menu) {
                            if(file_exists($menu->php_file)) include($menu->php_file);
                            Context::set($menu_id, $menu);
                        }
                    }

                    // 레이아웃 정보를 Context::set
                    Context::set('layout_info', $layout_info);

                    $oModule->setLayoutPath($layout_info->path);
                    $oModule->setLayoutFile('layout');
                }
            }

            // 컨텐츠 출력
            $oDisplayHandler = new DisplayHandler();
            $oDisplayHandler->printContent($oModule);

            // DB 및 기타 자원의 종결 처리
            Context::close();
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
                    case 'class' :
                            $instance_name = $module;
                            $class_file = sprintf('%s%s.class.php', $class_path, $module);
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
                $oModule->setModule($module);
                $oModule->setModulePath($class_path);
                if(method_exists($oModule, 'init') && !$GLOBALS['_inited_module'][$module][$type]) {
                    $GLOBALS['_inited_module'][$module][$type] = true;
                    $oModule->init();
                }

                // GLOBALS 변수에 생성된 객체 저장
                $GLOBALS['_loaded_module'][$module][$type] = $oModule;
            }

            // 객체 리턴
            return $GLOBALS['_loaded_module'][$module][$type];
        }
    }
?>
