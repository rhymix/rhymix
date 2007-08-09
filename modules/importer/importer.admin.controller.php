<?php
    /**
     * @class  importerAdminController
     * @author zero (zero@nzeo.com)
     * @brief  importer 모듈의 admin controller class
     **/

    class importerAdminController extends importer {

        var $oXml = null;
        var $oMemberController = null;
        var $oMemberModel = null;
        var $oDocumentController = null;
        var $oFileController = null;
        var $oCommentController = null;
        var $oTrackbackController = null;

        var $total_count = '';
        var $start_position = 0;
        var $position = 0;
        var $limit_count = 50;
        var $file_point = 0;
        var $default_group_srl = 0;

        var $module_srl = 0;
        var $target_path = 0;
        var $category_srl = 0;
        var $category_list = array();
        var $msg = null;

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief import step1
         * import하려는 대상에 따라 결과값을 구해서 return
         * 회원정보 : next_step=2, module_list = null
         * 모듈정보 : next_step=12, module_list = modules..
         * 회원정보 동기화 : next_step=3
         **/
        function procImporterAdminStep1() {
            $source_type = Context::get('source_type');
            switch($source_type) {
                case 'module' :
                        // 모듈 목록을 구함
                        $oModuleModel = &getModel('module');
                        $module_list = $oModuleModel->getMidList();
                        foreach($module_list as $key => $val) {
                            $module_list_arr[] = sprintf('%d,%s (%s)', $val->module_srl, $val->browser_title, $val->mid);
                        }
                        if(count($module_list_arr)) $module_list = implode("\n",$module_list_arr);
                        $next_step = 12;
                    break;
                case 'member' :
                        $next_step = 2;
                    break;
                case 'syncmember' :
                        $next_step = 3;
                    break;
            }

            $this->add('next_step', $next_step);
            $this->add('module_list', $module_list);
        }

        /**
         * @brief import step12
         * module_srl을 이용하여 대상 모듈에 카테고리값이 있는지 확인하여
         * 있으면 카테고리 정보를 return, 아니면 파일 업로드 단계로 이동
         **/
        function procImporterAdminStep12() {
            $target_module= Context::get('target_module');

            // 대상 모듈의 카테고리 목록을 구해옴
            $oDocumentModel = &getModel('document');
            $category_list = $oDocumentModel->getCategoryList($target_module);

            if(count($category_list)) {
                foreach($category_list as $key => $val) {
                    $category_list_arr[] = sprintf('%d,%s', $val->category_srl, $val->title);
                }
                if(count($category_list_arr)) {
                    $category_list = implode("\n",$category_list_arr);
                    $next_step = 13;
                }
            } else {
                $category_list = null;
                $next_step = 2;
            }

            $this->add('next_step', $next_step);
            $this->add('category_list', $category_list);
        }

        /**
         * @brief import 실행
         **/
        function procImporterAdminImport() {
            // 실행시간 무한대로 설정
            @set_time_limit(0);

            // 변수 체크
            $this->module_srl = Context::get('module_srl');
            $this->target_path = Context::get('target_path');
            if(substr($this->target_path,-1)!="/") $this->target_path .= "/";
            $this->category_srl = Context::get('category_srl');
            $xml_file = Context::get('xml_file');
            $this->start_position = $this->position = (int)Context::get('position');
            $this->total_count = (int)Context::get('total_count');
            $this->file_point = (int)Context::get('file_point');

            // 파일을 찾을 수 없으면 에러 표시
            if(!file_exists($xml_file)) return new Object(-1,'msg_no_xml_file');

            $this->oXml = new XmlParser();

            $oDB = &DB::getInstance();
            $oDB->begin();

            // module_srl이 있으면 module데이터로 판단하여 처리, 아니면 회원정보로..
            if($this->module_srl) {
                $this->limit_count = 100;
                $is_finished = $this->importDocument($xml_file);
            } else {
                $this->limit_count = 500;
                $is_finished = $this->importMember($xml_file);
            }

            $oDB->commit();

            if($is_finished) {
                $this->add('is_finished', 'Y');
                $this->add('position', $this->total_count);
                $this->add('total_count', $this->total_count);
                $this->setMessage( sprintf(Context::getLang('msg_import_finished'), $this->position) );
            } else {
                $this->add('position', $this->position);
                $this->add('total_count', $this->total_count);
                $this->add('file_point', $this->file_point);
                $this->add('is_finished', 'N');

                $this->setMessage( $this->msg );
            }
        }

        /**
         * @brief 회원정보 import
         **/
        function importMember($xml_file) {
            $filesize = filesize($xml_file);
            if($filesize<1) return true;

            $this->oMemberController = &getController('member');
            $this->oMemberModel = &getModel('member');

            $default_group = $this->oMemberModel->getDefaultGroup();
            $this->default_group_srl = $default_group->group_srl;

            $is_finished = true;

            $fp = @fopen($xml_file, "r");
            if($this->file_point) fseek($fp, $this->file_point, SEEK_SET);
            if($fp) {
                $buff = '';
                while(!feof($fp)) {
                    $str = fgets($fp,1024);
                    $buff .= $str;

                    $buff = preg_replace_callback("!<root([^>]*)>!is", array($this, '_parseRootInfo'), $buff);
                    $buff = preg_replace_callback("!<member user_id=\"([^\"]*)\">(.*?)<\/member>!is", array($this, '_importMember'), $buff);

                    if($this->start_position+$this->limit_count <= $this->position) {
                        $is_finished = false;
                        $this->file_point = ftell($fp) - strlen($buff);;
                        break;
                    }
                }
                fclose($fp);
            }

            return $is_finished;
        }

        function _importMember($matches) {
            $user_id = $matches[1];
            $xml_doc = $this->oXml->parse($matches[0]);

            $args->user_id = strtolower($xml_doc->member->user_id->body);
            $args->user_name = $xml_doc->member->user_name->body;
            $args->nick_name = $xml_doc->member->nick_name->body;
            $args->homepage = $xml_doc->member->homepage->body;
            $args->blog = $xml_doc->member->blog->body;
            if($args->homepage && !eregi("^http:\/\/",$args->homepage)) $args->homepage = 'http://'.$args->homepage;
            if($args->blog && !eregi("^http:\/\/",$args->blog)) $args->blog = 'http://'.$args->blog;
            $args->birthday = $xml_doc->member->birthday->body;
            $args->email_address = $xml_doc->member->email_address->body;
            list($args->email_id, $args->email_host) = explode('@', $args->email_address);
            $args->password = $xml_doc->member->password->body;
            $args->regdate = $xml_doc->member->regdate->body;
            $args->allow_mailing = $xml_doc->member->allow_mailing->body;
            if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
            $args->allow_message = 'Y';
            if(!in_array($args->allow_message, array('Y','N','F'))) $args->allow_message= 'Y';

            $args->member_srl = getNextSequence();
            $output = executeQuery('member.insertMember', $args);

            if(!$output->toBool()) {
                // 닉네임이 같으면 닉네임을 변경후 재 입력
                $member_srl = $this->oMemberModel->getMemberSrlByNickName($args->nick_name);
                if($member_srl) {
                    $args->nick_name .= rand(111,999);
                    $output = executeQuery('member.insertMember', $args);
                }
            }

            if($output->toBool()) {

                // 기본 그룹 가입 시킴 
                $member_srl = $args->member_srl;
                $args->group_srl = $this->default_group_srl;
                executeQuery('member.addMemberToGroup',$args);

                // 이미지네임
                if($xml_doc->member->image_nickname->body) {
                    $image_nickname = base64_decode($xml_doc->member->image_nickname->body);
                    FileHandler::writeFile('./files/cache/tmp_imagefile.gif', $image_nickname);
                    $this->oMemberController->insertImageName($member_srl, './files/cache/tmp_imagefile.gif');
                    @unlink('./files/cache/tmp_imagefile.gif');
                }

                // 이미지 마크
                if($xml_doc->member->image_mark->body) {
                    $image_mark = base64_decode($xml_doc->member->image_mark->body);
                    FileHandler::writeFile('./files/cache/tmp_imagefile.gif', $image_mark);
                    $this->oMemberController->insertImageMark($member_srl, './files/cache/tmp_imagefile.gif');
                    @unlink('./files/cache/tmp_imagefile.gif');
                }

                // 서명
                if($xml_doc->member->signature->body) {
                    $this->oMemberController->putSignature($member_srl, base64_decode($xml_doc->member->signature->body));
                }
            } else {
                $this->msg .= $args->user_id." : ".$output->getMessage()."<br />";
            }

            $this->position++;

            return '';
        }

        /**
         * @brief 게시물정보 import
         **/
        function importDocument($xml_file) {
            $filesize = filesize($xml_file);
            if($filesize<1) return;

            $this->oDocumentController = &getController('document');
            $this->oFileController = &getController('file');
            $this->oCommentController = &getController('comment');
            $this->oTrackbackController = &getController('trackback');

            $oDocumentModel = &getModel('document');
            $tmp_category_list = $oDocumentModel->getCategoryList($this->module_srl);
            if(count($tmp_category_list)) {
                foreach($tmp_category_list as $key => $val) $this->category_list[$val->title] = $key;
            } else {
                $this->category_list = array();
            }

            $is_finished = true;

            $fp = @fopen($xml_file, "r");
            if($this->file_point) fseek($fp, $this->file_point, SEEK_SET);
            if($fp) {
                $buff = '';
                while(!feof($fp)) {
                    $str = fread($fp,1024);
                    $buff .= $str;
                    $buff = preg_replace_callback("!<root([^>]*)>!is", array($this, '_parseRootInfo'), $buff);
                    if(!$this->category_srl) $buff = preg_replace_callback("!<categories>(.*?)</categories>!is", array($this, '_parseCategoryInfo'), $buff);
                    $buff = preg_replace_callback("!<document sequence=\"([^\"]*)\">(.*?)<\/document>!is", array($this, '_importDocument'), $buff);

                    if($this->start_position+$this->limit_count <= $this->position) {
                        $is_finished = false;
                        $this->file_point = ftell($fp) - strlen($buff);;
                        break;
                    }
                }
                fclose($fp);
            }

            return $is_finished;
        }

        function _importDocument($matches) {
            $sequence = $matches[1];
            $matches[0] = str_replace(array('',''),'',$matches[0]);
            $xml_doc = $this->oXml->parse($matches[0]);

            // 문서 번호와 내용 미리 구해 놓기
            $args->document_srl = getNextSequence();
            $args->content = $xml_doc->document->content->body;

            // 첨부파일 미리 등록
            $files = $xml_doc->document->files->file;
            if($files && !is_array($files)) $files = array($files);
            if(count($files)) {
                foreach($files as $key => $val) {
                    $filename = $val->filename->body;
                    $path = $val->path->body;
                    $download_count = (int)$val->download_count->body;

                    $tmp_filename = './files/cache/tmp_uploaded_file';

                    $path = $this->target_path.$path;

                    if(!eregi("^http",$path)) {
                        if(preg_match('/[\xEA-\xED][\x80-\xFF]{2}/', $path)&&function_exists('iconv')) {
                            $tmp_path = iconv("UTF-8","EUC-KR",$path);
                            if(file_exists($tmp_path)) $path = $tmp_path;
                        }
                        if(file_exists($path)) @copy($path, $tmp_filename);

                    } else FileHandler::getRemoteFile($path, $tmp_filename);

                    if(file_exists($tmp_filename)) {
                      $file_info['tmp_name'] = $tmp_filename;
                      $file_info['name'] = $filename;
                      $this->oFileController->insertFile($file_info, $this->module_srl, $args->document_srl, $download_count, true);

                      // 컨텐츠의 내용 수정 (이미지 첨부파일 관련)
                      if(eregi("\.(jpg|gif|jpeg|png)$", $filename)) $args->content = str_replace($filename, sprintf('./files/attach/images/%s/%s/%s', $this->module_srl, $args->document_srl, $filename), $args->content);
                    }
                    @unlink($tmp_filename);
                }
            }

            // 문서 입력
            $args->module_srl = $this->module_srl;
            if($this->category_srl) $args->category_srl = $this->category_srl;
            elseif($xml_doc->document->category->body) $args->category_srl = $this->category_list[$xml_doc->document->category->body];
            $args->is_notice = $xml_doc->document->is_notice->body;
            $args->is_secret = $xml_doc->document->is_secret->body;
            $args->title = $xml_doc->document->title->body;
            $args->readed_count = $xml_doc->document->readed_count->body;
            $args->voted_count = $xml_doc->document->voted_count->body;
            $args->comment_count = $xml_doc->document->comment_count->body;
            $args->trackback_count = $xml_doc->document->trackback_count->body;
            $args->uploaded_count = $xml_doc->document->uploaded_count->body;
            $args->password = $xml_doc->document->password->body;
            $args->nick_name = $xml_doc->document->nick_name->body;
            $args->member_srl = -1;
            $args->user_id = $xml_doc->document->user_id->body;
            $args->user_name = $xml_doc->document->user_name->body;
            $args->email_address = $xml_doc->document->email_address->body;
            $args->homepage = $xml_doc->document->homepage->body;
            $args->tags = $xml_doc->document->tags->body;
            $args->regdate = $xml_doc->document->regdate->body;
            $args->ipaddress = $xml_doc->document->ipaddress->body;
            $args->allow_comment = $xml_doc->document->allow_comment->body;
            $args->lock_comment = $xml_doc->document->lock_comment->body;
            $args->allow_trackback = $xml_doc->document->allow_trackback->body;
            $args->extra_vars1 = $xml_doc->document->extra_vars1->body;
            $args->extra_vars2 = $xml_doc->document->extra_vars2->body;
            $args->extra_vars3 = $xml_doc->document->extra_vars3->body;
            $args->extra_vars4 = $xml_doc->document->extra_vars4->body;
            $args->extra_vars5 = $xml_doc->document->extra_vars5->body;
            $args->extra_vars6 = $xml_doc->document->extra_vars6->body;
            $args->extra_vars7 = $xml_doc->document->extra_vars7->body;
            $args->extra_vars8 = $xml_doc->document->extra_vars8->body;
            $args->extra_vars9 = $xml_doc->document->extra_vars9->body;
            $args->extra_vars10 = $xml_doc->document->extra_vars10->body;
            $args->extra_vars11 = $xml_doc->document->extra_vars11->body;
            $args->extra_vars12 = $xml_doc->document->extra_vars12->body;
            $args->extra_vars13 = $xml_doc->document->extra_vars13->body;
            $args->extra_vars14 = $xml_doc->document->extra_vars14->body;
            $args->extra_vars15 = $xml_doc->document->extra_vars15->body;
            $args->extra_vars16 = $xml_doc->document->extra_vars16->body;
            $args->extra_vars17 = $xml_doc->document->extra_vars17->body;
            $args->extra_vars18 = $xml_doc->document->extra_vars18->body;
            $args->extra_vars19 = $xml_doc->document->extra_vars19->body;
            $args->extra_vars20 = $xml_doc->document->extra_vars20->body;
            
            $output = $this->oDocumentController->insertDocument($args, true);
            if($output->toBool()) {

                // 코멘트 입력
                $comments = $xml_doc->document->comments->comment;
                if($comments && !is_array($comments)) $comments = array($comments);
                if(count($comments)) {
                    foreach($comments as $key => $val) {
                        $comment_args->document_srl = $args->document_srl;
                        $comment_args->comment_srl = getNextSequence();
                        $comment_args->module_srl = $this->module_srl;
                        //$comment_args->parent_srl = $val->parent_srl->body;
                        $comment_args->parent_srl = 0;
                        $comment_args->content = $val->content->body;
                        $comment_args->password = $val->password->body;
                        $comment_args->nick_name = $val->nick_name->body;
                        $comment_args->user_id = $val->user_id->body;
                        $comment_args->user_name = $val->user_name->body;
                        $comment_args->member_srl = -1;
                        $comment_args->email_address = $val->email_address->body;
                        $comment_args->regdate = $val->regdate->body;
                        $comment_args->ipaddress = $val->ipaddress->body;
                        $this->oCommentController->insertComment($comment_args, true);
                    }
                }

                // 트랙백 입력
                $trackbacks = $xml_doc->document->trackbacks->trackback;
                if($trackbacks && !is_array($trackbacks)) $trackbacks = array($trackbacks);
                if(count($trackbacks)) {
                    foreach($trackbacks as $key => $val) {
                        $trackback_args->document_srl = $args->document_srl;
                        $trackback_args->module_srl = $this->module_srl;
                        $trackback_args->url = $val->url->body;
                        $trackback_args->title = $val->title->body;
                        $trackback_args->blog_name = $val->blog_name->body;
                        $trackback_args->excerpt = $val->excerpt->body;
                        $trackback_args->regdate = $val->regdate->body;
                        $trackback_args->ipaddress = $val->ipaddress->body;
                        $this->oTrackbackController->insertTrackback($trackback_args, true);
                    }
                }
            } else {
                $this->msg .= $sequence." : ".$output->getMessage()."<br />";
            }

            $this->position++;
            return '';
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
         * @brief <root>정보를 읽어서 정보를 구함
         **/
        function _parseRootInfo($matches) {
            $root = $matches[0].'</root>';
            $xml_doc = $this->oXml->parse($root);
            $this->total_count = $xml_doc->root->attrs->count;
        }

        /**
         * @brief <categories>정보를 읽어서 정보를 구함
         **/
        function _parseCategoryInfo($matches) {
            $xml_doc = $this->oXml->parse($matches[0]);

            $category_list = $xml_doc->categories->category;
            if(!$category_list) return;

            if(!is_array($category_list)) $category_list = array($category_list);

            $oDocumentController = &getAdminController('document');

            foreach($category_list as $key => $val) {
                $title = $val->body;
                if($this->category_list[$title]) continue;

                $output = $oDocumentController->insertCategory($this->module_srl, $title);
                $this->category_list[$title] = $output->get('category_srl');
            }
        }
    }
?>
