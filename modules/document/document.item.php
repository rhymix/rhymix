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

        function getContentText() {
            if($this->isSecret() && !$this->isGranted()) return Context::getLang('msg_is_secret');

            return htmlspecialchars($this->get('content'));
        }

        function getContent() {
            if($this->isSecret() && !$this->isGranted()) return Context::getLang('msg_is_secret');

            return sprintf('<!--BeforeDocument(%d,%d)-->%s<!--AfterDocument(%d,%d)-->', $this->document_srl, $this->get('member_srl'), $this->get('content'), $this->document_srl, $this->get('member_srl'));
        }

        function getSummary($str_size = 50) {
            $content = strip_tags($this->get('content'));
            return cut_str($content, $str_size, '...');
        }

        function getRegdate($format = 'Y.m.d H:i:s') {
            return zdate($this->get('regdate'), $format);
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

        function thumbnailExists($width) {
            if(!$this->getThumbnail($width)) return false;
            return true;
        }

        function getThumbnail($width = 80) {
            if(!preg_match('!<img([^>]*?)(\/){0,1}>!is',$this->get('content'),$matches)) return;

            $document_path = sprintf('files/attach/images/%d/%d/',$this->get('module_srl'), $this->get('document_srl'));
            if(!is_dir($document_path)) FileHandler::makeDir($document_path);

            $thumbnail_file = sprintf('%sthumbnail_%d.gif', $document_path, $width);

            if(!file_exists($thumbnail_file)) {

                $tmp_file = sprintf('%sthumbnail_%d.tmp.gif', $document_path, $width);

                preg_match('!src=("|\'){0,1}([^"|^\'|^\ ]*)("|\'| ){0,1}!is', $matches[0], $src_matches);
                $src = $src_matches[2];

                // 첨부된 파일일 경우
                if(substr($src,0,7)=='./files') {
                    copy($src, $tmp_file);

                // 웹에서 링크한 경우
                } else {
                    FileHandler::getRemoteFile($src, $tmp_file);
                }

                FileHandler::writeFile($thumbnail_file, '', 'w');

                if(file_exists($tmp_file)) {
                    // 파일정보를 보아서 가로/세로크기가 64보다 작으면 무시시킴
                    list($s_width, $s_height, $s_type, $s_attrs) = @getimagesize($tmp_file);

                    if($s_width > 64 || $s_height > 64) FileHandler::createImageFile($tmp_file, $thumbnail_file, $width, $width, 'gif');
                }

                @unlink($tmp_file);
            } 

            if(filesize($thumbnail_file)<1) return;

            $thumbnail_url = Context::getRequestUri().$thumbnail_file;
            return $thumbnail_url;
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
