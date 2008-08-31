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
            /**
             * 스킨등에서 사용될 module_srl이나 module_info등을 context set
             **/
            // 템플릿에서 사용할 변수를 Context::set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            // 현재 호출된 게시판의 모듈 정보를 module_info 라는 이름으로 context setting
            Context::set('module_info',$this->module_info);
        
            // 기본 모듈 정보들 설정 (list_count, page_count는 게시판 모듈 전용 정보이고 기본 값에 대한 처리를 함)
            if($this->module_info->list_count) $this->list_count = $this->module_info->list_count;
            if($this->module_info->search_list_count) $this->search_list_count = $this->module_info->search_list_count;
            if($this->module_info->page_count) $this->page_count = $this->module_info->page_count;

            // 일반 목록에서 공지사항을 제외하는 기능의 체크
            if($this->module_info->except_notice == 'N') $this->except_notice = false; 
            else $this->except_notice = true;

            // 상담 기능 체크. 현재 게시판의 관리자이면 상담기능을 off시킴
            if($this->module_info->consultation == 'Y' && !$this->grant->manager) {
                $this->consultation = true; 

                // 현재 사용자가 비로그인 사용자라면 글쓰기/댓글쓰기/목록보기/글보기 권한을 제거함
                if(!Context::get('is_logged')) $this->grant->list = $this->grant->write_document = $this->grant->write_comment = $this->grant->view = false;
            } else {
                $this->consultation = false;
            }

            /**
             * 스킨 경로를 미리 template_path 라는 변수로 설정함
             **/
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);

            // 만약 스킨 경로가 없다면 xe_board로 변경
            if(!is_dir($template_path)) {
                $this->module_info->skin = 'xe_board';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }

            $this->setTemplatePath($template_path);
        }

        /**
         * @brief 목록 및 선택된 글 출력
         **/
        function dispBoardContent() {
            /**
             * 목록보기 권한 체크 (모든 권한은 ModuleObject에서 xml 정보와 module_info의 grant 값을 비교하여 미리 설정하여 놓음)
             **/
            if(!$this->grant->list) return $this->dispBoardMessage('msg_not_permitted');

            /**
             * 카테고리를 사용하는지 확인후 사용시 카테고리 목록을 구해와서 Context에 세팅
             **/
            if($this->module_info->use_category=='Y') {
                $oDocumentModel = &getModel('document');
                Context::set('category_list', $oDocumentModel->getCategoryList($this->module_srl));
            }

            /**
             * 목록이 노출될때 같이 나오는 검색 옵션을 정리하여 스킨에서 쓸 수 있도록 context set
             **/
            // 템플릿에서 사용할 검색옵션 세팅 (검색옵션 key값은 미리 선언되어 있는데 이에 대한 언어별 변경을 함)
            foreach($this->search_option as $opt) $search_option[$opt] = Context::getLang($opt);

            // 모듈정보를 확인하여 확장변수에서도 검색이 설정되어 있는지 확인
            for($i=1;$i<=20;$i++) {
                $ex_name = trim($this->module_info->extra_vars[$i]->name);
                if(!$ex_name) continue;

                if($this->module_info->extra_vars[$i]->search == 'Y') $search_option['extra_vars'.$i] = $ex_name;
            }
            Context::set('search_option', $search_option);

            /**
             * 게시글 목록을 추출함
             **/

            // 목록 구현에 필요한 변수들을 가져온다
            $document_srl = Context::get('document_srl');
            $page = Context::get('page');

            // document model 객체를 생성
            $oDocumentModel = &getModel('document');

            // 혹시 선택된 문서가 있다면 해당 문서에 대한 객체를 생성함 (일단 빈객체를 만드는 것은 선택된 글이 없을때 스킨에서 object 오류발생하는 것을 막기 위함)
            $oDocument = $oDocumentModel->getDocument(0);

            // document_srl이 있다면 해당 글을 구해와서 $oDocument로 세팅
            if($document_srl) {

                // 글에 대한 정보를 구함
                $oDocument->setDocument($document_srl);

                // 상담기능이 사용되고 공지사항이 아니고 사용자의 글도 아니면 무시
                if($oDocument->isExists() && $this->consultation && !$oDocument->isNotice()) {
                    $logged_info = Context::get('logged_info');
                    if($oDocument->get('member_srl')!=$logged_info->member_srl) $oDocument = new DocumentItem();
                }

                // 글이 존재하지 않으면 글이 존재 하지 않는다는 오류 메세지 출력
                if(!$oDocument->isExists()) {

                    unset($document_srl);

                    Context::set('document_srl','',true);

                    $this->alertMessage('msg_not_founded');

                // 글이 존재하면 글 보기 권한에 대한 확인과 조회수증가/ 브라우저 타이틀의 설정을 함
                } else {

                    // 글과 요청된 모듈이 다르다면 오류 표시
                    if($oDocument->get('module_srl')!=Context::get('module_srl') ) return $this->stop('msg_invalid_request');

                    // 관리 권한이 있다면 권한을 부여
                    if($this->grant->manager) $oDocument->setGrant();

                    // 글 보기 권한을 체크해서 권한이 없으면 오류 메세지 출력하도록 처리
                    if(!$this->grant->view && !$oDocument->isGranted()) {
                        $oDocument = null;
                        $oDocument = $oDocumentModel->getDocument(0);

                        Context::set('document_srl','',true);

                        $this->alertMessage('msg_not_permitted');
                    } else {
                        // 브라우저 타이틀에 글의 제목을 추가
                        Context::addBrowserTitle($oDocument->getTitleText());

                        // 조회수 증가 (비밀글일 경우 권한 체크)
                        if(!$oDocument->isSecret() || $oDocument->isGranted()) $oDocument->updateReadedCount();
                    }
                }
            }

            // 스킨에서 사용하기 위해 context set
            Context::set('oDocument', $oDocument);

            // 공지사항 목록을 구해서 context set (공지사항을 매페이지 제일 상단에 위치하기 위해서)
            $args->module_srl = $this->module_srl; ///< 현재 모듈의 module_srl

            $notice_output = $oDocumentModel->getNoticeList($args);
            Context::set('notice_list', $notice_output->data);

            // 목록을 구하기 위한 대상 모듈/ 페이지 수/ 목록 수/ 페이지 목록 수에 대한 옵션 설정
            $args->page = $page; ///< 페이지
            $args->list_count = $this->list_count; ///< 한페이지에 보여줄 글 수
            $args->page_count = $this->page_count; ///< 페이지 네비게이션에 나타날 페이지의 수

            // 검색과 정렬을 위한 변수 설정
            $args->search_target = Context::get('search_target'); ///< 검색 대상 (title, contents...)
            $args->search_keyword = Context::get('search_keyword'); ///< 검색어
            if($this->module_info->use_category=='Y') $args->category_srl = Context::get('category'); ///< 카테고리 사용시 선택된 카테고리

            $args->sort_index = Context::get('sort_index');
            $args->order_type = Context::get('order_type');

            // 지정된 정렬값이 없다면 스킨에서 설정한 정렬 값을 이용함
            if(!in_array($args->sort_index, $this->order_target)) $args->sort_index = $this->module_info->order_target?$this->module_info->order_target:'list_order';
            if(!in_array($args->order_type, array('asc','desc'))) $args->order_type = $this->module_info->order_type?$this->module_info->order_type:'asc';

            // 특정 문서의 permalink로 직접 접속할 경우 page값을 직접 구함
            if(count($_GET)==1 && isset($_GET['document_srl']) && $oDocument->isExists() && !$oDocument->isNotice()) {
                $page = $oDocumentModel->getDocumentPage($oDocument, $args);
                Context::set('page', $page);
                $args->page = $page;
            }

            // 만약 카테고리가 있거나 검색어가 있으면list_count를 search_list_count 로 이용
            if($args->category_srl || $args->search_keyword) $args->list_count = $this->search_list_count;

            // 상담 기능이 on되어 있으면 현재 로그인 사용자의 글만 나타나도록 옵션 변경
            if($this->consultation) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            }

            // 일반 글을 구해서 context set
            $output = $oDocumentModel->getDocumentList($args, $this->except_notice);
            Context::set('document_list', $output->data);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

            // template_file을 list.html로 지정
            $this->setTemplateFile('list');
        }

        /**
         * @brief 태그 목록 모두 보기
         **/
        function dispBoardTagList() {
            // 만약 목록 보기 권한조치 없을 경우 태그 목록도 보여주지 않음
            if(!$this->grant->list) return $this->dispBoardMessage('msg_not_permitted');

            // 태그 모델 객체에서 태그 목록을 구해옴
            $oTagModel = &getModel('tag');

            $obj->mid = $this->module_info->mid;
            $obj->list_count = 10000;
            $output = $oTagModel->getTagList($obj);

            // 내용을 랜덤으로 정렬
            if(count($output->data)) {
                $numbers = array_keys($output->data);
                shuffle($numbers);

                if(count($output->data)) {
                    foreach($numbers as $k => $v) {
                        $tag_list[] = $output->data[$v];
                    }
                }
            }

            Context::set('tag_list', $tag_list);

            $this->setTemplateFile('tag_list');
        }
        
        /**
         * @brief 글 작성 화면 출력
         **/
        function dispBoardWrite() {
            // 권한 체크
            if(!$this->grant->write_document) return $this->dispBoardMessage('msg_not_permitted');

            $oDocumentModel = &getModel('document');

            /**
             * 카테고리를 사용하는지 확인후 사용시 카테고리 목록을 구해와서 Context에 세팅, 권한도 함께 체크
             **/
            if($this->module_info->use_category=='Y') {

                // 로그인한 사용자의 그룹 정보를 구함
                if(Context::get('is_logged')) {
                    $logged_info = Context::get('logged_info');
                    $group_srls = array_keys($logged_info->group_list);
                } else {
                    $group_srls = array();
                }
                $group_srls_count = count($group_srls);

                // 카테고리 목록을 구하고 권한을 체크
                $normal_category_list = $oDocumentModel->getCategoryList($this->module_srl);
                if(count($normal_category_list)) {
                    foreach($normal_category_list as $category_srl => $category) {
                        $is_granted = true;
                        if($category->group_srls) {
                            $category_group_srls = explode(',',$category->group_srls);
                            $is_granted = false;
                            if(count(array_intersect($group_srls, $category_group_srls))) $is_granted = true; 

                        }
                        if($is_granted) $category_list[$category_srl] = $category;
                    }
                }
                Context::set('category_list', $category_list);
            }

            // GET parameter에서 document_srl을 가져옴
            $document_srl = Context::get('document_srl');

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
        function dispBoardWriteComment() {
            $document_srl = Context::get('document_srl');

            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispBoardMessage('msg_not_permitted');

            // 원본글을 구함
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return $this->dispBoardMessage('msg_invalid_request');

            // 해당 댓글를 찾아본다 (comment_form을 같이 쓰기 위해서 빈 객체 생성)
            $oCommentModel = &getModel('comment');
            $oSourceComment = $oComment = $oCommentModel->getComment(0);
            $oComment->add('document_srl', $document_srl);
            $oComment->add('module_srl', $this->module_srl);

            // 필요한 정보들 세팅
            Context::set('oDocument',$oDocument);
            Context::set('oSourceComment',$oSourceComment);
            Context::set('oComment',$oComment);

            $this->setTemplateFile('comment_form');
        }

        /**
         * @brief 댓글의 답글 화면 출력
         **/
        function dispBoardReplyComment() {
            // 권한 체크
            if(!$this->grant->write_comment) return $this->dispBoardMessage('msg_not_permitted');

            // 목록 구현에 필요한 변수들을 가져온다
            $parent_srl = Context::get('comment_srl');

            // 지정된 원 댓글이 없다면 오류
            if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

            // 해당 댓글를 찾아본다
            $oCommentModel = &getModel('comment');
            $oSourceComment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$oSourceComment->isExists()) return $this->dispBoardMessage('msg_invalid_request');
            if($oSourceComment->get('document_srl') != Context::get('document_srl')) return $this->dispBoardMessage('meg_invalid_request');

            // 대상 댓글을 생성
            $oComment = $oCommentModel->getComment();
            $oComment->add('parent_srl', $parent_srl);
            $oComment->add('document_srl', $oSourceComment->get('document_srl'));

            // 필요한 정보들 세팅
            Context::set('oSourceComment',$oSourceComment);
            Context::set('oComment',$oComment);

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
            $oComment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

            // 댓글이 없다면 오류
            if(!$oComment->isExists()) return $this->dispBoardMessage('msg_invalid_request');

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if(!$oComment->isGranted()) return $this->setTemplateFile('input_password_form');

            // 필요한 정보들 세팅
            Context::set('oSourceComment', $oCommentModel->getComment());
            Context::set('oComment', $oComment);

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

            // 삭제하려는 댓글이 있는지 확인
            if($comment_srl) {
                $oCommentModel = &getModel('comment');
                $oComment = $oCommentModel->getComment($comment_srl, $this->grant->manager);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$oComment->isExists() ) return $this->dispBoardContent();

            // 권한이 없는 경우 비밀번호 입력화면으로
            if(!$oComment->isGranted()) return $this->setTemplateFile('input_password_form');

            Context::set('oComment',$oComment);

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
