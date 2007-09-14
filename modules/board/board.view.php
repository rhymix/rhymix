<?php
    /**
     * @class  boardView
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 View class
     **/

    class boardView extends board {

        /**
         * @brief 초기화
         *
         * board 모듈은 일반 사용과 관리자용으로 나누어진다.\n
         **/
        function init() {
            // 카테고리를 사용하는지 확인후 사용시 카테고리 목록을 구해와서 Context에 세팅
            if($this->module_info->use_category=='Y') {
                $oDocumentModel = &getModel('document');
                $this->category_list = $oDocumentModel->getCategoryList($this->module_srl);
                Context::set('category_list', $this->category_list);
            }

            // 템플릿에서 사용할 변수를 Context::set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            Context::set('module_info',$this->module_info);
        
            // 기본 모듈 정보들 설정
            $this->list_count = $this->module_info->list_count?$this->module_info->list_count:20;
            $this->page_count = $this->module_info->page_count?$this->module_info->page_count:10;

            // 스킨 템플릿 경로 지정
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            $this->setTemplatePath($template_path);

            // rss url
            if($this->module_info->open_rss != 'N') Context::set('rss_url', getUrl('','mid',$this->mid,'act','rss'));
        }

        /**
         * @brief 목록 및 선택된 글 출력
         **/
        function dispBoardContent() {
            // 권한 체크
            if(!$this->grant->list) return $this->dispBoardMessage('msg_not_permitted');

            // 템플릿에서 사용할 검색옵션 세팅
            $count_search_option = count($this->search_option);
            for($i=0;$i<$count_search_option;$i++) {
                $search_option[$this->search_option[$i]] = Context::getLang($this->search_option[$i]);
            }

            // 확장변수에서도 검색이 설정되어 있는지 확인
            for($i=1;$i<=20;$i++) {
                $ex_name = $this->module_info->extra_vars[$i]->name;
                $ex_search = $this->module_info->extra_vars[$i]->search;
                if($ex_name && $ex_search == 'Y') {
                    $search_option['extra_vars'.$i] = $ex_name;
                }
            }
            Context::set('search_option', $search_option);

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $page = Context::get('page');

            // document 객체를 생성. 기본 데이터 구조의 경우 document모듈만 쓰면 만사 해결.. -_-;
            $oDocumentModel = &getModel('document');

            // 선택된 문서 표시를 위한 객체 생성 
            $oDocument = $oDocumentModel->getDocument(0, $this->grant->manager);

            // document_srl이 있다면 해당 글을 구해와서 $oDocument로 세팅
            if($document_srl) {

                // 글을 구함
                $oDocument->setDocument($document_srl);
                if($this->grant->manager) $oDocument->setGrant();

                // 글이 존재하지 않으면 그냥 무시하고 글이 존재 하지 않는다는 오류 메세지 출력
                if(!$oDocument->isExists()) {

                    unset($document_srl);

                    Context::set('document_srl','',true);

                    $this->alertMessage('msg_not_founded');

                } else {

                    // 글 보기 권한을 체크해서 권한이 없으면 오류 메세지 출력하도록 처리
                    if(!$this->grant->view) {

                        $oDocument = null;
                        $oDocument = $oDocumentModel->getDocument(0, $this->grant->manager);

                        Context::set('document_srl','',true);

                        $this->alertMessage('msg_not_permitted');


                    } else {
                        // 브라우저 타이틀 설정
                        Context::setBrowserTitle($oDocument->getTitleText());

                        // 조회수 증가
                        $oDocument->updateReadedCount();
                    }
                }
            }
            Context::set('oDocument', $oDocument);

            // 댓글에디터 설정
            $this->setCommentEditor(0, 100);

            // 만약 document_srl은 있는데 page가 없다면 글만 호출된 경우 page를 구해서 세팅해주자..
            if($document_srl && !$page && ($oDocument->isExists()&&!$oDocument->isNotice()) && !Context::get('category') && !Context::get('search_keyword')) {
                $page = $oDocumentModel->getDocumentPage($document_srl, $this->module_srl, $this->list_count);
                Context::set('page', $page);
            }

            // 목록을 구하기 위한 옵션
            $args->module_srl = $this->module_srl; ///< 현재 모듈의 module_srl
            $args->page = $page; ///< 페이지
            $args->list_count = $this->list_count; ///< 한페이지에 보여줄 글 수
            $args->page_count = $this->page_count; ///< 페이지 네비게이션에 나타날 페이지의 수

            // 검색 옵션
            $args->search_target = Context::get('search_target'); ///< 검색 대상 (title, contents...)
            $args->search_keyword = Context::get('search_keyword'); ///< 검색어
            if($this->module_info->use_category=='Y') $args->category_srl = Context::get('category'); ///< 카테고리 사용시 선택된 카테고리
            $args->sort_index = Context::get('sort_index');
            $args->order_type = Context::get('order_type');

            // 스킨에서 설정한 기본 정렬 대상을 구함
            if(!$args->sort_index) {
                switch($this->module_info->order_target) {
                    case "updated" :
                            $args->sort_index = "update_order";
                            $args->order_type = "asc";
                        break;
                    default :
                            $args->sort_index = "list_order";
                            $args->order_type = "asc";
                        break;
                }
            }

            // 목록 구함, document->getDocumentList 에서 걍 알아서 다 해버리는 구조이다... (아.. 이거 나쁜 버릇인데.. ㅡ.ㅜ 어쩔수 없다)
            $output = $oDocumentModel->getDocumentList($args);

            // 템플릿에 쓰기 위해서 document_model::getDocumentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('document_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 관리자일 경우 체크한 문서들의 목록을 세팅
            if($this->grant->is_admin) {
                Context::set('check_list',$_SESSION['document_management'][$this->module_srl]);
            }

            $this->setTemplateFile('list');
        }
        
        /**
         * @brief 글 작성 화면 출력
         **/
        function dispBoardWrite() {
            // 권한 체크
            if(!$this->grant->write_document) return $this->dispBoardMessage('msg_not_permitted');

            // GET parameter에서 document_srl을 가져옴
            $document_srl = Context::get('document_srl');

            // document 모듈 객체 생성
            $oDocumentModel = &getModel('document');

            $oDocument = $oDocumentModel->getDocument(0, $this->grant->manager);
            $oDocument->setDocument($document_srl);

            if(!$oDocument->isExists()) {
                $document_srl = getNextSequence();
                Context::set('document_srl',$document_srl);
            }

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if($oDocument->isExists()&&!$oDocument->isGranted()) return $this->setTemplateFile('input_password_form');

            Context::set('document_srl',$document_srl);
            Context::set('oDocument', $oDocument);

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $option->primary_key_name = 'document_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = $this->grant->fileupload;
            $option->enable_autosave = true;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = true;
            $option->height = 400;
            $editor = $oEditorModel->getEditor($document_srl, $option);
            Context::set('editor', $editor);

            // 확장변수처리를 위해 xml_js_filter를 직접 header에 적용
            $oDocumentController = &getController('document');
            $oDocumentController->addXmlJsFilter($this->module_info);

            $this->setTemplateFile('write_form');
        }

        /**
         * @brief 문서 삭제 화면 출력
         **/
        function dispBoardDelete() {
            // 권한 체크
            if(!$this->grant->write_document) return $this->dispBoardMessage('msg_not_permitted');

            // 삭제할 문서번호를 가져온다
            $document_srl = Context::get('document_srl');

            // 지정된 글이 있는지 확인
            if($document_srl) {
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($document_srl);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$oDocument->isExists()) return $this->dispBoardContent();

            // 권한이 없는 경우 비밀번호 입력화면으로
            if(!$oDocument->isGranted()) return $this->setTemplateFile('input_password_form');

            Context::set('oDocument',$oDocument);

            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 댓글의 답글 화면 출력
         **/
        function dispBoardReplyComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispBoardMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $parent_srl = Context::get('comment_srl');

            // 지정된 원 댓글이 없다면 오류
            if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $source_comment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$source_comment) return $this->dispBoardMessage('msg_invalid_request');

            // 필요한 정보들 세팅
            Context::set('document_srl',$document_srl);
            Context::set('parent_srl',$parent_srl);
            Context::set('comment_srl',NULL);
            Context::set('source_comment',$source_comment);

            // 댓글 에디터 세팅 
            $this->setCommentEditor(0, 400);

            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 수정 폼 출력
         **/
        function dispBoardModifyComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispBoardMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // 지정된 댓글이 없다면 오류
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$comment) return $this->dispBoardMessage('msg_invalid_request');

            Context::set('document_srl',$comment->document_srl);

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            // 필요한 정보들 세팅
            Context::set('comment_srl',$comment_srl);
            Context::set('comment', $comment);

            // 댓글 에디터 세팅 
            $this->setCommentEditor($comment_srl, 300);

            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 삭제 화면 출력
         **/
        function dispBoardDeleteComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispBoardMessage('msg_not_permitted');

            // 삭제할 댓글번호를 가져온다
            $comment_srl = Context::get('comment_srl');

            // 삭제하려는 댓글가 있는지 확인
            if($comment_srl) {
                $oCommentModel = &getModel('comment');
                $comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$comment) return $this->dispBoardContent();

            Context::set('document_srl',$comment->document_srl);

            // 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('comment',$comment);

            $this->setTemplateFile('delete_comment_form');
        }

        /**
         * @brief 엮인글 삭제 화면 출력
         **/
        function dispBoardDeleteTrackback() {
            // 삭제할 댓글번호를 가져온다
            $trackback_srl = Context::get('trackback_srl');

            // 삭제하려는 댓글가 있는지 확인
            $oTrackbackModel = &getModel('trackback');
            $output = $oTrackbackModel->getTrackback($trackback_srl);
            $trackback = $output->data;

            // 삭제하려는 글이 없으면 에러
            if(!$trackback) return $this->dispBoardContent();

            Context::set('trackback',$trackback);

            $this->setTemplateFile('delete_trackback_form');
        }

        /**
         * @brief 메세지 출력
         **/
        function dispBoardMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

        /**
         * @brief 댓글의 editor 를 세팅
         * 댓글의 경우 수정하는 경우가 아니라면 고유값이 없음.\n
         * 따라서 고유값이 없을 경우 고유값을 가져와서 지정해 주어야 함
         **/
        function setCommentEditor($comment_srl = 0, $height = 100) {
            Context::set('comment_srl', $comment_srl);

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $option->primary_key_name = 'comment_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = $this->grant->comment_fileupload;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = true;
            $option->height = $height;
            $comment_editor = $oEditorModel->getEditor($comment_srl, $option);
            Context::set('comment_editor', $comment_editor);
        }

        /**
         * @brief 오류메세지를 system alert로 출력하는 method
         * 특별한 오류를 알려주어야 하는데 별도의 디자인까지는 필요 없을 경우 페이지를 모두 그린후에
         * 오류를 출력하도록 함
         **/
        function alertMessage($message) {
            $script =  sprintf('<script type="text/javascript"> xAddEventListener(window,"load", function() { alert("%s"); } );</script>', Context::getLang($message));
            Context::addHtmlHeader( $script );
        }

    }
?>
