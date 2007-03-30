<?php
    /**
     * @class  editor
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 controller class
     **/

    class editorController extends editor {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 자동 저장
         **/
        function procEditorSaveDoc() {

            $this->deleteSavedDoc();

            $args->document_srl = Context::get('document_srl');
            $args->content = Context::get('content');
            $args->title = Context::get('title');

            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }

            // 필요한 데이터가 없으면 pass
            if(!$args->document_srl || (!$args->title && !$args->content)) return new Object(0,'');

            // 저장
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.insertSavedDoc', $args);

            $this->setMessage('msg_auto_saved');
        }

        /**
         * @brief 컴포넌트에서 ajax요청시 해당 컴포넌트의 method를 실행 
         **/
        function procEditorCall() {
            $component = Context::get('component');
            $method = Context::get('method');
            if(!$component) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            $oEditorModel = &getModel('editor');
            $oComponent = &$oEditorModel->getComponentObject($component);
            if(!$oComponent->toBool()) return $oComponent;

            if(!method_exists($oComponent, $method)) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            $output = call_user_method($method, $oComponent);
            if((is_a($output, 'Object') || is_subclass_of($output, 'Object')) && !$output->toBool()) return $output;

            $this->setError($oComponent->getError());
            $this->setMessage($oComponent->getMessage());

            $vars = $oComponent->getVariables();
            if(count($vars)) {
                foreach($vars as $key=>$val) $this->add($key, $val);
            }
        }

        /**
         * @brief 컴포넌트의 활성화
         **/
        function procEditorAdminEnableComponent() {
            $args->component_name = Context::get('component_name');
            $args->enabled = 'Y';

            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트의 비활성화
         **/
        function procEditorAdminDisableComponent() {
            $args->component_name = Context::get('component_name');
            $args->enabled = 'N';

            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트의 위치 변경
         **/
        function procEditorAdminMoveListOrder() {
            $args->component_name = Context::get('component_name');
            $mode = Context::get('mode');

            // DB에서 전체 목록 가져옴
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.getComponentList', $args);
            $db_list = $output->data;
            foreach($db_list as $key => $val) {
                if($val->component_name == $args->component_name) break;
            }

            if($mode=="up") {
                if($key == 2) return new Object(-1,'msg_component_is_first_order');

                $prev_args->component_name = $db_list[$key-1]->component_name;
                $prev_args->list_order = $db_list[$key]->list_order;
                $oDB->executeQuery('editor.updateComponent', $prev_args);

                $cur_args->component_name = $db_list[$key]->component_name;
                $cur_args->list_order = $db_list[$key-1]->list_order;
                $oDB->executeQuery('editor.updateComponent', $cur_args);
            } else {
                if($key == count($db_list)-1) return new Object(-1,'msg_component_is_last_order');

                $next_args->component_name = $db_list[$key+1]->component_name;
                $next_args->list_order = $db_list[$key]->list_order;
                $oDB->executeQuery('editor.updateComponent', $next_args);

                $cur_args->component_name = $db_list[$key]->component_name;
                $cur_args->list_order = $db_list[$key+1]->list_order;
                $oDB->executeQuery('editor.updateComponent', $cur_args);
            }

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트 설정
         **/
        function procEditorAdminSetupComponent() {
            $component_name = Context::get('component_name');
            $extra_vars = Context::getRequestVars();
            unset($extra_vars->component_name);
            unset($extra_vars->module);
            unset($extra_vars->act);

            $args->component_name = $component_name;
            $args->extra_vars = serialize($extra_vars);

            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 자동 저장된 글을 삭제
         * 현재 접속한 사용자를 기준
         **/
        function deleteSavedDoc() {
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }

            // 일단 이전 저장본 삭제
            $oDB = &DB::getInstance();
            $oDB->executeQuery('editor.deleteSavedDoc', $args);
        }

        /**
         * @brief 컴포넌트를 DB에 추가
         **/
        function insertComponent($component_name, $enabled = false) {
            if($enabled) $enabled = 'Y';
            else $enabled = 'N';

            $oDB = &DB::getInstance();

            $args->component_name = $component_name;
            $args->enabled = $enabled;

            // 컴포넌트가 있는지 확인
            $output = $oDB->executeQuery('editor.isComponentInserted', $args);
            if($output->data->count) return new Object(-1, 'msg_component_is_not_founded');

            // 입력
            $args->list_order = $oDB->getNextSequence();
            $output = $oDB->executeQuery('editor.insertComponent', $args);
            return $output;
        }
    }
?>
