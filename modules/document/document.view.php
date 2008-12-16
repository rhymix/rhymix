<?php
    /**
     * @class  documentView
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 View class
     **/

    class documentView extends document {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 문서 인쇄 기능
         * 해당 글만 찾아서 그냥 출력해버린다;;
         **/
        function dispDocumentPrint() {
            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');

            // document 객체를 생성. 기본 데이터 구조의 경우 document모듈만 쓰면 만사 해결.. -_-;
            $oDocumentModel = &getModel('document');

            // 선택된 문서 표시를 위한 객체 생성 
            $oDocument = $oDocumentModel->getDocument($document_srl, $this->grant->manager);
            if(!$oDocument->isExists()) return new Object(-1,'msg_invalid_request');

            // 권한 체크
            if(!$oDocument->isAccessible()) return new Object(-1,'msg_not_permitted');


            // 설정된 확장 변수를 찾는다.
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
            $extra_vars = array();
            foreach($module_info->extra_vars as $key => $extra_var){
                $extra_vars[$key]->name = $extra_var->name;
                $extra_vars[$key]->value = $oDocument->getExtraValue($key);
                if(is_array($extra_vars[$key]->value)) $extra_vars[$key]->value = join("",$extra_vars[$key]->value);
            }
            Context::set('extra_vars', $extra_vars);

            // 브라우저 타이틀 설정
            Context::setBrowserTitle($oDocument->getTitleText());
            Context::set('oDocument', $oDocument);

            Context::set('layout','none');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('print_page');
        }

        /**
         * @brief 미리 보기
         **/
        function dispDocumentPreview() {
            Context::set('layout','none');

            $content = Context::get('content');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('preview_page');
        }
        
    }
?>
