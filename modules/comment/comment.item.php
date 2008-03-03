<?php
    /**
     * @class  commentItem
     * @author zero (zero@nzeo.com)
     * @brief  comment 객체 
     **/

    class commentItem extends Object {

        var $comment_srl = 0;

        function commentItem($comment_srl = 0) {
            $this->comment_srl = $comment_srl;
            $this->_loadFromDB();
        }

        function setComment($comment_srl) {
            $this->comment_srl = $comment_srl;
            $this->_loadFromDB();
        }

        function _loadFromDB() {
            if(!$this->comment_srl) return;

            $args->comment_srl = $this->comment_srl;
            $output = executeQuery('comment.getComment', $args);

            $this->setAttribute($output->data);
        }

        function setAttribute($attribute) {
            if(!$attribute->comment_srl) {
                $this->comment_srl = null;
                return;
            }
            $this->comment_srl = $attribute->comment_srl;
            $this->adds($attribute);

            // 기존 스킨의 호환을 위해 변수를 객체 자신에 재선언
            if(count($attribute)) foreach($attribute as $key => $val) $this->{$key} = $val;
        }

        function isExists() {
            return $this->comment_srl ? true : false;
        }

        function isGranted() {
            if($_SESSION['own_comment'][$this->comment_srl]) return true;

            if(!Context::get('is_logged')) return false;

            $logged_info = Context::get('logged_info');

            if($logged_info->is_admin == 'Y') return true;

            if($this->get('member_srl') && $this->get('member_srl') == $logged_info->member_srl) return true;

            return false;
        }

        function setGrant() {
            $_SESSION['own_comment'][$this->comment_srl] = true;
            $this->is_granted = true;
        }

        function setAccessible() {
            $_SESSION['accessibled_comment'][$this->comment_srl] = true;
        }

        function isEditable() {
            if($this->isGranted() || !$this->get('member_srl')) return true;
            return false;
        }

        function isSecret() {
            return $this->get('is_secret') == 'Y' ? true : false;
        }

        function isAccessible() {
            if($_SESSION['accessibled_comment'][$this->comment_srl]) return true;

            if($this->isGranted() || !$this->isSecret()) {
                $this->setAccessible();
                return true;
            }

            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($this->get('document_srl'));
            if($oDocument->isGranted()) {
                $this->setAccessible();
                return true;
            }

            return false;
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

            // 원본글의 주소를 구함
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($this->get('document_srl'));

            // 변수 정리
            if($type) $title = "[".$type."] ";
            $title .= cut_str(strip_tags($content), 10, '...');
            $content = sprintf('%s<br /><br />from : <a href="%s#comment_%s" onclick="window.open(this.href);return false;">%s</a>',$content, $oDocument->getPermanentUrl(), $this->get('comment_srl'), $oDocument->getPermanentUrl());
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
        
        function getContentText($strlen = 0) {
            if($this->isSecret() && !$this->isAccessible()) return Context::getLang('msg_is_secret');

            $content = $this->get('content');

            if($strlen) return cut_str(strip_tags($content),$strlen,'...');

            return htmlspecialchars($content);
        }

        function getContent($add_popup_menu = true, $add_content_info = true) {
            if($this->isSecret() && !$this->isAccessible()) return Context::getLang('msg_is_secret');

            $content = $this->get('content');

            // url에 대해서 정규표현식으로 치환
            $content = preg_replace('!([^>^"^\'^=])(http|https|ftp|mms):\/\/([^ ^<^"^\']*)!is','$1<a href="$2://$3" onclick="window.open(this.href);return false;">$2://$3</a>',' '.$content);

            // 이 댓글을... 팝업메뉴를 출력할 경우
            if($add_popup_menu) {
                $content = sprintf(
                        '%s<div class="comment_popup_menu"><span class="comment_%d">%s</span></div>',
                        $content, 
                        $this->comment_srl, Context::getLang('cmd_comment_do')
                );
            }

            // 컨텐츠에 대한 조작이 가능한 추가 정보를 설정하였을 경우
            if($add_content_info) {
                $content = sprintf(
                        '<!--BeforeComment(%d,%d)--><div class="comment_%d_%d xe_content">%s</div><!--AfterComment(%d,%d)-->', 
                        $this->comment_srl, $this->get('member_srl'), 
                        $this->comment_srl, $this->get('member_srl'), 
                        $content, 
                        $this->comment_srl, $this->get('member_srl')
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
            $content = trim(cut_str($content, $str_size, '...'));

            // >, <, "를 다시 복구
            return str_replace(array('<','>','"'),array('&lt;','&gt;','&quot;'), $content);
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

        function hasUploadedFiles() {
            if($this->isSecret() && !$this->isGranted()) return false;
            return $this->get('uploaded_count')? true : false;
        }

        function getUploadedFiles() {
            if($this->isSecret() && !$this->isGranted()) return;
            if(!$this->get('uploaded_count')) return;

            $oFileModel = &getModel('file');
            $file_list = $oFileModel->getFiles($this->comment_srl, $is_admin);
            return $file_list;
        }

        /**
         * @brief 에디터 html을 구해서 return
         **/
        function getEditor() {
            $module_srl = $this->get('module_srl');
            if(!$module_srl) $module_srl = Context::get('module_srl');
            $oEditorModel = &getModel('editor');
            return $oEditorModel->getModuleEditor('comment', $module_srl, $this->comment_srl, 'comment_srl', 'content');
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
            $max_signature_height = $GLOBALS['__member_signature_max_height'];
            if($max_signature_height) $signature = sprintf('<div style="height:%dpx;overflow-y:auto;overflow-x:hidden;">%s</div>',$max_signature_height, $signature);

            return $signature;
        }
    }
?>
