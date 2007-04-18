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
            if(substr_count($this->act, 'Admin')) $this->initAdmin();
            else $this->initNormal();
        }

        /**
         * @brief 관리자 act 호출시에 관련 정보들 세팅해줌
         **/
        function initAdmin() {
            // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
            $module_srl = Context::get('module_srl');
            if(!$module_srl && $this->module_srl) {
                $module_srl = $this->module_srl;
                Context::set('module_srl', $module_srl);
            }

            // module model 객체 생성 
            $oModuleModel = &getModel('module');

            // module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'list';
                } else {
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }

            // 모듈 카테고리 목록을 구함
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            // 템플릿 경로 지정 (blog의 경우 tpl에 관리자용 템플릿 모아놓음)
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

        /**
         * @brief 일반 블로그 호출시에 관련 정보를 세팅해줌
         **/
        function initNormal() {
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
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->skin);
            $this->setTemplatePath($template_path);

            // rss url
            if($this->grant->view) Context::set('rss_url', getUrl('','mid',$this->mid,'act','dispBlogRss'));
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

            // document_srl이 있다면 해당 글을 구해오자
            if($this->grant->view && $document_srl) {

                // 글을 구함
                $document = $oDocumentModel->getDocument($document_srl, $this->grant->manager, true);

                // 찾아지지 않았다면 초기화
                if($document->document_srl != $document_srl) {
                    unset($document);
                    unset($document_srl);
                    Context::set('document_srl','',true);

                // 글이 찾아졌으면 댓글 권한과 허용 여부를 체크하여 댓글 에디터 세팅 
                } elseif($this->grant->write_comment && $document->allow_comment == 'Y' && $document->lock_comment != 'Y') {

                    // 브라우저 타이틀
                    $browser_title = $this->module_info->browser_title.' - '.$document->title;
                    Context::setBrowserTitle($browser_title);

                    // 댓글
                    $this->setCommentEditor();
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

            // 검색 옵션
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

            // 관리자일 경우 체크한 문서들의 목록을 세팅
            if($this->grant->is_admin) {
                Context::set('check_list',$_SESSION['document_management'][$this->module_srl]);
            }

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

            // 지정된 글이 없다면 (신규) 새로운 번호를 만든다
            if($document_srl) {
                $document = $oDocumentModel->getDocument($document_srl, $this->grant->manager);
                if(!$document) {
                    unset($document_srl);
                    Context::set('document_srl','');
                }
            }

            if(!$document_srl) $document_srl = getNextSequence();

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if($document&&!$document->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('document_srl',$document_srl);
            Context::set('document', $document);

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $editor = $oEditorModel->getEditor($document_srl, $this->grant->fileupload, true);
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
                $document = $oDocumentModel->getDocument($document_srl);
            }

            // 삭제하려는 글이 없으면 에러
            if(!$document) return $this->dispBlogContent();

            // 권한이 없는 경우 비밀번호 입력화면으로
            if($document&&!$document->is_granted) return $this->setTemplateFile('input_password_form');

            Context::set('document',$document);

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
            Context::set('document_srl',$document_srl);
            Context::set('parent_srl',$parent_srl);
            Context::set('comment_srl',NULL);
            Context::set('source_comment',$source_comment);

            // 댓글 에디터 세팅 
            $this->setCommentEditor();

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

            // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
            if($comment_srl&&$comment&&!$comment->is_granted) return $this->setTemplateFile('input_password_form');

            // 필요한 정보들 세팅
            Context::set('document_srl',$document_srl);
            Context::set('comment_srl',$comment_srl);
            Context::set('comment', $comment);

            // 댓글 에디터 세팅 
            $this->setCommentEditor($comment_srl);

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
         * @brief RSS 출력
         **/
        function dispBlogRss() {
            // 권한 체크
            if(!$this->grant->list) return $this->dispBlogMessage('msg_not_permitted');

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

            // RSS 모듈을 불러서 출력할 내용을 지정
            $oRssView = &getView('rss');
            $oRssView->dispRss($info, $document_list);

            // RSS 모듈의 tempate을 가져옴
            $this->setTemplatePath($oRssView->getTemplatePath());
            $this->setTemplateFile($oRssView->getTemplateFile());
        }

        /**
         * @brief 댓글의 editor 를 세팅
         * 댓글의 경우 수정하는 경우가 아니라면 고유값이 없음.\n
         * 따라서 고유값이 없을 경우 고유값을 가져와서 지정해 주어야 함
         **/
        function setCommentEditor($comment_srl=0) {
            if(!$comment_srl) {
                $comment_srl = getNextSequence();
                Context::set('comment_srl', $comment_srl);
            }

            // 에디터 모듈의 getEditor를 호출하여 세팅
            $oEditorModel = &getModel('editor');
            $comment_editor = $oEditorModel->getEditor($comment_srl, $this->grant->fileupload);
            Context::set('comment_editor', $comment_editor);
        }

        /**
         * @brief 블로그 관리 목록 보여줌
         **/
        function dispBlogAdminContent() {
            // 등록된 blog 모듈을 불러와 세팅
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = executeQuery('blog.getBlogList', $args);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('blog_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        /**
         * @brief 블로그에 필요한 기본 설정들
         **/
        function dispBlogAdminModuleConfig() {

            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('blog');
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplateFile('blog_config');
        }

        /**
         * @brief 선택된 블로그의 정보 출력
         **/
        function dispBlogAdminBlogInfo() {

            // module_srl 값이 없다면 그냥 index 페이지를 보여줌
            if(!Context::get('module_srl')) return $this->dispBlogAdminContent();

            // 레이아웃이 정해져 있다면 레이아웃 정보를 추가해줌(layout_title, layout)
            if($this->module_info->layout_srl) {
                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($this->module_info->layout_srl);
                $this->module_info->layout = $layout_info->layout;
                $this->module_info->layout_title = $layout_info->layout_title;
            }

            // 정해진 스킨이 있으면 해당 스킨의 정보를 구함
            if($this->module_info->skin) {
                $oModuleModel = &getModel('module');
                $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $this->module_info->skin);
                $this->module_info->skin_title = $skin_info->title;
            }

            // 템플릿 파일 지정
            $this->setTemplateFile('blog_info');
        }

        /**
         * @brief 블로그 추가 폼 출력
         **/
        function dispBlogAdminInsertBlog() {

            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('blog_insert');
        }

        /**
         * @brief 블로그 삭제 화면 출력
         **/
        function dispBlogAdminDeleteBlog() {

            if(!Context::get('module_srl')) return $this->dispBlogAdminContent();

            $module_info = Context::get('module_info');

            $oDocumentModel = &getModel('document');
            $document_count = $oDocumentModel->getDocumentCount($module_info->module_srl);
            $module_info->document_count = $document_count;

            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('blog_delete');
        }

        /**
         * @brief 스킨 정보 보여줌
         **/
        function dispBlogAdminSkinInfo() {

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
        function dispBlogAdminCategoryInfo() {

            // module_srl을 구함
            $module_srl = Context::get('module_srl');

            // 카테고리의 목록을 구해옴
            $oDocumentModel = &getModel('document');
            $category_list = $oDocumentModel->getCategoryList($module_srl);
            Context::set('category_list', $category_list);

            // 수정하려는 카테고리가 있다면해당 카테고리의 정보를 가져옴
            $category_srl = Context::get('category_srl');

            if($category_srl) {

                $selected_category = $oDocumentModel->getCategory($category_srl);

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
        function dispBlogAdminGrantInfo() {
            // module_srl을 구함
            $module_srl = Context::get('module_srl');

            // module.xml에서 권한 관련 목록을 구해옴
            $grant_list = $this->xml_info->grant;
            Context::set('grant_list', $grant_list);

            // 권한 그룹의 목록을 가져온다
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            $this->setTemplateFile('grant_list');
        }
    }
?>
