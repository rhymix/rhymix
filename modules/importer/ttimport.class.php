<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
@set_time_limit(0);
@require_once('./modules/importer/extract.class.php');

/**
 * ttimport class
 * ttxml import class
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/importer
 * @version 0.1
 */
class ttimport
{
	/**
	 * Xml Parse
	 * @var XmlParser
	 */
	var $oXmlParser = null;

	/**
	 * Import data in module.xml format
	 * @param int $key
	 * @param int $cur
	 * @param string $index_file
	 * @param int $unit_count
	 * @param int $module_srl
	 * @param int $guestbook_module_srl
	 * @param string $user_id
	 * @param string $module_name
	 * @return int
	 */
	function importModule($key, $cur, $index_file, $unit_count, $module_srl, $guestbook_module_srl, $user_id, $module_name=null)
	{
		// Pre-create the objects needed
		$this->oXmlParser = new XmlParser();
		// Get category information of the target module
		$oDocumentController = getController('document');
		$oDocumentModel = getModel('document');
		$category_list = $category_titles = array();
		$category_list = $oDocumentModel->getCategoryList($module_srl);
		if(count($category_list)) foreach($category_list as $key => $val) $category_titles[$val->title] = $val->category_srl;
		// First handle categorty information
		$category_file = preg_replace('/index$/i', 'category.xml', $index_file);
		if(file_exists($category_file))
		{
			// Create the xmlParser object
			$xmlDoc = $this->oXmlParser->loadXmlFile($category_file);
			// List category information
			if($xmlDoc->categories->category)
			{
				$categories = array();
				$idx = 0;
				$this->arrangeCategory($xmlDoc->categories, $categories, $idx, 0);

				$match_sequence = array();
				foreach($categories as $k => $v)
				{
					$category = $v->name;
					if(!$category || $category_titles[$category]) continue;

					$obj = null;
					$obj->title = $category;
					$obj->module_srl = $module_srl; 
					if($v->parent) $obj->parent_srl = $match_sequence[$v->parent];
					$output = $oDocumentController->insertCategory($obj);

					if($output->toBool()) $match_sequence[$v->sequence] = $category_titles[$category] = $output->get('category_srl');
				}
				$oDocumentController->makeCategoryFile($module_srl);
			}
			FileHandler::removeFile($category_file);
		}
		$category_list = $category_titles = array();
		$category_list = $oDocumentModel->getCategoryList($module_srl);
		if(count($category_list)) foreach($category_list as $key => $val) $category_titles[$val->title] = $val->category_srl;
		// Get administrator information
		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByUserID($user_id);
		$author_xml_id = 0;

		if(!$cur) $cur = 0;
		// Open an index file
		$f = fopen($index_file,"r");
		// Pass if already read
		for($i=0;$i<$cur;$i++) fgets($f, 1024);
		// Read each line until the codition meets
		for($idx=$cur;$idx<$cur+$unit_count;$idx++)
		{
			if(feof($f)) break;
			// Find a location
			$target_file = trim(fgets($f, 1024));

			if(!file_exists($target_file)) continue;
			// Start importing data
			$fp = fopen($target_file,"r");
			if(!$fp) continue;

			$obj = null;
			$obj->module_srl = $module_srl;
			$obj->document_srl = getNextSequence();
			$obj->uploaded_count = 0;

			$files = array();

			$started = false;
			$buff = null;
			// Start importing from the body data
			while(!feof($fp))
			{
				$str = fgets($fp, 1024);
				// Prepare an item
				if(substr($str,0,5) == '<post')
				{
					$started = true;
					continue;
					// Import the attachment
				}
				else if(substr($str,0,12) == '<attachment ')
				{
					if($this->importAttaches($fp, $module_srl, $obj->document_srl, $files, $str)) $obj->uploaded_count++;
					continue;
				}

				if($started) $buff .= $str;
			}

			$xmlDoc = $this->oXmlParser->parse('<post>'.$buff);

			$author_xml_id = $xmlDoc->post->author->body;

			if($xmlDoc->post->category->body)
			{
				$tmp_arr = explode('/',$xmlDoc->post->category->body);
				$category = trim($tmp_arr[count($tmp_arr)-1]);
				if($category_titles[$category]) $obj->category_srl = $category_titles[$category];
			}

			$obj->is_notice = 'N';
			$obj->status = in_array($xmlDoc->post->visibility->body, array('public','syndicated'))?$oDocumentModel->getConfigStatus('public'):$oDocumentModel->getConfigStatus('secret');
			$obj->title = $xmlDoc->post->title->body;
			$obj->content = $xmlDoc->post->content->body;
			$obj->password = md5($xmlDoc->post->password->body);
			$obj->commentStatus = $xmlDoc->post->acceptcomment->body=='1'?'ALLOW':'DENY';
			$obj->allow_trackback = $xmlDoc->post->accepttrackback->body=='1'?'Y':'N';
			//$obj->allow_comment = $xmlDoc->post->acceptComment->body=='1'?'Y':'N';
			//$obj->allow_trackback = $xmlDoc->post->acceptTrackback->body=='1'?'Y':'N';
			$obj->regdate = date("YmdHis",$xmlDoc->post->published->body);
			$obj->last_update = date("YmdHis", $xmlDoc->post->modified->body);
			if(!$obj->last_update) $obj->last_update = $obj->regdate;

			$tag = null;
			$tmp_tags = null;
			$tag = $xmlDoc->post->tag;
			if($tag)
			{
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
			$obj->notify_message = 'N';
			// Change content information (attachment)
			$obj->content = str_replace('[##_ATTACH_PATH_##]/','',$obj->content);
			if(count($files))
			{
				foreach($files as $key => $val) {
					$obj->content = preg_replace('/(src|href)\=(["\']?)'.preg_quote($key).'(["\']?)/i','$1="'.$val->url.'"',$obj->content);
				}
			}

			$obj->content = preg_replace_callback('!\[##_Movie\|([^\|]*)\|(.*?)_##\]!is', array($this, '_replaceTTMovie'), $obj->content);

			if(count($files))
			{
				$this->files = $files;
				$obj->content = preg_replace_callback('!\[##_([a-z0-9]+)\|([^\|]*)\|([^\|]*)\|(.*?)_##\]!is', array($this, '_replaceTTAttach'), $obj->content);
			}
			// Trackback inserted
			$obj->trackback_count = 0;
			if($xmlDoc->post->trackback)
			{
				$trackbacks = $xmlDoc->post->trackback;
				if(!is_array($trackbacks)) $trackbacks = array($trackbacks);
				if(count($trackbacks))
				{
					foreach($trackbacks as $key => $val)
					{
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
			// Comment
			$obj->comment_count = 0;
			if($xmlDoc->post->comment)
			{
				$comment = $xmlDoc->post->comment;
				if(!is_array($comment)) $comment = array($comment);
				foreach($comment as $key => $val)
				{
					$parent_srl = $this->insertComment($val, $module_srl, $obj->document_srl, $member_info, 0, $author_xml_id);
					if($parent_srl === false) continue;

					$obj->comment_count++;
					if($val->comment)
					{
						$child_comment = $val->comment;
						if(!is_array($child_comment)) $child_comment = array($child_comment);
						foreach($child_comment as $k => $v)
						{
							$result = $this->insertComment($v, $module_srl, $obj->document_srl, $member_info, $parent_srl, $author_xml_id);
							if($result !== false) $obj->comment_count++;
						}
					}
				}
			}

			if($module_name == 'textyle')
			{
				$args->document_srl = $obj->document_srl;
				$args->module_srl = $obj->module_srl;
				$args->logs = serialize(null);
				$output = executeQuery('textyle.insertPublishLog', $args);
				// Visibility value of published state
				$status_published = array('public', 'syndicated');
				// Save state if not published
				if(!in_array($xmlDoc->post->visibility->body, $status_published))
				{
					$obj->module_srl = $member_info->member_srl; 
				}
			}
			// Document
			$output = executeQuery('document.insertDocument', $obj);

			if($output->toBool())
			{
				// Tags
				if($obj->tags)
				{
					$tag_list = explode(',',$obj->tags);
					$tag_count = count($tag_list);
					for($i=0;$i<$tag_count;$i++)
					{
						$args = new stdClass;
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
		// Guestbook information
		$guestbook_file = preg_replace('/index$/i', 'guestbook.xml', $index_file);
		if(file_exists($guestbook_file))
		{
			// Create the xmlParser object
			$xmlDoc = $this->oXmlParser->loadXmlFile($guestbook_file);
			// Handle guest book information
			if($guestbook_module_srl && $xmlDoc->guestbook->comment)
			{
				$comment = $xmlDoc->guestbook->comment;
				if(!is_array($comment)) $comment = array($comment);

				if($module_name =='textyle')
				{
					foreach($comment as $key => $val)
					{
						$textyle_guestbook_srl  = getNextSequence();

						if($val->comment)
						{
							$child_comment = $val->comment;
							if(!is_array($child_comment)) $child_comment = array($child_comment);
							foreach($child_comment as $k => $v)
							{
								$result = $this->insertTextyleGuestbookItem($v, $module_srl, $member_info,0,$textyle_guestbook_srl,$author_xml_id);
							}
						}

						$result = $this->insertTextyleGuestbookItem($val, $module_srl, $member_info,$textyle_guestbook_srl,0,$author_xml_id);
					}
				}
				else
				{
					foreach($comment as $key => $val)
					{
						$obj = null;
						$obj->module_srl = $guestbook_module_srl;
						$obj->document_srl = getNextSequence();
						$obj->uploaded_count = 0;
						$obj->is_notice = 'N';
						$obj->status = $val->secret->body=='1'?$oDocumentModel->getConfigStatus('secret'):$oDocumentModel->getConfigStatus('public');
						$obj->content = nl2br($val->content->body);

						// Extract a title form the bocy
						$obj->title = cut_str(strip_tags($obj->content),20,'...');
						if ($obj->title == '') $obj->title = 'Untitled';

						$obj->commentStatus = 'ALLOW';
						$obj->allow_trackback = 'N';
						$obj->regdate = date("YmdHis",$val->written->body);
						$obj->last_update = date("YmdHis", $val->written->body);
						if(!$obj->last_update) $obj->last_update = $obj->regdate;
						$obj->tags = '';
						$obj->readed_count = 0;
						$obj->voted_count = 0;
						if($author_xml_id && $val->commenter->attrs->id == $author_xml_id)
						{
							$obj->password = '';
							$obj->nick_name = $member_info->nick_name;
							$obj->user_name = $member_info->user_name;
							$obj->user_id = $member_info->user_id;
							$obj->member_srl = $member_info->member_srl;
							$obj->email_address = $member_info->email_address;
							$obj->homepage = $member_info->homepage;
						}
						else
						{
							$obj->password = $val->password->body;
							$obj->nick_name = $val->commenter->name->body;
							$obj->member_srl = 0;
							$homepage = $val->commenter->homepage->body;
						}
						$obj->ipaddress = $val->commenter->ip->body;
						$obj->list_order = $obj->update_order = $obj->document_srl*-1;
						$obj->notify_message = 'N';
						$obj->trackback_count = 0;

						$obj->comment_count = 0;
						if($val->comment)
						{
							$child_comment = $val->comment;
							if(!is_array($child_comment)) $child_comment = array($child_comment);
							foreach($child_comment as $k => $v)
							{
								$result = $this->insertComment($v, $module_srl, $obj->document_srl, $member_info, 0,$author_xml_id);
								if($result !== false) $obj->comment_count++;
							}
						}

						// Document
						$output = executeQuery('document.insertDocument', $obj);
					}
				}
			}
			FileHandler::removeFile($guestbook_file);
		}

		return $idx-1;
	}

	/**
	 * Insert textyle guest book
	 * @param object $val
	 * @param int $module_srl
	 * @param object $member_info
	 * @param int $textyle_guestbook_srl
	 * @param int $parent_srl
	 * @param int $author_xml_id
	 * @return int|bool
	 */
	function insertTextyleGuestbookItem($val, $module_srl, $member_info, $textyle_guestbook_srl,$parent_srl = 0, $author_xml_id=null)
	{
		$tobj = null;
		if($textyle_guestbook_srl>0)
		{
			$tobj->textyle_guestbook_srl = $textyle_guestbook_srl;
		}
		else
		{
			$tobj->textyle_guestbook_srl = getNextSequence();
		}
		$tobj->module_srl = $module_srl;
		$tobj->is_secret = $val->secret->body=='1'?1:-1;
		$tobj->content = nl2br($val->content->body);
		if($author_xml_id && $val->commenter->attrs->id == $author_xml_id)
		{
			$tobj->password = '';
			$tobj->nick_name = $member_info->nick_name;
			$tobj->user_name = $member_info->user_name;
			$tobj->user_id = $member_info->user_id;
			$tobj->member_srl = $member_info->member_srl;
			$tobj->homepage = $member_info->homepage;
			$tobj->email_address = $member_info->email_address;
		}
		else
		{
			$tobj->password = $val->password->body;
			$tobj->nick_name = $val->commenter->name->body;
			$tobj->homepage = $val->commenter->homepage->body;
			$tobj->member_srl = 0;
		}
		$tobj->last_update = $tobj->regdate = date("YmdHis",$val->written->body);
		$tobj->ipaddress = $val->commenter->ip->body;

		if($parent_srl>0)
		{
			$tobj->parent_srl = $parent_srl;
			$tobj->list_order = $tobj->parent_srl * -1;
		}
		else
		{
			$tobj->list_order = $tobj->textyle_guestbook_srl*-1;
		}

		$output = executeQuery('textyle.insertTextyleGuestbook', $tobj);

		if($output->toBool()) return $tobj->textyle_guestbook_srl;
		return false;
	}

	/**
	 * Attachment
	 * @param resource $fp
	 * @param int $module_srl
	 * @param int $upload_target_srl
	 * @param array $files
	 * @param string $buff
	 * @return bool
	 */
	function importAttaches($fp, $module_srl, $upload_target_srl, &$files, $buff)
	{
		$uploaded_count = 0;

		$file_obj  = null;
		$file_obj->file_srl = getNextSequence();
		$file_obj->upload_target_srl = $upload_target_srl;
		$file_obj->module_srl = $module_srl;

		while(!feof($fp))
		{
			$str = fgets($fp, 1024);
			// If it ends with </attaches>, break
			if(trim($str) == '</attachment>') break;
			// If it starts with <file>, handle the attachement in the xml file
			if(substr($str, 0, 9)=='<content>')
			{
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
		// Set upload path by checking if the attachement is an image or other kind of file
		if(preg_match("/\.(jpg|jpeg|gif|png|wmv|wma|mpg|mpeg|avi|swf|flv|mp1|mp2|mp3|mp4|asf|wav|asx|mid|midi|asf|mov|moov|qt|rm|ram|ra|rmm|m4v)$/i", $file_obj->source_filename))
		{
			$path = sprintf("./files/attach/images/%s/%s", $module_srl,getNumberingPath($upload_target_srl,3));
			$filename = $path.$file_obj->source_filename;
			$file_obj->direct_download = 'Y';
		}
		else
		{
			$path = sprintf("./files/attach/binaries/%s/%s", $module_srl, getNumberingPath($upload_target_srl,3));
			$filename = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
			$file_obj->direct_download = 'N';
		}
		// Create a directory
		if(!FileHandler::makeDir($path)) return;

		FileHandler::rename($file_obj->file, $filename);
		// Insert to the DB
		unset($file_obj->file);
		$file_obj->uploaded_filename = $filename;
		$file_obj->file_size = filesize($filename);
		$file_obj->comment = NULL;
		$file_obj->member_srl = 0;
		$file_obj->sid = md5(rand(rand(1111111,4444444),rand(4444445,9999999)));
		$file_obj->isvalid = 'Y';
		$output = executeQuery('file.insertFile', $file_obj);

		if($output->toBool())
		{
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
	 * Return a filename to temporarily use
	 * @return string
	 */
	function getTmpFilename()
	{
		$path = "./files/cache/importer";
		if(!is_dir($path)) FileHandler::makeDir($path);
		$filename = sprintf("%s/%d", $path, rand(11111111,99999999));
		if(file_exists($filename)) $filename .= rand(111,999);
		return $filename;
	}

	/**
	 * Read buff until key value comes out from a specific file point
	 * @param resource $fp
	 * @param string $buff
	 * @return string
	 */
	function saveTemporaryFile($fp, $buff)
	{
		$temp_filename = $this->getTmpFilename();
		$buff = substr($buff, 9);

		while(!feof($fp))
		{
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
	 * Replace img tag in the ttxml
	 * @param array $matches
	 * @return string
	 */
	function _replaceTTAttach($matches)
	{
		$name = $matches[2];
		if(!$name) return $matches[0];

		$obj = $this->files[$name];
		// If multimedia file is,
		if($obj->direct_download == 'Y')
		{
			// If image file is
			if(preg_match('/\.(jpg|gif|jpeg|png)$/i', $obj->source_filename))
			{
				return sprintf('<img editor_component="image_link" src="%s" alt="%s" />', $obj->url, str_replace('"','\\"',$matches[4]));
				// If other multimedia file but image is, 
			}
			else
			{
				return sprintf('<img src="./common/img/blank.gif" editor_component="multimedia_link" multimedia_src="%s" width="400" height="320" style="display:block;width:400px;height:320px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;" auto_start="false" alt="" />', $obj->url);
			}
			// If binary file is
		}
		else
		{
			return sprintf('<a href="%s">%s</a>', $obj->url, $obj->source_filename);
		}
	}

	/**
	 * Convert the video file
	 * @return string
	 */
	function _replaceTTMovie($matches)
	{
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
	 * Comment
	 * @param object $val
	 * @param int $module_srl
	 * @param int $document_srl
	 * @param object $member_info
	 * @param int $parent_srl
	 * @param int $author_xml_id
	 * @return bool|int|object
	 */
	function insertComment($val, $module_srl, $document_srl, $member_info, $parent_srl = 0, $author_xml_id)
	{
		$tobj = null;
		$tobj->comment_srl = getNextSequence();
		$tobj->module_srl = $module_srl;
		$tobj->document_srl = $document_srl;
		$tobj->is_secret = $val->secret->body=='1'?'Y':'N';
		$tobj->notify_message = 'N';
		$tobj->content = nl2br($val->content->body);
		$tobj->voted_count = 0;
		$tobj->status = 1;
		if($author_xml_id && $val->commenter->attrs->id == $author_xml_id)
		{
			$tobj->password = '';
			$tobj->nick_name = $member_info->nick_name;
			$tobj->user_name = $member_info->user_name;
			$tobj->user_id = $member_info->user_id;
			$tobj->member_srl = $member_info->member_srl;
			$tobj->homepage = $member_info->homepage;
			$tobj->email_address = $member_info->email_address;
		}
		else
		{
			$tobj->password = $val->password->body;
			$tobj->nick_name = $val->commenter->name->body;
			$tobj->homepage = $val->commenter->homepage->body;
			$tobj->member_srl = 0;
		}
		$tobj->last_update = $tobj->regdate = date("YmdHis",$val->written->body);
		$tobj->ipaddress = $val->commenter->ip->body;
		$tobj->list_order = $tobj->comment_srl*-1;
		$tobj->sequence = $sequence;
		$tobj->parent_srl = $parent_srl;
		// Comment list first
		$list_args = new stdClass;
		$list_args->comment_srl = $tobj->comment_srl;
		$list_args->document_srl = $tobj->document_srl;
		$list_args->module_srl = $tobj->module_srl;
		$list_args->regdate = $tobj->regdate;
		// Set data directly if parent comment doesn't exist
		if(!$tobj->parent_srl)
		{
			$list_args->head = $list_args->arrange = $tobj->comment_srl;
			$list_args->depth = 0;
			// Get parent_srl if parent comment exists
		}
		else
		{
			// Get parent_srl
			$parent_args->comment_srl = $tobj->parent_srl;
			$parent_output = executeQuery('comment.getCommentListItem', $parent_args);
			// Return if parent comment doesn't exist
			if(!$parent_output->toBool() || !$parent_output->data) return false;
			$parent = $parent_output->data;

			$list_args->head = $parent->head;
			$list_args->depth = $parent->depth+1;
			if($list_args->depth<2) $list_args->arrange = $tobj->comment_srl;
			else
			{
				$list_args->arrange = $parent->arrange;
				$output = executeQuery('comment.updateCommentListArrange', $list_args);
				if(!$output->toBool()) return $output;
			}
		}

		$output = executeQuery('comment.insertCommentList', $list_args);
		if($output->toBool())
		{
			$output = executeQuery('comment.insertComment', $tobj);
			if($output->toBool()) return $tobj->comment_srl;
		}
		return false;
	}

	/**
	 * List category
	 * @param object $obj
	 * @param array $category
	 * @param int $idx
	 * @param int $parent
	 * @return void
	 */
	function arrangeCategory($obj, &$category, &$idx, $parent = 0)
	{
		if(!$obj->category) return;
		if(!is_array($obj->category)) $c = array($obj->category);
		else $c = $obj->category;
		foreach($c as $val)
		{
			$idx++;
			$priority = $val->priority->body;
			$name = $val->name->body;
			$obj = null;
			$obj->priority = $priority;
			$obj->name = $name;
			$obj->sequence = $idx;
			$obj->parent = $parent;

			$category[$idx] = $obj;

			$this->arrangeCategory($val, $category, $idx, $idx);
		}
	}
}
/* End of file ttimport.class.php */
/* Location: ./modules/importer/ttimport.class.php */
