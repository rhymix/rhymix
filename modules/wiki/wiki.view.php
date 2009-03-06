<?php
    /**
     * @class  wikiView
     * @author haneul (haneul0318@gmail.com)
     * @brief  wiki 모듈의 View class
     **/

    class wikiView extends wiki {

        /**
         * @brief 초기화
         * wiki 모듈은 일반 사용과 관리자용으로 나누어진다.\n
         **/
        function init() {
            /**
             * 스킨 경로를 미리 template_path 라는 변수로 설정함
             * 스킨이 존재하지 않는다면 xe_wiki로 변경
             **/
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)) {
                $this->module_info->skin = 'xe_wiki';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }
            $this->setTemplatePath($template_path);

            $oModuleModel = &getModel('module');

            $document_config = $oModuleModel->getModulePartConfig('document', $this->module_info->module_srl);
            if(!isset($document_config->use_history)) $document_config->use_history = 'N';
            Context::set('use_history', $document_config->use_history);

            Context::addJsFile($this->module_path.'tpl/js/wiki.js');
        }

        /**
         * @brief 선택된 글 출력
         **/
        function dispWikiContent() {
            $output = $this->dispWikiContentView();
            if(!$output->toBool()) return;
        }

        function dispWikiHistory() {
            $oDocumentModel = &getModel('document');
            $document_srl = Context::get('document_srl');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return $this->stop('msg_invalid_request');
            $entry = $oDocument->getTitleText();
            Context::set('entry',$entry);
            $histories = $oDocumentModel->getHistories($document_srl);
            if(!$histories) $histories = array();
            Context::set('histories',$histories);
            Context::set('oDocument', $oDocument);
            $this->setTemplateFile('histories');
        }

        function dispWikiEditPage() {
            if(!$this->grant->write_document) return $this->dispWikiMessage('msg_not_permitted');

            $oDocumentModel = &getModel('document');
            $document_srl = Context::get('document_srl');
            $oDocument = $oDocumentModel->getDocument(0, $this->grant->manager);
            $oDocument->setDocument($document_srl);
            $oDocument->add('module_srl', $this->module_srl);
            Context::set('document_srl',$document_srl);
            Context::set('oDocument', $oDocument);

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert.xml');

            $this->setTemplateFile('write_form');
        }

        /**
         * @brief Displaying Message 
         **/
        function dispWikiMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

        function dispWikiTitleIndex() {
            $page = Context::get('page');
            $oDocumentModel = &getModel('document');
            $obj->module_srl = $this->module_info->module_srl;
            $obj->sort_index = "title";
            $obj->page = $page;
            $obj->list_count = 50;
            $output = $oDocumentModel->getDocumentList($obj);
            debugPrint($output);

            Context::set('document_list', $output->data);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);
            $this->setTemplateFile('title_index');
        }

        function dispWikiContentView() {
            // 요청된 변수 값들을 정리
            $document_srl = Context::get('document_srl');
            $entry = Context::get('entry');
            $oDocumentModel = &getModel('document');
            if(!$document_srl && !$entry) {
                $entry = "Front Page";
                Context::set('entry', $entry);
                $document_srl = $oDocumentModel->getDocumentSrlByAlias($this->module_info->mid, $entry);
            }


            // document model 객체 생성 

            /**
             * 요청된 문서 번호가 있다면 문서를 구함
             **/
            if($document_srl) {
                $oDocument = $oDocumentModel->getDocument($document_srl);

                // 해당 문서가 존재할 경우 필요한 처리를 함
                if($oDocument->isExists()) {

                    // 글과 요청된 모듈이 다르다면 오류 표시
                    if($oDocument->get('module_srl')!=$this->module_info->module_srl ) return $this->stop('msg_invalid_request');

                    // 관리 권한이 있다면 권한을 부여
                    if($this->grant->manager) $oDocument->setGrant();

                    if(!Context::get('entry')) Context::set('entry', $oDocument->getTitleText());

                    // 상담기능이 사용되고 공지사항이 아니고 사용자의 글도 아니면 무시

                // 요청된 문서번호의 문서가 없으면 document_srl null 처리 및 경고 메세지 출력
                } else {
                    Context::set('document_srl','',true);
                    $this->alertMessage('msg_not_founded');
                }

            /**
             * 요청된 문서 번호가 아예 없다면 빈 문서 객체 생성
             **/
            } else {
                $oDocument = $oDocumentModel->getDocument(0);
            }

            /**
             * 글 보기 권한을 체크해서 권한이 없으면 오류 메세지 출력하도록 처리
             **/
            if($oDocument->isExists()) {
                // 브라우저 타이틀에 글의 제목을 추가
                Context::addBrowserTitle($oDocument->getTitleText());

                // 조회수 증가 (비밀글일 경우 권한 체크)
                if(!$oDocument->isSecret() || $oDocument->isGranted()) $oDocument->updateReadedCount();

                // 비밀글일때 컨텐츠를 보여주지 말자.
                if($oDocument->isSecret() && !$oDocument->isGranted()) $oDocument->add('content',Context::getLang('thisissecret'));
                $this->setTemplateFile('view_document');
            }
            else
            {
                $this->setTemplateFile('create_document');
            }

            // 스킨에서 사용할 oDocument 변수 세팅
            Context::set('oDocument', $oDocument);

            /** 
             * 사용되는 javascript 필터 추가
             **/
            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');
        
            return new Object();
        }
    }
?>
