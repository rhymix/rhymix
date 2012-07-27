<?php
	/**
	 * commentItem class
	 * comment Object
	 *
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/comment
	 * @version 0.1
	 */
    class commentItem extends Object {
		/**
		 * comment number
		 * @var int
		 */
        var $comment_srl = 0;
		/**
		 * Get the column list int the table
		 * @var array
		 */
		var $columnList = array();

		/**
		 * Constructor
		 * @param int $comment_srl
		 * @param array $columnList
		 * @return void
		 */
        function commentItem($comment_srl = 0, $columnList = array()) {
            $this->comment_srl = $comment_srl;
			$this->columnList = $columnList;
            $this->_loadFromDB();
        }

        function setComment($comment_srl) {
            $this->comment_srl = $comment_srl;
            $this->_loadFromDB();
        }

		/**
		 * Load comment data from DB and set to commentItem object
		 * @return void
		 */
        function _loadFromDB() {
            if(!$this->comment_srl) return;

            $args->comment_srl = $this->comment_srl;
            $output = executeQuery('comment.getComment', $args, $this->columnList);

            $this->setAttribute($output->data);
        }

		/**
		 * Comment attribute set to Object object
		 * @return void
		 */
        function setAttribute($attribute) {
            if(!$attribute->comment_srl) {
                $this->comment_srl = null;
                return;
            }
            $this->comment_srl = $attribute->comment_srl;
            $this->adds($attribute);
            // define vars on the object for backward compatibility of skins
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

			$grant = Context::get('grant');
			if($grant->manager) return true;

            if($this->get('member_srl') && ($this->get('member_srl') == $logged_info->member_srl || $this->get('member_srl')*-1 == $logged_info->member_srl)) return true;

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

		/**
		 * Notify to comment owner
		 * @return void
		 */
        function notify($type, $content) {
            // return if not useNotify
            if(!$this->useNotify()) return;
            // pass if the author is not logged-in user 
            if(!$this->get('member_srl')) return;
            // return if the currently logged-in user is an author of the comment.
            $logged_info = Context::get('logged_info');
            if($logged_info->member_srl == $this->get('member_srl')) return;
            // get where the comment belongs to 
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($this->get('document_srl'));
            // Variables
            if($type) $title = "[".$type."] ";
            $title .= cut_str(strip_tags($content), 30, '...');
            $content = sprintf('%s<br /><br />from : <a href="%s#comment_%s" target="_blank">%s</a>',$content, getFullUrl('','document_srl',$this->get('document_srl')), $this->get('comment_srl'),  getFullUrl('','document_srl',$this->get('document_srl')));
            $receiver_srl = $this->get('member_srl');
            $sender_member_srl = $logged_info->member_srl;
            // send a message
            $oCommunicationController = &getController('communication');
            $oCommunicationController->sendMessage($sender_member_srl, $receiver_srl, $title, $content, false);
        }

        function getIpAddress() {
            if($this->isGranted()) return $this->get('ipaddress');
            return preg_replace('/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/','*.$2.$3.$4', $this->get('ipaddress'));
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

		/**
		 * Return content with htmlspecialchars
		 * @return string
		 */
        function getContentText($strlen = 0) {
            if($this->isSecret() && !$this->isAccessible()) return Context::getLang('msg_is_secret');

            $content = $this->get('content');

            if($strlen) return cut_str(strip_tags($content),$strlen,'...');

            return htmlspecialchars($content);
        }

		/**
		 * Return content after filter
		 * @return string
		 */
        function getContent($add_popup_menu = true, $add_content_info = true, $add_xe_content_class = true) {
            if($this->isSecret() && !$this->isAccessible()) return Context::getLang('msg_is_secret');

            $content = $this->get('content');
            stripEmbedTagForAdmin($content, $this->get('member_srl'));
            // when displaying the comment on the pop-up menu
            if($add_popup_menu && Context::get('is_logged') ) {
                $content = sprintf(
                        '%s<div class="comment_popup_menu"><a href="#popup_menu_area" class="comment_%d" onclick="return false">%s</a></div>',
                        $content,
                        $this->comment_srl, Context::getLang('cmd_comment_do')
                );
            }
            // if additional information which can access contents is set
            if($add_content_info) {
				$memberSrl = $this->get('member_srl');
				if($memberSrl < 0)
				{
					$memberSrl = 0;
				}
                $content = sprintf(
                        '<!--BeforeComment(%d,%d)--><div class="comment_%d_%d xe_content">%s</div><!--AfterComment(%d,%d)-->',
                        $this->comment_srl, $memberSrl,
                        $this->comment_srl, $memberSrl,
                        $content,
                        $this->comment_srl, $memberSrl
                );
            // xe_content class name should be specified although content access is not necessary.
            } else {
                if($add_xe_content_class) $content = sprintf('<div class="xe_content">%s</div>', $content);
            }

            return $content;
        }

		/**
		 * Return summary content
		 * @return string
		 */
        function getSummary($str_size = 50, $tail = '...') {
            $content = $this->getContent(false, false);
            // for newline, insert a blank.
            $content = preg_replace('!(<br[\s]*/{0,1}>[\s]*)+!is', ' ', $content);
            // replace tags such as </p> , </div> , </li> by blanks.
            $content = str_replace(array('</p>', '</div>', '</li>'), ' ', $content);
            // Remove tags
            $content = preg_replace('!<([^>]*?)>!is','', $content);
            // replace < , >, " 
            $content = str_replace(array('&lt;','&gt;','&quot;','&nbsp;'), array('<','>','"',' '), $content);
            // delete a series of blanks
            $content = preg_replace('/ ( +)/is', ' ', $content);
            // truncate strings
            $content = trim(cut_str($content, $str_size, $tail));
            // restore >, <, , "\
            $content = str_replace(array('<','>','"'),array('&lt;','&gt;','&quot;'), $content);

            return $content;
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

        function getPermanentUrl() {
            return getFullUrl('','document_srl',$this->get('document_srl')).'#comment_'.$this->get('comment_srl');
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
            if(($this->isSecret() && !$this->isAccessible()) && !$this->isGranted()) return false;
            return $this->get('uploaded_count')? true : false;
        }

        function getUploadedFiles() {
            if(($this->isSecret() && !$this->isAccessible()) && !$this->isGranted()) return;
            if(!$this->get('uploaded_count')) return;

            $oFileModel = &getModel('file');
            $file_list = $oFileModel->getFiles($this->comment_srl, $is_admin);
            return $file_list;
        }

		/**
		 * Return the editor html
		 * @return string
		 */
        function getEditor() {
            $module_srl = $this->get('module_srl');
            if(!$module_srl) $module_srl = Context::get('module_srl');
            $oEditorModel = &getModel('editor');
            return $oEditorModel->getModuleEditor('comment', $module_srl, $this->comment_srl, 'comment_srl', 'content');
        }

		/**
		 * Return author's profile image
		 * @return object
		 */
        function getProfileImage() {
            if(!$this->isExists() || !$this->get('member_srl')) return;
            $oMemberModel = &getModel('member');
            $profile_info = $oMemberModel->getProfileImage($this->get('member_srl'));
            if(!$profile_info) return;

            return $profile_info->src;
        }

		/**
		 * Return author's signiture
		 * @return string
		 */
        function getSignature() {
            // pass if the posting not exists.
            if(!$this->isExists() || !$this->get('member_srl')) return;
            // get the signiture information
            $oMemberModel = &getModel('member');
            $signature = $oMemberModel->getSignature($this->get('member_srl'));
            // check if max height of the signiture is specified on the member module
            if(!isset($GLOBALS['__member_signature_max_height'])) {
               $oModuleModel = &getModel('module');
               $member_config = $oModuleModel->getModuleConfig('member');
               $GLOBALS['__member_signature_max_height'] = $member_config->signature_max_height;
            }
            $max_signature_height = $GLOBALS['__member_signature_max_height'];
            if($max_signature_height) $signature = sprintf('<div style="max-height:%dpx;overflow:auto;overflow-x:hidden;height:expression(this.scrollHeight > %d ? \'%dpx\': \'auto\')">%s</div>', $max_signature_height, $max_signature_height, $max_signature_height, $signature);

            return $signature;
        }

        function thumbnailExists($width = 80, $height = 0, $type = '') {
            if(!$this->comment_srl) return false;
            if(!$this->getThumbnail($width, $height, $type)) return false;
            return true;
        }

        function getThumbnail($width = 80, $height = 0, $thumbnail_type = '') {
            // return false if no doc exists
            if(!$this->comment_srl) return;
            // If signiture height setting is omitted, create a square
            if(!$height) $height = $width;
            // return false if neigher attached file nor image;
            if(!$this->hasUploadedFiles() && !preg_match("!<img!is", $this->get('content'))) return;
            // get thumbail generation info on the doc module configuration.
            if(!in_array($thumbnail_type, array('crop','ratio'))) $thumbnail_type = 'crop';
            // Define thumbnail information
            $thumbnail_path = sprintf('files/cache/thumbnails/%s',getNumberingPath($this->comment_srl, 3));
            $thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
            $thumbnail_url  = Context::getRequestUri().$thumbnail_file;
            // return false if a size of existing thumbnail file is 0. otherwise return the file path
            if(file_exists($thumbnail_file)) {
                if(filesize($thumbnail_file)<1) return false;
                else return $thumbnail_url;
            }
            // Target file
            $source_file = null;
            $is_tmp_file = false;
            // find an image file among attached files
            if($this->hasUploadedFiles()) {
                $file_list = $this->getUploadedFiles();
                if(count($file_list)) {
                    foreach($file_list as $file) {
                        if($file->direct_download!='Y') continue;
                        if(!preg_match("/\.(jpg|png|jpeg|gif|bmp)$/i",$file->source_filename)) continue;

                        $source_file = $file->uploaded_filename;
                        if(!file_exists($source_file)) $source_file = null;
                        else break;
                    }
                }
            }
            // get an image file from the doc content if no file attached. 
            if(!$source_file) {
                $content = $this->get('content');
                $target_src = null;
                preg_match_all("!src=(\"|')([^\"' ]*?)(\"|')!is", $content, $matches, PREG_SET_ORDER);
                $cnt = count($matches);
                for($i=0;$i<$cnt;$i++) {
                    $target_src = $matches[$i][2];
                    if(preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $target_src)) continue;
                    else {
                        if(!preg_match('/^(http|https):\/\//i',$target_src)) $target_src = Context::getRequestUri().$target_src;
                        $tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$this->comment_srl));
                        if(!is_dir('./files/cache/tmp')) FileHandler::makeDir('./files/cache/tmp');
                        FileHandler::getRemoteFile($target_src, $tmp_file);
                        if(!file_exists($tmp_file)) continue;
                        else {
                            list($_w, $_h, $_t, $_a) = @getimagesize($tmp_file);
                            if($_w<$width || $_h<$height) continue;

                            $source_file = $tmp_file;
                            $is_tmp_file = true;
                            break;
                        }
                    }
                }
            }

            $output = FileHandler::createImageFile($source_file, $thumbnail_file, $width, $height, 'jpg', $thumbnail_type);

            if($is_tmp_file) FileHandler::removeFile($source_file);
            // return the thumbnail path if successfully generated.
            if($output) return $thumbnail_url;
            // create an empty file not to attempt to generate the thumbnail afterwards
            else FileHandler::writeFile($thumbnail_file, '','w');

            return;
        }

        function isCarted() {
            return $_SESSION['comment_management'][$this->comment_srl];
        }
    }
?>
