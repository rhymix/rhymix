<?php
    /**
     * @class  documentItem
     * @author NHN (developers@xpressengine.com)
     * @brief document object
     **/

    class documentItem extends Object {

        var $document_srl = 0;
        var $lang_code = null;

        var $allow_trackback_status = null;
		var $columnList = array();
		var $allowscriptaccessList = array();
		var $allowscriptaccessKey = 0;
		var $uploadedFiles = array();

        function documentItem($document_srl = 0, $load_extra_vars = true, $columnList = array()) {
            $this->document_srl = $document_srl;
			$this->columnList = $columnList;

            $this->_loadFromDB($load_extra_vars);
        }

        function setDocument($document_srl, $load_extra_vars = true) {
            $this->document_srl = $document_srl;
            $this->_loadFromDB($load_extra_vars);
        }

        function _loadFromDB($load_extra_vars = true) {
            if(!$this->document_srl) return;

            // cache controll
            $oCacheHandler = &CacheHandler::getInstance('object');
            if($oCacheHandler->isSupport()){
                    $cache_key = 'object_document_item:'.$this->document_srl;
                    $output = $oCacheHandler->get($cache_key);
            }
            if(!$output) {
                $args->document_srl = $this->document_srl;
                $output = executeQuery('document.getDocument', $args, $this->columnList);
                //insert in cache
                if($output->data->document_srl && $oCacheHandler->isSupport())
                   $oCacheHandler->put($cache_key,$output);
            }
            $this->setAttribute($output->data,$load_extra_vars);
        }

        function setAttribute($attribute,$load_extra_vars=true) {
            if(!$attribute->document_srl) {
                $this->document_srl = null;
                return;
            }
            $this->document_srl = $attribute->document_srl;
            $this->lang_code = $attribute->lang_code;
            $this->adds($attribute);
            // Tags
            if($this->get('tags')) {
                $tags = explode(',',$this->get('tags'));
                $tag_count = count($tags);
                for($i=0;$i<$tag_count;$i++) if(trim($tags[$i])) $tag_list[] = trim($tags[$i]);
                $this->add('tag_list', $tag_list);
            }

            $oDocumentModel = &getModel('document');
            $GLOBALS['XE_DOCUMENT_LIST'][$this->document_srl] = $this;
            if($load_extra_vars) {
                $oDocumentModel->setToAllDocumentExtraVars();
                $this->add('title', $GLOBALS['XE_DOCUMENT_LIST'][$this->document_srl]->get('title'));
                $this->add('content', $GLOBALS['XE_DOCUMENT_LIST'][$this->document_srl]->get('content'));
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

			$oModuleModel = &getModel('module');
			$grant = $oModuleModel->getGrant($oModuleModel->getModuleInfoByModuleSrl($this->get('module_srl')), $logged_info);
			if($grant->manager) return true;

            if($this->get('member_srl') && ($this->get('member_srl') == $logged_info->member_srl || $this->get('member_srl')*-1 == $logged_info->member_srl)) return true;

            return false;
        }

        function setGrant() {
            $_SESSION['own_document'][$this->document_srl] = true;
        }

        function isAccessible() {
            return $_SESSION['accessible'][$this->document_srl]==true?true:false;
        }

        function allowComment()
		{
			// init write, document is not exists. so allow comment status is true
            if(!$this->isExists()) return true;

            return $this->get('comment_status') == 'ALLOW' ? true : false;
        }

        function allowTrackback() {
            static $allow_trackback_status = null;
            if(is_null($allow_trackback_status)) {
                // If the trackback module is configured to be disabled, do not allow. Otherwise, check the setting of each module.
                $oModuleModel = &getModel('module');
                $trackback_config = $oModuleModel->getModuleConfig('trackback');
                if(!isset($trackback_config->enable_trackback)) $trackback_config->enable_trackback = 'Y';
                if($trackback_config->enable_trackback != 'Y') $allow_trackback_status = false;
                else {
                    $module_srl = $this->get('module_srl');
                    // Check settings of each module
                    $module_config = $oModuleModel->getModulePartConfig('trackback', $module_srl);
                    if($module_config->enable_trackback == 'N') $allow_trackback_status = false;
                    else if($this->get('allow_trackback')=='Y' || !$this->isExists()) $allow_trackback_status = true;
                }
            }
            return $allow_trackback_status;
        }

        function isLocked() {
			if(!$this->isExists()) return false;

            return $this->get('comment_status') == 'ALLOW' ? false : true;
        }

        function isEditable() {
            if($this->isGranted() || !$this->get('member_srl')) return true;
            return false;
        }

        function isSecret() {
			$oDocumentModel = &getModel('document');
            return $this->get('status') == $oDocumentModel->getConfigStatus('secret') ? true : false;
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
            // return if it is not useNotify
            if(!$this->useNotify()) return;
            // Pass if an author is not a logged-in user
            if(!$this->get('member_srl')) return;
            // Return if the currently logged-in user is an author
            $logged_info = Context::get('logged_info');
            if($logged_info->member_srl == $this->get('member_srl')) return;
            // List variables
            if($type) $title = "[".$type."] ";
            $title .= cut_str(strip_tags($content), 10, '...');
            $content = sprintf('%s<br /><br />from : <a href="%s" target="_blank">%s</a>',$content, getFullUrl('','document_srl',$this->document_srl), getFullUrl('','document_srl',$this->document_srl));
            $receiver_srl = $this->get('member_srl');
            $sender_member_srl = $logged_info->member_srl;
            // Send a message
            $oCommunicationController = &getController('communication');
            $oCommunicationController->sendMessage($sender_member_srl, $receiver_srl, $title, $content, false);
        }

        function getLangCode() {
            return $this->get('lang_code');
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
            $this->add('title_color', trim($this->get('title_color')));
            if($this->get('title_bold')=='Y') $attrs[] = "font-weight:bold;";
            if($this->get('title_color') && $this->get('title_color') != 'N') $attrs[] = "color:#".$this->get('title_color');

            if(count($attrs)) return sprintf("<span style=\"%s\">%s</span>", implode(';',$attrs), htmlspecialchars($title));
            else return htmlspecialchars($title);
        }

        function getContentText($strlen = 0) {
            if(!$this->document_srl) return;

            if($this->isSecret() && !$this->isGranted() && !$this->isAccessible()) return Context::getLang('msg_is_secret');

			$result = $this->_checkAccessibleFromStatus();
			if($result) $_SESSION['accessible'][$this->document_srl] = true;

            $content = $this->get('content');
			$content = preg_replace_callback('/<(object|param|embed)[^>]*/is', array($this, '_checkAllowScriptAccess'), $content);
			$content = preg_replace_callback('/<object[^>]*>/is', array($this, '_addAllowScriptAccess'), $content);

            if($strlen) return cut_str(strip_tags($content),$strlen,'...');

            return htmlspecialchars($content);
        }

		function _addAllowScriptAccess($m)
		{
			if($this->allowscriptaccessList[$this->allowscriptaccessKey] == 1)
			{
				$m[0] = $m[0].'<param name="allowscriptaccess" value="never"></param>';
			}
			$this->allowscriptaccessKey++;
			return $m[0];
		}

		function _checkAllowScriptAccess($m)
		{
			if($m[1] == 'object')
			{
				$this->allowscriptaccessList[] = 1;
			}

			if($m[1] == 'param')
			{
				if(strpos(strtolower($m[0]), 'allowscriptaccess'))
				{
					$m[0] = '<param name="allowscriptaccess" value="never"';
					if(substr($m[0], -1) == '/')
					{
						$m[0] .= '/';
					}
					$this->allowscriptaccessList[count($this->allowscriptaccessList)-1]--;
				}
			}
			else if($m[1] == 'embed')
			{
				if(strpos(strtolower($m[0]), 'allowscriptaccess'))
				{
					$m[0] = preg_replace('/always|samedomain/i', 'never', $m[0]);
				}
				else
				{
					$m[0] = preg_replace('/\<embed/i', '<embed allowscriptaccess="never"', $m[0]);
				}
			}
			return $m[0];
		}

        function getContent($add_popup_menu = true, $add_content_info = true, $resource_realpath = false, $add_xe_content_class = true, $stripEmbedTagException = false) {
            if(!$this->document_srl) return;

            if($this->isSecret() && !$this->isGranted() && !$this->isAccessible()) return Context::getLang('msg_is_secret');

			$result = $this->_checkAccessibleFromStatus();
            if($result) $_SESSION['accessible'][$this->document_srl] = true;

            $content = $this->get('content');
            if(!$stripEmbedTagException) stripEmbedTagForAdmin($content, $this->get('member_srl'));

            // Define a link if using a rewrite module
            $oContext = &Context::getInstance();
            if($oContext->allow_rewrite) {
                $content = preg_replace('/<a([ \t]+)href=("|\')\.\/\?/i',"<a href=\\2". Context::getRequestUri() ."?", $content);
            }
            // To display a pop-up menu
            if($add_popup_menu) {
                $content = sprintf(
                        '%s<div class="document_popup_menu"><a href="#popup_menu_area" class="document_%d" onclick="return false">%s</a></div>',
                        $content,
                        $this->document_srl, Context::getLang('cmd_document_do')
                );
            }
            // If additional content information is set
            if($add_content_info) {
				$memberSrl = $this->get('member_srl');
				if($memberSrl < 0)
				{
					$memberSrl = 0;
				}
                $content = sprintf(
                        '<!--BeforeDocument(%d,%d)--><div class="document_%d_%d xe_content">%s</div><!--AfterDocument(%d,%d)-->',
                        $this->document_srl, $memberSrl,
                        $this->document_srl, $memberSrl,
                        $content,
                        $this->document_srl, $memberSrl,
                        $this->document_srl, $memberSrl
                );
            // Add xe_content class although accessing content is not required
            } else {
                if($add_xe_content_class) $content = sprintf('<div class="xe_content">%s</div>', $content);
            }
            // Change the image path to a valid absolute path if resource_realpath is true
            if($resource_realpath) {
                $content = preg_replace_callback('/<img([^>]+)>/i',array($this,'replaceResourceRealPath'), $content);
            }

            return $content;
        }

        /**
         * Return transformed content by Editor codes
         **/
        function getTransContent($add_popup_menu = true, $add_content_info = true, $resource_realpath = false, $add_xe_content_class = true) {
            $oEditorController = &getController('editor');

            $content = $this->getContent($add_popup_menu, $add_content_info, $resource_realpath, $add_xe_content_class);
            $content = $oEditorController->transComponent($content);

            return $content;
        }

        function getSummary($str_size = 50, $tail = '...') {
            $content = $this->getContent(false,false);

            // For a newlink, inert a whitespace
            $content = preg_replace('!(<br[\s]*/{0,1}>[\s]*)+!is', ' ', $content);

            // Replace tags such as </p> , </div> , </li> and others to a whitespace
            $content = str_replace(array('</p>', '</div>', '</li>'), ' ', $content);

            // Remove Tags
            $content = preg_replace('!<([^>]*?)>!is','', $content);

            // Replace < , >, "
            $content = str_replace(array('&lt;','&gt;','&quot;','&nbsp;'), array('<','>','"',' '), $content);

            // Delete  a series of whitespaces
            $content = preg_replace('/ ( +)/is', ' ', $content);

            // Truncate string
            $content = trim(cut_str($content, $str_size, $tail));

            // Replace back < , <, "
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
            return getFullUrl('','document_srl',$this->get('document_srl'));
        }

        function getTrackbackUrl() {
            if(!$this->document_srl) return;
            // Generate a key to prevent spams
            $oTrackbackModel = &getModel('trackback');
            return $oTrackbackModel->getTrackbackUrl($this->document_srl);
        }

        function updateReadedCount() {
            $oDocumentController = &getController('document');
            if($oDocumentController->updateReadedCount($this)) {
                $readed_count = $this->get('readed_count');
                $this->add('readed_count', $readed_count+1);
            }
        }

        function isExtraVarsExists() {
            if(!$this->get('module_srl')) return false;
            $oDocumentModel = &getModel('document');
            $extra_keys = $oDocumentModel->getExtraKeys($this->get('module_srl'));
            return count($extra_keys)?true:false;
        }

        function getExtraVars() {
            if(!$this->get('module_srl') || !$this->document_srl) return null;

            $oDocumentModel = &getModel('document');
            return $oDocumentModel->getExtraVars($this->get('module_srl'), $this->document_srl);
        }

        function getExtraValue($idx) {
            $extra_vars = $this->getExtraVars();
            return $extra_vars[$idx]->value;
        }

        function getExtraValueHTML($idx) {
            $extra_vars = $this->getExtraVars();
            if(is_array($extra_vars) && array_key_exists($idx,$extra_vars)){
                return $extra_vars[$idx]->getValueHTML();
            }else{
                return '';
            }

        }

        function getExtraEidValue($eid) {
            $extra_vars = $this->getExtraVars();

			if($extra_vars)
			{
				// Handle extra variable(eid)
				foreach($extra_vars as $idx => $key) {
					$extra_eid[$key->eid] = $key;
				}
			}
            return $extra_eid[$eid]->value;
        }

        function getExtraEidValueHTML($eid) {
            $extra_vars = $this->getExtraVars();
            // Handle extra variable(eid)
            foreach($extra_vars as $idx => $key) {
                $extra_eid[$key->eid] = $key;
            }
            return $extra_eid[$eid]->getValueHTML();
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
            if(!$this->getCommentCount()) return;
            if(!$this->isGranted() && $this->isSecret()) return;
            // cpage is a number of comment pages
			$cpageStr = sprintf('%d_cpage', $this->document_srl);
			$cpage = Context::get($cpageStr);
			
			if(!$cpage)
			{
            	$cpage = Context::get('cpage');
			}

            // Get a list of comments
            $oCommentModel = &getModel('comment');
            $output = $oCommentModel->getCommentList($this->document_srl, $cpage, $is_admin);
            if(!$output->toBool() || !count($output->data)) return;
            // Create commentItem object from a comment list
            // If admin priviledge is granted on parent posts, you can read its child posts.
            $accessible = array();
			$comment_list = array();
            foreach($output->data as $key => $val) {
                $oCommentItem = new commentItem();
                $oCommentItem->setAttribute($val);
                // If permission is granted to the post, you can access it temporarily
                if($oCommentItem->isGranted()) $accessible[$val->comment_srl] = true;
                // If the comment is set to private and it belongs child post, it is allowable to read the comment for who has a admin privilege on its parent post
                if($val->parent_srl>0 && $val->is_secret == 'Y' && !$oCommentItem->isAccessible() && $accessible[$val->parent_srl]===true) {
                    $oCommentItem->setAccessible();
                }
                $comment_list[$val->comment_srl] = $oCommentItem;
            }
            // Variable setting to be displayed on the skin
            Context::set($cpageStr, $output->page_navigation->cur_page);
            Context::set('cpage', $output->page_navigation->cur_page);
            if($output->total_page>1) $this->comment_page_navigation = $output->page_navigation;

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
            // Return false if the document doesn't exist
            if(!$this->document_srl) return;
            // If not specify its height, create a square
            if(!$height) $height = $width;
            // Return false if neither attachement nor image files in the document
            if(!$this->get('uploaded_count') && !preg_match("!<img!is", $this->get('content'))) return;
            // Get thumbnai_type information from document module's configuration
            if(!in_array($thumbnail_type, array('crop','ratio'))) {
                $config = $GLOBALS['__document_config__'];
                if(!$config) {
                    $oDocumentModel = &getModel('document');
                    $config = $oDocumentModel->getDocumentConfig();
                    $GLOBALS['__document_config__'] = $config;
                }
                $thumbnail_type = $config->thumbnail_type;
            }
            // Define thumbnail information
            $thumbnail_path = sprintf('files/cache/thumbnails/%s',getNumberingPath($this->document_srl, 3));
            $thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
            $thumbnail_url  = Context::getRequestUri().$thumbnail_file;
            // Return false if thumbnail file exists and its size is 0. Otherwise, return its path
            if(file_exists($thumbnail_file)) {
                if(filesize($thumbnail_file)<1) return false;
                else return $thumbnail_url;
            }
            // Target File
            $source_file = null;
            $is_tmp_file = false;
            // Find an iamge file among attached files if exists
            if($this->get('uploaded_count')) {
                $oFileModel = &getModel('file');
                $file_list = $oFileModel->getFiles($this->document_srl);
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
            // If not exists, file an image file from the content
            if(!$source_file) {
                $content = $this->get('content');
                $target_src = null;
                preg_match_all("!src=(\"|')([^\"' ]*?)(\"|')!is", $content, $matches, PREG_SET_ORDER);
                $cnt = count($matches);
                for($i=0;$i<$cnt;$i++) {
                    $target_src = trim($matches[$i][2]);
                    if(!preg_match("/\.(jpg|png|jpeg|gif|bmp)$/i",$target_src)) continue;
                    if(preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $target_src)) continue;
                    else {
                        if(!preg_match('/^(http|https):\/\//i',$target_src)) $target_src = Context::getRequestUri().$target_src;
                        $tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$this->document_srl));
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

            if($source_file){
                $output = FileHandler::createImageFile($source_file, $thumbnail_file, $width, $height, 'jpg', $thumbnail_type);
            }
            if($is_tmp_file) FileHandler::removeFile($source_file);
            // Return its path if a thumbnail is successfully genetated
            if($output) return $thumbnail_url;
            // Create an empty file not to re-generate the thumbnail
            else FileHandler::writeFile($thumbnail_file, '','w');

            return;
        }

        /**
         * @brief Functions to display icons for new post, latest update, secret(private) post, image/video/attachment
         * Determine new post and latest update by $time_interval
         **/
        function getExtraImages($time_interval = 43200) {
            if(!$this->document_srl) return;
            // variables for icon list
            $buffs = array();

            $check_files = false;

            // Check if secret post is
            if($this->isSecret()) $buffs[] = "secret";

            // Set the latest time
            $time_check = date("YmdHis", time()-$time_interval);

            // Check new post
            if($this->get('regdate')>$time_check) $buffs[] = "new";
            else if($this->get('last_update')>$time_check) $buffs[] = "update";

            /*
            $content = $this->get('content');

            // Check image files
            preg_match_all('!<img([^>]*?)>!is', $content, $matches);
            $cnt = count($matches[0]);
            for($i=0;$i<$cnt;$i++) {
                if(preg_match('/editor_component=/',$matches[0][$i])&&!preg_match('/image_(gallery|link)/i',$matches[0][$i])) continue;
                $buffs[] = "image";
                $check_files = true;
                break;
            }

            // Check video files
            if(preg_match('!<embed([^>]*?)>!is', $content) || preg_match('/editor_component=("|\')*multimedia_link/i', $content) ) {
                $buffs[] = "movie";
                $check_files = true;
            }
            */

            // Check the attachment
            if($this->hasUploadedFiles()) $buffs[] = "file";

            return $buffs;
        }

		function getStatus()
		{
			if(!$this->get('status')) return $this->getDefaultStatus();
			return $this->get('status');
		}

        /**
         * @brief Return the value obtained from getExtraImages with image tag
         **/
        function printExtraImages($time_check = 43200) {
            if(!$this->document_srl) return;
            // Get the icon directory
            $path = sprintf('%s%s',getUrl(), 'modules/document/tpl/icons/');

            $buffs = $this->getExtraImages($time_check);
            if(!count($buffs)) return;

            $buff = null;
            foreach($buffs as $key => $val) {
                $buff .= sprintf('<img src="%s%s.gif" alt="%s" title="%s" style="margin-right:2px;" />', $path, $val, $val, $val);
            }
            return $buff;
        }

        function hasUploadedFiles() {
            if(!$this->document_srl) return;

            if($this->isSecret() && !$this->isGranted()) return false;
            return $this->get('uploaded_count')? true : false;
        }

		function getUploadedFiles($sortIndex = 'file_srl')
		{
			if(!$this->document_srl) return;

			if($this->isSecret() && !$this->isGranted()) return;
			if(!$this->get('uploaded_count')) return;

			if(!$this->uploadedFiles[$sortIndex])
			{
				$oFileModel = &getModel('file');
				$this->uploadedFiles[$sortIndex] = $oFileModel->getFiles($this->document_srl, array(), $sortIndex);
			}

			return $this->uploadedFiles[$sortIndex];
		}

        /**
         * @brief Return Editor html
         **/
        function getEditor() {
            $module_srl = $this->get('module_srl');
            if(!$module_srl) $module_srl = Context::get('module_srl');

            $oEditorModel = &getModel('editor');
            return $oEditorModel->getModuleEditor('document', $module_srl, $this->document_srl, 'document_srl', 'content');
        }

        /**
         * @brief Check whether to have a permission to write comment
         * Authority to write a comment and to write a document is separated
         **/
        function isEnableComment() {
            // Return false if not authorized, if a secret document, if the document is set not to allow any comment
			if (!$this->allowComment()) return false;
			if(!$this->isGranted() && $this->isSecret()) return false;

			return true;
        }

        /**
         * @brief Return comment editor's html
         **/
        function getCommentEditor() {
            if(!$this->isEnableComment()) return;

            $oEditorModel = &getModel('editor');
            return $oEditorModel->getModuleEditor('comment', $this->get('module_srl'), $comment_srl, 'comment_srl', 'content');
        }

        /**
         * @brief Return author's profile image
         **/
        function getProfileImage() {
            if(!$this->isExists() || !$this->get('member_srl')) return;
            $oMemberModel = &getModel('member');
            $profile_info = $oMemberModel->getProfileImage($this->get('member_srl'));
            if(!$profile_info) return;

            return $profile_info->src;
        }

        /**
         * @brief Return author's signiture
         **/
        function getSignature() {
            // Pass if a document doesn't exist
            if(!$this->isExists() || !$this->get('member_srl')) return;
            // Get signature information
            $oMemberModel = &getModel('member');
            $signature = $oMemberModel->getSignature($this->get('member_srl'));
            // Check if a maximum height of signiture is set in the member module
            if(!isset($GLOBALS['__member_signature_max_height'])) {
               $oModuleModel = &getModel('module');
               $member_config = $oModuleModel->getModuleConfig('member');
               $GLOBALS['__member_signature_max_height'] = $member_config->signature_max_height;
            }
            if($signature) {
                $max_signature_height = $GLOBALS['__member_signature_max_height'];
                if($max_signature_height) $signature = sprintf('<div style="max-height:%dpx;overflow:auto;overflow-x:hidden;height:expression(this.scrollHeight > %d ? \'%dpx\': \'auto\')">%s</div>', $max_signature_height, $max_signature_height, $max_signature_height, $signature);
            }

            return $signature;
        }

        /**
         * @brief Change an image path in the content to absolute path
         **/
        function replaceResourceRealPath($matches) {
            return preg_replace('/src=(["\']?)files/i','src=$1'.Context::getRequestUri().'files', $matches[0]);
        }

		function _checkAccessibleFromStatus()
		{
            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y') return true;

			$status = $this->get('status');
			if(empty($status)) return false;

			$oDocumentModel = &getModel('document');
			$configStatusList = $oDocumentModel->getStatusList();

			if($status == $configStatusList['public'] || $status == $configStatusList['publish'])
				return true;
			else if($status == $configStatusList['private'] || $status == $configStatusList['secret'])
			{
				if($this->get('member_srl') == $logged_info->member_srl)
					return true;
			}
			return false;
		}
		
		function getTranslationLangCodes()
        {
            $obj->document_srl = $this->document_srl;
            // -2 is an index for content. We are interested if content has other translations.
            $obj->var_idx = -2;
            $output = executeQueryArray('document.getDocumentTranslationLangCodes', $obj);

            if (!$output->data)
            {
                $output->data = array();
            }
            // add original page's lang code as well
            $origLangCode->lang_code = $this->getLangCode();
            $output->data[] = $origLangCode;

            return $output->data;
        }


		/**
		 * @brief Returns the document's mid in order to construct SEO friendly URLs
		 */
		function getDocumentMid() {
			$model = &getModel('module');
			$module = $model->getModuleInfoByModuleSrl($this->get('module_srl'));
			return $module->mid;
		}

		/**
		 * @brief Returns the document's type (document/page/wiki/board/etc)
		 */
		function getDocumentType() {
			$model = &getModel('module');
			$module = $model->getModuleInfoByModuleSrl($this->get('module_srl'));
			return $module->module;
		}

		/**
		 * @brief Returns the document's alias
		 */
		function getDocumentAlias() {
			$oDocumentModel = &getModel('document');
			return $oDocumentModel->getAlias($this->document_srl);
		}

		/**
		 * @brief Returns the document's actual title (browser_title)
		 */
		function getModuleName() {
			$model = &getModel('module');
			$module = $model->getModuleInfoByModuleSrl($this->get('module_srl'));
			return $module->browser_title;
		}

    }
?>
