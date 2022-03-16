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
class commentItem extends BaseObject
{

	/**
	 * comment number
	 * @var int
	 */
	var $comment_srl = 0;
	/**
	 * grant
	 * @var bool
	 */
	var $grant_cache = null;
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
	function __construct($comment_srl = 0, $columnList = array())
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
		if(!is_object($attribute) || !$attribute->comment_srl)
		{
			$this->comment_srl = NULL;
			return;
		}

		$this->comment_srl = $attribute->comment_srl;
		$this->adds($attribute);

		// define vars on the object for backward compatibility of skins
		if(countobj($attribute))
		{
			foreach($attribute as $key => $val)
			{
				$this->{$key} = $val;
			}
		}
	}

	function isExists()
	{
		return (bool) ($this->comment_srl);
	}
	
	function isGranted()
	{
		if(!$this->isExists())
		{
			return false;
		}
		
		if (isset($_SESSION['granted_comment'][$this->comment_srl]))
		{
			return true;
		}
		
		if ($this->grant_cache !== null)
		{
			return $this->grant_cache;
		}
		
		$logged_info = Context::get('logged_info');
		if (!$logged_info->member_srl)
		{
			return $this->grant_cache = false;
		}
		if ($logged_info->is_admin == 'Y')
		{
			return $this->grant_cache = true;
		}
		if ($this->get('member_srl') && abs($this->get('member_srl')) == $logged_info->member_srl)
		{
			return $this->grant_cache = true;
		}
		
		$grant = ModuleModel::getGrant(ModuleModel::getModuleInfoByModuleSrl($this->get('module_srl')), $logged_info);
		if ($grant->manager)
		{
			return $this->grant_cache = true;
		}
		
		return $this->grant_cache = false;
	}
	
	function setGrant()
	{
		$this->grant_cache = true;
	}
	
	function setGrantForSession()
	{
		$_SESSION['granted_comment'][$this->comment_srl] = true;
		$this->setGrant();
	}
	
	/**
	 * Return the status code.
	 * 
	 * @return string
	 */
	public function getStatus()
	{
		switch ($this->get('status'))
		{
			case RX_STATUS_TEMP: return 'TEMP';
			case RX_STATUS_PUBLIC: return $this->get('is_secret') !== 'Y' ? 'PUBLIC' : 'SECRET';
			case RX_STATUS_SECRET: return 'SECRET';
			case RX_STATUS_EMBARGO: return 'EMBARGO';
			case RX_STATUS_TRASH: return 'TRASH';
			case RX_STATUS_CENSORED: return 'CENSORED';
			case RX_STATUS_CENSORED_BY_ADMIN: return 'CENSORED_BY_ADMIN';
			case RX_STATUS_DELETED: return 'DELETED';
			case RX_STATUS_DELETED_BY_ADMIN: return 'DELETED_BY_ADMIN';
			case RX_STATUS_OTHER: return 'OTHER';
			default: return 'OTHER';
		}
	}

	/**
	 * Return the status in human-readable text.
	 * 
	 * @return string
	 */
	public function getStatusText()
	{
		$status = $this->getStatus();
		$statusList = lang('document.status_name_list');
		if ($status && isset($statusList[$status]))
		{
			return $statusList[$status];
		}
		else
		{
			return $statusList['OTHER'];
		}
	}

	function isAccessible()
	{
		if(!$this->isExists())
		{
			return false;
		}
		
		if (isset($_SESSION['accessible'][$this->comment_srl]) && $_SESSION['accessible'][$this->comment_srl] === $this->get('last_update'))
		{
			return true;
		}
		
		if ($this->get('status') == RX_STATUS_PUBLIC && $this->get('is_secret') !== 'Y')
		{
			$this->setAccessible();
			return true;
		}
		
		if ($this->isGranted())
		{
			$this->setAccessible();
			return true;
		}
		
		$oDocument = DocumentModel::getDocument($this->get('document_srl'));
		if ($oDocument->isGranted())
		{
			$this->setAccessible();
			return true;
		}
		
		return false;
	}
	
	function setAccessible()
	{
		if(Context::getSessionStatus())
		{
			$_SESSION['accessible'][$this->comment_srl] = $this->get('last_update');
		}
	}
	
	function isEditable()
	{
		return !$this->get('member_srl') || $this->isGranted();
	}
	
	function isSecret()
	{
		return $this->get('status') == RX_STATUS_SECRET || $this->get('is_secret') == 'Y';
	}
	
	function isDeleted()
	{
		return $this->get('status') == RX_STATUS_DELETED || $this->get('status') == RX_STATUS_DELETED_BY_ADMIN;
	}
	
	function isDeletedByAdmin()
	{
		return $this->get('status') == RX_STATUS_DELETED_BY_ADMIN;
	}
	
	function useNotify()
	{
		return $this->get('notify_message') == 'Y';
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
		$oDocument = DocumentModel::getDocument($this->get('document_srl'));

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
		$oCommunicationController->sendMessage($sender_member_srl, $receiver_srl, $title, $content, false, null, false);
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

		if(!preg_match('@^[a-z]+://@i', $url))
		{
			$url = 'http://' . $url;
		}

		return escape($url, false);
	}

	function getMemberSrl()
	{
		return $this->get('member_srl');
	}

	function getUserID()
	{
		return escape($this->get('user_id'), false);
	}

	function getUserName()
	{
		return escape($this->get('user_name'), false);
	}

	function getNickName()
	{
		return escape($this->get('nick_name'), false);
	}

	function getVote()
	{
		return $this->getMyVote();
	}

	function getMyVote()
	{
		if(!$this->comment_srl) return false;
		if(isset($_SESSION['voted_comment'][$this->comment_srl]))
		{
			return $_SESSION['voted_comment'][$this->comment_srl];
		}

		$logged_info = Context::get('logged_info');
		if(!$logged_info->member_srl) return false;

		$args = new stdClass();
		if($logged_info->member_srl)
		{
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->member_srl = 0;
			$args->ipaddress = \RX_CLIENT_IP;
		}
		$args->comment_srl = $this->comment_srl;
		$output = executeQuery('comment.getCommentVotedLog', $args);

		if($output->data->point)
		{
			return $_SESSION['voted_comment'][$this->comment_srl] = $output->data->point;
		}

		return $_SESSION['voted_comment'][$this->comment_srl] = false;
	}

	function getContentPlainText($strlen = 0)
	{
		if($this->isDeletedByAdmin())
		{
			$content = lang('msg_admin_deleted_comment');
		}
		elseif($this->isDeleted())
		{
			$content = lang('msg_deleted_comment');
		}
		elseif($this->isSecret() && !$this->isAccessible())
		{
			$content = lang('msg_is_secret');
		}
		else
		{
			$content = $this->get('content');
		}
		
		$content = trim(utf8_normalize_spaces(html_entity_decode(strip_tags($content))));
		if($strlen)
		{
			$content = cut_str($content, $strlen, '...');
		}
		return escape($content);
	}

	/**
	 * Return content with htmlspecialchars
	 * @return string
	 */
	function getContentText($strlen = 0)
	{
		if($this->isDeletedByAdmin())
		{
			$content = lang('msg_admin_deleted_comment');
		}
		elseif($this->isDeleted())
		{
			$content = lang('msg_deleted_comment');
		}
		elseif($this->isSecret() && !$this->isAccessible())
		{
			$content = lang('msg_is_secret');
		}
		else
		{
			$content = $this->get('content');
		}
		
		if($strlen)
		{
			$content = trim(utf8_normalize_spaces(html_entity_decode(strip_tags($content))));
			$content = cut_str($content, $strlen, '...');
		}
		return escape($content);
	}

	/**
	 * Return content after filter
	 * @return string
	 */
	function getContent($add_popup_menu = TRUE, $add_content_info = TRUE, $add_xe_content_class = TRUE)
	{
		if($this->isDeletedByAdmin())
		{
			$content = lang('msg_admin_deleted_comment');
			$additional_class = ' is_deleted is_deleted_by_admin';
		}
		elseif($this->isDeleted())
		{
			$content = lang('msg_deleted_comment');
			$additional_class = ' is_deleted';
		}
		elseif($this->isSecret() && !$this->isAccessible())
		{
			$content = lang('msg_is_secret');
			$additional_class = ' is_secret';
		}
		else
		{
			$content = $this->get('content');
			$additional_class = '';
			stripEmbedTagForAdmin($content, $this->get('member_srl'));
		}

		// when displaying the comment on the pop-up menu
		if($add_popup_menu && Context::get('is_logged'))
		{
			$content = sprintf(
					'%s<div class="comment_popup_menu"><a href="#popup_menu_area" class="comment_%d" onclick="return false">%s</a></div>', $content, $this->comment_srl, lang('cmd_comment_do')
			);
		}

		// if additional information which can access contents is set
		if($add_content_info)
		{
			$member_srl = $this->get('member_srl');
			if($member_srl < 0)
			{
				$member_srl = 0;
			}
			$content = vsprintf('<!--BeforeComment(%d,%d)--><div class="comment_%d_%d rhymix_content xe_content%s">%s</div><!--AfterComment(%d,%d)-->', array(
				$this->comment_srl, $member_srl, $this->comment_srl, $member_srl, $additional_class, $content, $this->comment_srl, $member_srl
			));
		}
		else
		{
			if($add_xe_content_class)
			{
				$content = sprintf('<div class="rhymix_content xe_content%s">%s</div>', $additional_class, $content);
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
		// Remove tags
		$content = strip_tags($this->getContent(false, false));
		
		// Convert temporarily html entity for truncate
		$content = html_entity_decode($content, ENT_QUOTES);
		
		// Replace all whitespaces to single space
		$content = utf8_trim(utf8_normalize_spaces($content));
		
		// Truncate string
		$content = cut_str($content, $str_size, $tail);
		
		return escape($content);
	}

	function getRegdate($format = 'Y.m.d H:i:s', $conversion = true)
	{
		return zdate($this->get('regdate'), $format, $conversion);
	}

	function getRegdateTime()
	{
		return ztime($this->get('regdate'));
	}

	function getRegdateGM($format = 'r')
	{
		return gmdate($format, $this->getRegdateTime());
	}

	function getRegdateDT($format = 'c')
	{
		return Rhymix\Framework\DateTime::formatTimestampForCurrentUser($format, $this->getRegdateTime());
	}

	function getUpdate($format = 'Y.m.d H:i:s', $conversion = true)
	{
		return zdate($this->get('last_update'), $format, $conversion);
	}

	function getUpdateTime()
	{
		return ztime($this->get('last_update'));
	}

	function getUpdateGM($format = 'r')
	{
		return gmdate($format, $this->getUpdateTime());
	}

	function getUpdateDT($format = 'c')
	{
		return Rhymix\Framework\DateTime::formatTimestampForCurrentUser($format, $this->getUpdateTime());
	}

	function getPermanentUrl()
	{
		$mid = ModuleModel::getMidByModuleSrl($this->get('module_srl'));
		return getFullUrl('', 'mid', $mid, 'document_srl', $this->get('document_srl')) . '#comment_' . $this->get('comment_srl');
	}

	function hasUploadedFiles()
	{
		if(!$this->isAccessible())
		{
			return false;
		}
		
		return $this->get('uploaded_count') ? TRUE : FALSE;
	}

	function getUploadedFiles()
	{
		if(!$this->isAccessible())
		{
			return;
		}
		
		if(!$this->get('uploaded_count'))
		{
			return;
		}
		
		$file_list = FileModel::getFiles($this->comment_srl, array(), 'file_srl', TRUE);
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
		return EditorModel::getModuleEditor('comment', $module_srl, $this->comment_srl, 'comment_srl', 'content');
	}

	/**
	 * Return author's profile image
	 * @return object
	 */
	function getProfileImage()
	{
		if(!$this->isExists() || $this->get('member_srl') <= 0)
		{
			return;
		}
		$profile_info = MemberModel::getProfileImage($this->get('member_srl'));
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
		if(!$this->isExists() || $this->get('member_srl') <= 0)
		{
			return;
		}

		// get the signiture information
		$signature = MemberModel::getSignature($this->get('member_srl'));

		// check if max height of the signiture is specified on the member module
		if(!isset($GLOBALS['__member_signature_max_height']))
		{
			$member_config = ModuleModel::getModuleConfig('member');
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
		if(!$this->comment_srl || !$this->isAccessible())
		{
			return;
		}

		// Get thumbnail type information from document module's configuration
		$config = DocumentModel::getDocumentConfig();
		if ($config->thumbnail_target === 'none' || $config->thumbnail_type === 'none')
		{
			return;
		}
		if(!in_array($thumbnail_type, array('crop', 'ratio', 'fill', 'stretch', 'center')))
		{
			$thumbnail_type = $config->thumbnail_type ?: 'fill';
		}
		if(!$config->thumbnail_quality)
		{
			$config->thumbnail_quality = 75;
		}
		
		// If signiture height setting is omitted, create a square
		if(!is_int($width))
		{
			$width = intval($width);
		}
		if(!$height || (!is_int($height) && !ctype_digit(strval($height)) && $height !== 'auto'))
		{
			$height = $width;
		}

		// Define thumbnail information
		$thumbnail_path = sprintf('files/thumbnails/%s', getNumberingPath($this->comment_srl, 3));
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_lockfile = sprintf('%s%dx%d.%s.lock', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_url = RX_BASEURL . $thumbnail_file;
		$thumbnail_file = RX_BASEDIR . $thumbnail_file;

		// return false if a size of existing thumbnail file is 0. otherwise return the file path
		if(file_exists($thumbnail_file) || file_exists($thumbnail_lockfile))
		{
			if(filesize($thumbnail_file) < 1)
			{
				return FALSE;
			}
			else
			{
				return $thumbnail_url . '?' . date('YmdHis', filemtime($thumbnail_file));
			}
		}

		// Call trigger for custom thumbnails.
		$trigger_obj = (object)[
			'document_srl' => $this->document_srl, 'comment_srl' => $this->comment_srl,
			'width' => $width, 'height' => $height,
			'image_type' => 'jpg', 'type' => $thumbnail_type, 'quality' => $config->thumbnail_quality,
			'filename' => $thumbnail_file, 'url' => $thumbnail_url,
		];
		$output = ModuleHandler::triggerCall('comment.getThumbnail', 'before', $trigger_obj);
		clearstatcache(true, $thumbnail_file);
		if (file_exists($thumbnail_file) && filesize($thumbnail_file) > 0)
		{
			return $thumbnail_url . '?' . date('YmdHis', filemtime($thumbnail_file));
		}
		
		// return false if neigher attached file nor image;
		if(!$this->get('uploaded_count') && !preg_match("!<img!is", $this->get('content')))
		{
			return;
		}

		// Create lockfile to prevent race condition
		FileHandler::writeFile($thumbnail_lockfile, '', 'w');

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
					$source_file = FileHandler::getRealPath($file->uploaded_filename);
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
				$source_file = FileHandler::getRealPath($first_image);
			}
		}

		// get an image file from the doc content if no file attached. 
		if(!$source_file && $config->thumbnail_target !== 'attachment')
		{
			$external_image_min_width = min(100, round($trigger_obj->width * 0.3));
			$external_image_min_height = min(100, round($trigger_obj->height * 0.3));
			preg_match_all("!<img\s[^>]*?src=(\"|')([^\"' ]*?)(\"|')!is", $this->get('content'), $matches, PREG_SET_ORDER);
			foreach($matches as $match)
			{
				$target_src = htmlspecialchars_decode(trim($match[2]));
				if(preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $target_src))
				{
					continue;
				}
				else
				{
					if(!preg_match('/^https?:\/\//i',$target_src))
					{
						$target_src = Context::getRequestUri().$target_src;
					}

					$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111, 999999) . $this->comment_srl));
					if(!is_dir('./files/cache/tmp'))
					{
						FileHandler::makeDir('./files/cache/tmp');
					}
					FileHandler::getRemoteFile($target_src, $tmp_file);
					if(!file_exists($tmp_file))
					{
						continue;
					}
					else
					{
						if($is_img = @getimagesize($tmp_file))
						{
							list($_w, $_h, $_t, $_a) = $is_img;
							if($_w < ($external_image_min_width) && ($height === 'auto' || $_h < ($external_image_min_height)))
							{
								continue;
							}
						}
						else
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

		if($source_file)
		{
			$output = FileHandler::createImageFile($source_file, $thumbnail_file, $trigger_obj->width, $trigger_obj->height, $trigger_obj->image_type, $trigger_obj->type, $trigger_obj->quality);
		}

		// Remove source file if it was temporary
		if($is_tmp_file)
		{
			FileHandler::removeFile($source_file);
		}

		// Remove lockfile
		FileHandler::removeFile($thumbnail_lockfile);

		// Return the thumbnail path if it was successfully generated
		if($output)
		{
			return $thumbnail_url . '?' . date('YmdHis');
		}
		// Create an empty file if thumbnail generation failed
		else
		{
			FileHandler::writeFile($thumbnail_file, '','w');
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
