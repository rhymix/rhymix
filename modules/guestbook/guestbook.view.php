<?php
    /**
     * @class  guestbookView
     * @author zero (zero@nzeo.com)
     * @brief  guestbook 모듈의 View class
     * guestbook의 view 클래스는 사용자가 방명록의 목록을 보고 글을 쓰거나 댓글을 쓸수 있게 하는 사용자 부분의 display를 관장한다.
     **/

    class guestbookView extends guestbook {

        /**
         * @brief 초기화
         *
         * 사용자부분의 목록 및 기타 페이지 출력을 위해 스킨 정보라든지 스킨의 템플릿 파일 위치 등을 선언해 놓는다.
         **/
        function init() {
            /**
             * 템플릿에서 사용할 변수를 Context::set()
             * 혹시 사용할 수 있는 module_srl 변수를 설정한다.
             **/
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            /**
             * 현재 방명록 모듈의 정보를 module_info라는 이름으로 템플릿에서 사용할 수 있게 하기 위해 세팅한다
             **/
            Context::set('module_info',$this->module_info);
        
            /**
             * 스킨 정보에서 받는 목록수나 페이지수를 미리 선언해 놓는다
             **/
            $this->list_count = $this->module_info->list_count?$this->module_info->list_count:20;
            $this->page_count = $this->module_info->page_count?$this->module_info->page_count:10;

            /**
             * 모듈정보에서 넘어오는 skin값을 이용하여 최종 출력할 템플릿의 위치를 출력한다.
             * $this->module_path는 ./modules/guestbook/의 값을 가지고 있다
             **/
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            $this->setTemplatePath($template_path);

            /**
             * 방명록 모듈 생성 또는 정보 수정시 open_rss값의 세팅에 따라서 rss_url을 선언해 놓는다.
             * 이 rss_url은 ./common/tpl/common_layout.html에서 application/rss+xml의 href로 지정된다
             **/ 
            if($this->module_info->open_rss != 'N') Context::set('rss_url', getUrl('','mid',$this->mid,'act','rss'));
        }

        /**
         * @brief 목록 및 입력항목 출력
         **/
        function dispGuestbookContent() {
            /**
             * 목록 구현에 필요한 변수들을 가져온다
             * 방명록은 기본적으로 page변수만 있으면 된다
             **/
            $page = Context::get('page');

            $oDocumentModel =  &getModel('document'); ///< getModel, getController, getView 함수를 통해서 간단히 원하는 객체를 생성할 수 있다.

            /**
             * write_form.html을 목록에서도 include를 하게 되는데 write_form.html의 경우 $oDocument라는 선택된 문서의 객체가 필요하다.
             * 목록에서는 수정이 아닌 입력만 있어서 이 $oDocument라는 object를 생성을 해 준다
             **/
            $oDocument = $oDocumentModel->getDocument(0, $this->grant->manager);
            Context::set('oDocument', $oDocument);

            /**
             * 글 작성 권한이 있다면 글쓰기 에디터를 세팅한다
             * write_document는 ./conf/module.xml에 정의되어 있고 관리페이지에서 권한 그룹을 설정한 값이다.
             **/
            if($this->grant->write_document) {
                /**
                 * 에디터에서 사용할 고유 문서 번호를 구해 온다.
                 * ZBXE에서는 모든 고유값을 getNextSequence() 로 구해 올 수 있고 글쓰기(editor) 모듈은 이 고유번호를 바탕으로 동작을 한다.
                 **/
                $document_srl = getNextSequence();

                /**
                 * editor model객체의 getEditor method를 호출하여 세팅한다.
                 * 이 때 여러가지 옵션을 지정하여 다른 에디터 코드를 받을 수 있다.
                 **/
                $oEditorModel = &getModel('editor');  
                $option->primary_key_name = 'document_srl';
                $option->content_key_name = 'content';
                $option->allow_fileupload = false; ///< 파일 업로드 기능을 제한
                $option->enable_autosave = true; ///< 자동 저장 기능을 활성화
                $option->enable_default_component = true; ///< 기본 에디터 컴포넌트의 활성화
                $option->enable_component = true; ///< 추가 에디터 컴포넌트의 활성화
                $option->resizable = false; ///< 글쓰기 폼의 상하 조절 가능하도록 설정
                $option->height = 200; ///< 에디터의 높이 지정
                $editor = $oEditorModel->getEditor($document_srl, $option); ///< 에디터코드를 받음
                Context::set('editor', $editor); ///< 에디터코드를 editor라는 이름으로 세팅.
            }

            /**
             * document 모듈을 이용해서 현재 방명록의 module_srl로 목록을 구한다.
             * 목록을 구할때 필요한 변수를 $args에 세팅후 document.model객체를 생성하고 getDocumentList() method를 호출한다.
             **/
            // 목록을 구하기 위한 옵션
            $args->module_srl = $this->module_srl; ///< 현재 모듈의 module_srl
            $args->page = $page; ///< 페이지
            $args->list_count = $this->list_count; ///< 한페이지에 보여줄 글 수
            $args->page_count = $this->page_count; ///< 페이지 네비게이션에 나타날 페이지의 수
            $args->sort_index = 'list_order'; ///< 목록의 정렬 대상 (list_order, 즉 날짜의 역순을 정렬 대상으로 한다)
            $args->order_type = 'asc'; ///< 정렬 순서 (list_order는 -1부터 -1되어서 저장되는 값이라 asc로 정렬 순서를 정하면 된다)

            /**
             * document model객체를 생성하여 목록을 구한다.
             **/
            $output = $oDocumentModel->getDocumentList($args);

            /**
             * 템플릿에 쓰기 위해서 document_model::getDocumentList() 의 return object에 있는 값들을 세팅
             * ZBXE에서 목록의 경우 5가지의 값으로 결과를 받는다.
             * total_count : 대상의 전체 글 수
             * total_page : 대상의 전체 페이지 수 (list_count, page_count로 계산되어진 값)
             * page : 현재 페이지
             * data : 목록 배열
             * page_navigation : 페이지 네비게이션을 출력하기 위한 object
             **/
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('document_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            /**
             * 템플릿 파일을 지정한다.
             * 이미 template path는 init()에서 정의를 하였다.
             **/
            $this->setTemplateFile('list');
        }

        /**
         * @brief 글 수정 화면 출력
         **/
        function dispGuestbookModify() {
            // 권한 체크
            if(!$this->grant->write_document) return $this->dispGuestbookMessage('msg_not_permitted');

            // GET parameter에서 document_srl을 가져옴
            $document_srl = Context::get('document_srl');

            // document 모듈 객체 생성
            $oDocumentModel = &getModel('document');

            $oDocument = $oDocumentModel->getDocument(0, $this->grant->manager);
            $oDocument->setDocument($document_srl);

            if(!$oDocument->isExists()) Context::set('document_srl','');

            if(!$document_srl) $document_srl = getNextSequence();

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
            $option->height = 600;
            $editor = $oEditorModel->getEditor($document_srl, $option);
            Context::set('editor', $editor);

            $this->setTemplateFile('write_form');
        }


        /**
         * @brief 문서 삭제 화면 출력
         **/
        function dispGuestbookDelete() {
            /**
             * 권한 체크
             * 글쓰기 권한이 없다면 아예 접근이 불가능하도록 해 버린다.
             **/
            if(!$this->grant->write_document) return $this->dispGuestbookMessage('msg_not_permitted');

            /**
             * 삭제할 문서번호를 가져온다
             * 이 문서 번호는 get parmameter에 저장되어 있고 Context 클래스에서 미리 세팅을 해 놓은 상태이다.
             **/
            $document_srl = Context::get('document_srl');

            /**
             * 문서 번호가 없으면 잘못된 접근으로 에러 메세지를 출력한다.
             **/
            if(!$document_srl) return $this->dispGuestbookMessage('msg_invalid_request');

            /**
             * 문서 번호로 문서객체를 구해온다
             **/
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);

            // 대상 문서가 없으면 에러
            if(!$oDocument->isExists()) return $this->dispGuestbookContent();

            /**
             * 권한을 체크한다.
             * 권한 체크는 글쓴 사용자와 현재 로그인한 사용자의 정보가 같거나 최고관리자 일 경우 권한이 있다고 판단하고,
             * 그렇지 않은 경우는 비밀번호 입력 폼을 출력한다.
             **/
            if(!$oDocument->isGranted()) return $this->setTemplateFile('input_password_form');

            // 구해진 문서를 context setting하고 delete_form.html 파일을 템플릿 파일로 지정하여 삭제 폼을 출력한다.
            Context::set('oDocument',$oDocument);

            // delete_from.html 템플릿 파일의 지정
            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 댓글의 답글 화면 출력
         **/
        function dispGuestbookReplyComment() {
            // 댓글 작성 권한을 체크한다.
            if(!$this->grant->write_comment) return $this->dispGuestbookMessage('msg_not_permitted');

            // 댓글의 답글을 출력하기 위해서 문서와 원 댓글의 유효성을 검사하기 위해 변수를 가져온다.
            $document_srl = Context::get('document_srl');
            $parent_srl = Context::get('comment_srl');

            // 지정된 원 댓글이 없다면 오류
            if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $source_comment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$source_comment) return $this->dispGuestbookMessage('msg_invalid_request');

            // 필요한 정보들 세팅
            Context::set('document_srl',$source_comment->document_srl);
            Context::set('parent_srl',$parent_srl);
            Context::set('comment_srl',NULL);
            Context::set('source_comment',$source_comment);

            /**
             * comment_form.html 템플릿 파일을 출력할 파일로 지정
             **/
            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 수정 폼 출력
         **/
        function dispGuestbookModifyComment() {
            // 댓글 작성 권한을 체크한다.
            if(!$this->grant->write_comment) return $this->dispGuestbookMessage('msg_not_permitted');

            // 댓글을 수정하기 위하여 문서와 원 댓글의 유효성을 검사하기 위해 변수를 가져온다.
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // 지정된 댓글이 없다면 오류
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$comment) return $this->dispGuestbookMessage('msg_invalid_request');

            // 문서번호를 context setting한다
            Context::set('document_srl',$comment->document_srl);

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if(!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            // 필요한 정보들 세팅
            Context::set('comment_srl',$comment_srl);
            Context::set('comment', $comment);

            // comment_form 파일을 템플릿 출력 파일로 지정
            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 삭제 화면 출력
         **/
        function dispGuestbookDeleteComment() {
            // 댓글 작성 권한을 체크한다.
            if(!$this->grant->write_comment) return $this->dispGuestbookMessage('msg_not_permitted');

            // 삭제할 댓글번호를 가져온다
            $comment_srl = Context::get('comment_srl');

            // 삭제하려는 댓글이 있는지 확인
            if(!$comment_srl) return $this->dispGuestbookMessage('msg_invalid_request');

            // 해당 댓글을 가져온다.
            $oCommentModel = &getModel('comment');
            $comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

            // 삭제하려는 댓글이 없으면 에러
            if(!$comment) return $this->dispGuestbookContent('msg_invalid_request');

            // 문서 번호를 context setting한다.
            Context::set('document_srl',$comment->document_srl);

            // 권한이 없는 경우 비밀번호 입력화면으로
            if(!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('comment',$comment);

            // delete_comemnt_form.html파일을 출력 파일로 지정한다.
            $this->setTemplateFile('delete_comment_form');
        }

        /**
         * @brief 메세지 출력
         **/
        function dispGuestbookMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

    }
?>
