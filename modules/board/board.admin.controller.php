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
         * @brief 게시판 추가
         **/
        function procBoardAdminInsertBoard($args = null) {
            // module 모듈의 model/controller 객체 생성
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // 게시판 모듈의 정보 설정
            $args = Context::getRequestVars();
            $args->module = 'board';
            $args->mid = $args->board_name;
            unset($args->board_name);

            // 기본 값외의 것들을 정리
            if($args->use_category!='Y') $args->use_category = 'N';
            if($args->except_notice!='Y') $args->except_notice = 'N';
            if($args->consultation!='Y') $args->consultation = 'N';
            if(!in_array($args->order_target,$this->order_target)) $args->order_target = 'list_order';
            if(!in_array($args->order_type,array('asc','desc'))) $args->order_type = 'asc';

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // module_srl의 값에 따라 insert/update
            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
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
         * @brief 게시판 목록 지정
         **/
        function procBoardAdminInsertListConfig() {
            $module_srl = Context::get('module_srl');
            $list = explode(',',Context::get('list'));
            if(!count($list)) return new Object(-1, 'msg_invalid_request');

            $list_arr = array();
            foreach($list as $val) {
                $val = trim($val);
                if(!$val) continue;
                if(substr($val,0,10)=='extra_vars') $val = substr($val,10);
                $list_arr[] = $val;
            }

            $oModuleController = &getController('module');
            $oModuleController->insertModulePartConfig('board', $module_srl, $list_arr);
        }
    }
?>
