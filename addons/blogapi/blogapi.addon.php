<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file blogapicounter.addon.php
     * @author zero (zero@nzeo.com)
     * @brief blogAPI 애드온
     *
     * ms live writer, 파이어폭스의 performancing, zoundry 등의 외부 툴을 이용하여 글을 입력할 수 있게 합니다.
     * 모듈 실행 이전(before_module_proc)에 호출이 되어야 하며 정상동작후에는 강제 종료를 한다.
     **/

    // called_position가 after_module_proc일때 rsd태그 삽입
    if($called_position == 'after_module_proc') {
        // 현재 모듈의 rsd주소를 만듬
        $rsd_url = sprintf('%s%s/api', Context::getRequestUri(), $this->module);

        // 헤더에 rsd태그 삽입
        Context::addHtmlHeader("    ".'<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.$rsd_url.'" />');
    }

    // called_position가 before_module_proc일때 실행
    if($called_position != 'before_module_proc' || $_REQUEST['act'] != 'api') return;

    // 관련 func 파일 읽음
    require_once('./addons/blogapi/blogapi.func.php');

    // 요청된 xmlrpc를 파싱
    $oXmlParser = new XmlParser();
    $xmlDoc = $oXmlParser->parse();

    $method_name = $xmlDoc->methodcall->methodname->body;
    $params = $xmlDoc->methodcall->params->param;
    if($params && !is_array($params)) $params = array($params);

    // blogger.deletePost일 경우 첫번째 인자 값 삭제
    if($method_name == 'blogger.deletePost') array_shift($params);

    // user_id, password를 구해서 로그인 시도
    $user_id = trim($params[1]->value->string->body);
    $password = trim($params[2]->value->string->body);

    // member controller을 이용해서 로그인 시도
    if($user_id && $password) {
        $oMemberController = &getController('member');
        $output = $oMemberController->doLogin($user_id, $password);
        // 로그인 실패시 에러 메시지 출력 
        if(!$output->toBool()) {
            $content = getXmlRpcFailure(1, $output->getMessage());
            printContent($content);
        }
    }

    // 카테고리의 정보를 구해옴
    $oDocumentModel = &getModel('document');
    $category_list = $oDocumentModel->getCategoryList($this->module_srl);

    // 임시 파일 저장 장소 지정
    $tmp_uploaded_path = sprintf('./files/cache/blogapi/%s/%s/', $this->mid, $user_id);
    $uploaded_target_path = sprintf('/files/cache/blogapi/%s/%s/', $this->mid, $user_id);

    switch($method_name) {
        // 블로그 정보
        case 'blogger.getUsersBlogs' :
                $obj->url = Context::getRequestUri().$this->mid;
                $obj->blogid = $this->mid;
                $obj->blogName = $this->module_info->browser_title;
                $blog_list = array($obj);

                $content = getXmlRpcResponse($blog_list);
                printContent($content);
            break;

        // 카테고리 목록 return
        case 'metaWeblog.getCategories' :
                $category_obj_list = array();
                if($category_list) {
                    foreach($category_list as $category_srl => $category_info) {
                        unset($obj);
                        $obj->description = $category_info->title;
                        //$obj->htmlUrl = Context::getRequestUri().$this->mid.'/1'; 
                        //$obj->rssUrl= Context::getRequestUri().'rss/'.$this->mid.'/1'; 
                        $obj->title = $category_info->title;
                        $obj->categoryid = $category_srl;
                        $category_obj_list[] = $obj;
                    }
                }

                $content = getXmlRpcResponse($category_obj_list);
                printContent($content);
            break;

        // 파일 업로드
        case 'metaWeblog.newMediaObject' :
                $fileinfo = $params[3]->value->struct->member;
                foreach($fileinfo as $key => $val) {
                    $nodename = $val->name->body;
                    if($nodename == 'bits') $filedata = base64_decode($val->value->base64->body);
                    elseif($nodename == 'name') $filename = $val->value->string->body;
                }

                $tmp_arr = explode('/',$filename);
                $filename = array_pop($tmp_arr);

                if(!is_dir($tmp_uploaded_path)) FileHandler::makeDir($tmp_uploaded_path);

                $target_filename = sprintf('%s%s', $tmp_uploaded_path, $filename);
                FileHandler::writeFile($target_filename, $filedata);
                $obj->url = 'http://blog.nzeo.com/'.$target_filename;

                $content = getXmlRpcResponse($obj);
                printContent($content);
            break;

        // 글작성
        case 'metaWeblog.newPost' :
                unset($obj);
                $info = $params[3];
                // 글, 제목, 카테고리 정보 구함
                for($i=0;$i<count($info->value->struct->member);$i++) {
                    $val = $info->value->struct->member[$i];
                    switch($val->name->body) {
                        case 'title' :
                                $obj->title = $val->value->string->body;
                            break;
                        case 'description' :
                                $obj->content = $val->value->string->body;
                            break;
                        case 'categories' :
                                $categories = $val->value->array->data->value;
                                if(!is_array($categories)) $categories = array($categories);
                                $category = $categories[0]->string->body;
                                if($category && $category_list) {
                                    foreach($category_list as $category_srl => $category_info) {
                                        if($category_info->title == $category) $obj->category_srl = $category_srl;
                                    }
                                }
                            break;
                        case 'tagwords' :
                                $tags = $val->value->array->data->value;
                                if(!is_array($tags)) $tags = array($tags);
                                for($j=0;$j<count($tags);$j++) {
                                    $tag_list[] = $tags[$j]->string->body;
                                }
                                if(count($tag_list)) $obj->tags = implode(',',$tag_list);
                            break;
                    }

                }

                // 문서 번호 설정
                $document_srl = getNextSequence();
                $obj->document_srl = $document_srl;
                $obj->module_srl = $this->module_srl;

                // 첨부파일 정리
                if(is_dir($tmp_uploaded_path)) {
                    $file_list = FileHandler::readDir($tmp_uploaded_path);
                    $file_count = count($file_list);
                    if($file_count) {
                        $oFileController = &getController('file');
                        for($i=0;$i<$file_count;$i++) {
                            $file_info['tmp_name'] = sprintf('%s%s', $tmp_uploaded_path, $file_list[$i]);
                            $file_info['name'] = $file_list[$i];
                            $oFileController->insertFile($file_info, $this->module_srl, $document_srl, 0, true);
                        }
                        $obj->uploaded_count = $file_count;
                    }
                }
                $obj->content = str_replace($uploaded_target_path,sprintf('/files/attach/images/%s/%s/%s', $this->module_srl, $document_srl, $filename), $obj->content);

                $oDocumentController = &getController('document');
                $obj->allow_comment = 'Y';
                $obj->allow_trackback = 'Y';
                $output = $oDocumentController->insertDocument($obj);

                if(!$output->toBool()) {
                    $content = getXmlRpcFailure(1, $output->getMessage());
                } else {
                    //$content = getXmlRpcResponse(Context::getRequestUri().$this->mid.'/'.$document_srl);
                    $content = getXmlRpcResponse(''.$document_srl);
                }
                FileHandler::removeDir($tmp_uploaded_path);

                printContent($content);
            break;

        // 글 수정
        case 'metaWeblog.editPost' :
                $tmp_val = $params[0]->value->string->body;
                $tmp_arr = explode('/', $tmp_val);
                $document_srl = array_pop($tmp_arr);

                $oDocumentModel = &getModel('document');
                $source_obj = $oDocumentModel->getDocument($document_srl);
                $obj = $oDocumentModel->getDocument($document_srl);

                if(!$obj->is_granted) {
                    $content = getXmlRpcFailure(1, 'no permisstion');
                    break;
                }

                $info = $params[3];

                // 글, 제목, 카테고리 정보 구함
                for($i=0;$i<count($info->value->struct->member);$i++) {
                    $val = $info->value->struct->member[$i];
                    switch($val->name->body) {
                        case 'title' :
                                $obj->title = $val->value->string->body;
                            break;
                        case 'description' :
                                $obj->content = $val->value->string->body;
                            break;
                        case 'categories' :
                                $categories = $val->value->array->data->value;
                                if(!is_array($categories)) $categories = array($categories);
                                $category = $categories[0]->string->body;
                                if($category && $category_list) {
                                    foreach($category_list as $category_srl => $category_info) {
                                        if($category_info->title == $category) $obj->category_srl = $category_srl;
                                    }
                                }
                            break;
                        case 'tagwords' :
                                $tags = $val->value->array->data->value;
                                if(!is_array($tags)) $tags = array($tags);
                                for($j=0;$j<count($tags);$j++) {
                                    $tag_list[] = $tags[$j]->string->body;
                                }
                                if(count($tag_list)) $obj->tags = implode(',',$tag_list);
                            break;
                    }

                }

                // 문서 번호 설정
                $obj->document_srl = $document_srl;
                $obj->module_srl = $this->module_srl;

                // 첨부파일 정리
                if(is_dir($tmp_uploaded_path)) {
                    $file_list = FileHandler::readDir($tmp_uploaded_path);
                    $file_count = count($file_list);
                    if($file_count) {
                        $oFileController = &getController('file');
                        for($i=0;$i<$file_count;$i++) {
                            $file_info['tmp_name'] = sprintf('%s%s', $tmp_uploaded_path, $file_list[$i]);
                            $file_info['name'] = $file_list[$i];

                            $moved_filename = sprintf('./files/attach/images/%s/%s/%s', $this->module_srl, $document_srl, $file_info['name']);
                            if(file_exists($moved_filename)) continue;

                            $oFileController->insertFile($file_info, $this->module_srl, $document_srl, 0, true);
                        }
                        $obj->uploaded_count += $file_count;
                    }
                }
                $obj->content = str_replace($uploaded_target_path,sprintf('/files/attach/images/%s/%s/%s', $this->module_srl, $document_srl, $filename), $obj->content);

                $oDocumentController = &getController('document');
                $output = $oDocumentController->updateDocument($source_obj,$obj);

                if(!$output->toBool()) {
                    $content = getXmlRpcFailure(1, $output->getMessage());
                } else {
                    $content = getXmlRpcResponse(Context::getRequestUri().$this->mid.'/'.$document_srl);
                    FileHandler::removeDir($tmp_uploaded_path);
                }

                printContent($content);
            break;

        // 글삭제
        case 'blogger.deletePost' :
                $tmp_val = $params[0]->value->string->body;
                $tmp_arr = explode('/', $tmp_val);
                $document_srl = array_pop($tmp_arr);

                $oDocumentController = &getController('document');
                $output = $oDocumentController->deleteDocument($document_srl);
                if(!$output->toBool()) $content = getXmlRpcFailure(1, $output->getMessage());
                else $content = getXmlRpcResponse(true);

                printContent($content);
            break;

        // 최신글 받기
        case 'metaWeblog.getRecentPosts' :
                // 목록을 구하기 위한 옵션
                $args->module_srl = $this->module_srl; ///< 현재 모듈의 module_srl
                $args->page = 1;
                $args->list_count = 20;
                $args->sort_index = 'list_order'; ///< 소팅 값
                $output = $oDocumentModel->getDocumentList($args);
                if(!$output->toBool() || !$output->data) {
                    $content = getXmlRpcFailure(1, 'post not founded');
                    printContent($content);
                } else {
                    $posts = array();
                    foreach($output->data as $key => $val) {
                        $post = null;
                        $post->link = $post->permaLink = getUrl('','mid',$this->mid,'document_srl',$val->document_srl);
                        $post->userid = $val->user_id;
                        $post->mt_allow_pings = 0;
                        $post->mt_allow_comments = $val->allow_comment=='Y'?1:0;
                        $post->description = $val->content;
                        $post->postid = $val->document_srl;
                        $post->title = $val->title;

                        $year = substr($val->regdate,0,4);
                        $month = substr($val->regdate,4,2);
                        $day = substr($val->regdate,6,2);
                        $hour = substr($val->regdate,8,2);
                        $min = substr($val->regdate,10,2);
                        $sec = substr($val->regdate,12,2);
                        $time = mktime($hour,$min,$sec,$month,$day,$year);
                        $post->dateCreated = gmdate("D, d M Y H:i:s", $time);
                        $posts[] = $post;
                    }
                    $content = getXmlRpcResponse($posts);
                    printContent($content);
                }
            break;

        // 아무런 요청이 없을 경우 RSD 출력
        default :

                $homepagelink = getUrl('','mid',$this->mid);
                $api_url = sprintf('%s%s/api', Context::getRequestUri(), $this->module);
                $content = <<<RSDContent
<?xml version="1.0" ?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd" >
<service>
    <engineName>zeroboardXE</engineName>
    <engineLink>http://www.zeroboard.com/ </engineLink>
    <homePageLink>{$homepagelink}</homePageLink>
    <apis>
        <api name="MetaWeblog" preferred="true" apiLink="{$api_url}" blogID="" />
    </apis>
</service>
</rsd>
RSDContent;
                printContent($content);
            break;
    }
?>
