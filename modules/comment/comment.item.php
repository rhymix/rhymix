<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * commentItem class
 * comment Object
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/comment
 * @version 0.1
 */
class commentItem extends Object
{

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
	function commentItem($comment_srl = 0, $columnList = array())
	{
		$this->comment_srl = $comment_srl;
		$this->columnList = $columnList;
		$this->_loadFromDB();
	}

	function setComment($comment_srl)
	{
		$this->comment_srl = $comment_srl;
		$this->_loadFromDB();
	}

	/**
	 * Load comment data from DB and set to commentItem object
	 * @return void
	 */
	function _loadFromDB()
	{
		if(!$this->comment_srl)
		{
			return;
		}

		$args = new stdClass();
		$args->comment_srl = $this->comment_srl;
		$output = executeQuery('comment.getComment', $args, $this->columnList);

		$this->setAttribute($output->data);
	}

	/**
	 * Comment attribute set to Object object
	 * @return void
	 */
	function setAttribute($attribute)
	{
		if(!$attribute->comment_srl)
		{
			$this->comment_srl = NULL;
			return;
		}

		$this->comment_srl = $attribute->comment_srl;
		$this->adds($attribute);

		// define vars on the object for backward compatibility of skins
		if(count($attribute))
		{
			foreach($attribute as $key => $val)
			{
				$this->{$key} = $val;
			}
		}
	}

	function isExists()
	{
		return $this->comment_srl ? TRUE : FALSE;
	}

	function isGranted()
	{
		if($_SESSION['own_comment'][$this->comment_srl])
		{
			return TRUE;
		}

		if(!Context::get('is_logged'))
		{
			return FALSE;
		}

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin == 'Y')
		{
			return TRUE;
		}

		$grant = Context::get('grant');
		if($grant->manager)
		{
			return TRUE;
		}

		if($this->get('member_srl') && ($this->get('member_srl') == $logged_info->member_srl || $this->get('member_srl') * -1 == $logged_info->member_srl))
		{
			return TRUE;
		}

		return FALSE;
	}

	function setGrant()
	{
		$_SESSION['own_comment'][$this->comment_srl] = TRUE;
		$this->is_granted = TRUE;
	}

	function setAccessible()
	{
		$_SESSION['accessibled_comment'][$this->comment_srl] = TRUE;
	}

	function isEditable()
	{
		if($this->isGranted() || !$this->get('member_srl'))
		{
			return TRUE;
		}
		return FALSE;
	}

	function isSecret()
	{
		return $this->get('is_secret') == 'Y' ? TRUE : FALSE;
	}

	function isAccessible()
	{
		if($_SESSION['accessibled_comment'][$this->comment_srl])
		{
			return TRUE;
		}

		if($this->isGranted() || !$this->isSecret())
		{
			$this->setAccessible();
			return TRUE;
		}

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($this->get('document_srl'));
		if($oDocument->isGranted())
		{
			$this->setAccessible();
			return TRUE;
		}

		return FALSE;
	}

	function useNotify()
	{
		return $this->get('notify_message') == 'Y' ? TRUE : FALSE;
	}

	/**
	 * Notify to comment owner
	 * @return void
	 */
	function notify($type, $content)
	{
		// return if not useNotify
		if(!$this->useNotify())
		{
			return;
		}

		// pass if the author is not logged-in user 
		if(!$this->get('member_srl'))
		{
			return;
		}

		// return if the currently logged-in user is an author of the comment.
		$logged_info = Context::get('logged_info');
		if($logged_info->member_srl == $this->get('member_srl'))
		{
			return;
		}

		// get where the comment belongs to 
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($this->get('document_srl'));

		// Variables
		if($type)
		{
			$title = "[" . $type . "] ";
		}

		$title .= cut_str(strip_tags($content), 30, '...');
		$content = sprintf('%s<br /><br />from : <a href="%s#comment_%s" target="_blank">%s</a>', $content, getFullUrl('', 'document_srl', $this->get('document_srl')), $this->get('comment_srl'), getFullUrl('', 'document_srl', $this->get('document_srl')));
		$receiver_srl = $this->get('member_srl');
		$sender_member_srl = $logged_info->member_srl;

		// send a message
		$oCommunicationController = getController('communication');
		$oCommunicationController->sendMessage($sender_member_srl, $receiver_srl, $title, $content, FALSE);
	}

	function getIpAddress()
	{
		if($this->isGranted())
		{
			return $this->get('ipaddress');
		}

		return '*' . strstr($this->get('ipaddress'), '.');
	}

	function isExistsHomepage()
	{
		if(trim($this->get('homepage')))
		{
			return TRUE;
		}

		return FALSE;
	}

	function getHomepageUrl()
	{
		$url = trim($this->get('homepage'));
		if(!$url)
		{
			return;
		}

		if(strncasecmp('http://', $url, 7) !== 0)
		{
			$url = "http://" . $url;
		}

		return htmlspecialchars($url, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}

	function getMemberSrl()
	{
		return $this->get('member_srl');
	}

	function getUserID()
	{
		return htmlspecialchars($this->get('user_id'), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}

	function getUserName()
	{
		return htmlspecialchars($this->get('user_name'), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}

	function getNickName()
	{
		return htmlspecialchars($this->get('nick_name'), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}

	/**
	 * Return content with htmlspecialchars
	 * @return string
	 */
	function getContentText($strlen = 0)
	{
		if($this->isSecret() && !$this->isAccessible())
		{
			return Context::getLang('msg_is_secret');
		}

		$content = $this->get('content');

		if($strlen)
		{
			return cut_str(strip_tags($content), $strlen, '...');
		}

		return htmlspecialchars($content, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
	}

	/**
	 * Return content after filter
	 * @return string
	 */
	function getContent($add_popup_menu = TRUE, $add_content_info = TRUE, $add_xe_content_class = TRUE)
	{
		if($this->isSecret() && !$this->isAccessible())
		{
			return Context::getLang('msg_is_secret');
		}

		$content = $this->get('content');
		stripEmbedTagForAdmin($content, $this->get('member_srl'));

		// when displaying the comment on the pop-up menu
		if($add_popup_menu && Context::get('is_logged'))
		{
			$content = sprintf(
					'%s<div class="comment_popup_menu"><a href="#popup_menu_area" class="comment_%d" onclick="return false">%s</a></div>', $content, $this->comment_srl, Context::getLang('cmd_comment_do')
			);
		}

		// if additional information which can access contents is set
		if($add_content_info)
		{
			$memberSrl = $this->get('member_srl');
			if($memberSrl < 0)
			{
				$memberSrl = 0;
			}
			$content = sprintf(
					'<!--BeforeComment(%d,%d)--><div class="comment_%d_%d xe_content">%s</div><!--AfterComment(%d,%d)-->', $this->comment_srl, $memberSrl, $this->comment_srl, $memberSrl, $content, $this->comment_srl, $memberSrl
			);
			// xe_content class name should be specified although content access is not necessary.
		}
		else
		{
			if($add_xe_content_class)
			{
				$content = sprintf('<div class="xe_content">%s</div>', $content);
			}
		}

		return $content;
	}

	/**
	 * Return summary content
	 * @return string
	 */
	function getSummary($str_size = 50, $tail = '...')
	{
		$content = $this->getContent(FALSE, FALSE);

		// for newline, insert a blank.
		$content = preg_replace('!(<br[\s]*/{0,1}>[\s]*)+!is', ' ', $content);

		// replace tags such as </p> , </div> , </li> by blanks.
		$content = str_replace(array('</p>', '</div>', '</li>', '-->'), ' ', $content);

		// Remove tags
		$content = preg_replace('!<([^>]*?)>!is', '', $content);

		// replace < , >, " 
		$content = str_replace(array('&lt;', '&gt;', '&quot;', '&nbsp;'), array('<', '>', '"', ' '), $content);

		// delete a series of blanks
		$content = preg_replace('/ ( +)/is', ' ', $content);

		// truncate strings
		$content = trim(cut_str($content, $str_size, $tail));

		// restore >, <, , "\
		$content = str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $content);

		return $content;
	}

	function getRegdate($format = 'Y.m.d H:i:s')
	{
		return zdate($this->get('regdate'), $format);
	}

	function getRegdateTime()
	{
		$regdate = $this->get('regdate');
		$year = substr($regdate, 0, 4);
		$month = substr($regdate, 4, 2);
		$day = substr($regdate, 6, 2);
		$hour = substr($regdate, 8, 2);
		$min = substr($regdate, 10, 2);
		$sec = substr($regdate, 12, 2);
		return mktime($hour, $min, $sec, $month, $day, $year);
	}

	function getRegdateGM()
	{
		return $this->getRegdate('D, d M Y H:i:s') . ' ' . $GLOBALS['_time_zone'];
	}

	function getUpdate($format = 'Y.m.d H:i:s')
	{
		return zdate($this->get('last_update'), $format);
	}

	function getPermanentUrl()
	{
		return getFullUrl('', 'document_srl', $this->get('document_srl')) . '#comment_' . $this->get('comment_srl');
	}

	function getUpdateTime()
	{
		$year = substr($this->get('last_update'), 0, 4);
		$month = substr($this->get('last_update'), 4, 2);
		$day = substr($this->get('last_update'), 6, 2);
		$hour = substr($this->get('last_update'), 8, 2);
		$min = substr($this->get('last_update'), 10, 2);
		$sec = substr($this->get('last_update'), 12, 2);
		return mktime($hour, $min, $sec, $month, $day, $year);
	}

	function getUpdateGM()
	{
		return gmdate("D, d M Y H:i:s", $this->getUpdateTime());
	}

	function hasUploadedFiles()
	{
		if(($this->isSecret() && !$this->isAccessible()) && !$this->isGranted())
		{
			return FALSE;
		}
		return $this->get('uploaded_count') ? TRUE : FALSE;
	}

	function getUploadedFiles()
	{
		if(($this->isSecret() && !$this->isAccessible()) && !$this->isGranted())
		{
			return;
		}

		if(!$this->get('uploaded_count'))
		{
			return;
		}

		$oFileModel = getModel('file');
		$file_list = $oFileModel->getFiles($this->comment_srl, array(), 'file_srl', TRUE);
		return $file_list;
	}

	/**
	 * Return the editor html
	 * @return string
	 */
	function getEditor()
	{
		$module_srl = $this->get('module_srl');
		if(!$module_srl)
		{
			$module_srl = Context::get('module_srl');
		}
		$oEditorModel = getModel('editor');
		return $oEditorModel->getModuleEditor('comment', $module_srl, $this->comment_srl, 'comment_srl', 'content');
	}

	/**
	 * Return author's profile image
	 * @return object
	 */
	function getProfileImage()
	{
		if(!$this->isExists() || !$this->get('member_srl'))
		{
			return;
		}
		$oMemberModel = getModel('member');
		$profile_info = $oMemberModel->getProfileImage($this->get('member_srl'));
		if(!$profile_info)
		{
			return;
		}

		return $profile_info->src;
	}

	/**
	 * Return author's signiture
	 * @return string
	 */
	function getSignature()
	{
		// pass if the posting not exists.
		if(!$this->isExists() || !$this->get('member_srl'))
		{
			return;
		}

		// get the signiture information
		$oMemberModel = getModel('member');
		$signature = $oMemberModel->getSignature($this->get('member_srl'));

		// check if max height of the signiture is specified on the member module
		if(!isset($GLOBALS['__member_signature_max_height']))
		{
			$oModuleModel = getModel('module');
			$member_config = $oModuleModel->getModuleConfig('member');
			$GLOBALS['__member_signature_max_height'] = $member_config->signature_max_height;
		}

		$max_signature_height = $GLOBALS['__member_signature_max_height'];

		if($max_signature_height)
		{
			$signature = sprintf('<div style="max-height:%dpx;overflow:auto;overflow-x:hidden;height:expression(this.scrollHeight > %d ? \'%dpx\': \'auto\')">%s</div>', $max_signature_height, $max_signature_height, $max_signature_height, $signature);
		}

		return $signature;
	}

	function thumbnailExists($width = 80, $height = 0, $type = '')
	{
		if(!$this->comment_srl)
		{
			return FALSE;
		}

		if(!$this->getThumbnail($width, $height, $type))
		{
			return FALSE;
		}

		return TRUE;
	}

	function getThumbnail($width = 80, $height = 0, $thumbnail_type = '')
	{
		// return false if no doc exists
		if(!$this->comment_srl)
		{
			return;
		}

		if($this->isSecret() && !$this->isGranted())
		{
			return;
		}

		// If signiture height setting is omitted, create a square
		if(!$height)
		{
			$height = $width;
		}

		// return false if neigher attached file nor image;
		if(!$this->hasUploadedFiles() && !preg_match("!<img!is", $this->get('content')))
		{
			return;
		}

		// get thumbail generation info on the doc module configuration.
		if(!in_array($thumbnail_type, array('crop', 'ratio')))
		{
			$thumbnail_type = 'crop';
		}

		// Define thumbnail information
		$thumbnail_path = sprintf('files/thumbnails/%s', getNumberingPath($this->comment_srl, 3));
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_url = Context::getRequestUri() . $thumbnail_file;

		// return false if a size of existing thumbnail file is 0. otherwise return the file path
		if(file_exists($thumbnail_file))
		{
			if(filesize($thumbnail_file) < 1)
			{
				return FALSE;
			}
			else
			{
				return $thumbnail_url;
			}
		}

		// Target file
		$source_file = NULL;
		$is_tmp_file = FALSE;

		// find an image file among attached files
		if($this->hasUploadedFiles())
		{
			$file_list = $this->getUploadedFiles();

			$first_image = null;
			foreach($file_list as $file)
			{
				if($file->direct_download !== 'Y') continue;

				if($file->cover_image === 'Y' && file_exists($file->uploaded_filename))
				{
					$source_file = $file->uploaded_filename;
					break;
				}

				if($first_image) continue;

				if(preg_match("/\.(jpe?g|png|gif|bmp)$/i", $file->source_filename))
				{
					if(file_exists($file->uploaded_filename))
					{
						$first_image = $file->uploaded_filename;
					}
				}
			}

			if(!$source_file && $first_image)
			{
				$source_file = $first_image;
			}
		}

		// get an image file from the doc content if no file attached. 
		if(!$source_file)
		{
			$content = $this->get('content');
			$target_src = NULL;

			preg_match_all("!src=(\"|')([^\"' ]*?)(\"|')!is", $content, $matches, PREG_SET_ORDER);

			$cnt = count($matches);

			for($i = 0; $i < $cnt; $i++)
			{
				$target_src = $matches[$i][2];
				if(preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $target_src))
				{
					continue;
				}
				else
				{
					if(!preg_match('/^(http|https):\/\//i', $target_src))
					{
						$target_src = Context::getRequestUri() . $target_src;
					}

					$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111, 999999) . $this->comment_srl));

					FileHandler::makeDir('./files/cache/tmp');

					FileHandler::getRemoteFile($target_src, $tmp_file);

					if(!file_exists($tmp_file))
					{
						continue;
					}
					else
					{
						list($_w, $_h, $_t, $_a) = @getimagesize($tmp_file);

						if($_w < $width || $_h < $height)
						{
							continue;
						}

						$source_file = $tmp_file;
						$is_tmp_file = TRUE;
						break;
					}
				}
			}
		}

		$output = FileHandler::createImageFile($source_file, $thumbnail_file, $width, $height, 'jpg', $thumbnail_type);

		if($is_tmp_file)
		{
			FileHandler::removeFile($source_file);
		}

		// return the thumbnail path if successfully generated.
		if($output)
		{
			return $thumbnail_url;
		}

		// create an empty file not to attempt to generate the thumbnail afterwards
		else
		{
			FileHandler::writeFile($thumbnail_file, '', 'w');
		}

		return;
	}

	function isCarted()
	{
		return $_SESSION['comment_management'][$this->comment_srl];
	}

}
/* End of file comment.item.php */
/* Location: ./modules/comment/comment.item.php */
