<?php
    /**
     * @class  ttimporterAdminController
     * @author zero (zero@nzeo.com)
     * @brief  ttimporter 모듈의 admin controller class
     **/

    class ttimporterAdminController extends ttimporter {

        var $oXml = null;

        var $oDocumentController = null;
        var $oFileController = null;
        var $oCommentController = null;
        var $oTrackbackController = null;

        var $position = 0;
        var $imported_count = 0;
        var $limit_count = 20;
        var $url = '';

        var $module_srl = 0;
        var $category_srl = 0;
        var $category_list = array();

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief import 실행
         **/
        function procTtimporterAdminImport() {
            // 실행시간 무한대로 설정
            @set_time_limit(0);

            // 변수 체크
            $this->module_srl = Context::get('module_srl');
            $xml_file = Context::get('xml_file');
            $this->url = Context::get('url');
            $this->position = (int)Context::get('position');
            if(substr($this->url,-1)!='/') $this->url .= '/';

            // 파일을 찾을 수 없으면 에러 표시
            if(!file_exists($xml_file)) return new Object(-1,'msg_no_xml_file');

            $this->oXml = new XmlParser();

            $oDocumentModel = &getModel('document');
            $tmp_category_list = $oDocumentModel->getCategoryList($this->module_srl);
            if(count($tmp_category_list)) {
                foreach($tmp_category_list as $key => $val) $this->category_list[$val->title] = $key;
            } else {
                $this->category_list = array();
            }

            // module_srl이 있으면 module데이터로 판단하여 처리, 아니면 회원정보로..
            $is_finished = $this->importDocument($xml_file);

            if($is_finished) {
                $this->add('is_finished', 'Y');
                $this->setMessage( sprintf(Context::getLang('msg_import_finished'), $this->imported_count) );
            } else {
                $this->add('position', $this->imported_count);
                $this->add('is_finished', 'N');
                $this->setMessage( sprintf(Context::getLang('msg_importing'), $this->imported_count) );
            }
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

            $is_finished = true;

            $fp = @fopen($xml_file, "r");
            if($fp) {
                $buff = '';
                while(!feof($fp)) {
                    $str = fread($fp,1024);
                    $buff .= $str;

                    $buff = preg_replace_callback("!<category>(.*?)<\/category>!is", array($this, '_parseCategoryInfo'), trim($buff));
                    $buff = preg_replace_callback("!<post (.*?)<\/post>!is", array($this, '_importDocument'), trim($buff));

                    if($this->position+$this->limit_count <= $this->imported_count) {
                        $is_finished = false;
                        break;
                    }
                }
                fclose($fp);
            }

            return $is_finished;
        }

        function _insertAttachment($matches) {
            $xml_doc = $this->oXml->parse($matches[0]);

            $filename = $xml_doc->attachment->name->body;
            $url = sprintf("%sattach/1/%s", $this->url, $filename);

            $tmp_filename = './files/cache/tmp_uploaded_file';
            if(FileHandler::getRemoteFile($url, $tmp_filename)) {
                $file_info['tmp_name'] = $tmp_filename;
                $file_info['name'] = $filename;
                $this->oFileController->insertFile($file_info, $this->module_srl, $this->document_srl, 0, true);
                $this->uploaded_count++;
            }
            @unlink($tmp_filename);
        }

        function _importDocument($matches) {
            if($this->position > $this->imported_count) {
                $this->imported_count++;
                return;
            }
            
            $this->uploaded_count = 0;

            $xml_doc = $this->oXml->parse($matches[0]);

            // 문서 번호와 내용 미리 구해 놓기
            $this->document_srl = $args->document_srl = getNextSequence();
            $args->content = $xml_doc->post->content->body;

            // 첨부파일 미리 등록
            preg_replace_callback("!<attachment (.*?)<\/attachment>!is", array($this, '_insertAttachment'), $matches[0]);

            // 컨텐츠의 내용 수정 (이미지 첨부파일 관련)
            $args->content = preg_replace("!(\[##\_1)([a-zA-Z]){1}\|([^\|]*)\|([^\|]*)\|([^\]]*)\]!is", sprintf('<img src="./files/attach/images/%s/%s/$3" $4 />', $this->module_srl, $args->document_srl), $args->content);

            if($xml_doc->post->comment && !is_array($xml_doc->post->comment)) $xml_doc->post->comment = array($xml_doc->post->comment);

            $logged_info = Context::get('logged_info');

            // 문서 입력
            $args->module_srl = $this->module_srl;
            $args->category_srl = $this->category_list[$xml_doc->post->category->body];
            $args->is_notice = 'N';
            $args->is_secret = 'N';
            $args->title = $xml_doc->post->title->body;
            $args->readed_count = 0;
            $args->voted_count = 0;
            $args->comment_count = count($xml_doc->post->comment);
            $args->trackback_count = 0;
            $args->uploaded_count = $this->uploaded_count;
            $args->password = '';
            $args->nick_name = $logged_info->nick_name;
            $args->member_srl = $logged_info->member_srl;
            $args->user_id = $logged_info->user_id;
            $args->user_name = $logged_info->user_name;
            $args->email_address = $logged_info->email_address;
            $args->homepage = $logged_info->homepage;

            $tag_list = array();
            for($i=0;$i<count($xml_doc->post->tag);$i++) {
                $tag_list[] = $xml_doc->post->tag[$i]->body;
            }
            $args->tags = implode(',',$tag_list);
            $args->regdate = date("YmdHis", $xml_doc->post->created->body);
            $args->ipaddress = '';
            $args->allow_comment = $xml_doc->post->acceptcomment->body?'Y':'N';
            $args->lock_comment = 'N';
            $args->allow_trackback = $xml_doc->post->accepttrackback->body?'Y':'N';
            
            $output = $this->oDocumentController->insertDocument($args, true);

            if($output->toBool()) {

                // 코멘트 입력
                $comments = $xml_doc->post->comment;
                if(count($comments)) {
                    foreach($comments as $key => $val) {
                        unset($comment_args);
                        $comment_args->document_srl = $args->document_srl;
                        $comment_args->comment_srl = getNextSequence();
                        $comment_args->module_srl = $this->module_srl;
                        $comment_args->parent_srl = 0;
                        $comment_args->content = $val->content->body;
                        $comment_args->password = '';
                        $comment_args->nick_name = $val->commenter->name->body;
                        $comment_args->user_id = '';
                        $comment_args->user_name = '';
                        $comment_args->member_srl = 0;
                        $comment_args->email_address = '';
                        $comment_args->regdate = date("YmdHis",$val->written->body);
                        $comment_args->ipaddress = $val->commenter->ip->body;
                        $this->oCommentController->insertComment($comment_args, true);
                    }
                }

                /*
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
                */
            }

            $this->imported_count ++;
            return '';
        }

        /**
         * @brief <categories>정보를 읽어서 정보를 구함
         **/
        function _parseCategoryInfo($matches) {
            $xml_doc = $this->oXml->parse($matches[0]);
            if(!$xml_doc->category->priority) return;

            $title = trim($xml_doc->category->name->body);
            if(!$title || $this->category_list[$title]) return;

            $oDocumentController = &getAdminController('document');
            $output = $oDocumentController->insertCategory($this->module_srl, $title);
            $this->category_list[$title] = $output->get('category_srl');
        }
    }
?>
