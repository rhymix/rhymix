<?php
    /**
     * @class  springnoteAdminController
     * @author zero (zero@nzeo.com)
     * @brief  springnote 모듈의 admin controller class
     * 관리자 기능을 담당하게 된다.
     * 보통 모듈의 관리자 기능은 해당 모듈의 생성이나 정보/권한/스킨정보의 수정등을 담당하게 된다.
     **/

    class springnoteAdminController extends springnote {

        /**
         * @brief 초기화
         **/
        function init() { }

        /**
         * @brief 방명록 추가
         * springnote_name은 mid의 값이 되고 나머지 모듈 공통 값을 받아서 저장을 하게 된다.
         **/
        function procSpringnoteAdminInsertSpringnote($args = null) {
            // module 모듈의 model/controller 객체 생성
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // 게시판 모듈의 정보 설정
            $args = Context::getRequestVars();
            $args->module = 'springnote';
            $args->mid = $args->springnote_name;
            unset($args->springnote_name);

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // module_srl의 값에 따라 insert/update
            if(!$args->module_srl) {
                // module controller를 이용하여 모듈을 생성한다.
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
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
         * @brief 방명록 삭제
         **/
        function procSpringnoteAdminDeleteSpringnote() {
            // 삭제할 대상 방명록의 module_srl을 구한다.
            $module_srl = Context::get('module_srl');

            // 원본을 구해온다
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);

            // 삭제 처리시 오류가 발생하면 결과 객체를 바로 리턴한다.
            if(!$output->toBool()) return $output;

            // 등록후 페이지 이동을 위해 변수 설정 및 메세지를 설정한다.
            $this->add('module','springnote');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }
 

    }
?>
