<?php
    /**
     * @class ttimport 
     * @author zero (zero@nzeo.com)
     * @brief  ttxml import class
     **/

    @set_time_limit(0);
    @require_once('./modules/importer/extract.class.php');

    class ttimport {

        var $oXmlParser = null;

        /**
         * @brief module.xml 형식의 데이터 import
         **/
        function importModule($key, $cur, $index_file, $unit_count, $module_srl, $user_id) {
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

                // 카테고리 정보를 정리
                if($xmlDoc->items->category) {
                    $categories = array();
                    $idx = 0;
                    $this->arrangeCategory($xmlDoc->items, $categories, $idx, 0);

                    $match_sequence = array();
                    foreach($categories as $k => $v) {
                        $category = $v->name;
                        if(!$category || $category_titles[$category]) continue;

                        $obj = null;
                        $obj->title = $category;
                        $obj->module_srl = $module_srl; 
                        if($v->parent) $obj->parent_srl = $match_sequence[$v->parent];
                        $output = $oDocumentController->insertCategory($obj);

                        if($output->toBool()) $match_sequence[$v->sequence] = $output->get('category_srl');
                    }
                    $oDocumentController->makeCategoryFile($module_srl);
                }
                FileHandler::removeFile($category_file);
            }
            $category_list = $category_titles = array();
            $category_list = $oDocumentModel->getCategoryList($module_srl);
            if(count($category_list)) foreach($category_list as $key => $val) $category_titles[$val->title] = $val->category_srl;

            // 관리자 정보를 구함
            $oMemberModel = &getModel('member');
            $member_info = $oMemberModel->getMemberInfoByUserID($user_id);
           
            if(!$cur) $cur = 0;

            // index파일을 염
            $f = fopen($index_file,"r");

            // 이미 읽혀진 것은 패스
            for($i=0;$i<$cur;$i++) fgets($f, 1024);

            // 라인단위로 읽어들이면서 $cur보다 커지고 $cur+$unit_count개보다 작으면 중지
            for($idx=$cur;$idx<$cur+$unit_count;$idx++) {
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
                $obj->uploaded_count = 0;

                $files = array();

                $started = false;
                $buff = null;

                // 본문 데이터부터 처리 시작
                while(!feof($fp)) {
                    $str = fgets($fp, 1024);

                    // 한 아이템 준비 시작
                    if(substr($str,0,5) == '<post') {
                        $started = true;
                        continue;

                    // 첨부파일 입력
                    } else if(substr($str,0,12) == '<attachment ') {
                        if($this->importAttaches($fp, $module_srl, $obj->document_srl, $files, $str)) $obj->uploaded_count++;
                        continue;
                    }

                    if($started) $buff .= $str;
                }

                $xmlDoc = $this->oXmlParser->parse('<post>'.$buff);
                
                if($xmlDoc->post->category->body) {
                    $tmp_arr = explode('/',$xmlDoc->post->category->body);
                    $category = trim($tmp_arr[count($tmp_arr)-1]);
                    if($category_titles[$category]) $obj->category_srl = $category_titles[$category];
                }

                $obj->is_notice = 'N';
                $obj->is_secret = in_array($xmlDoc->post->visibility->body, array('public','syndicated'))?'N':'Y';
                $obj->title = $xmlDoc->post->title->body;
                $obj->content = $xmlDoc->post->content->body;
                $obj->password = md5($xmlDoc->post->password->body);
                //$obj->allow_comment = $xmlDoc->post->acceptComment->body==1?'Y':'N';
                $obj->allow_comment = 'Y';
                //$obj->allow_trackback = $xmlDoc->post->acceptTrackback->body==1?'Y':'N';
                $obj->allow_trackback = 'Y';
                $obj->regdate = date("YmdHis",$xmlDoc->post->published->body);
                $obj->last_update = date("YmdHis", $xmlDoc->post->modified->body);
                if(!$obj->last_update) $obj->last_update = $obj->regdate;

                $tag = null;
                $tmp_tags = null;
                $tag = $xmlDoc->post->tag;
                if($tag) {
                    if(!is_array($tag)) $tag = array($tag);
                    foreach($tag as $key => $val) $tmp_tags[] = $val->body;
                    $obj->tags = implode(',',$tmp_tags);
                }

                $obj->readed_count = 0;
                $obj->voted_count = 0;
                $obj->nick_name = $member_info->nick_name;
                $obj->user_name = $member_info->user_name;
                $obj->user_id = $member_info->user_id;
                $obj->member_srl = $member_info->member_srl;
                $obj->email_address = $member_info->email_address;
                $obj->homepage = $member_info->homepage;
                $obj->ipaddress = $_REMOTE['SERVER_ADDR'];
                $obj->list_order = $obj->update_order = $obj->document_srl*-1;
                $obj->lock_comment = 'N';
                $obj->notify_message = 'N';

                // content 정보 변경 (첨부파일)
                $obj->content = str_replace('[##_ATTACH_PATH_##]/','',$obj->content);
                if(count($files)) {
                    foreach($files as $key => $val) {
                        $obj->content = preg_replace('/(src|href)\=(["\']?)'.preg_quote($key).'(["\']?)/i','$1="'.$val->url.'"',$obj->content);
                    }
                }

                $obj->content = preg_replace_callback('!\[##_Movie\|([^\|]*)\|(.*?)_##\]!is', array($this, '_replaceTTMovie'), $obj->content);

                if(count($files)) {
                    $this->files = $files;
                    $obj->content = preg_replace_callback('!\[##_([a-z0-9]+)\|([^\|]*)\|([^\|]*)\|(.*?)_##\]!is', array($this, '_replaceTTAttach'), $obj->content);
                }

                // 역인글 입력
                $obj->trackback_count = 0;
                if($xmlDoc->post->trackback) {
                    $trackbacks = $xmlDoc->post->trackback;
                    if(!is_array($trackbacks)) $trackbacks = array($trackbacks);
                    if(count($trackbacks)) {
                        foreach($trackbacks as $key => $val) {
                            $tobj = null;
                            $tobj->trackback_srl = getNextSequence();
                            $tobj->module_srl = $module_srl;
                            $tobj->document_srl = $obj->document_srl;
                            $tobj->url = $val->url->body;
                            $tobj->title = $val->title->body;
                            $tobj->blog_name = $val->site->body;
                            $tobj->excerpt = $val->excerpt->body;
                            $tobj->regdate = date("YmdHis",$val->received->body);
                            $tobj->ipaddress = $val->ip->body;
                            $tobj->list_order = -1*$tobj->trackback_srl;
                            $output = executeQuery('trackback.insertTrackback', $tobj);
                            if($output->toBool()) $obj->trackback_count++;
                        }
                    }
                }

                // 댓글입력
                $obj->comment_count = 0;
                if($xmlDoc->post->comment) {
                    $comment = $xmlDoc->post->comment;
                    if(!is_array($comment)) $comment = array($comment);
                    foreach($comment as $key => $val) {
                        $parent_srl = $this->insertComment($val, $module_srl, $obj->document_srl, 0);
                        if($parent_srl === false) continue;

                        $obj->comment_count++;
                        if($val->comment) {
                            $child_comment = $val->comment;
                            if(!is_array($child_comment)) $child_comment = array($child_comment);
                            foreach($child_comment as $k => $v) {
                                $result = $this->insertComment($v, $module_srl, $obj->document_srl, $parent_srl);
                                if($result !== false) $obj->comment_count++;
                            }
                        }
                    }
                }

                // 문서 입력
                $output = executeQuery('document.insertDocument', $obj);

                if($output->toBool()) {
                    // 태그 입력
                    if($obj->tags) {
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
                }

                fclose($fp);
                FileHandler::removeFile($target_file);
            }

            fclose($f);

            if(count($category_list)) foreach($category_list as $key => $val) $oDocumentController->updateCategoryCount($module_srl, $val->category_srl);

            return $idx-1;
        }

        /**
         * @brief 첨부파일 정리
         **/
        function importAttaches($fp, $module_srl, $upload_target_srl, &$files, $buff) {
            $uploaded_count = 0;

            $file_obj  = null;
            $file_obj->file_srl = getNextSequence();
            $file_obj->upload_target_srl = $upload_target_srl;
            $file_obj->module_srl = $module_srl;

            while(!feof($fp)) {
                $str = fgets($fp, 1024);

                // </attaches>로 끝나면 중단
                if(trim($str) == '</attachment>') break;

                // <file>로 시작하면 xml파일내의 첨부파일로 처리
                if(substr($str, 0, 9)=='<content>') {
                    $file_obj->file = $this->saveTemporaryFile($fp, $str);
                    continue;
                }

                $buff .= $str;
            }
            if(!file_exists($file_obj->file)) return false;

            $buff .= '</attachment>';

            $xmlDoc = $this->oXmlParser->parse($buff);

            $file_obj->source_filename = $xmlDoc->attachment->label->body;
            $file_obj->download_count = $xmlDoc->attachment->downloads->body;
            $name = $xmlDoc->attachment->name->body;

            // 이미지인지 기타 파일인지 체크하여 upload path 지정
            if(preg_match("/\.(jpg|jpeg|gif|png|wmv|wma|mpg|mpeg|avi|swf|flv|mp1|mp2|mp3|asaf|wav|asx|mid|midi|asf|mov|moov|qt|rm|ram|ra|rmm|m4v)$/i", $file_obj->source_filename)) {
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

            FileHandler::rename($file_obj->file, $filename);

            // DB입력
            unset($file_obj->file);
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
                if($file_obj->direct_download == 'Y') $files[$name]->url = $file_obj->uploaded_filename; 
                else $files[$name]->url = getUrl('','module','file','act','procFileDownload','file_srl',$file_obj->file_srl,'sid',$file_obj->sid);
                $files[$name]->direct_download = $file_obj->direct_download;
                $files[$name]->source_filename = $file_obj->source_filename;
                return true;
            }

            return false;
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
        function saveTemporaryFile($fp, $buff) {
            $temp_filename = $this->getTmpFilename();
            $buff = substr($buff, 9);

            while(!feof($fp)) {
                $str = trim(fgets($fp, 1024));
                $buff .= $str;
                if(substr($str, -10) == '</content>') break;
            }

            $buff = substr($buff, 0, -10);

            $f = fopen($temp_filename, "w");
            fwrite($f, base64_decode($buff));
            fclose($f);
            return $temp_filename;
        }

        /**
         * @brief ttxml의 자체 img 태그를 치환
         **/
        function _replaceTTAttach($matches) {
            $name = $matches[2];
            if(!$name) return $matches[0];

            $obj = $this->files[$name];

            // 멀티미디어성 파일의 경우
            if($obj->direct_download == 'Y') {
                // 이미지의 경우
                if(preg_match('/\.(jpg|gif|jpeg|png)$/i', $obj->source_filename)) {
                    return sprintf('<img editor_component="image_link" src="%s" alt="%s" />', $obj->url, str_replace('"','\\"',$matches[4]));
                // 이미지 외의 멀티미디어성 파일의 경우
                } else {
                   return sprintf('<img src="./common/tpl/images/blank.gif" editor_component="multimedia_link" multimedia_src="%s" width="400" height="320" style="display:block;width:400px;height:320px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;" auto_start="false" alt="" />', $obj->url);
                }

            // binary파일일 경우
            } else {
                return sprintf('<a href="%s">%s</a>', $obj->url, $obj->source_filename);
            }
        }

        /**
         * @brief ttxml의 동영상 변환
         **/
        function _replaceTTMovie($matches) {
            $key = $matches[1];
            if(!$key) return $matches[0];

            return 
                    '<object type="application/x-shockwave-flash" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="100%" height="402">'.
                    '<param name="movie" value="http://flvs.daum.net/flvPlayer.swf?vid='.urlencode($key).'"/>'.
                    '<param name="allowScriptAccess" value="always"/>'.
                    '<param name="allowFullScreen" value="true"/>'.
                    '<param name="bgcolor" value="#000000"/>'.
                    '<embed src="http://flvs.daum.net/flvPlayer.swf?vid='.urlencode($key).'" width="100%" height="402" allowscriptaccess="always" allowfullscreen="true" type="application/x-shockwave-flash" bgcolor="#000000"/>'.
                    '</object>';
        }

        /**
         * @brief 댓글 입력 
         **/
        function insertComment($val, $module_srl, $document_srl, $parent_srl = 0) {
            $tobj = null;
            $tobj->comment_srl = getNextSequence();
            $tobj->module_srl = $module_srl;
            $tobj->document_srl = $document_srl;
            $tobj->is_secret = $val->secret->body==1?'Y':'N';
            $tobj->notify_message = 'N';
            $tobj->content = nl2br($val->content->body);
            $tobj->voted_count = 0;
            $tobj->password = $val->password->body;
            $tobj->nick_name = $val->commenter->name->body;
            $tobj->member_srl = 0;
            $tobj->homepage = $val->commenter->homepage->body;
            $tobj->last_update = $tobj->regdate = date("YmdHis",$val->written->body);
            $tobj->ipaddress = $val->commenter->ip->body;
            $tobj->list_order = $tobj->comment_srl*-1;
            $tobj->sequence = $sequence;
            $tobj->parent_srl = $parent_srl;

            // 댓글 목록 부분을 먼저 입력
            $list_args = null;
            $list_args->comment_srl = $tobj->comment_srl;
            $list_args->document_srl = $tobj->document_srl;
            $list_args->module_srl = $tobj->module_srl;
            $list_args->regdate = $tobj->regdate;

            // 부모댓글이 없으면 바로 데이터를 설정
            if(!$tobj->parent_srl) {
                $list_args->head = $list_args->arrange = $tobj->comment_srl;
                $list_args->depth = 0;

            // 부모댓글이 있으면 부모글의 정보를 구해옴
            } else {
                // 부모댓글의 정보를 구함
                $parent_args->comment_srl = $tobj->parent_srl;
                $parent_output = executeQuery('comment.getCommentListItem', $parent_args);

                // 부모댓글이 존재하지 않으면 return
                if(!$parent_output->toBool() || !$parent_output->data) continue;
                $parent = $parent_output->data;

                $list_args->head = $parent->head;
                $list_args->depth = $parent->depth+1;
                if($list_args->depth<2) $list_args->arrange = $tobj->comment_srl;
                else {
                    $list_args->arrange = $parent->arrange;
                    $output = executeQuery('comment.updateCommentListArrange', $list_args);
                    if(!$output->toBool()) return $output;
                }
            }

            $output = executeQuery('comment.insertCommentList', $list_args);
            if($output->toBool()) {
                $output = executeQuery('comment.insertComment', $tobj);
                if($output->toBool()) return $tobj->comment_srl;
            }
            return false;
        }

        // 카테고리 정리
        function arrangeCategory($obj, &$category, &$idx, $parent = 0) {
            if(!$obj->category) return;
            if(!is_array($obj->category)) $c = array($obj->category);
            else $c = $obj->category;
            foreach($c as $val) {
                $idx++;
                $priority = $val->priority->body;
                $name = $val->name->body;
                $obj = null;
                $obj->priority = $priority;
                $obj->name = $name;
                $obj->sequence = $idx;
                $obj->parent = $parent;

                $category[$priority] = $obj;

                $this->arrangeCategory($val, $category, $idx, $idx);
            }
        }
    }
?>
