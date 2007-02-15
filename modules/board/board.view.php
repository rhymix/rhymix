<?php
    /**
     * @class  boardView
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 View class
     **/

    class boardView extends Module {

        var $search_option = array('title','content','title_content','user_name'); ///< 검색 옵션

        var $skin = "default"; ///< 스킨 이름
        var $list_count = 3; ///< 한 페이지에 나타날 글의 수
        var $page_count = 10; ///< 페이지의 수
        var $category_list = NULL; ///< 카테고리 목록

        var $grant_list = array( 
                'list',
                'view',
                'write_document',
                'write_comment',
                'fileupload',
            );   ///< 권한의 종류를 미리 설정

        var $editor = 'default'; ///< 에디터 종류

        /**
         * @brief 초기화
         **/
        function init() {
            // 카테고리를 사용한다면 카테고리 목록을 구해옴
            if($this->module_info->use_category=='Y') {
                $oDocumentModel = getModel('document');
                $this->category_list = $oDocumentModel->getCategoryList($this->module_srl);
                Context::set('category_list', $this->category_list);
            }

            // 에디터 세팅
            Context::set('editor', $this->editor);
            $editor_path = sprintf("./editor/%s/", $this->editor);
            Context::set('editor_path', $editor_path);
            Context::loadLang($editor_path);

            // 스킨 디렉토리 세팅
            $skin_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            $this->setTemplatePath($skin_path);

            return true;
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
            $oDocumentModel = getModel('document');

            // document_srl이 있다면 해당 글을 구해오자
            if($this->grant->view && $document_srl) {
                $document = $oDocumentModel->getDocument($document_srl);

                // 글이 찾아지지 않으면 무효화
                if(!$document) {
                    Context::set('document_srl','');
                    $document_srl = NULL;
                    unset($document);
                } 
            }

            // 글이 찾아지면 조회수 업데이트 및 기타 등등
            if($document) {

                // 비밀글이고 권한이 없을 경우 인증페이지로
                if($document->is_secret=='Y' && !$document->is_granted) return $this->setTemplateFile('input_password_form');

                // 조회수 업데이트
                if($oDocument->updateReadedCount($document_srl)) $document->readed_count++;

                // 댓글 가져오기
                if($document->comment_count && $document->allow_comment == 'Y') {
                    $oCommentModel = getModel('comment');
                    $comment_list = $oCommentModel->getCommentList($document_srl);
                    Context::set('comment_list', $comment_list);
                }

                // 트랙백 가져오기
                if($document->trackback_count && $document->allow_trackback == 'Y') {
                    $oTrackback = getModule('trackback');
                    $trackback_list = $oTrackback->getTrackbackList($document_srl);
                    Context::set('trackback_list', $trackback_list);
                }

                // 첨부파일 가져오기
                if($document->uploaded_count) {
                    $file_list = $oDocument->getFiles($document_srl);
                    $document->uploaded_list = $file_list;
                }

                Context::set('document', $document);
            }

            // 만약 document_srl은 있는데 page가 없다면 글만 호출된 경우,
            // 그럼 page를 구해서 세팅해주자..
            if($document_srl && !$page) {
                $page = $oDocument->getDocumentPage($document_srl, $this->module_srl, $this->list_count);
                Context::set('page', $page);
            }

            // 검색옵션
            $search_target = Context::get('search_target');
            $keyword = Context::get('keyword');
            if($search_target && $keyword) {
                $keyword = str_replace(' ','%',$keyword);
                switch($search_target) {
                    case 'title' :
                            $search_obj->s_title = $keyword;
                        break;
                    case 'content' :
                            $search_obj->s_content = $keyword;
                        break;
                    case 'title_content' :
                            $search_obj->s_title = $keyword;
                            $search_obj->s_content = $keyword;
                        break;
                    case 'user_name' :
                            $search_obj->s_user_name = $keyword;
                        break;
                }
            }

            // 카테고리
            $category = Context::get('category');
            if($category) $search_obj->category_srl = $category;

            // 목록의 경우 document->getDocumentList 에서 걍 알아서 다 해버리는 구조이다... (아.. 이거 나쁜 버릇인데.. ㅡ.ㅜ 어쩔수 없다)
            $oDocumentModel = getModel('document');
            $output = $oDocumentModel->getDocumentList($this->module_srl, 'list_order', $page, $this->list_count, $this->page_count, $search_obj);

            // 템플릿에 쓰기 위해서 context::set
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

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');

            // document 모듈 객체 생성
            $oDocument = getModule('document');

            // 지정된 글이 없다면 (신규) 새로운 번호를 만든다
            if(!$document_srl) {
                $oDB = &DB::getInstance();
                $document_srl = $oDB->getNextSequence();
                
            // 글의 수정일 경우 원본 글을 가져와서 확인을 한다
            } else {
                $document = $oDocument->getDocument($document_srl);
                if(!$document) {
                    $oDB = &DB::getInstance();
                    $document_srl = $oDB->getNextSequence();
                }  
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
                $oDocument = getModule('document');
                $document = $oDocument->getDocument($document_srl);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$document) return $this->list();

            // 권한이 없는 경우 비밀번호 입력화면으로
            if($document&&!$document->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('document',$document);

            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 댓글의 답글 화면 출력
         **/
        function dispCommentReply() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $parent_srl = Context::get('comment_srl');

            // 지정된 원 댓글이 없다면 오류
            if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oComment = getModule('comment');
            $source_comment = $oComment->getComment($parent_srl);

            // 댓글이 없다면 오류
            if(!$source_comment) return new Object(-1, 'msg_invalid_request');

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
        function dispCommentModify() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // 지정된 댓글이 없다면 오류
            if(!$comment_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oComment = getModule('comment');
            $comment = $oComment->getComment($comment_srl);

            // 댓글이 없다면 오류
            if(!$comment) return new Object(-1, 'msg_invalid_request');

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$_SESSION['own_comment'][$comment_srl]) return $this->setTemplateFile('input_password_form');

            // 필요한 정보들 세팅
            Context::set('document_srl',$document_srl);
            Context::set('comment_srl',$comment_srl);
            Context::set('comment', $comment);

            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글 삭제 화면 출력
         **/
        function dispCommentDelete() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispMessage('msg_not_permitted');

            // 삭제할 댓글번호를 가져온다
            $comment_srl = Context::get('comment_srl');

            // 삭제하려는 댓글가 있는지 확인
            if($comment_srl) {
                $oComment = getModule('comment');
                $comment = $oComment->getComment($comment_srl);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$comment) return $this->list();

            // 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$_SESSION['own_comment'][$comment_srl]) return $this->setTemplateFile('input_password_form');

            Context::set('comment',$comment);

            $this->setTemplateFile('delete_comment_form');
        }

        /**
         * @brief 로그인 폼 출력
         **/
        function dispLogin() {
            if(Context::get('is_logged')) return $this->list();
            $this->setTemplateFile('login_form');
        }

        /**
         * @brief 로그아웃 화면 출력
         **/
        function dispLogout() {
            if(!Context::get('is_logged')) return $this->list();
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
         * @brief 엮인글 삭제 화면 출력
         **/
        function dispTrackbackDelete() {
            // 삭제할 댓글번호를 가져온다
            $trackback_srl = Context::get('trackback_srl');

            // 삭제하려는 댓글가 있는지 확인
            $oTrackback = getModule('trackback');
            $output = $oTrackback->getTrackback($trackback_srl);
            $trackback = $output->data;

            // 삭제하려는 글이 없으면 에러
            if(!$trackback) return $this->list();

            Context::set('trackback',$trackback);

            $this->setTemplateFile('delete_trackback_form');
        }

        /**
         * @brief RSS 출력
         **/
        function dispRss() {
            // 권한 체크
            if(!$this->grant->list) return $this->dispMessage('msg_not_permitted');

            $page = Context::get('page');

            // rss 제목 및 정보등을 추출
            $info->title = Context::getBrowserTitle();
            $info->description = $this->module_info->description;
            $info->language = Context::getLangType();
            $info->date = gmdate("D, d M Y H:i:s");
            $info->link = sprintf("%s?mid=%s", Context::getRequestUri(), Context::get('mid'));

            // 컨텐츠 추출
            $oDocument = getModule('document');
            $output = $oDocument->getDocumentList($this->module_srl, 'update_order', $page, 20, 20, NULL);
            $document_list = $output->data;

            // 출력하고 끝내기
            $oRss = getModule('rss');
            $oRss->printRssDocument($info, $document_list);
            exit();
        }

        /**
         * @brief 게시판 관리 목록 보여줌
         **/
        function dispAdminContent() {
            // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
            $module_srl = Context::get('module_srl');
            if($module_srl) {
                $oModule = getModule('module_manager');
                $module_info = $oModule->getModuleInfoByModuleSrl($module_srl);
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
            $this->setTemplateFile('list');
        }

        /**
         * @brief 게시판의 정보 출력
         **/
        function dispAdminBoardInfo() {
            if(!Context::get('module_srl')) return $this->list();

            // 템플릿 파일 지정
            $this->setTemplateFile('info');
        }

        /**
         * @brief 게시판 추가 폼 출력
         **/
        function dispAdminInsertBoard() {
            // 템플릿 파일 지정
            $this->setTemplateFile('insert_form');
        }

        /**
         * @brief 게시판 삭제 화면 출력
         **/
        function dispAdminDeleteBoard() {
            if(!Context::get('module_srl')) return $this->list();

            $module_info = Context::get('module_info');

            $oDocument = getModule('document');
            $document_count = $oDocument->getDocumentCount($module_info->module_srl);
            $module_info->document_count = $document_count;

            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('delete_form');
        }

        /**
         * @brief 스킨 정보 보여줌
         **/
        function dispAdminSkinInfo() {
            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $module_info = Context::get('module_info');
            $skin = $module_info->skin;

            $oModule = getModule('module_manager');
            $skin_info = $oModule->loadSkinInfo($this->module_path, $skin);

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
            $oDocument = getModule('document');
            $category_list = $oDocument->getCategoryList($module_srl);
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
            $oBoard = getModule('board');
            $grant_list = $oBoard->grant_list;

            // 권한 목록 세팅
            Context::set('grant_list', $grant_list);

            // 권한 그룹의 목록을 가져온다
            $oMember = getModule('member');
            $group_list = $oMember->getGroups();
            Context::set('group_list', $group_list);

            $this->setTemplateFile('grant_list');
        }
    }
?>
