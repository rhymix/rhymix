<?php
    /**
     * @class  trackbackView
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 view class
     **/

    class trackbackView extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 목록 출력 (관리자용)
         **/
        function dispTrackbackSend() {
            $document_srl = Context::get('document_srl');
            if(!$document_srl) return $this->stop('msg_invalid_request');

            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return $this->stop('msg_not_permitted');

            // 원본 글의 정보를 구함
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return $this->stop('msg_invalid_document');
            if($oDocument->isSecret()) return $this->stop('msg_invalid_request');

            if($oDocument->getMemberSrl() != $logged_info->member_srl) return $this->stop('msg_not_permitted');

            Context::set('oDocument', $oDocument);

            // 템플릿 지정
            $this->setLayoutFile('popup_layout');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('send_trackback_form');
        }

        /**
         * @brief 서비스형 모듈의 추가 설정을 위한 부분
         * trackback의 사용 형태에 대한 설정만 받음
         **/
        function triggerDispTrackbackAdditionSetup(&$obj) {
            $current_module_srl = Context::get('module_srl');
            $current_module_srls = Context::get('module_srls');

            if(!$current_module_srl && !$current_module_srls) {
                // 선택된 모듈의 정보를 가져옴
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
                if(!$current_module_srl) return new Object();
            }

            // 선택된 모듈의 trackback설정을 가져옴
            $oTrackbackModel = &getModel('trackback');
            $trackback_config = $oTrackbackModel->getTrackbackModuleConfig($current_module_srl);
            Context::set('trackback_config', $trackback_config);

            // 템플릿 파일 지정
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'trackback_module_config');
            $obj .= $tpl;

            return new Object();
        }
    }
?>
