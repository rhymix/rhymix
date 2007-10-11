<?php
    /**
     * @class  documentItem
     * @author zero (zero@nzeo.com)
     * @brief  document 객체 
     **/

    class documentItem extends Object {

        var $document_srl = 0;

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
            if(!$attribute->document_srl || !$attribute->content) {
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
            return $this->get('allow_comment') == 'Y' || !$this->isExists() ? true : false;
        }

        function allowTrackback() {
            return $this->get('allow_trackback') == 'Y'  || !$this->isExists() ? true : false;
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

        function notify($type, $content) {
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

            if(!eregi("^http:\/\/",$url)) $url = "http://".$url;

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
            return htmlspecialchars($this->getTitle($cut_size, $tail));
        }

        function getTitle($cut_size = 0, $tail='...') {
            if($this->isSecret() && !$this->isGranted()) return Context::getLang('msg_is_secret');

            if($cut_size) return cut_str($this->get('title'), $cut_size, $tail);

            return $this->get('title');
        }

        function getContentText($strlen = 0) {
            if($this->isSecret() && !$this->isGranted()) return Context::getLang('msg_is_secret');

            $_SESSION['accessible'][$this->document_srl] = true;

            $content = $this->get('content');

            if($strlen) return cut_str(strip_tags($content),$strlen,'...');

            return htmlspecialchars($content);
        }

        function getContent($add_document_info = true) {
            if($this->isSecret() && !$this->isGranted()) return Context::getLang('msg_is_secret');

            $_SESSION['accessible'][$this->document_srl] = true;

            $content = $this->get('content');

            // OL/LI 태그를 위한 치환 처리
            $content = preg_replace('!<(ol|ul|blockquote)>!is','<\\1 style="margin-left:40px;">',$content);

            // url에 대해서 정규표현식으로 치환
            $content = preg_replace('!([^>^"^\'^=])(http|https|ftp|mms):\/\/([^ ^<^"^\']*)!is','$1<a href="$2://$3" onclick="window.open(this.href);return false;">$2://$3</a>',' '.$content);
            
            if($add_document_info) return sprintf('<!--BeforeDocument(%d,%d)-->%s<!--AfterDocument(%d,%d)-->', $this->document_srl, $this->get('member_srl'), $content, $this->document_srl, $this->get('member_srl'));

            return $content;
        }

        function getSummary($str_size = 50) {
            $content = htmlspecialchars(strip_tags($this->getContent()));
            return cut_str($content, $str_size, '...');
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
            return getUrl('','document_srl',$this->document_srl,'act','trackback');
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

        function getCommentCount() {
            if(!$this->isGranted() && $this->isSecret()) return 0;
            return $this->get('comment_count');
        }

        function getComments() {
            if(!$this->allowComment() || !$this->get('comment_count')) return;
            if(!$this->isGranted() && $this->isSecret()) return;

            $oCommentModel = &getModel('comment');
            return $oCommentModel->getCommentList($this->document_srl, $is_admin);
        }

        function getTrackbackCount() {
            return $this->get('trackback_count');
        }

        function getTrackbacks() {
            if(!$this->allowTrackback() || !$this->get('trackback_count')) return;

            $oTrackbackModel = &getModel('trackback');
            return $oTrackbackModel->getTrackbackList($this->document_srl, $is_admin);
        }

        function thumbnailExists($width = 80, $height = 0, $type = '') {
            if(!$this->getThumbnail($width, $height, $type)) return false;
            return true;
        }

        function getThumbnail($width = 80, $height = 0, $thumbnail_type = '') {
            if(!$height) $height = $width;

            // 문서의 이미지 첨부파일 위치를 구함
            $document_path = sprintf('./files/attach/images/%d/%d/',$this->get('module_srl'), $this->get('document_srl'));
            if(!is_dir($document_path)) FileHandler::makeDir($document_path);

            // 썸네일 임시 파일명을 구함
            if($width != $height) $thumbnail_file = sprintf('%sthumbnail_%dx%d.jpg', $document_path, $width, $height);
            else $thumbnail_file = sprintf('%sthumbnail_%d.jpg', $document_path, $width);

            // 썸네일이 있더라도 글의 수정시간과 비교해서 다르면 다시 생성함
            if(file_exists($thumbnail_file)) {
                $file_created_time = date("YmdHis",filemtime($thumbnail_file));
                $modified_time = $this->get('last_update');
                if($modified_time > $file_created_time) @unlink($thumbnail_file);
            }

            if(file_exists($thumbnail_file)&&filesize($thumbnail_file)<1) return;

            // 썸네일 파일이 있으면 url return
            if(file_exists($thumbnail_file)) return Context::getRequestUri().$thumbnail_file;

            // 문서 모듈의 기본 설정에서 Thumbnail의 생성 방법을 구함
            if(!in_array($thumbnail_type, array('crop','ratio'))) {
                $oDocumentModel = &getModel('document');
                $config = $oDocumentModel->getDocumentConfig();
                $thumbnail_type = $config->thumbnail_type;
            }
            
            // 생성 시작
            FileHandler::writeFile($thumbnail_file, '', 'w');

            // 첨부파일이 있는지 확인하고 있으면 썸네일 만듬
            $oFile = &getModel('file');
            $file_list = $oFile->getFiles($this->document_srl);
            if(count($file_list)) {
                foreach($file_list as $file) {
                    if($file->direct_download!='Y') continue;
                    if(!eregi("(jpg|png|jpeg|gif)$",$file->source_filename)) continue;

                    $filename = $file->uploaded_filename;
                    if(!file_exists($filename)) continue;

                    FileHandler::createImageFile($filename, $thumbnail_file, $width, $height, 'jpg', $thumbnail_type);
                    if(file_exists($thumbnail_file)) return Context::getRequestUri().$thumbnail_file;
                }
            }

            // 첨부파일이 없으면 내용에서 추출
            $content = $this->get('content');

            preg_match_all("!http:\/\/([^ ^\"^']*?)\.(jpg|png|gif|jpeg)!is", $content, $matches, PREG_SET_ORDER);
            for($i=0;$i<count($matches);$i++) {
                $src = $matches[$i][0];
                if(strpos($src,"/common/tpl")!==false || strpos($src,"/modules")!==false) continue;
                break;
            }

            $tmp_file = sprintf('%sthumbnail_%d.tmp.jpg', $document_path, $width);

            if($src) FileHandler::getRemoteFile($src, $tmp_file);
            else {
                FileHandler::writeFile($thumbnail_file,'');
                return;
            }

            FileHandler::createImageFile($tmp_file, $thumbnail_file, $width, $height, 'jpg', $config->thumbnail_type);
            @unlink($tmp_file);

            return Context::getRequestUri().$thumbnail_file;
        }

        /**
         * @brief 새글, 최신 업데이트글, 비밀글, 이미지/동영상/첨부파일등의 아이콘 출력용 함수
         * $time_interval 에 지정된 시간(초)로 새글/최신 업데이트글의 판별
         **/
        function getExtraImages($time_interval = 43200) {

            // 아이콘 목록을 담을 변수 미리 설정
            $buffs = array();

            $check_files = false;

            // 사진 이미지 체크
            if(preg_match('!<img([^>]*?)>!is', $this->get('content'))) {
                $buffs[] = "image";
                $check_files = true;
            }

            // 동영상 체크
            if(preg_match('!<embed([^>]*?)>!is', $this->get('content'))) {
                $buffs[] = "movie";
                $check_files = true;
            }

            // 첨부파일 체크
            if(!$check_files && $this->hasUploadedFiles()) $buffs[] = "file";

            // 비밀글 체크
            if($this->isSecret()) $buffs[] = "secret";

            // 최신 시간 설정
            $time_check = date("YmdHis", time()-$time_interval);

            // 새글 체크
            if($this->get('regdate')>$time_check) $buffs[] = "new";
            else if($this->get('last_update')>$time_check) $buffs[] = "update";


            return $buffs;
        }
        
        /**
         * @brief getExtraImages로 구한 값을 이미지 태그를 씌워서 리턴
         **/
        function printExtraImages($time_check = 43200) {
            // 아이콘 디렉토리 구함
            $path = sprintf('%s%s',getUrl(), 'modules/document/tpl/icons/');

            $buffs = $this->getExtraImages($time_check);
            if(!count($buffs)) return;

            $buff = null;
            foreach($buffs as $key => $val) {
                $buff .= sprintf('<img src="%s%s.gif" alt="%s" title="%s" align="absmiddle"/>', $path, $val, $val, $val);
            }
            return $buff;
        }

        function hasUploadedFiles() {
            if($this->isSecret() && !$this->isGranted()) return false;
            return $this->get('uploaded_count')? true : false;
        }

        function getUploadedFiles() {
            if($this->isSecret() && !$this->isGranted()) return;
            if(!$this->get('uploaded_count')) return;

            $oFileModel = &getModel('file');
            $file_list = $oFileModel->getFiles($this->document_srl, $is_admin);
            return $file_list;
        }
    }
?>
