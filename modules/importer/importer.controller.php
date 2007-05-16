<?php
    /**
     * @class  importerController
     * @author zero (zero@nzeo.com)
     * @brief  importer 모듈의 Controller class
     **/

    class importerController extends importer {

        var $oXml = null;
        var $oMemberController = null;
        var $oDocumentController = null;
        var $oFileController = null;
        var $oCommentController = null;
        var $oTrackbackController = null;

        var $position = 0;
        var $imported_count = 0;
        var $limit_count = 500;

        var $module_srl = 0;
        var $category_srl = 0;

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

            // 디버그 메세지의 양이 무척 커지기에 디버그 메세지 생성을 중단
            define('__STOP_DEBUG__', true);

            // 변수 체크
            $this->module_srl = Context::get('module_srl');
            $this->category_srl = Context::get('category_list');
            $xml_file = Context::get('xml_file');
            $this->position = (int)Context::get('position');

            // 파일을 찾을 수 없으면 에러 표시
            if(!file_exists($xml_file)) return new Object(-1,'msg_no_xml_file');

            $this->oXml = new XmlParser();

            // module_srl이 있으면 module데이터로 판단하여 처리, 아니면 회원정보로..
            if($this->module_srl) {
                $this->limit_count = 100;
                $this->importDocument($xml_file);
            } else {
                $this->importMember($xml_file);
            }

            if($this->position+$this->limit_count > $this->imported_count) {
                $this->add('is_finished', 'Y');
                $this->setMessage( sprintf(Context::getLang('msg_import_finished'), $this->imported_count) );
            } else {
                $this->add('position', $this->imported_count);
                $this->add('is_finished', 'N');
            }
        }

        /**
         * @brief 회원정보 import
         **/
        function importMember($xml_file) {
            $filesize = filesize($xml_file);
            if($filesize<1) return;

            $this->oMemberController = &getController('member');

            $fp = @fopen($xml_file, "r");
            if($fp) {
                $buff = '';
                while(!feof($fp)) {
                    $str = fgets($fp,1024);
                    $buff .= $str;

                    $buff = preg_replace_callback("!<member user_id=\"([^\"]*)\">(.*?)<\/member>!is", array($this, '_importMember'), trim($buff));

                    if($this->position+$this->limit_count <= $this->imported_count) break;
                }
                fclose($fp);
            }
        }

        function _importMember($matches) {
            if($this->position > $this->imported_count) {
                $this->imported_count++;
                return;
            }

            $user_id = $matches[1];
            $xml_doc = $this->oXml->parse($matches[0]);

            $args->user_id = $xml_doc->member->user_id->body;
            $args->user_name = $xml_doc->member->user_name->body;
            $args->nick_name = $xml_doc->member->nick_name->body;
            $args->homepage = $xml_doc->member->homepage->body;
            $args->birthday = $xml_doc->member->birthday->body;
            $args->email_address = $xml_doc->member->email_address->body;
            $args->password = $xml_doc->member->password->body;
            $args->regdate = $xml_doc->member->regdate->body;
            $args->allow_mailing = $xml_doc->member->allow_mailing->body;
            $args->allow_message = 'Y';
            $output = $this->oMemberController->insertMember($args);
            if($output->toBool()) {
                $member_srl = $output->get('member_srl');
                if($xml_doc->member->image_nickname->body) {
                    $image_nickname = base64_decode($xml_doc->member->image_nickname->body);
                    FileHandler::writeFile('./files/cache/tmp_imagefile.gif', $image_nickname);
                    $this->oMemberController->insertImageName($member_srl, './files/cache/tmp_imagefile.gif');
                    @unlink('./files/cache/tmp_imagefile.gif');
                }
                if($xml_doc->member->image_mark->body) {
                    $image_mark = base64_decode($xml_doc->member->image_mark->body);
                    FileHandler::writeFile('./files/cache/tmp_imagefile.gif', $image_mark);
                    $this->oMemberController->insertImageMark($member_srl, './files/cache/tmp_imagefile.gif');
                    @unlink('./files/cache/tmp_imagefile.gif');
                }
                if($xml_doc->member->signature->body) {
                    $this->oMemberController->putSignature($member_srl, base64_decode($xml_doc->member->signature->body));
                }

                $this->imported_count ++;
            }
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

            $fp = @fopen($xml_file, "r");
            if($fp) {
                $buff = '';
                while(!feof($fp)) {
                    $str = fgets($fp,1024);
                    $buff .= $str;

                    $buff = preg_replace_callback("!<document sequence=\"([^\"]*)\">(.*?)<\/document>!is", array($this, '_importDocument'), trim($buff));

                    if($this->position+$this->limit_count <= $this->imported_count) break;
                }
                fclose($fp);
            }
        }

        function _importDocument($matches) {
            if($this->position > $this->imported_count) {
                $this->imported_count++;
                return;
            }

            $sequence = $matches[1];
            $xml_doc = $this->oXml->parse($matches[0]);

            // 문서 번호 미리 따오기 
            $args->document_srl = getNextSequence();

            // 첨부파일 미리 등록
            $files = $xml_doc->document->files->file;
            if($files && !is_array($files)) $files = array($files);
            if(count($files)) {
                foreach($files as $key => $val) {
                    $filename = $val->attrs->name;
                    $downloaded_count = (int)$val->downloaded_count->body;
                    $file_buff = base64_decode($val->buff);

                    $tmp_filename = './files/cache/tmp_uploaded_file';
                    FileHandler::writeFile($tmp_filename, $file_buff);

                    $file_info['tmp_name'] = $tmp_filename;
                    $file_info['name'] = $filename;
                    $this->oFileController->insertFile($file_info, $this->module_srl, $args->document_srl, $downloaded_count);
                }
            }

            // 문서 입력
            $args->module_srl = $this->module_srl;
            $args->category_srl = $this->category_srl;
            $args->is_notice = $xml_doc->document->is_notice->body;
            $args->is_secret = $xml_doc->document->is_secret->body;
            $args->title = $xml_doc->document->title->body;
            $args->content = $xml_doc->document->content->body;
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
            $output = $this->oDocumentController->insertDocument($args, true);
            if(!$output->toBool()) return;

            // 코멘트 입력
            $comments = $xml_doc->document->comments->comment;
            if($comments && !is_array($comments)) $comments = array($comments);
            if(count($comments)) {
                foreach($comments as $key => $val) {
                    $comment_args->document_srl = $args->document_srl;
                    $comment_args->comment_srl = getNextSequence();
                    $comment_args->module_srl = $this->module_srl;
                    $comment_args->parent_srl = $val->parent_srl->body;
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

            $this->imported_count ++;
            return '';
        }
    }
?>
