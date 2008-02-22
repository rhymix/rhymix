<?php
    /**
     * @class  documentItem
     * @author zero (zero@nzeo.com)
     * @brief  document 객체 
     **/

    class documentItem extends Object {

        var $document_srl = 0;

        var $allow_trackback_status = null;

        function documentItem($document_srl = 0) {
            $this->document_srl = $document_srl;
            $this->_loadFromDB();
        }

        function setDocument($document_srl) {
            $this->document_srl = $document_srl;
            $this->_loadFromDB();
        }

        function _loadFromDB() {
            if(!$this->document_srl) return;

            $args->document_srl = $this->document_srl;
            $output = executeQuery('document.getDocument', $args);

            $this->setAttribute($output->data);
        }

        function setAttribute($attribute) {
            if(!$attribute->document_srl) {
                $this->document_srl = null;
                return;
            }
            $this->document_srl = $attribute->document_srl;
            $this->adds($attribute);

            // 태그 정리
            if($this->get('tags')) {
                $tags = explode(',',$this->get('tags'));
                $tag_count = count($tags);
                for($i=0;$i<$tag_count;$i++) if(trim($tags[$i])) $tag_list[] = trim($tags[$i]);
                $this->add('tag_list', $tag_list);
            }
        }

        function isExists() {
            return $this->document_srl ? true : false;
        }

        function isGranted() {
            if($_SESSION['own_document'][$this->document_srl]) return true;

            if(!Context::get('is_logged')) return false;

            $logged_info = Context::get('logged_info');

            if($logged_info->is_admin == 'Y') return true;

            if($this->get('member_srl') && $this->get('member_srl') == $logged_info->member_srl) return true;

            return false;
        }

        function setGrant() {
            $_SESSION['own_document'][$this->document_srl] = true;
        }

        function isAccessible() {
            return $_SESSION['accessible'][$this->document_srl]==true?true:false;
        }

        function allowComment() {
            if(!$this->isExists()) return true;

            return $this->get('allow_comment') == 'Y' ? true : false;
        }

        function allowTrackback() {
            if(!$this->isExists()) return false;

            // allowTrackback()의 경우 여러번 호출됨으로 자체 변수 설정후 사용
            if(!isset($this->allow_trackback_status)) {

                // 글쓴이가 허용하였으면 사용으로 체크
                if($this->get('allow_trackback')=='Y') $this->allow_trackback_status = true;

                // 글쓴이가 허용하였더라도 모듈 설정에서 허용이 아니라면 금지로 설정
                if($this->allow_trackback_status) {
                    $oModuleModel = &getModel('module');
                    $trackback_config = $oModuleModel->getModuleConfig('trackback');

                    // 전체 설정에서 엮인글 사용금지이면 모든 엮인글의 사용을 금지함
                    if($trackback_config->enable_trackback != 'Y') $this->allow_trackback_status = false;

                    // 전체 설정에서 허용시 모듈별 설정을 체크
                    else {
                        $module_config = $trackback_config->module_config[$this->get('module_srl')];
                        if(!$module_config || $module_config->enable_trackback != 'Y') $this->allow_trackback_status = false;
                    }
                }
            }

            return $this->allow_trackback_status;
        }

        function isLocked() {
            return $this->get('lock_comment') == 'Y'  ? true : false;
        }

        function isEditable() {
            if($this->isGranted() || !$this->get('member_srl')) return true;
            return false;
        }

        function isSecret() {
            return $this->get('is_secret') == 'Y' ? true : false;
        }

        function isNotice() {
            return $this->get('is_notice') == 'Y' ? true : false;
        }

        function useNotify() {
            return $this->get('notify_message')=='Y' ? true : false;
        }

        function doCart() {
            if(!$this->document_srl) return false;
            if($this->isCarted()) $this->removeCart();
            else $this->addCart();
        }

        function addCart() {
            $_SESSION['document_management'][$this->document_srl] = true;
        }

        function removeCart() {
            unset($_SESSION['document_management'][$this->document_srl]);
        }

        function isCarted() {
            return $_SESSION['document_management'][$this->document_srl];
        }

        function notify($type, $content) {
            if(!$this->document_srl) return;

            // useNotify가 아니면 return
            if(!$this->useNotify()) return;

            // 글쓴이가 로그인 유저가 아니면 패스~
            if(!$this->get('member_srl')) return;

            // 현재 로그인한 사용자와 글을 쓴 사용자를 비교하여 동일하면 return
            $logged_info = Context::get('logged_info');
            if($logged_info->member_srl == $this->get('member_srl')) return;

            // 변수 정리
            if($type) $title = "[".$type."] ";
            $title .= cut_str(strip_tags($content), 10, '...');
            $content = sprintf('%s<br /><br />from : <a href="%s" onclick="window.open(this.href);return false;">%s</a>',$content, $this->getPermanentUrl(), $this->getPermanentUrl());
            $receiver_srl = $this->get('member_srl');
            $sender_member_srl = $logged_info->member_srl;

            // 쪽지 발송
            $oMemberController = &getController('member');
            $oMemberController->sendMessage($sender_member_srl, $receiver_srl, $title, $content, false);
        }

        function isExistsHomepage() {
            if(trim($this->get('homepage'))) return true;
            return false;
        }

        function getHomepageUrl() {
            $url = trim($this->get('homepage'));
            if(!$url) return;

            if(!preg_match("/^http:\/\//i",$url)) $url = "http://".$url;

            return $url;
        }

        function getMemberSrl() {
            return $this->get('member_srl');
        }
        
        function getUserID() {
            return htmlspecialchars($this->get('user_id'));
        }
        
        function getUserName() {
            return htmlspecialchars($this->get('user_name'));
        }
        
        function getNickName() {
            return htmlspecialchars($this->get('nick_name'));
        }

        function getTitleText($cut_size = 0, $tail='...') {
            if(!$this->document_srl) return;

            if($cut_size) $title = cut_str($this->get('title'), $cut_size, $tail);
            else $title = $this->get('title');

            return $title;
        }

        function getTitle($cut_size = 0, $tail='...') {
            if(!$this->document_srl) return;

            $title = $this->getTitleText($cut_size, $tail);

            $attrs = array();
            if($this->get('title_bold')=='Y') $attrs[] = "font-weight:bold;";
            if($this->get('title_color')) $attrs[] = "color:#".$this->get('title_color');

            if(count($attrs)) return sprintf("<span style=\"%s\">%s</span>", implode(';',$attrs), htmlspecialchars($title));
            else return htmlspecialchars($title);
        }

        function getContentText($strlen = 0) {
            if(!$this->document_srl) return;

            if($this->isSecret() && !$this->isGranted()) return Context::getLang('msg_is_secret');

            $_SESSION['accessible'][$this->document_srl] = true;

            $content = $this->get('content');

            if($strlen) return cut_str(strip_tags($content),$strlen,'...');

            return htmlspecialchars($content);
        }

        function getContent($add_popup_menu = true, $add_content_info = true) {
            if(!$this->document_srl) return;

            if($this->isSecret() && !$this->isGranted()) return Context::getLang('msg_is_secret');

            $_SESSION['accessible'][$this->document_srl] = true;

            $content = $this->get('content');

            // url에 대해서 정규표현식으로 치환
            $content = preg_replace('!([^>^"^\'^=])(http|https|ftp|mms):\/\/([^ ^<^"^\']*)!is','$1<a href="$2://$3" onclick="window.open(this.href);return false;">$2://$3</a>',' '.$content);

            // 이 게시글을... 팝업메뉴를 출력할 경우
            if($add_popup_menu) {
                $content = sprintf(
                        '%s<div class="document_popup_menu"><span class="document_%d">%s</span></div>',
                        $content, 
                        $this->document_srl, Context::getLang('cmd_document_do')
                );
            }

            // 컨텐츠에 대한 조작이 가능한 추가 정보를 설정하였을 경우
            if($add_content_info) {
                $content = sprintf(
                        '<!--BeforeDocument(%d,%d)--><div class="document_%d_%d xe_content">%s</div><!--AfterDocument(%d,%d)-->', 
                        $this->document_srl, $this->get('member_srl'), 
                        $this->document_srl, $this->get('member_srl'), 
                        $content, 
                        $this->document_srl, $this->get('member_srl'), 
                        $this->document_srl, $this->get('member_srl')
                );
            // 컨텐츠에 대한 조작이 필요하지 않더라도 xe_content라는 클래스명을 꼭 부여
            } else {
                $content = sprintf('<div class="xe_content">%s</div>', $content);
            }

            return $content;
        }

        function getSummary($str_size = 50) {
            // 먼저 태그들을 제거함
            $content = preg_replace('!<([^>]*?)>!is','', $this->getContent(false,false));

            // < , > , " 를 치환
            $content = str_replace(array('&lt;','&gt;','&quot;','&nbsp;'), array('<','>','"',' '), $content);

            // 문자열을 자름
            $content = cut_str($content, $str_size, '...');

            // >, <, "를 다시 복구
            return str_replace(array('<','>','"',' '),array('&lt;','&gt;','&quot;','&nbsp;'), $content);
        }

        function getRegdate($format = 'Y.m.d H:i:s') {
            return zdate($this->get('regdate'), $format);
        }

        function getRegdateTime() {
            $regdate = $this->get('regdate');
            $year = substr($regdate,0,4);
            $month = substr($regdate,4,2);
            $day = substr($regdate,6,2);
            $hour = substr($regdate,8,2);
            $min = substr($regdate,10,2);
            $sec = substr($regdate,12,2);
            return mktime($hour,$min,$sec,$month,$day,$year);
        }

        function getRegdateGM() {
            return $this->getRegdate('D, d M Y H:i:s').' '.$GLOBALS['_time_zone'];
        }

        function getUpdate($format = 'Y.m.d H:i:s') {
            return zdate($this->get('last_update'), $format);
        }

        function getUpdateTime() {
            $year = substr($this->get('last_update'),0,4);
            $month = substr($this->get('last_update'),4,2);
            $day = substr($this->get('last_update'),6,2);
            $hour = substr($this->get('last_update'),8,2);
            $min = substr($this->get('last_update'),10,2);
            $sec = substr($this->get('last_update'),12,2);
            return mktime($hour,$min,$sec,$month,$day,$year);
        }

        function getUpdateGM() {
            return gmdate("D, d M Y H:i:s", $this->getUpdateTime());
        }

        function getPermanentUrl() {
            return getUrl('','document_srl',$this->document_srl);
        }

        function getTrackbackUrl() {
            if(!$this->document_srl) return;

            // 스팸을 막기 위한 key 생성
            $oTrackbackModel = &getModel('trackback');
            return $oTrackbackModel->getTrackbackUrl($this->document_srl);
        }

        function updateReadedCount() {
            $oDocumentController = &getController('document');
            if($oDocumentController->updateReadedCount($this)) {
                $readed_count = $this->get('readed_count');
                $readed_count++;
                $this->add('readed_count', $readed_count);
            }
        }

        function isExtraVarsExists() {
            for($i=1;$i<=20;$i++) {
                if($this->get('extra_vars'.$i)) return true;
            }
            return false;
        }

        function getExtraValue($key) {
            $val = $this->get('extra_vars'.$key);
            if(strpos($val,'|@|')!==false) $val = explode('|@|', $val);
            return $val;
        }

        function getExtraVarsValue($key) {
            $extra_vals = unserialize($this->get('extra_vars'));
            $val = $extra_vals->$key;
            return $val;
        }

        function getCommentCount() {
            return $this->get('comment_count');
        }

        function getComments() {
            if(!$this->allowComment() || !$this->getCommentCount()) return;
            if(!$this->isGranted() && $this->isSecret()) return;

            $cpage = Context::get('cpage');
            if(!$cpage) $cpage = 1;

            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getCommentList($this->document_srl, $cpage, $is_admin);
            if(!$output->toBool() || !count($output->data)) return;

            $oCommentModel = &getModel('comment');
            foreach($output->data as $key => $val) {
                $oCommentItem = new commentItem();
                $oCommentItem->setAttribute($val);
                $comment_list[$val->comment_srl] = $oCommentItem;
            }

            Context::set('comment_page_navigation', $output->page_navigation);
            Context::set('cpage', $output->page_navigation->cur_page);

            return $comment_list;
        }

        function getTrackbackCount() {
            return $this->get('trackback_count');
        }

        function getTrackbacks() {
            if(!$this->document_srl) return;
            
            if(!$this->allowTrackback() || !$this->get('trackback_count')) return;

            $oTrackbackModel = &getModel('trackback');
            return $oTrackbackModel->getTrackbackList($this->document_srl, $is_admin);
        }

        function thumbnailExists($width = 80, $height = 0, $type = '') {
            if(!$this->document_srl) return false;
            if(!$this->getThumbnail($width, $height, $type)) return false;
            return true;
        }

        function getThumbnail($width = 80, $height = 0, $thumbnail_type = '') {
            if(!$this->document_srl) return;

            if(!$height) $height = $width;
            
            // 문서 모듈의 기본 설정에서 Thumbnail의 생성 방법을 구함
            if(!in_array($thumbnail_type, array('crop','ratio'))) {
                $config = $GLOBALS['__document_config__'];
                if(!$config) {
                    $oDocumentModel = &getModel('document');
                    $config = $oDocumentModel->getDocumentConfig();
                    $GLOBALS['__document_config__'] = $config;
                }
                $thumbnail_type = $config->thumbnail_type;
            }

            // 문서의 이미지 첨부파일 위치를 구함
            $document_path = sprintf('./files/attach/images/%d/%d/',$this->get('module_srl'), $this->get('document_srl'));
            if(!is_dir($document_path)) FileHandler::makeDir($document_path);

            // 썸네일 임시 파일명을 구함
            if($width != $height) $thumbnail_file = sprintf('%sthumbnail_%dx%d_%s.jpg', $document_path, $width, $height, $thumbnail_type);
            else $thumbnail_file = sprintf('%sthumbnail_%d_%s.jpg', $document_path, $width, $thumbnail_type);

            // 썸네일이 있더라도 글의 수정시간과 비교해서 다르면 다시 생성함
            if(file_exists($thumbnail_file)) {
                $file_created_time = date("YmdHis",filemtime($thumbnail_file));
                $modified_time = $this->get('last_update');
                if($modified_time > $file_created_time) @unlink($thumbnail_file);
            }

            if(file_exists($thumbnail_file)&&filesize($thumbnail_file)<1) return;

            // 썸네일 파일이 있으면 url return
            if(file_exists($thumbnail_file)) return Context::getRequestUri().$thumbnail_file;

            // 생성 시작
            FileHandler::writeFile($thumbnail_file, '', 'w');

            // 첨부파일이 있는지 확인하고 있으면 썸네일 만듬
            $oFile = &getModel('file');
            $file_list = $oFile->getFiles($this->document_srl);
            if(count($file_list)) {
                foreach($file_list as $file) {
                    if($file->direct_download!='Y') continue;
                    if(!preg_match("/(jpg|png|jpeg|gif)$/i",$file->source_filename)) continue;

                    $filename = $file->uploaded_filename;
                    if(!file_exists($filename)) continue;

                    FileHandler::createImageFile($filename, $thumbnail_file, $width, $height, 'jpg', $thumbnail_type);
                    if(file_exists($thumbnail_file)) return Context::getRequestUri().$thumbnail_file;
                }
            }

            // 첨부파일이 없으면 내용에서 추출
            $content = $this->get('content');

            $target_src = null;
            preg_match_all("!http:\/\/([^ ^\"^']*?)\.(jpg|png|gif|jpeg)!is", $content, $matches, PREG_SET_ORDER);
            for($i=0;$i<count($matches);$i++) {
                $src = $matches[$i][0];
                if(preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $src)) continue;
                else {
                    $target_src = $src; 
                    break;
                }
            }

            if($target_src) {
                $tmp_file = sprintf('%sthumbnail_%d.tmp.jpg', $document_path, $width);
                FileHandler::getRemoteFile($target_src, $tmp_file);
                FileHandler::createImageFile($tmp_file, $thumbnail_file, $width, $height, 'jpg', $config->thumbnail_type);
                @unlink($tmp_file);
                return Context::getRequestUri().$thumbnail_file;
            }

            FileHandler::writeFile($thumbnail_file,'');
            return;
        }

        /**
         * @brief 새글, 최신 업데이트글, 비밀글, 이미지/동영상/첨부파일등의 아이콘 출력용 함수
         * $time_interval 에 지정된 시간(초)로 새글/최신 업데이트글의 판별
         **/
        function getExtraImages($time_interval = 43200) {
            if(!$this->document_srl) return;

            // 아이콘 목록을 담을 변수 미리 설정
            $buffs = array();

            $check_files = false;

            $content = $this->get('content');

            // 비밀글 체크
            if($this->isSecret()) $buffs[] = "secret";

            // 최신 시간 설정
            $time_check = date("YmdHis", time()-$time_interval);

            // 새글 체크
            if($this->get('regdate')>$time_check) $buffs[] = "new";
            else if($this->get('last_update')>$time_check) $buffs[] = "update";

            // 사진 이미지 체크
            preg_match_all('!<img([^>]*?)>!is', $content, $matches);
            $cnt = count($matches[0]);
            for($i=0;$i<$cnt;$i++) {
                if(preg_match('/src=("|\'|\.|\/)*(common|modules|widgets|layouts)/i', $matches[0][$i])) continue;
                $buffs[] = "image";
                $check_files = true;
                break;
            }

            // 동영상 체크
            if(preg_match('!<embed([^>]*?)>!is', $content) || preg_match('/editor_component=("|\')*multimedia_link/i', $content) ) {
                $buffs[] = "movie";
                $check_files = true;
            }

            // 첨부파일 체크
            if(!$check_files && $this->hasUploadedFiles()) $buffs[] = "file";


            return $buffs;
        }
        
        /**
         * @brief getExtraImages로 구한 값을 이미지 태그를 씌워서 리턴
         **/
        function printExtraImages($time_check = 43200) {
            if(!$this->document_srl) return;

            // 아이콘 디렉토리 구함
            $path = sprintf('%s%s',getUrl(), 'modules/document/tpl/icons/');

            $buffs = $this->getExtraImages($time_check);
            if(!count($buffs)) return;

            $buff = null;
            foreach($buffs as $key => $val) {
                $buff .= sprintf('<img src="%s%s.gif" alt="%s" title="%s" style="vertical-align:middle;"/>', $path, $val, $val, $val);
            }
            return $buff;
        }

        function hasUploadedFiles() {
            if(!$this->document_srl) return;

            if($this->isSecret() && !$this->isGranted()) return false;
            return $this->get('uploaded_count')? true : false;
        }

        function getUploadedFiles() {
            if(!$this->document_srl) return;

            if($this->isSecret() && !$this->isGranted()) return;
            if(!$this->get('uploaded_count')) return;

            $oFileModel = &getModel('file');
            $file_list = $oFileModel->getFiles($this->document_srl, $is_admin);
            return $file_list;
        }

        /**
         * @brief 에디터 html을 구해서 return
         **/
        function getEditor() {
            $module_srl = $this->get('module_srl');
            if(!$module_srl) $module_srl = Context::get('module_srl');

            $oEditorModel = &getModel('editor');
            return $oEditorModel->getModuleEditor('document', $module_srl, $this->document_srl, 'document_srl', 'content');
        }

        /**
         * @brief 댓글을 달 수 있는지에 대한 권한 체크
         * 게시글의 댓글 권한과 또 다른 부분
         **/
        function isEnableComment() {
            // 권한이 없고 비밀글 or 댓글금지 or 댓글허용금지이면 return false
            if(!$this->isGranted() && ( $this->isSecret() || $this->isLocked() || !$this->allowComment() ) ) return false;

            return true;
        }

        /**
         * @brief 댓글 에디터 html을 구해서 return
         **/
        function getCommentEditor() {
            if(!$this->isEnableComment()) return;

            $oEditorModel = &getModel('editor');
            return $oEditorModel->getModuleEditor('comment', $this->get('module_srl'), $comment_srl, 'comment_srl', 'content');
        }

        /**
         * @brief 작성자의 프로필 이미지를 return
         **/
        function getProfileImage() {
            if(!$this->isExists() || !$this->get('member_srl')) return;
            $oMemberModel = &getModel('member');
            $profile_info = $oMemberModel->getProfileImage($this->get('member_srl'));
            if(!$profile_info) return;

            return $profile_info->src;
        }

        /**
         * @brief 작성자의 서명을 return
         **/
        function getSignature() {
            // 존재하지 않는 글이면 패스~
            if(!$this->isExists() || !$this->get('member_srl')) return;

            // 서명정보를 구함
            $oMemberModel = &getModel('member');
            $signature = $oMemberModel->getSignature($this->get('member_srl'));

            // 회원모듈에서 서명 최고 높이 지정되었는지 검사
            if(!isset($GLOBALS['__member_signature_max_height'])) {
               $oModuleModel = &getModel('module');  
               $member_config = $oModuleModel->getModuleConfig('member');
               $GLOBALS['__member_signature_max_height'] = $member_config->signature_max_height;
            }
            if($signature) {
                $max_signature_height = $GLOBALS['__member_signature_max_height'];
                if($max_signature_height) $signature = sprintf('<div style="height:%dpx;overflow-y:auto;overflow-x:hidden;">%s</div>',$max_signature_height, $signature);
            }

            return $signature;
        }
    }
?>
