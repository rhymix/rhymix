<?php
    /**
     * @class  blogView
     * @author zero (zero@nzeo.com)
     * @brief  blog 모듈의 View class
     **/

    class blogView extends blog {

        /**
         * @brief 초기화
         *
         * blog 모듈은 일반 사용과 관리자용으로 나누어진다.\n
         **/
        function init() {
            // 템플릿에서 사용할 변수를 Context::set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);
        
            // 기본 모듈 정보들 설정
            $this->list_count = $this->module_info->list_count?$this->module_info->list_count:1;
            $this->page_count = $this->module_info->page_count?$this->module_info->page_count:10;

            // 카테고리 목록을 가져오고 선택된 카테고리의 값을 설정
            $oDocumentModel = &getModel('document');
            $this->category_list = $oDocumentModel->getCategoryList($this->module_srl);
            Context::set('category_list', $this->category_list);

            // 스킨 경로 구함
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            $this->setTemplatePath($template_path);

            // rss url
            if($this->module_info->open_rss != 'N') Context::set('rss_url', getUrl('','mid',$this->mid,'act','rss'));

            // 레이아웃의 정보를 속이기 위해서  layout_srl을 현 블로그의 module_srl로 입력
            $this->module_info->layout_srl = $this->module_info->module_srl;

            /**
             * 블로그는 자체 레이아웃을 관리하기에 이와 관련된 세팅을 해줌
             **/
            // 레이아웃 경로와 파일 지정 (블로그는 자체 레이아웃을 가지고 있음)
            $this->setLayoutPath($template_path);
            $this->setLayoutFile("layout");

            // 수정된 레이아웃 파일이 있으면 지정
            $edited_layout = sprintf('./files/cache/layout/%d.html', $this->module_info->module_srl);
            if(file_exists($edited_layout)) $this->setEditedLayoutFile($edited_layout);

            // 카테고리 xml 파일 위치 지정
            $this->module_info->category_xml_file = sprintf('%s/files/cache/blog_category/%d.xml.php', getUrl(), $this->module_info->module_srl);

            // 메뉴 등록시 메뉴 정보를 구해옴
            if($this->module_info->menu) {
                foreach($this->module_info->menu as $menu_id => $menu_srl) {
                    $menu_php_file = sprintf("./files/cache/menu/%s.php", $menu_srl);
                    if(file_exists($menu_php_file)) @include($menu_php_file);
                    Context::set($menu_id, $menu);
                }
            }

            // layout_info 변수 설정 
            Context::set('layout_info',$this->module_info);

            // 모듈정보 세팅
            Context::set('module_info',$this->module_info);
        }

        /**
         * @brief 목록 및 선택된 글 출력
         **/
        function dispBlogContent() {
            // 권한 체크
            if(!$this->grant->list) return $this->dispBlogMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $page = Context::get('page');

            // document 객체를 생성. 기본 데이터 구조의 경우 document모듈만 쓰면 만사 해결.. -_-;
            $oDocumentModel = &getModel('document');

            $oDocument = $oDocumentModel->getDocument(0, $this->grant->manager);

            // document_srl이 있다면 해당 글을 구해오자
            if($this->grant->list && $document_srl) {

                // 글을 구함
                $oDocument->setDocument($document_srl);

                // 찾아지지 않았다면 초기화
                if(!$oDocument->isExists()) {
                    unset($document_srl);
                    Context::set('document_srl','',true);
                } else {

                    // 브라우저 타이틀 설정
                    Context::setBrowserTitle($oDocument->getTitleText());

                    // 댓글에디터 설정
                    if($this->grant->write_comment && $oDocument->allowComment() && !$oDocument->isLocked()) $comment_editor[$oDocument->document_srl] = $this->getCommentEditor($oDocument->document_srl, 0, 100);

                    // 조회수 증가
                    $oDocument->updateReadedCount();

                    // 목록수를 1개로 수정 (글이 선택되었을 때만)
                    $this->list_count = 1;

                    // 페이지 변수를 제거하여 직접 구하도록 변경
                    unset($page);
                }

            }

            Context::set('oDocument', $oDocument);

            // 만약 document_srl은 있는데 page가 없다면 글만 호출된 경우 page를 구해서 세팅해주자..
            if($document_srl && !$page) {
                $page = $oDocumentModel->getDocumentPage($document_srl, $this->module_srl, $this->list_count);
                Context::set('page', $page);
            }

            // 목록을 구하기 위한 옵션
            $args->module_srl = $this->module_srl; ///< 현재 모듈의 module_srl
            $args->page = $page; ///< 페이지
            $args->list_count = $this->list_count; ///< 한페이지에 보여줄 글 수
            $args->page_count = $this->page_count; ///< 페이지 네비게이션에 나타날 페이지의 수

            // 검색 옵션
            $args->search_target = trim(Context::get('search_target')); ///< 검색대상
            $args->search_keyword = trim(Context::get('search_keyword')); ///< 검색어

            // 키워드 검색이 아닌 검색일 경우 목록의 수를 40개로 고정
            if($args->search_target && $args->search_keyword) $args->list_count = 40;

            // 키워드 검색의 경우 제목,내용으로 검색 대상 고정
            if($args->search_keyword && !$args->search_target) $args->search_target = "title_content"; 

            // 블로그 카테고리 
            $args->category_srl = (int)Context::get('category');

            $args->sort_index = 'list_order'; ///< 소팅 값

            // 목록 구함, document->getDocumentList 에서 걍 알아서 다 해버리는 구조
            $output = $oDocumentModel->getDocumentList($args, true);

            // 템플릿에 쓰기 위해서 document_model::getDocumentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('document_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 문서 갯수만큼 comment editor 생성
            if(count($output->data)) {
                foreach($output->data as $obj) {
                    $comment_editor[$obj->document_srl] = $this->getCommentEditor($obj->document_srl, 0, 100);
                }
            }

            // 에디터 세팅
            Context::set('comment_editor', $comment_editor);

            // 템플릿에서 사용할 검색옵션 세팅
            $count_search_option = count($this->search_option);
            for($i=0;$i<$count_search_option;$i++) {
                $search_option[$this->search_option[$i]] = Context::getLang($this->search_option[$i]);
            }
            Context::set('search_option', $search_option);

            // 블로그의 코멘트는 ajax로 호출되기에 미리 css, js파일을 import
            Context::addJsFile('./modules/editor/tpl/js/editor.js');
            Context::addCSSFile('./modules/editor/tpl/css/editor.css');

            $this->setTemplateFile('list');
        }
        
        /**
         * @brief 글 작성 화면 출력
         **/
        function dispBlogWrite() {
            // 권한 체크
            if(!$this->grant->write_document) return $this->dispBlogMessage('msg_not_permitted');

            // GET parameter에서 document_srl을 가져옴
            $document_srl = Context::get('document_srl');

            // document 모듈 객체 생성
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl, $this->grant->manager);

            // 지정된 글이 없다면 (신규) 새로운 번호를 만든다
            if(!$oDocument->isExists()) {
                $document_srl = getNextSequence();
                Context::set('document_srl','');
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

            $this->setTemplateFile('write_form');
        }

        /**
         * @brief 문서 삭제 화면 출력
         **/
        function dispBlogDelete() {
            // 권한 체크
            if(!$this->grant->write_document) return $this->dispBlogMessage('msg_not_permitted');

            // 삭제할 문서번호를 가져온다
            $document_srl = Context::get('document_srl');

            // 지정된 글이 있는지 확인
            if($document_srl) {
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($document_srl);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$oDocument->isExists()) return $this->dispBlogContent();

            // 권한이 없는 경우 비밀번호 입력화면으로
            if(!$oDocument->isGranted()) return $this->setTemplateFile('input_password_form');

            Context::set('oDocument',$oDocument);

            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 댓글의 답글 화면 출력
         **/
        function dispBlogReplyComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispBlogMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $parent_srl = Context::get('comment_srl');

            // 지정된 원 댓글이 없다면 오류
            if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $source_comment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$source_comment) return $this->dispBlogMessage('msg_invalid_request');

            // 필요한 정보들 세팅
            Context::set('document_srl',$source_comment->document_srl);
            Context::set('parent_srl',$parent_srl);
            Context::set('comment_srl',NULL);
            Context::set('source_comment',$source_comment);

            // 댓글 에디터 세팅 
            Context::set('editor', $this->getCommentEditor($document_srl, 0, 400));

            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 수정 폼 출력
         **/
        function dispBlogModifyComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispBlogMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // 지정된 댓글이 없다면 오류
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$comment) return $this->dispBlogMessage('msg_invalid_request');

            Context::set('document_srl',$comment->document_srl);

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            // 필요한 정보들 세팅
            Context::set('comment_srl',$comment_srl);
            Context::set('comment', $comment);

            // 댓글 에디터 세팅 
            Context::set('editor', $this->getCommentEditor($document_srl, $comment_srl, 400));

            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 삭제 화면 출력
         **/
        function dispBlogDeleteComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispBlogMessage('msg_not_permitted');

            // 삭제할 댓글번호를 가져온다
            $comment_srl = Context::get('comment_srl');

            // 삭제하려는 댓글가 있는지 확인
            if($comment_srl) {
                $oCommentModel = &getModel('comment');
                $comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$comment) return $this->dispBlogContent();

            Context::set('document_srl',$comment->document_srl);

            // 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('comment',$comment);

            $this->setTemplateFile('delete_comment_form');
        }

        /**
         * @brief 엮인글 삭제 화면 출력
         **/
        function dispBlogDeleteTrackback() {
            // 삭제할 댓글번호를 가져온다
            $trackback_srl = Context::get('trackback_srl');

            // 삭제하려는 댓글가 있는지 확인
            $oTrackbackModel = &getModel('trackback');
            $output = $oTrackbackModel->getTrackback($trackback_srl);
            $trackback = $output->data;

            // 삭제하려는 글이 없으면 에러
            if(!$trackback) return $this->dispBlogContent();

            Context::set('trackback',$trackback);

            $this->setTemplateFile('delete_trackback_form');
        }

        /**
         * @brief 메세지 출력
         **/
        function dispBlogMessage($msg_code) {
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
        function getCommentEditor($editor_sequence, $comment_srl=0, $height = 100) {
            $oEditorModel = &getModel('editor');
            $option->editor_sequence = $editor_sequence;
            $option->primary_key_name = 'comment_srl';
            $option->content_key_name = 'content';
            $option->allow_fileupload = $this->grant->comment_fileupload;
            $option->enable_autosave = false;
            $option->enable_default_component = true;
            $option->enable_component = true;
            $option->resizable = true;
            $option->height = $height;
            $comment_editor = $oEditorModel->getEditor($comment_srl, $option);
            return $comment_editor;
        }

    }
?>
