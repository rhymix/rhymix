<?php
    /**
     * @file   : modules/board/board.admin.php
     * @author : zero <zero@nzeo.com>
     * @desc   : board의 관리자 파일
     *           Module class에서 상속을 받아서 사용
     *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
     *           미리 기록을 하여야 함
     **/

    class board_admin extends Module {

        /**
         * 기본 action 지정
         * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
         **/
        var $default_act = 'dispContent';

        /**
         * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
         * css/js파일의 load라든지 lang파일 load등을 미리 선언
         *
         * Init() => 공통 
         * dispInit() => disp시에
         * procInit() => proc시에
         *
         * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
         * (ex: $this->module_path = "./modules/system_install/";
         **/

        // 초기화
        function init() {
        // 기본 정보를 읽음
        Context::loadLang($this->module_path.'lang');

        // 스킨의 종류를 읽음
        $oModule = getModule('module_manager');
        $skins = $oModule->getSkins($this->module_path);
        Context::set('skins', $skins);
        }

        // disp 초기화
        function dispInit() {
        // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
        $module_srl = Context::get('module_srl');
        if($module_srl) {
        $oModule = getModule('module_manager');
        $module_info = $oModule->getModuleInfoByModuleSrl($module_srl);
        if(!$module_info) {
        Context::set('module_srl','');
        $this->act = 'dispContent';
        } else Context::set('module_info',$module_info);
        }

        return true;
        }

        // proc 초기화
        function procInit() {
        return true;
        }

        /**
         * 여기서부터는 action의 구현
         * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
         *
         * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
         * procXXXX : 처리를 위한 method, output에는 error, message가 지정되어야 한다
         **/

        // 출력 부분
        function dispContent() {

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

        function dispInfo() {
        if(!Context::get('module_srl')) return $this->dispContent();

        // 템플릿 파일 지정
        $this->setTemplateFile('info');
        }

        function dispCategoryInfo() {
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

        function dispGrantInfo() {
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

        function dispSkinInfo() {
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

        function dispInsert() {
        // 템플릿 파일 지정
        $this->setTemplateFile('insert_form');
        }

        function dispDeleteForm() {
        if(!Context::get('module_srl')) return $this->dispContent();

        $module_info = Context::get('module_info');

        $oDocument = getModule('document');
        $document_count = $oDocument->getDocumentCount($module_info->module_srl);
        $module_info->document_count = $document_count;

        Context::set('module_info',$module_info);

        // 템플릿 파일 지정
        $this->setTemplateFile('delete_form');
        }

        // 실행 부분
        function procInsert() {
        // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
        $args = Context::gets('module_srl','mid','skin','use_category','browser_title','description','is_default','header_text','footer_text','admin_id');
        $args->module = 'board';
        if($args->is_default!='Y') $args->is_default = 'N';
        if($args->use_category!='Y') $args->use_category = 'N';

        // 기본 값외의 것들을 정리
        $extra_var = delObjectVars(Context::getRequestVars(), $args);
        unset($extra_var->sid);
        unset($extra_var->act);
        unset($extra_var->page);

        // module_srl이 있으면 원본을 구해온다
        $oModule = getModule('module_manager');

        // module_srl이 넘어오면 원 모듈이 있는지 확인
        if($args->module_srl) {
        $module_info = $oModule->getModuleInfoByModuleSrl($args->module_srl);
        // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
        if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
        }

        // $extra_var를 serialize
        $args->extra_var = serialize($extra_var);

        // is_default=='Y' 이면
        if($args->is_default=='Y') $oModule->clearDefaultModule();

        // module_srl의 값에 따라 insert/update
        if(!$args->module_srl) {
        $output = $oModule->insertModule($args);
        $msg_code = 'success_registed';
        } else {
        $output = $oModule->updateModule($args);
        $msg_code = 'success_updated';
        }

        if(!$output->toBool()) return $output;

        $this->add('sid','board');
        $this->add('act','dispInfo');
        $this->add('page',Context::get('page'));
        $this->add('module_srl',$output->get('module_srl'));
        $this->setMessage($msg_code);
        }

        function procDelete() {
        $module_srl = Context::get('module_srl');

        // 원본을 구해온다
        $oModule = getModule('module_manager');
        $output = $oModule->deleteModule($module_srl);
        if(!$output->toBool()) return $output;

        $this->add('sid','board');
        $this->add('act','dispContent');
        $this->add('page',Context::get('page'));
        $this->setMessage('success_deleted');
        }

        function procInsertCategory() {
        // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
        $module_srl = Context::get('module_srl');
        $category_title = Context::get('category_title');

        // module_srl이 있으면 원본을 구해온다
        $oDocument = getModule('document');
        $output = $oDocument->insertCategory($module_srl, $category_title);
        if(!$output->toBool()) return $output;

        $this->add('sid','board');
        $this->add('act','dispCategoryInfo');
        $this->add('page',Context::get('page'));
        $this->add('module_srl',$module_srl);
        $this->setMessage('success_registed');
        }

        function procUpdateCategory() {
        $category_srl = Context::get('category_srl');
        $mode = Context::get('mode');

        $oDocument = getModule('document');

        switch($mode) {
        case 'up' :
        $output = $oDocument->moveCategoryUp($category_srl);
        $msg_code = 'success_moved';
        break;
        case 'down' :
        $output = $oDocument->moveCategoryDown($category_srl);
        $msg_code = 'success_moved';
        break;
        case 'delete' :
        $output = $oDocument->deleteCategory($category_srl);
        $msg_code = 'success_deleted';
        break;
        case 'update' :
        $selected_category = $oDocument->getCategory($category_srl);
        $args->category_srl = $selected_category->category_srl;
        $args->title = Context::get('category_title');
        $args->list_order = $selected_category->list_order;
        $output = $oDocument->updateCategory($args);
        $msg_code = 'success_updated';
        break;
        }
        if(!$output->toBool()) return $output;
        $this->add('module_srl', $selected_category->module_srl);
        $this->setMessage($msg_code);
        }

        function procUpdateSkinInfo() {
        // module_srl에 해당하는 정보들을 가져오기
        $module_srl = Context::get('module_srl');
        $oModule = getModule('module_manager');
        $module_info = $oModule->getModuleInfoByModuleSrl($module_srl);
        $skin = $module_info->skin;

        // 스킨의 정볼르 구해옴 (extra_vars를 체크하기 위해서)
        $oModule = getModule('module_manager');
        $skin_info = $oModule->loadSkinInfo($this->module_path, $skin);

        // 입력받은 변수들을 체크 (sid, act, module_srl, page등 기본적인 변수들 없앰)
        $obj = Context::getRequestVars();
        unset($obj->sid);
        unset($obj->act);
        unset($obj->module_srl);
        unset($obj->page);

        // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
        if($skin_info->extra_vars) {
        foreach($skin_info->extra_vars as $vars) {
        if($vars->type!='image') continue;

        $image_obj = $obj->{$vars->name};

        // 삭제 요청에 대한 변수를 구함
        $del_var = $obj->{"del_".$vars->name};
        unset($obj->{"del_".$vars->name});
        if($del_var == 'Y') {
        @unlink($module_info->{$vars->name});
        continue;
        }

        // 업로드 되지 않았다면 이전 데이터를 그대로 사용
        if(!$image_obj['tmp_name']) {
        $obj->{$vars->name} = $module_info->{$vars->name};
        continue;
        }

        // 정상적으로 업로드된 파일이 아니면 무시
        if(!is_uploaded_file($image_obj['tmp_name'])) {
        unset($obj->{$vars->name});
        continue;
        }

        // 이미지 파일이 아니어도 무시
        if(!eregi("\.(jpg|jpeg|gif|png)$", $image_obj['name'])) {
        unset($obj->{$vars->name});
        continue;
        }

        // 경로를 정해서 업로드
        $path = sprintf("./files/attach/images/%s/", $module_srl);

        // 디렉토리 생성
        if(!FileHandler::makeDir($path)) return false;

        $filename = $path.$image_obj['name'];

        // 파일 이동
        if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
        unset($obj->{$vars->name});
        continue;
        }

        // 변수를 바꿈
        unset($obj->{$vars->name});
        $obj->{$vars->name} = $filename;
        }
        }

        // serialize하여 저장
        $extra_vars = serialize($obj);

        $oModule = getModule('module_manager');
        $oModule->updateModuleExtraVars($module_srl, $extra_vars);

        $url = sprintf("./admin.php?sid=%s&module_srl=%s&act=dispSkinInfo&page=%s", 'board', $module_srl, Context::get('page'));
        print "<script type=\"text/javascript\">location.href=\"".$url."\";</script>";
        exit();
        }

        function procInsertGrant() {
        $module_srl = Context::get('module_srl');

        // 현 모듈의 권한 목록을 가져옴
        $oBoard = getModule('board');
        $grant_list = $oBoard->grant_list;

        if(count($grant_list)) {
        foreach($grant_list as $grant) {
        $group_srls = Context::get($grant);
        if($group_srls) $arr_grant[$grant] = explode(',',Context::get($grant));
        }
        $grant = serialize($arr_grant);
        }

        $oModule = getModule('module_manager');
        $oModule->updateModuleGrant($module_srl, $grant);

        $this->add('sid','board');
        $this->add('act','dispGrantInfo');
        $this->add('page',Context::get('page'));
        $this->add('module_srl',Context::get('module_srl'));
        $this->setMessage('success_registed');
        }

        /**
         * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
         **/

        /**
         * 기본 action 지정
         * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
         **/
        var $default_act = 'dispContent';

        // 검색 옵션
        var $search_option = array('title','content','title_content','user_name');

        // 모듈에서 사용할 변수들
        var $skin = "default";
        var $list_count = 3;
        var $page_count = 10;
        var $category_list = NULL;

        // 권한의 종류를 미리 설정
        var $grant_list = array(
        'list',
        'view',
        'write_document',
        'write_comment',
        'fileupload',
        );

        // 에디터
        var $editor = 'default';

        /**
         * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
         * css/js파일의 load라든지 lang파일 load등을 미리 선언
         *
         * Init() => 공통 
         * dispInit() => disp시에
         * procInit() => proc시에
         *
         * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
         * (ex: $this->module_path = "./modules/system_install/";
         **/

        // 초기화
        function init() {
        // lang
        Context::loadLang($this->template_path.'lang/');
        }

        // disp 초기화
        function dispInit() {

        // 카테고리를 사용한다면 카테고리 목록을 구해옴
        if($this->module_info->use_category=='Y') {
        $oDocument = getModule('document');
        $this->category_list = $oDocument->getCategoryList($this->module_srl);
        Context::set('category_list', $this->category_list);
        }

        // 에디터 세팅
        Context::set('editor', $this->editor);
        $editor_path = sprintf("./editor/%s/", $this->editor);
        Context::set('editor_path', $editor_path);
        Context::loadLang($editor_path);

        return true;
        }

        // proc 초기화
        function procInit() {
        // 파일 업로드일 경우 $act값을 procUploadFile() 로 변경
        if(Context::isUploaded() && $this->grant->fileupload) $this->act = 'procUploadFile';

        return true;
        }

        /**
         * 여기서부터는 action의 구현
         * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
         *
         * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
         * procXXXX : 처리를 위한 method, output에는 error, message가 지정되어야 한다
         **/

        // 출력 부분
        function dispContent() {
        // 권한 체크
        if(!$this->grant->list) return $this->dispMessage('msg_not_permitted');

        // 목록 구현에 필요한 변수들을 가져온다
        $document_srl = Context::get('document_srl');
        $page = Context::get('page');

        // document 객체를 생성. 기본 데이터 구조의 경우 document모듈만 쓰면 만사 해결.. -_-;
        $oDocument = getModule('document');

        // document_srl이 있다면 해당 글을 구해오자
        if($this->grant->view && $document_srl) {
        $document = $oDocument->getDocument($document_srl);

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
        $oComment = getModule('comment');
        $comment_list = $oComment->getCommentList($document_srl);
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
        $output = $oDocument->getDocumentList($this->module_srl, 'list_order', $page, $this->list_count, $this->page_count, $search_obj);

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

        function dispWriteForm() {
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

        function dispDeleteForm() {
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
        if(!$document) return $this->dispContent();

        // 권한이 없는 경우 비밀번호 입력화면으로
        if($document&&!$document->is_granted) return $this->setTemplateFile('input_password_form');

        Context::set('document',$document);

        $this->setTemplateFile('delete_form');
        }

        function dispCommentModifyForm() {
        // 권한 체크
        if(!$this->grant->write_comment) return $this->dispMessage('msg_not_permitted');

        // 목록 구현에 필요한 변수들을 가져온다
        $document_srl = Context::get('document_srl');
        $comment_srl = Context::get('comment_srl');

        // 지정된 댓글이 없다면 오류
        if(!$comment_srl) return new Output(-1, 'msg_invalid_request');

        // 해당 댓글를 찾아본다
        $oComment = getModule('comment');
        $comment = $oComment->getComment($comment_srl);

        // 댓글이 없다면 오류
        if(!$comment) return new Output(-1, 'msg_invalid_request');

        // 글을 수정하려고 할 경우 권한이 없는 경우 비밀번호 입력화면으로
        if($comment_srl&&$comment&&!$_SESSION['own_comment'][$comment_srl]) return $this->setTemplateFile('input_password_form');

        // 필요한 정보들 세팅
        Context::set('document_srl',$document_srl);
        Context::set('comment_srl',$comment_srl);
        Context::set('comment', $comment);

        $this->setTemplateFile('comment_form');
        }

        function dispCommentDeleteForm() {
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
        if(!$comment) return $this->dispContent();

        // 권한이 없는 경우 비밀번호 입력화면으로
        if($comment_srl&&$comment&&!$_SESSION['own_comment'][$comment_srl]) return $this->setTemplateFile('input_password_form');

        Context::set('comment',$comment);

        $this->setTemplateFile('delete_comment_form');
        }

        function dispCommentReplyForm() {
        // 권한 체크
        if(!$this->grant->write_comment) return $this->dispMessage('msg_not_permitted');

        // 목록 구현에 필요한 변수들을 가져온다
        $document_srl = Context::get('document_srl');
        $parent_srl = Context::get('comment_srl');

        // 지정된 원 댓글이 없다면 오류
        if(!$parent_srl) return new Output(-1, 'msg_invalid_request');

        // 해당 댓글를 찾아본다
        $oComment = getModule('comment');
        $source_comment = $oComment->getComment($parent_srl);

        // 댓글이 없다면 오류
        if(!$source_comment) return new Output(-1, 'msg_invalid_request');

        // 필요한 정보들 세팅
        Context::set('document_srl',$document_srl);
        Context::set('parent_srl',$parent_srl);
        Context::set('comment_srl',NULL);
        Context::set('source_comment',$source_comment);

        $this->setTemplateFile('comment_form');
        }

        function dispTrackbackDeleteForm() {
        // 삭제할 댓글번호를 가져온다
        $trackback_srl = Context::get('trackback_srl');

        // 삭제하려는 댓글가 있는지 확인
        $oTrackback = getModule('trackback');
        $output = $oTrackback->getTrackback($trackback_srl);
        $trackback = $output->data;

        // 삭제하려는 글이 없으면 에러
        if(!$trackback) return $this->dispContent();

        Context::set('trackback',$trackback);

        $this->setTemplateFile('delete_trackback_form');
        }

        function dispLogin() {
        if(Context::get('is_logged')) return $this->dispContent();
        $this->setTemplateFile('login_form');
        }

        function dispLogout() {
        if(!Context::get('is_logged')) return $this->dispContent();
        $this->setTemplateFile('logout');
        }

        function dispMessage($msg_code) {
        $msg = Context::getLang($msg_code);
        if(!$msg) $msg = $msg_code;
        Context::set('message', $msg);
        $this->setTemplateFile('message');
        }

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

        // 실행 부분
        function procInsertDocument() {
        // 글작성시 필요한 변수를 가져옴
        $obj = Context::getRequestVars();
        //$obj = Context::gets('document_srl','user_name','email_address','homepage','tags','title','content','password','allow_comment','lock_comment','allow_trackback','category_srl','is_notice','is_secret');
        $obj->module_srl = $this->module_srl;
        if($obj->is_notice!='Y') $obj->is_notice = 'N';
        if($obj->is_secret!='Y') $obj->is_secret = 'N';
        if($obj->allow_comment!='Y') $obj->allow_comment = 'N';
        if($obj->lock_comment!='Y') $obj->lock_comment = 'N';
        if($obj->allow_trackback!='Y') $obj->allow_trackback = 'N';

        // document module 객체 생성
        $oDocument = getModule('document');

        // 첨부 파일의 갯수를 구함
        $obj->uploaded_count = $oDocument->getFilesCount($obj->document_srl);

        // 이미 존재하는 글인지 체크
        $document = $oDocument->getDocument($obj->document_srl);

        // 이미 존재하는 경우 수정
        if($document->document_srl == $obj->document_srl) {
        $output = $oDocument->updateDocument($document, $obj);
        $msg_code = 'success_updated';

        // 그렇지 않으면 신규 등록
        } else {
        $output = $oDocument->insertDocument($obj);
        $msg_code = 'success_registed';
        $obj->document_srl = $output->get('document_srl');
        }

        // 트랙백 발송
        $trackback_url = Context::get('trackback_url');
        $trackback_charset = Context::get('trackback_charset');
        if($trackback_url) {
        $oTrackback = getModule('trackback');
        $oTrackback->sendTrackback($obj, $trackback_url, $trackback_charset);
        }

        if(!$output->toBool()) return $output;
        $this->setMessage($msg_code);
        $this->add('mid', Context::get('mid'));
        $this->add('document_srl', $output->get('document_srl'));
        }

        function procDeleteDocument() {
        // 문서 번호 확인
        $document_srl = Context::get('document_srl');
        if(!$document_srl) return $this->doError('msg_invalid_document');

        // 문서 있는지 확인
        $oDocument = getModule('document');
        $document = $oDocument->getDocument($document_srl);
        if($document->document_srl!=$document_srl) return $this->doError('msg_invalid_document');

        // 글 삭제
        $output = $oDocument->deleteDocument($document);
        if(!$output->toBool()) return $output;

        $this->add('mid', Context::get('mid'));
        $this->add('page', $output->get('page'));
        $this->setMessage('success_deleted');
        }

        function procVoteDocument() {
        $oDocument = getModule('document');
        $document_srl = Context::get('document_srl');
        return $oDocument->updateVotedCount($document_srl);
        }

        function procInsertComment() {
        // 댓글 입력에 필요한 데이터 추출
        $obj = Context::gets('document_srl','comment_srl','parent_srl','content','password','nick_name','user_name','member_srl','email_address','homepage');
        $obj->module_srl = $this->module_srl;

        // comment 객체 생성
        $oComment = getModule('comment');

        // comment_srl이 없을 경우 신규 입력
        if(!$obj->comment_srl) {
        // parent_srl이 있으면 답변으로
        if($obj->parent_srl) {
        $comment = $oComment->getComment($obj->parent_srl);
        if(!$comment) return new Output(-1, 'msg_invalid_request');
        $output = $oComment->insertComment($obj);
        $comment_srl = $output->get('comment_srl');
        // 없으면 신규
        } else {
        $output = $oComment->insertComment($obj);
        }

        // comment_srl이 있으면 수정으로
        } else {
        $comment = $oComment->getComment($obj->comment_srl);
        if(!$comment) return new Output(-1, 'msg_invalid_request');

        $obj->parent_srl = $comment->parent_srl;
        $output = $oComment->updateComment($obj);
        $comment_srl = $obj->comment_srl;
        }

        if(!$output->toBool()) return $output;
        $this->setMessage('success_registed');
        $this->add('mid', Context::get('mid'));
        $this->add('document_srl', $obj->document_srl);
        $this->add('comment_srl', $comment_srl);
        }

        function procDeleteComment() {
        // 댓글 번호 확인
        $comment_srl = Context::get('comment_srl');
        if(!$comment_srl) return $this->doError('msg_invalid_request');

        // 삭제
        $oComment = getModule('comment');
        $output = $oComment->deleteComment($comment_srl);
        if(!$output->toBool()) return $output;

        $this->add('mid', Context::get('mid'));
        $this->add('page', Context::get('page'));
        $this->add('document_srl', $output->get('document_srl'));
        $this->setMessage('success_deleted');
        }

        function procReceiveTrackback() {
        $obj = Context::gets('document_srl','url','title','excerpt');
        $oTrackback = getModule('trackback');
        $oTrackback->insertTrackback($obj);
        }

        function procDeleteTrackback() {
        $trackback_srl = Context::get('trackback_srl');
        $oTrackback = getModule('trackback');
        $output = $oTrackback->deleteTrackback($trackback_srl);

        $this->add('mid', Context::get('mid'));
        $this->add('page', Context::get('page'));
        $this->add('document_srl', $output->get('document_srl'));
        $this->setMessage('success_deleted');
        }

        function procLogin() {
        // 아이디, 비밀번호를 받음
        $user_id = Context::get('user_id');
        $password = Context::get('password');

        // member모듈 객체 생성
        $oMember = getModule('member');
        return $oMember->doLogin($user_id, $password);
        }

        function procLogout() {
        // member모듈 객체 생성
        $oMember = getModule('member');
        return $oMember->doLogout();
        }

        function procVerificationPassword() {
        // 비밀번호와 문서 번호를 받음
        $password = md5(Context::get('password'));
        $document_srl = Context::get('document_srl');
        $comment_srl = Context::get('comment_srl');

        // comment_srl이 있을 경우 댓글이 대상
        if($comment_srl) {
        // 문서번호에 해당하는 글이 있는지 확인
        $oComment = getModule('comment');
        $data = $oComment->getComment($comment_srl);
        // comment_srl이 없으면 문서가 대상
        } else {
        // 문서번호에 해당하는 글이 있는지 확인
        $oDocument = getModule('document');
        $data = $oDocument->getDocument($document_srl);
        }

        // 글이 없을 경우 에러
        if(!$data) return $this->doError('msg_invalid_request');

        // 문서의 비밀번호와 입력한 비밀번호의 비교
        if($data->password != $password) return $this->doError('msg_invalid_password');

        // 해당 글에 대한 권한 부여
        if($comment_srl) $_SESSION['own_comment'][$comment_srl] = true;
        else $_SESSION['own_document'][$document_srl] = true;
        }

        function procUploadFile() {
        // 기본적으로 필요한 변수인 document_srl, module_srl을 설정
        $document_srl = Context::get('document_srl');
        $module_srl = $this->module_srl;

        // document모듈 객체 생성후 걍 넘겨버림
        $oDocument = getModule('document');
        $output = $oDocument->insertFile($module_srl, $document_srl);
        print $this->printUploadedFileList($document_srl);
        exit();
        }

        function procDeleteFile() {
        // 기본적으로 필요한 변수인 document_srl, module_srl을 설정
        $document_srl = Context::get('document_srl');
        $module_srl = $this->module_srl;
        $file_srl = Context::get('file_srl');

        // document모듈 객체 생성후 걍 넘겨버림
        $oDocument = getModule('document');
        $output = $oDocument->deleteFile($file_srl);
        print $this->printUploadedFileList($document_srl);
        exit();
        }

        function procDownload() {
        // 다운로드에 필요한 변수 체크
        $file_srl = Context::get('file_srl');
        $sid = Context::get('sid');

        // document module 객체 생성후 해당 파일의 정보를 체크
        $oDocument = getModule('document');
        $file_obj = $oDocument->getFile($file_srl);
        if($file_obj->file_srl!=$file_srl||$file_obj->sid!=$sid) exit();

        // 이상이 없으면 download_count 증가
        $args->file_srl = $file_srl;
        $oDB = &DB::getInstance();
        $oDB->executeQuery('document.updateFileDownloadCount', $args);

        // 파일 출력
        $filename = $file_obj->source_filename;

        if(strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
        $filename = urlencode($filename);
        $filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
        }

        $uploaded_filename = $file_obj->uploaded_filename;
        if(!file_exists($uploaded_filename)) exit();

        $fp = fopen($uploaded_filename, 'rb');
        if(!$fp) exit();

        header("Cache-Control: ");
        header("Pragma: ");
        header("Content-Type: application/octet-stream");

        header("Content-Length: " .(string)($file_obj->file_size));
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header("Content-Transfer-Encoding: binary\n");

        fpassthru($fp);
        exit();
        }

        function procClearFile() {
        $document_srl = Context::get('document_srl');

        // document_srl의 글이 등록되어 있다면 pass
        $oDocument = getModule('document');
        $data = $oDocument->getDocument($document_srl);
        if($data) exit();

        // 등록되어 있지 않다면 첨부파일 삭제
        $oDocument->deleteFiles($this->module_srl, $document_srl);
        }

        /**
         * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
         **/
        1,$s/\/\*}}}\*\///g
        function printUploadedFileList($document_srl) {
        // 첨부파일들의 정보를 취합해서 return
        $oDocument = getModule('document');
        $file_list = $oDocument->getFiles($document_srl);
        $file_count = count($file_list);
        $buff = "";
        for($i=0;$i<$file_count;$i++) {
        $file_info = $file_list[$i];
        if(!$file_info->file_srl) continue;

        $buff .= sprintf("parent.editor_insert_uploaded_file(\"%d\", \"%d\",\"%s\", \"%d\", \"%s\", \"%s\", \"%s\");\n", $document_srl, $file_info->file_srl, $file_info->source_filename, $file_info->file_size, FileHandler::filesize($file_info->file_size), $file_info->direct_download=='Y'?$file_info->uploaded_filename:'', $file_info->sid);
        }

        $buff = sprintf("<script type=\"text/javascript\">\nparent.editor_upload_clear_list(\"%s\");\n%s</script>", $document_srl, $buff);
        return $buff;
        }
    }
?>
