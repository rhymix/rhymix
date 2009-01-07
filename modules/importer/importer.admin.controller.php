<?php
    /**
     * @class  importerAdminController
     * @author zero (zero@nzeo.com)
     * @brief  importer 모듈의 admin controller class
     **/

    @set_time_limit(0);
    @require_once('./modules/importer/extract.class.php');

    class importerAdminController extends importer {

        var $unit_count = 300;
        var $oXmlParser = null;

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 회원정보와 게시물 정보를 싱크
         **/
        function procImporterAdminSync() {
            // 게시물정보 싱크
            $output = executeQuery('importer.updateDocumentSync');

            // 댓글정보 싱크
            $output = executeQuery('importer.updateCommentSync');

            $this->setMessage('msg_sync_completed');
        }

        /**
         * @brief XML파일을 미리 분석하여 개발 단위로 캐싱
         **/
        function procImporterAdminPreProcessing() {
            // 이전할 대상 xml파일을 구함
            $xml_file = Context::get('xml_file');

            // 이전할 대상의 type을 구함
            $type = Context::get('type');

            // xml파일에서 정해진 규칙으로 캐싱
            $oExtract = new extract();

            switch($type) {
                case 'member' :
                        $output = $oExtract->set($xml_file,'<members ', '</members>', '<member>', '</member>');
                        if($output->toBool()) $oExtract->saveItems();
                    break;
                case 'message' :
                        $output = $oExtract->set($xml_file,'<messages ', '</messages>', '<message>','</message>');
                        if($output->toBool()) $oExtract->saveItems();
                    break;
                case 'ttxml' :
                        // 카테고리 정보를 먼저 구함
                        $output = $oExtract->set($xml_file,'','<author', '<category>', '<post ');
                        if($output->toBool()) {
                            // ttxml 카테고리는 별도로 구함
                            $started = false;
                            $buff = '';
                            while(!feof($oExtract->fd)) {
                                $str = fgets($oExtract->fd, 1024);
                                if(substr($str,0,strlen('<category>'))=='<category>') $started = true;
                                if(substr($str,0,strlen('<post '))=='<post ') break;
                                if($started) $buff .= $str;
                            }
                            $buff = '<items>'.$buff.'</items>';
                            $oExtract->closeFile();
                            $category_filename = sprintf('%s/%s', $oExtract->cache_path, 'category');
                            FileHandler::writeFile($category_filename, $buff);

                            // 개별 아이템 구함
                            $output = $oExtract->set($xml_file,'<blog', '</blog>', '<post ', '</post>');
                            if($output->toBool()) $oExtract->saveItems();
                        }
                    break;
                default :
                        // 카테고리 정보를 먼저 구함
                        $output = $oExtract->set($xml_file,'<categories>', '</categories>', '<category','</category>');
                        if($output->toBool()) {
                            $oExtract->mergeItems('category');

                            // 개별 아이템 구함
                            $output = $oExtract->set($xml_file,'<posts ', '</posts>', '<post>', '</post>');
                            if($output->toBool()) $oExtract->saveItems();
                        }
                    break;

            }

            if(!$output->toBool()) {
                $this->add('error',0);
                $this->add('status',-1);
                $this->setMessage($output->getMessage());
                return;
            }

            // extract가 종료됨을 알림
            $this->add('type',$type);
            $this->add('total',$oExtract->getTotalCount());
            $this->add('cur',0);
            $this->add('key', $oExtract->getKey());
            $this->add('status',0);
        }

        /**
         * @brief xml파일의 내용이 extract되고 난후 차례대로 마이그레이션
         **/
        function procImporterAdminImport() {
            // 변수 설정
            $type = Context::get('type');
            $total = Context::get('total');
            $cur = Context::get('cur');
            $key = Context::get('key');
            $user_id = Context::get('user_id');
            $target_module = Context::get('target_module');
            $this->unit_count = Context::get('unit_count');
            
            // index파일이 있는지 확인
            $index_file = './files/cache/tmp/'.$key.'/index';
            if(!file_exists($index_file)) return new Object(-1, 'msg_invalid_xml_file');

            switch($type) {
                case 'ttxml' :
                        if(!$target_module) return new Object(-1,'msg_invalid_request');

                        require_once('./modules/importer/ttimport.class.php');
                        $oTT = new ttimport();
                        $cur = $oTT->importModule($key, $cur, $index_file, $this->unit_count, $target_module, $user_id);
                    break;
                case 'message' :
                        $cur = $this->importMessage($key, $cur, $index_file);
                    break;
                case 'member' :
                        $cur = $this->importMember($key, $cur, $index_file);
                    break;
                case 'module' :
                        // 타켓 모듈의 유무 체크
                        if(!$target_module) return new Object(-1,'msg_invalid_request');
                        $cur = $this->importModule($key, $cur, $index_file, $target_module);
                    break;
            }

            // extract가 종료됨을 알림
            $this->add('type',$type);
            $this->add('total',$total);
            $this->add('cur',$cur);
            $this->add('key', $key);
            $this->add('target_module', $target_module);

            // 모두 입력시 성공 메세지 출력하고 cache 파일제거
            if($total <= $cur) {
                $this->setMessage( sprintf(Context::getLang('msg_import_finished'), $cur, $total) );
                FileHandler::removeFilesInDir('./files/cache/tmp/');
            } else $this->setMessage( sprintf(Context::getLang('msg_importing'), $total, $cur) );
        }

        /**
         * @brief 회원 정보 입력
         **/
        function importMember($key, $cur, $index_file) {
            if(!$cur) $cur = 0;

            // xmlParser객체 생성
            $oXmlParser = new XmlParser();

            // 회원 입력을 위한 기본 객체들 생성
            $this->oMemberController = &getController('member');
            $this->oMemberModel = &getModel('member');

            // 기본 회원 그룹을 구함
            $default_group = $this->oMemberModel->getDefaultGroup();
            $default_group_srl = $default_group->group_srl;

            // index파일을 염
            $f = fopen($index_file,"r");

            // 이미 읽혀진 것은 패스
            for($i=0;$i<$cur;$i++) fgets($f, 1024);

            // 라인단위로 읽어들이면서 $cur보다 커지고 $cur+$this->unit_count개보다 작으면 중지
            for($idx=$cur;$idx<$cur+$this->unit_count;$idx++) {
                if(feof($f)) break;

                // 정해진 위치를 찾음
                $target_file = trim(fgets($f, 1024));

                // 대상 파일을 읽여서 파싱후 입력
                $xmlObj = $oXmlParser->loadXmlFile($target_file);
                FileHandler::removeFile($target_file);
                if(!$xmlObj) continue;

                // 객체 정리
                $obj = null;
                $obj->user_id = base64_decode($xmlObj->member->user_id->body);
                $obj->password = base64_decode($xmlObj->member->password->body);
                $obj->user_name = base64_decode($xmlObj->member->user_name->body);
                $obj->nick_name = base64_decode($xmlObj->member->nick_name->body);
                if(!$obj->user_name) $obj->user_name = $obj->nick_name;
                $obj->email = base64_decode($xmlObj->member->email->body);
                $obj->homepage = base64_decode($xmlObj->member->homepage->body);
                $obj->blog = base64_decode($xmlObj->member->blog->body);
                $obj->birthday = substr(base64_decode($xmlObj->member->birthday->body),0,8);
                $obj->allow_mailing = base64_decode($xmlObj->member->allow_mailing->body);
                $obj->point = base64_decode($xmlObj->member->point->body);
                $obj->image_nickname = base64_decode($xmlObj->member->image_nickname->buff->body);
                $obj->image_mark = base64_decode($xmlObj->member->image_mark->buff->body);
                $obj->profile_image = base64_decode($xmlObj->member->profile_image->buff->body);
                $obj->signature = base64_decode($xmlObj->member->signature->body);
                $obj->regdate = base64_decode($xmlObj->member->regdate->body);
                $obj->last_login = base64_decode($xmlObj->member->last_login->body);

                if($xmlObj->member->extra_vars) {
                    foreach($xmlObj->member->extra_vars as $key => $val) {
                        if(in_array($key, array('node_name','attrs','body'))) continue;
                        $obj->extra_vars->{$key} = base64_decode($val->body);
                    }
                }

                // homepage, blog의 url을 정확히 만듬
                if($obj->homepage && !preg_match("/^http:\/\//i",$obj->homepage)) $obj->homepage = 'http://'.$obj->homepage;
                if($obj->blog && !preg_match("/^http:\/\//i",$obj->blog)) $obj->blog = 'http://'.$obj->blog;

                // email address 필드 정리
                $obj->email_address = $obj->email;
                list($obj->email_id, $obj->email_host) = explode('@', $obj->email);

                // 메일링 허용 체크
                if($obj->allow_mailing!='Y') $obj->allow_mailing = 'N';

                // 쪽지 수신 체크
                $obj->allow_message = 'Y';
                if(!in_array($obj->allow_message, array('Y','N','F'))) $obj->allow_message= 'Y';

                // 최종 로그인 시간이 없으면 가입일을 입력
                if(!$obj->last_login) $obj->last_login = $obj->regdate;

                // 회원 번호를 구함
                $obj->member_srl = getNextSequence();

                // 확장변수의 정리
                $extra_vars = $obj->extra_vars;
                unset($obj->extra_vars);
                $obj->extra_vars = serialize($extra_vars);

                // 중복되는 nick_name 데이터가 있는지 체크
                $nick_args = null;
                $nick_args->nick_name = $obj->nick_name;
                $nick_output = executeQuery('member.getMemberSrl', $nick_args);
                if(!$nick_output->toBool()) $obj->nick_name .= '_'.$obj->member_srl;

                // 회원 추가
                $output = executeQuery('member.insertMember', $obj);

                // 입력 성공시 그룹 가입/ 이미지이름-마크-서명등을 추가
                if($output->toBool()) {

                    // 기본 그룹 가입 시킴 
                    $obj->group_srl = $default_group_srl;
                    executeQuery('member.addMemberToGroup',$obj);

                    // 이미지네임
                    if($obj->image_nickname) {
                        $target_path = sprintf('files/member_extra_info/image_name/%s/', getNumberingPath($obj->member_srl));
                        $target_filename = sprintf('%s%d.gif', $target_path, $obj->member_srl);
                        FileHandler::writeFile($target_filename, $obj->image_nickname);
                    }

                    // 이미지마크
                    if($obj->image_mark && file_exists($obj->image_mark)) {
                        $target_path = sprintf('files/member_extra_info/image_mark/%s/', getNumberingPath($obj->member_srl));
                        $target_filename = sprintf('%s%d.gif', $target_path, $obj->member_srl);
                        FileHandler::writeFile($target_filename, $obj->image_mark);
                    }

                    // 프로필 이미지
                    if($obj->profile_image) {
                        $target_path = sprintf('files/member_extra_info/profile_image/%s/', getNumberingPath($obj->member_srl));
                        $target_filename = sprintf('%s%d.gif', $target_path, $obj->member_srl);
                        FileHandler::writeFile($target_filename, $obj->profile_image);
                    }

                    // 서명
                    if($obj->signature) {
                        $signature = removeHackTag($obj->signature);
                        $signature_buff = sprintf('<?php if(!defined("__ZBXE__")) exit();?>%s', $signature);

                        $target_path = sprintf('files/member_extra_info/signature/%s/', getNumberingPath($obj->member_srl));
                        if(!is_dir($target_path)) FileHandler::makeDir($target_path);
                        $target_filename = sprintf('%s%d.signature.php', $target_path, $obj->member_srl);

                        FileHandler::writeFile($target_filename, $signature_buff);
                    }
                }
            }

            fclose($f);

            return $idx-1;
        }

        /**
         * @brief 주어진 xml 파일을 파싱해서 쪽지 정보 입력
         **/
        function importMessage($key, $cur, $index_file) {
            if(!$cur) $cur = 0;

            // xmlParser객체 생성
            $oXmlParser = new XmlParser();

            // index파일을 염
            $f = fopen($index_file,"r");

            // 이미 읽혀진 것은 패스
            for($i=0;$i<$cur;$i++) fgets($f, 1024);

            // 라인단위로 읽어들이면서 $cur보다 커지고 $cur+$this->unit_count개보다 작으면 중지
            for($idx=$cur;$idx<$cur+$this->unit_count;$idx++) {
                if(feof($f)) break;

                // 정해진 위치를 찾음
                $target_file = trim(fgets($f, 1024));

                // 대상 파일을 읽여서 파싱후 입력
                $xmlObj = $oXmlParser->loadXmlFile($target_file);
                FileHandler::removeFile($target_file);
                if(!$xmlObj) continue;

                // 객체 정리
                $obj = null;
                $obj->receiver = base64_decode($xmlObj->message->receiver->body);
                $obj->sender = base64_decode($xmlObj->message->sender->body);
                $obj->title = base64_decode($xmlObj->message->title->body);
                $obj->content = base64_decode($xmlObj->message->content->body);
                $obj->readed = base64_decode($xmlObj->message->readed->body)=='Y'?'Y':'N';
                $obj->regdate = base64_decode($xmlObj->message->regdate->body);
                $obj->readed_date = base64_decode($xmlObj->message->readed_date->body);
                $obj->receiver = base64_decode($xmlObj->message->receiver->body);

                // 보낸이/ 받는이의 member_srl을 구함 (존재하지 않으면 그냥 pass..)
                if(!$obj->sender) continue;
                $sender_args->user_id = $obj->sender;
                $sender_output = executeQuery('member.getMemberInfo',$sender_args);
                $sender_srl = $sender_output->data->member_srl;
                if(!$sender_srl) continue;

                $receiver_args->user_id = $obj->receiver;
                if(!$obj->receiver) continue;
                $receiver_output = executeQuery('member.getMemberInfo',$receiver_args);
                $receiver_srl = $receiver_output->data->member_srl;
                if(!$receiver_srl) continue;

                // 보내는 사용자의 쪽지함에 넣을 쪽지
                $sender_args->sender_srl = $sender_srl;
                $sender_args->receiver_srl = $receiver_srl;
                $sender_args->message_type = 'S';
                $sender_args->title = $obj->title;
                $sender_args->content = $obj->content;
                $sender_args->readed = $obj->readed;
                $sender_args->regdate = $obj->regdate;
                $sender_args->readed_date = $obj->readed_date;
                $sender_args->related_srl = getNextSequence();
                $sender_args->message_srl = getNextSequence();
                $sender_args->list_order = $sender_args->message_srl * -1;

                $output = executeQuery('communication.sendMessage', $sender_args);
                if($output->toBool()) {
                    // 받는 회원의 쪽지함에 넣을 쪽지
                    $receiver_args->message_srl = $sender_args->related_srl;
                    $receiver_args->list_order = $sender_args->related_srl*-1;
                    $receiver_args->sender_srl = $sender_srl;
                    if(!$receiver_args->sender_srl) $receiver_args->sender_srl = $receiver_srl;
                    $receiver_args->receiver_srl = $receiver_srl;
                    $receiver_args->message_type = 'R';
                    $receiver_args->title = $obj->title;
                    $receiver_args->content = $obj->content;
                    $receiver_args->readed = $obj->readed;
                    $receiver_args->regdate = $obj->regdate;
                    $receiver_args->readed_date = $obj->readed_date;
                    $output = executeQuery('communication.sendMessage', $receiver_args);
                }
            }

            fclose($f);

            return $idx-1;
        }

        /**
         * @brief module.xml 형식의 데이터 import
         **/
        function importModule($key, $cur, $index_file, $module_srl) {
            // 필요한 객체 미리 생성
            $this->oXmlParser = new XmlParser();

            // 타겟 모듈의 카테고리 정보 구함
            $oDocumentController = &getController('document');
            $oDocumentModel = &getModel('document');
            $category_list = $category_titles = array();
            $category_list = $oDocumentModel->getCategoryList($module_srl);
            if(count($category_list)) foreach($category_list as $key => $val) $category_titles[$val->title] = $val->category_srl;

            // 먼저 카테고리 정보를 입력함
            $category_file = preg_replace('/index$/i', 'category', $index_file);
            if(file_exists($category_file)) {
                $buff = FileHandler::readFile($category_file);
               
                // xmlParser객체 생성
                $xmlDoc = $this->oXmlParser->loadXmlFile($category_file);

                $categories = $xmlDoc->items->category;
                if($categories) {
                    if(!is_array($categories)) $categories = array($categories);
                    $match_sequence = array();
                    foreach($categories as $k => $v) {
                        $category = trim(base64_decode($v->body));
                        if(!$category || $category_titles[$category]) continue;

                        $sequence = $v->attrs->sequence;
                        $parent = $v->attrs->parent;

                        $obj = null;
                        $obj->title = $category;
                        $obj->module_srl = $module_srl; 
                        if($parent) $obj->parent_srl = $match_sequence[$parent];

                        $output = $oDocumentController->insertCategory($obj);
                        if($output->toBool()) $match_sequence[$sequence] = $output->get('category_srl');
                    }
                    $oDocumentController = &getController('document');
                    $oDocumentController->makeCategoryFile($module_srl);
                }
                FileHandler::removeFile($category_file);
            }

            $category_list = $category_titles = array();
            $category_list = $oDocumentModel->getCategoryList($module_srl);
            if(count($category_list)) foreach($category_list as $key => $val) $category_titles[$val->title] = $val->category_srl;
           
            if(!$cur) $cur = 0;

            // index파일을 염
            $f = fopen($index_file,"r");

            // 이미 읽혀진 것은 패스
            for($i=0;$i<$cur;$i++) fgets($f, 1024);

            // 라인단위로 읽어들이면서 $cur보다 커지고 $cur+$this->unit_count개보다 작으면 중지
            for($idx=$cur;$idx<$cur+$this->unit_count;$idx++) {
                if(feof($f)) break;

                // 정해진 위치를 찾음
                $target_file = trim(fgets($f, 1024));

                if(!file_exists($target_file)) continue;

                // 이제부터 데이터를 가져오면서 처리
                $fp = fopen($target_file,"r");
                if(!$fp) continue;

                $obj = null;
                $obj->module_srl = $module_srl;
                $obj->document_srl = getNextSequence();

                $files = array();

                $started = false;
                $buff = null;

                // 본문 데이터부터 처리 시작
                while(!feof($fp)) {
                    $str = fgets($fp, 1024);

                    // 한 아이템 준비 시작
                    if(trim($str) == '<post>') {
                        $started = true;

                    // 엮인글 입력
                    } else if(substr($str,0,11) == '<trackbacks') {
                        $obj->trackback_count = $this->importTrackbacks($fp, $module_srl, $obj->document_srl);
                        continue;

                    // 댓글 입력
                    } else if(substr($str,0,9) == '<comments') {
                        $obj->comment_count = $this->importComments($fp, $module_srl, $obj->document_srl);
                        continue;

                    // 첨부파일 입력
                    } else if(substr($str,0,9) == '<attaches') {
                        $obj->uploaded_count = $this->importAttaches($fp, $module_srl, $obj->document_srl, $files);
                        continue;

                    // 추가 변수 시작 일 경우 
                    } elseif(trim($str) == '<extra_vars>') {
                        $this->importExtraVars($fp, $obj);
                        continue;
                    }

                    if($started) $buff .= $str;
                }

                $xmlDoc = $this->oXmlParser->parse($buff);
                
                $category = base64_decode($xmlDoc->post->category->body);
                if($category_titles[$category]) $obj->category_srl = $category_titles[$category];

                $obj->member_srl = 0;

                $obj->is_notice = base64_decode($xmlDoc->post->is_notice->body)=='Y'?'Y':'N';
                $obj->is_secret = base64_decode($xmlDoc->post->is_secret->body)=='Y'?'Y':'N';
                $obj->title = base64_decode($xmlDoc->post->title->body);
                $obj->content = base64_decode($xmlDoc->post->content->body);
                $obj->readed_count = base64_decode($xmlDoc->post->readed_count->body);
                $obj->voted_count = base64_decode($xmlDoc->post->voted_count->body);
                $obj->password = base64_decode($xmlDoc->post->password->body);
                $obj->user_name = $obj->nick_name = base64_decode($xmlDoc->post->nick_name->body);
                $obj->user_id = base64_decode($xmlDoc->post->user_id->body);
                $obj->email_address = base64_decode($xmlDoc->post->email->body);
                $obj->homepage = base64_decode($xmlDoc->post->homepage->body);
                if($obj->homepage && !preg_match('/^http:\/\//i',$obj->homepage)) $obj->homepage = 'http://'.$obj->homepage;
                $obj->tags = base64_decode($xmlDoc->post->tags->body);
                $obj->regdate = base64_decode($xmlDoc->post->regdate->body);
                $obj->last_update = base64_decode($xmlDoc->post->update->body);
                if(!$obj->last_update) $obj->last_update = $obj->regdate;
                $obj->ipaddress = base64_decode($xmlDoc->post->ipaddress->body);
                $obj->list_order = $obj->update_order = $obj->document_srl*-1;
                $obj->allow_comment = base64_decode($xmlDoc->post->allow_comment->body)!='N'?'Y':'N';
                $obj->lock_comment = base64_decode($xmlDoc->post->lock_comment->body)=='Y'?'Y':'N';
                $obj->allow_trackback = base64_decode($xmlDoc->post->allow_trackback->body)!='N'?'Y':'N';
                $obj->notify_message = base64_decode($xmlDoc->post->is_notice->body);

                // content 정보 변경 (첨부파일)
                if(count($files)) {
                    foreach($files as $key => $val) {
                        $obj->content = preg_replace('/(src|href)\=(["\']?)'.preg_quote($key).'(["\']?)/i','$1="'.$val.'"',$obj->content);
                    }
                }

                $output = executeQuery('document.insertDocument', $obj);

                if($output->toBool() && $obj->tags) {
                    $tag_list = explode(',',$obj->tags);
                    $tag_count = count($tag_list);
                    for($i=0;$i<$tag_count;$i++) {
                        $args = null;
                        $args->tag_srl = getNextSequence();
                        $args->module_srl = $module_srl;
                        $args->document_srl = $obj->document_srl;
                        $args->tag = trim($tag_list[$i]);
                        $args->regdate = $obj->regdate;
                        if(!$args->tag) continue;
                        $output = executeQuery('tag.insertTag', $args);
                    }
                    
                }

                fclose($fp);
                FileHandler::removeFile($target_file);
            }

            fclose($f);

            // 카테고리별 개수 동기화
            if(count($category_list)) foreach($category_list as $key => $val) $oDocumentController->updateCategoryCount($module_srl, $val->category_srl);

            return $idx-1;
        }

        /**
         * @brief 엮인글 정리
         **/
        function importTrackbacks($fp, $module_srl, $document_srl) {
            $started = false;
            $buff = null;
            $cnt = 0;
            while(!feof($fp)) {

                $str = fgets($fp, 1024);

                // </trackbacks>이면 중단
                if(trim($str) == '</trackbacks>') break;

                // <trackback>면 시작
                if(trim($str) == '<trackback>') $started = true;

                if($started) $buff .= $str;

                // </trackback>이면 DB에 입력
                if(trim($str) == '</trackback>') {
                    $xmlDoc = $this->oXmlParser->parse($buff);

                    $obj = null;
                    $obj->trackback_srl = getNextSequence();
                    $obj->module_srl = $module_srl;
                    $obj->document_srl = $document_srl;
                    $obj->url = base64_decode($xmlDoc->trackback->url->body);
                    $obj->title = base64_decode($xmlDoc->trackback->title->body);
                    $obj->blog_name = base64_decode($xmlDoc->trackback->blog_name->body);
                    $obj->excerpt = base64_decode($xmlDoc->trackback->excerpt->body);
                    $obj->regdate = base64_decode($xmlDoc->trackback->regdate->body);
                    $obj->ipaddress = base64_decode($xmlDoc->trackback->ipaddress->body);
                    $obj->list_order = -1*$obj->trackback_srl;
                    $output = executeQuery('trackback.insertTrackback', $obj);
                    if($output->toBool()) $cnt++;

                    $buff = null;
                    $started = false;
                }
            }
            return $cnt;
        }

        /**
         * @brief 댓글 정리
         **/
        function importComments($fp, $module_srl, $document_srl) {
            $started = false;
            $buff = null;
            $cnt = 0;

            $sequences = array();

            while(!feof($fp)) {

                $str = fgets($fp, 1024);

                // </comments>이면 중단
                if(trim($str) == '</comments>') break;

                // <comment>면 시작
                if(trim($str) == '<comment>') {
                    $started = true;
                    $obj = null;
                    $obj->comment_srl = getNextSequence();
                    $files = array();
                }

                // attaches로 시작하면 첨부파일 시작
                if(substr($str,0,9) == '<attaches') {
                    $obj->uploaded_count = $this->importAttaches($fp, $module_srl, $obj->comment_srl, $files);
                    continue;
                }

                if($started) $buff .= $str;

                // </comment>이면 DB에 입력
                if(trim($str) == '</comment>') {
                    $xmlDoc = $this->oXmlParser->parse($buff);

                    $sequence = base64_decode($xmlDoc->comment->sequence->body);
                    $sequences[$sequence] = $obj->comment_srl;
                    $parent = base64_decode($xmlDoc->comment->parent->body);

                    $obj->module_srl = $module_srl;

                    if($parent) $obj->parent_srl = $sequences[$parent];
                    else $obj->parent_srl = 0;

                    $obj->document_srl = $document_srl;
                    $obj->is_secret = base64_decode($xmlDoc->comment->is_secret->body)=='Y'?'Y':'N';
                    $obj->notify_message = base64_decode($xmlDoc->comment->notify_message->body)=='Y'?'Y':'N';
                    $obj->content = base64_decode($xmlDoc->comment->content->body);
                    $obj->voted_count = base64_decode($xmlDoc->comment->voted_count->body);
                    $obj->password = base64_decode($xmlDoc->comment->password->body);
                    $obj->user_name = $obj->nick_name = base64_decode($xmlDoc->comment->nick_name->body);
                    $obj->user_id = base64_decode($xmlDoc->comment->user_id->body);
                    $obj->member_srl = 0;
                    $obj->email_address = base64_decode($xmlDoc->comment->email->body);
                    $obj->homepage = base64_decode($xmlDoc->comment->homepage->body);
                    $obj->regdate = base64_decode($xmlDoc->comment->regdate->body);
                    $obj->last_update = base64_decode($xmlDoc->comment->update->body);
                    if(!$obj->last_update) $obj->last_update = $obj->regdate;
                    $obj->ipaddress = base64_decode($xmlDoc->comment->ipaddress->body);
                    $obj->list_order = $obj->comment_srl*-1;

                    // content 정보 변경 (첨부파일)
                    if(count($files)) {
                        foreach($files as $key => $val) {
                            $obj->content = preg_replace('/(src|href)\=(["\']?)'.preg_quote($key).'(["\']?)/i','$1="'.$val.'"',$obj->content);
                        }
                    }

                    // 댓글 목록 부분을 먼저 입력
                    $list_args = null;
                    $list_args->comment_srl = $obj->comment_srl;
                    $list_args->document_srl = $obj->document_srl;
                    $list_args->module_srl = $obj->module_srl;
                    $list_args->regdate = $obj->regdate;

                    // 부모댓글이 없으면 바로 데이터를 설정
                    if(!$obj->parent_srl) {
                        $list_args->head = $list_args->arrange = $obj->comment_srl;
                        $list_args->depth = 0;

                    // 부모댓글이 있으면 부모글의 정보를 구해옴
                    } else {
                        // 부모댓글의 정보를 구함
                        $parent_args->comment_srl = $obj->parent_srl;
                        $parent_output = executeQuery('comment.getCommentListItem', $parent_args);

                        // 부모댓글이 존재하지 않으면 return
                        if(!$parent_output->toBool() || !$parent_output->data) continue;
                        $parent = $parent_output->data;

                        $list_args->head = $parent->head;
                        $list_args->depth = $parent->depth+1;
                        if($list_args->depth<2) $list_args->arrange = $obj->comment_srl;
                        else {
                            $list_args->arrange = $parent->arrange;
                            $output = executeQuery('comment.updateCommentListArrange', $list_args);
                            if(!$output->toBool()) return $output;
                        }
                    }

                    $output = executeQuery('comment.insertCommentList', $list_args);
                    if($output->toBool()) {
                        $output = executeQuery('comment.insertComment', $obj);
                        if($output->toBool()) $cnt++;
                    }

                    $buff = null;
                    $started = false;
                }
            }
            return $cnt;
        }

        /**
         * @brief 첨부파일 정리
         **/
        function importAttaches($fp, $module_srl, $upload_target_srl, &$files) {
            $uploaded_count = 0;

            $started = false;
            $buff = null;

            while(!feof($fp)) {
                $str = trim(fgets($fp, 1024));

                // </attaches>로 끝나면 중단
                if(trim($str) == '</attaches>') break;

                // <attach>로 시작하면 첨부파일 수집
                if(trim($str) == '<attach>') {
                    $file_obj  = null;
                    $file_obj->file_srl = getNextSequence();
                    $file_obj->upload_target_srl = $upload_target_srl;
                    $file_obj->module_srl = $module_srl;

                    $started = true;
                    $buff = null;
                // <file>로 시작하면 xml파일내의 첨부파일로 처리
                } else if(trim($str) == '<file>') {
                    $file_obj->file = $this->saveTemporaryFile($fp);
                    continue;
                }

                if($started) $buff .= $str;

                // </attach>로 끝나면 첨부파일 정리
                if(trim($str) == '</attach>') {
                    $xmlDoc = $this->oXmlParser->parse($buff.$str);

                    $file_obj->source_filename = base64_decode($xmlDoc->attach->filename->body);
                    $file_obj->download_count = base64_decode($xmlDoc->attach->download_count->body);

                    if(!$file_obj->file) {
                        $url = base64_decode($xmlDoc->attach->url->body);
                        $path = base64_decode($xmlDoc->attach->path->body);
                        if($path && file_exists($path)) $file_obj->file = $path;
                        else {
                            $file_obj->file = $this->getTmpFilename();
                            FileHandler::getRemoteFile($url, $file_obj->file);
                        }
                    }

                    if(file_exists($file_obj->file)) {

                        // 이미지인지 기타 파일인지 체크하여 upload path 지정
                        if(preg_match("/\.(jpg|jpeg|gif|png|wmv|wma|mpg|mpeg|avi|swf|flv|mp1|mp2|mp3|asaf|wav|asx|mid|midi|asf|mov|moov|qt|rm|ram|ra|rmm)$/i", $file_obj->source_filename)) {
                            $path = sprintf("./files/attach/images/%s/%s", $module_srl,getNumberingPath($upload_target_srl,3));
                            $filename = $path.$file_obj->source_filename;
                            $file_obj->direct_download = 'Y';
                        } else {
                            $path = sprintf("./files/attach/binaries/%s/%s", $module_srl, getNumberingPath($upload_target_srl,3));
                            $filename = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
                            $file_obj->direct_download = 'N';
                        }

                        // 디렉토리 생성
                        if(!FileHandler::makeDir($path)) continue;

                        if(preg_match('/^\.\/files\/cache\/tmp/i',$file_obj->file)) FileHandler::rename($file_obj->file, $filename);
                        else @copy($file_obj->file, $filename);

                        // DB입력
                        unset($file_obj->file);
                        if(file_exists($filename)) {
                            $file_obj->uploaded_filename = $filename;
                            $file_obj->file_size = filesize($filename);
                            $file_obj->comment = NULL;
                            $file_obj->member_srl = 0;
                            $file_obj->sid = md5(rand(rand(1111111,4444444),rand(4444445,9999999)));
                            $file_obj->isvalid = 'Y';
                            $output = executeQuery('file.insertFile', $file_obj);
                            
                            if($output->toBool()) {
                                $uploaded_count++;
                                $tmp_obj = null;
                                $tmp_obj->source_filename = $file_obj->source_filename;
                                if($file_obj->direct_download == 'Y') $files[$file_obj->source_filename] = $file_obj->uploaded_filename; 
                                else $files[$file_obj->source_filename] = getUrl('','module','file','act','procFileDownload','file_srl',$file_obj->file_srl,'sid',$file_obj->sid);
                            }
                        }
                    }
                }
            }
            return $uploaded_count;
        }

        /**
         * @biref 임의로 사용할 파일이름을 return
         **/
        function getTmpFilename() {
            $path = "./files/cache/tmp";
            if(!is_dir($path)) FileHandler::makeDir($path);
            $filename = sprintf("%s/%d", $path, rand(11111111,99999999));
            if(file_exists($filename)) $filename .= rand(111,999);
            return $filename;
        }

        /**
         * @brief 특정 파일포인트로부터 key에 해당하는 값이 나타날때까지 buff를 읽음
         **/
        function saveTemporaryFile($fp) {
            $temp_filename = $this->getTmpFilename();
            $f = fopen($temp_filename, "w");

            $buff = '';
            while(!feof($fp)) {
                $str = trim(fgets($fp, 1024));
                if(trim($str) == '</file>') break;

                $buff .= $str;

                if(substr($buff,-7)=='</buff>') {
                    fwrite($f, base64_decode(substr($buff, 6, -7)));
                    $buff = '';
                }
            }
            fclose($f);
            return $temp_filename;
        }


        /**
         * @brief 게시글 추가 변수 설정
         **/
        function importExtraVars($fp, &$obj) {
            $index = 1;
            while(!feof($fp)) {
                $str = trim(fgets($fp, 1024));
                if(trim($str) == '</extra_vars>') break;

                $buff .= $str;
                $pos = strpos($buff, '>');
                $key = substr($buff, 1, $pos-1);
                if(substr($buff, -1 * ( strlen($key)+3)) == '</'.$key.'>') {
                    $val = base64_decode(substr($buff, $pos, strlen($buff)-$pos*2-2));
                    $obj->{"extra_vars".$index} = $val;
                    $buff = null;
                    $index++;
                }
            }
        }
    }
?>
