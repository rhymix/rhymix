<?php
    /**
     * @class  boardView
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 View class
     **/

    class boardView extends board {

        /**
         * @brief 초기화
         **/
        function init() {
            // 카테고리를 사용하는지 확인후 사용시 카테고리 목록을 구해와서 Context에 세팅
            if($this->module_info->use_category=='Y') {
                $oDocumentModel = &getModel('document');
                $this->category_list = $oDocumentModel->getCategoryList($this->module_srl);
                Context::set('category_list', $this->category_list);
            }

            // 에디터 세팅
            Context::set('editor', $this->editor);
            $editor_path = sprintf("./editor/%s/", $this->editor);
            Context::set('editor_path', $editor_path);
            Context::loadLang($editor_path);

            // act 값에 dispAdmin이 있으면 tpl.admin 으로 템플릿 경로 지정
            if(substr($this->act,0,9)=='dispAdmin') {
                $template_path = sprintf("%stpl.admin/",$this->module_path);
            } else {
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->skin);
            }
            $this->setTemplatePath($template_path);

            // 몇가지 템플릿에서 사용할 변수를 Context::set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);
        }

        /**
         * @brief 목록 및 선택된 글 출력
         **/
        function dispContent() {
            // 권한 체크
            if(!$this->grant->list) return $this->dispMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $page = Context::get('page');

            // document 객체를 생성. 기본 데이터 구조의 경우 document모듈만 쓰면 만사 해결.. -_-;
            $oDocumentModel = &getModel('document');

            // document_srl이 있다면 해당 글을 구해오자
            if($this->grant->view && $document_srl) {

                $document = $oDocumentModel->getDocument($document_srl, $this->grant->manager, true);
                if($document->document_srl != $document_srl) {
                    unset($document);
                    unset($document_srl);
                    Context::set('document_srl','',true);
                }

                Context::set('document', $document);
            }

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

            $args->search_target = Context::get('search_target'); ///< 검색 대상 (title, contents...)
            $args->search_keyword = Context::get('search_keyword'); ///< 검색어
            if($this->module_info->use_category=='Y') $args->category_srl = Context::get('category'); ///< 카테고리 사용시 선택된 카테고리

            $args->sort_index = 'list_order'; ///< 소팅 값

            // 목록 구함, document->getDocumentList 에서 걍 알아서 다 해버리는 구조이다... (아.. 이거 나쁜 버릇인데.. ㅡ.ㅜ 어쩔수 없다)
            $output = $oDocumentModel->getDocumentList($args);

            // 템플릿에 쓰기 위해서 document_model::getDocumentList() 의 return object에 있는 값들을 세팅
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('document_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿에서 사용할 검색옵션 세팅
            $count_search_option = count($this->search_option);
            for($i=0;$i<$count_search_option;$i++) {
                $search_option[$this->search_option[$i]] = Context::getLang($this->search_option[$i]);
            }
            Context::set('search_option', $search_option);

            $this->setTemplateFile('list');
        }
        
        /**
         * @brief 글 작성 화면 출력
         **/
        function dispWrite() {
            // 권한 체크
            if(!$this->grant->write_document) return $this->dispMessage('msg_not_permitted');

            // GET parameter에서 document_srl을 가져옴
            $document_srl = Context::get('document_srl');

            // document 모듈 객체 생성
            $oDocumentModel = &getModel('document');

            // 지정된 글이 없다면 (신규) 새로운 번호를 만든다
            if($document_srl) {
                $document = $oDocumentModel->getDocument($document_srl, $this->grant->manager);
                if(!$document) {
                   unset($document_srl);
                   Context::set('document_srl','');
                }
            }

            // 문서 번호가 없으면 새로운 값을 받아옴
            if(!$document_srl) {
                $oDB = &DB::getInstance();
                $document_srl = $oDB->getNextSequence();
            }

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if($document&&!$document->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('document_srl',$document_srl);
            Context::set('document', $document);

            $this->setTemplateFile('write_form');
        }

        /**
         * @brief 문서 삭제 화면 출력
         **/
        function dispDelete() {
            // 권한 체크
            if(!$this->grant->write_document) return $this->dispMessage('msg_not_permitted');

            // 삭제할 문서번호를 가져온다
            $document_srl = Context::get('document_srl');

            // 지정된 글이 있는지 확인
            if($document_srl) {
                $oDocumentModel = &getModel('document');
                $document = $oDocumentModel->getDocument($document_srl);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$document) return $this->dispContent();

            // 권한이 없는 경우 비밀번호 입력화면으로
            if($document&&!$document->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('document',$document);

            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 댓글의 답글 화면 출력
         **/
        function dispReplyComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $parent_srl = Context::get('comment_srl');

            // 지정된 원 댓글이 없다면 오류
            if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $source_comment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$source_comment) return $this->dispMessage('msg_invalid_request');

            // 필요한 정보들 세팅
            Context::set('document_srl',$document_srl);
            Context::set('parent_srl',$parent_srl);
            Context::set('comment_srl',NULL);
            Context::set('source_comment',$source_comment);

            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 수정 폼 출력
         **/
        function dispModifyComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // 지정된 댓글이 없다면 오류
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$comment) return $this->dispMessage('msg_invalid_request');

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            // 필요한 정보들 세팅
            Context::set('document_srl',$document_srl);
            Context::set('comment_srl',$comment_srl);
            Context::set('comment', $comment);

            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 삭제 화면 출력
         **/
        function dispDeleteComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispMessage('msg_not_permitted');

            // 삭제할 댓글번호를 가져온다
            $comment_srl = Context::get('comment_srl');

            // 삭제하려는 댓글가 있는지 확인
            if($comment_srl) {
                $oCommentModel = &getModel('comment');
                $comment = $oCommentModel->getComment($comment_srl, $this->grant->manager);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$comment) return $this->dispContent();

            // 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('comment',$comment);

            $this->setTemplateFile('delete_comment_form');
        }

        /**
         * @brief 엮인글 삭제 화면 출력
         **/
        function dispDeleteTrackback() {
            // 삭제할 댓글번호를 가져온다
            $trackback_srl = Context::get('trackback_srl');

            // 삭제하려는 댓글가 있는지 확인
            $oTrackbackModel = &getModel('trackback');
            $output = $oTrackbackModel->getTrackback($trackback_srl);
            $trackback = $output->data;

            // 삭제하려는 글이 없으면 에러
            if(!$trackback) return $this->dispContent();

            Context::set('trackback',$trackback);

            $this->setTemplateFile('delete_trackback_form');
        }

        /**
         * @brief 로그인 폼 출력
         **/
        function dispLogin() {
            if(Context::get('is_logged')) return $this->dispContent();
            $this->setTemplateFile('login_form');
        }

        /**
         * @brief 로그아웃 화면 출력
         **/
        function dispLogout() {
            if(!Context::get('is_logged')) return $this->dispContent();
            $this->setTemplateFile('logout');
        }


        /**
         * @brief 메세지 출력
         **/
        function dispMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

        /**
         * @brief RSS 출력
         **/
        function dispRss() {
            // 권한 체크
            if(!$this->grant->list) return $this->dispMessage('msg_not_permitted');

            // 컨텐츠 추출
            $args->module_srl = $this->module_srl; ///< 현재 모듈의 module_srl
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = $this->list_count; ///< 한페이지에 보여줄 글 수
            $args->page_count = $this->page_count; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->search_target = Context::get('search_target'); ///< 검색 대상 (title, contents...)
            $args->search_keyword = Context::get('search_keyword'); ///< 검색어
            if($this->module_info->use_category=='Y') $args->category_srl = Context::get('category'); ///< 카테고리 사용시 선택된 카테고리

            $args->sort_index = 'list_order'; ///< 소팅 값

            $oDocumentModel = &getModel('document');
            $output = $oDocumentModel->getDocumentList($args);
            $document_list = $output->data;

            // rss 제목 및 정보등을 추출
            $info->title = Context::getBrowserTitle();
            $info->description = $this->module_info->description;
            $info->language = Context::getLangType();
            $info->date = gmdate("D, d M Y H:i:s");
            $info->link = sprintf("%s?mid=%s", Context::getRequestUri(), Context::get('mid'));
            $info->total_count = $output->total_count;

            // 출력하고 끝내기
            $oRssView = &getView('rss');
            $oRssView->dispRss($info, $document_list);
        }

        /**
         * @brief 게시판 관리 목록 보여줌
         **/
        function dispAdminContent() {
            // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
            $module_srl = Context::get('module_srl');

            if($module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'list';
                } else Context::set('module_info',$module_info);
            }

            // 등록된 board 모듈을 불러와 세팅
            $oDB = &DB::getInstance();
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $output = $oDB->executeQuery('board.getBoardList', $args);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('board_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        /**
         * @brief 선택된 게시판의 정보 출력
         **/
        function dispAdminBoardInfo() {
            // module_srl 값이 없다면 그냥 index 페이지를 보여줌
            if(!Context::get('module_srl')) return $this->dispAdminContent();

            // 요청받은 모듈의 정보를 구함
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

            // template에서 사용하기 위해 설정
            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('board_info');
        }

        /**
         * @brief 게시판 추가 폼 출력
         **/
        function dispAdminInsertBoard() {
            // 템플릿 파일 지정
            $this->setTemplateFile('board_insert');
        }

        /**
         * @brief 게시판 삭제 화면 출력
         **/
        function dispAdminDeleteBoard() {
            if(!Context::get('module_srl')) return $this->dispContent();

            $module_info = Context::get('module_info');

            $oDocumentModel = &getModel('document');
            $document_count = $oDocumentModel->getDocumentCount($module_info->module_srl);
            $module_info->document_count = $document_count;

            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('board_delete');
        }

        /**
         * @brief 스킨 정보 보여줌
         **/
        function dispAdminSkinInfo() {
            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $module_info = Context::get('module_info');
            $skin = $module_info->skin;

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            // skin_info에 extra_vars 값을 지정
            if(count($skin_info->extra_vars)) {
                foreach($skin_info->extra_vars as $key => $val) {
                    $name = $val->name;
                    $type = $val->type;
                    $value = $module_info->{$name};
                    if($type=="checkbox"&&!$value) $value = array();
                    $skin_info->extra_vars[$key]->value= $value;
                }
            }

            Context::set('skin_info', $skin_info);
            $this->setTemplateFile('skin_info');
        }

        /**
         * @brief 카테고리의 정보 출력
         **/
        function dispAdminCategoryInfo() {
            $module_srl = Context::get('module_srl');

            // 카테고리의 목록을 구해옴
            $oDocumentModel = &getModel('document');
            $category_list = $oDocumentModel->getCategoryList($module_srl);
            Context::set('category_list', $category_list);

            // 수정하려는 카테고리가 있다면해당 카테고리의 정보를 가져옴
            $category_srl = Context::get('category_srl');
            if($category_srl) {
                $selected_category = $oDocument->getCategory($category_srl);
                if(!$selected_category) Context::set('category_srl','');
            else Context::set('selected_category',$selected_category);
                $this->setTemplateFile('category_update_form');
            } else {
                $this->setTemplateFile('category_list');
            }
        }

        /**
         * @brief 권한 목록 출력
         **/
        function dispAdminGrantInfo() {
            $module_srl = Context::get('module_srl');

            // 현 모듈의 권한 목록을 가져옴
            $oBoardModel = &getModel('board');
            $grant_list = $oBoardModel->grant_list;

            // 권한 목록 세팅
            Context::set('grant_list', $grant_list);

            // 권한 그룹의 목록을 가져온다
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            $this->setTemplateFile('grant_list');
        }
    }
?>
