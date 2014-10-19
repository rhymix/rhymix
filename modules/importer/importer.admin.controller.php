<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
@set_time_limit(0);
require_once('./modules/importer/extract.class.php');

/**
 * importerAdminController class
 * admin controller class of importer module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/importer
 * @version 0.1
 */
class importerAdminController extends importer
{
	/**
	 * Unit count
	 * @var int
	 */
	var $unit_count = 300;
	/**
	 * Xml parser
	 * @var XmlParser
	 */
	var $oXmlParser = null;

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Check whether the passing filename exists or not. Detect the file type, too.
	 * @return void
	 */
	function procImporterAdminCheckXmlFile()
	{
		global $lang;

		$filename = Context::get('filename');
		$isExists = 'false';

		if(strncasecmp('http://', $filename, 7) === 0)
		{
			if(ini_get('allow_url_fopen'))
			{
				$fp = @fopen($filename, "r");
				if($fp)
				{
					$str = fgets($fp, 100);
					if(strlen($str) > 0)
					{
						$isExists = 'true';
						$type = 'XML';
						if(stristr($str, 'tattertools')) $type = 'TTXML';

						$this->add('type', $type);
					}
					fclose($fp);
					$resultMessage = $lang->found_xml_file;
				}
				else $resultMessage = $lang->cannot_url_file;
			}
			else $resultMessage = $lang->cannot_allow_fopen_in_phpini;

			$this->add('exists', $isExists);
		}
		else
		{
			$realPath = FileHandler::getRealPath($filename);

			if(file_exists($realPath) && is_file($realPath)) $isExists = 'true';
			$this->add('exists', $isExists);

			if($isExists == 'true')
			{
				$type = 'XML';

				$fp = fopen($realPath, "r");
				$str = fgets($fp, 100);
				if(stristr($str, 'tattertools')) $type = 'TTXML';
				fclose($fp);

				$this->add('type', $type);
				$resultMessage = $lang->found_xml_file;
			}
			else $resultMessage = $lang->not_found_xml_file;
		}
		$this->add('result_message', $resultMessage);
	}

	/**
	 * Sync member information with document information
	 * @return void
	 */
	function procImporterAdminSync()
	{
		$oMemberModel = getModel('member');
		$member_config = $oMemberModel->getMemberConfig();

		$postFix = ($member_config->identifier == 'email_address') ? 'ByEmail' : '';

		// 계정이 이메일인 경우 이메일 정보로 사용자를 싱크하도록 한다. 이때 변수명은 그대로 user_id를 사용한다.

		/* DBMS가 CUBRID인 경우 MySQL과 동일한 방법으로는 문서 및 댓글에 대한 사용자 정보를 동기화 할 수 없으므로 예외 처리 합니다.
		   CUBRID를 사용하지 않는 경우에만 보편적인 기존 질의문을 사용합니다. */
		$db_info = Context::getDBInfo ();
		if($db_info->db_type != "cubrid")
		{
			$output = executeQuery('importer.updateDocumentSync'.$postFix);
			$output = executeQuery('importer.updateCommentSync'.$postFix);
		}
		else
		{
			$output = executeQueryArray ('importer.getDocumentMemberSrlWithUserID'.$postFix);
			if(is_array ($output->data) && count ($output->data))
			{
				$success_count = 0;
				$error_count = 0;
				$total_count = 0;
				foreach ($output->data as $val)
				{
					$args->user_id = $val->user_id;
					$args->member_srl = $val->member_srl;
					$tmp = executeQuery ('importer.updateDocumentSyncForCUBRID'.$postFix, $args);
					if($tmp->toBool () === true)
					{
						$success_count++;
					}
					else
					{
						$error_count++;
					}
					$total_count++;
				}
			} // documents section

			$output = executeQueryArray ('importer.getCommentMemberSrlWithUserID'.$postFix);
			if(is_array ($output->data) && count ($output->data))
			{
				$success_count = 0;
				$error_count = 0;
				$total_count = 0;
				foreach ($output->data as $val)
				{
					$args->user_id = $val->user_id;
					$args->member_srl = $val->member_srl;
					$tmp = executeQuery ('importer.updateCommentSyncForCUBRID'.$postFix, $args);
					if($tmp->toBool () === true)
					{
						$success_count++;
					}
					else
					{
						$error_count++;
					}
					$total_count++;
				}
			} // comments section
		}

		$this->setMessage('msg_sync_completed');
	}

	/**
	 * Pre-analyze the xml file and cache it
	 * @return void
	 */
	function procImporterAdminPreProcessing()
	{
		// Get the target xml file to import
		$xml_file = Context::get('xml_file');
		// Get a type of the target
		$type = Context::get('type');
		// Extract and cache information from the xml file
		$oExtract = new extract();

		switch($type)
		{
			case 'member' :
				$output = $oExtract->set($xml_file,'<members ', '</members>', '<member>', '</member>');
				if($output->toBool()) $oExtract->saveItems();
				break;
			case 'message' :
				$output = $oExtract->set($xml_file,'<messages ', '</messages>', '<message>','</message>');
				if($output->toBool()) $oExtract->saveItems();
				break;
			case 'ttxml' :
				// Category information
				$output = $oExtract->set($xml_file, '', '', '', '');
				if ($output->toBool())
				{
					// Get a category of ttxml separately
					$started = false;
					$buff = '';
					while (!feof($oExtract->fd))
					{
						$str = fgets($oExtract->fd, 1024);
						if(strstr($str, '<category>'))
						{
							$started = true;
							$str = strstr($str, '<category>');
						}
						if(substr($str,0,strlen('<post ')) == '<post ') break;
						if ($started) $buff .= $str;
					}
					$buff = '<categories>'.$buff.'</categories>';
					$oExtract->closeFile();
					$category_filename = sprintf('%s/%s', $oExtract->cache_path, 'category.xml');
					FileHandler::writeFile($category_filename, $buff);

					// Guestbook information
					$output = $oExtract->set($xml_file, '', '', '', '');
					if($output->toBool())
					{
						$started = false;
						$buff = '';
						while (!feof($oExtract->fd))
						{
							$str = fgets($oExtract->fd, 1024);
							if(strstr($str, '<guestbook>'))
							{
								$started = true;
								$str = strstr($str, '<guestbook>');
							}
							if($started)
							{
								$pos = strpos($str, '</guestbook>');
								if($pos !== false)
								{
									$buff .= substr($str, 0, $pos + strlen('</guestbook>'));
									break;
								}
								$buff .= $str;
							}
						}
						$oExtract->closeFile();
						$guestbook_filename = sprintf('%s/%s', $oExtract->cache_path, 'guestbook.xml');
						FileHandler::writeFile($guestbook_filename, $buff);
						// Individual items
						$output = $oExtract->set($xml_file,'<blog', '</blog>', '<post ', '</post>');
						if($output->toBool()) $oExtract->saveItems();
					}
				}
				break;
			default :
				// First get category information
				$output = $oExtract->set($xml_file,'<categories>', '</categories>', '<category','</category>');
				if($output->toBool())
				{
					$oExtract->mergeItems('category.xml');
					// Get each item
					$output = $oExtract->set($xml_file,'<posts ', '</posts>', '<post>', '</post>');
					if($output->toBool()) $oExtract->saveItems();
				}
				break;
		}

		if(!$output->toBool())
		{
			$this->add('error',0);
			$this->add('status',-1);
			$this->setMessage($output->getMessage());
			return;
		}
		// Notify that all data completely extracted
		$this->add('type',$type);
		$this->add('total',$oExtract->getTotalCount());
		$this->add('cur',0);
		$this->add('key', $oExtract->getKey());
		$this->add('status',0);
	}

	/**
	 * Migrate data after completing xml file extraction
	 * @return void
	 */
	function procImporterAdminImport()
	{
		// Variable setting
		$type = Context::get('type');
		$total = Context::get('total');
		$cur = Context::get('cur');
		$key = Context::get('key');
		$user_id = Context::get('user_id');
		$target_module = Context::get('target_module');
		$guestbook_target_module = Context::get('guestbook_target_module');
		$this->unit_count = Context::get('unit_count');
		// Check if an index file exists
		$index_file = './files/cache/importer/'.$key.'/index';
		if(!file_exists($index_file)) return new Object(-1, 'msg_invalid_xml_file');

		switch($type)
		{
			case 'ttxml' :
				if(!$target_module) return new Object(-1,'msg_invalid_request');

				$oModuleModel = getModel('module');
				$columnList = array('module_srl', 'module');
				$target_module_info = $oModuleModel->getModuleInfoByModuleSrl($target_module, $columnList);

				$ttimporter = FileHandler::exists(_XE_PATH_ . 'modules/importer/ttimport.class.php');
				if($ttimporter) require_once($ttimporter);

				$oTT = new ttimport();
				$cur = $oTT->importModule($key, $cur, $index_file, $this->unit_count, $target_module, $guestbook_target_module, $user_id, $target_module_info->module);
				break;
			case 'message' :
				$cur = $this->importMessage($key, $cur, $index_file);
				break;
			case 'member' :
				$cur = $this->importMember($key, $cur, $index_file);
				break;
			case 'module' :
				// Check if the target module exists
				if(!$target_module) return new Object(-1,'msg_invalid_request');
				$cur = $this->importModule($key, $cur, $index_file, $target_module);
				break;
		}
		// Notify that all data completely extracted
		$this->add('type',$type);
		$this->add('total',$total);
		$this->add('cur',$cur);
		$this->add('key', $key);
		$this->add('target_module', $target_module);
		// When completing, success message appears and remove the cache files
		if($total <= $cur)
		{
			$this->setMessage( sprintf(Context::getLang('msg_import_finished'), $cur, $total) );
			FileHandler::removeDir('./files/cache/importer/'.$key);
		}
		else $this->setMessage( sprintf(Context::getLang('msg_importing'), $total, $cur) );
	}

	/**
	 * Import member information
	 * @param int $key
	 * @param int $cur
	 * @param string $index_file
	 * @return int
	 */
	function importMember($key, $cur, $index_file)
	{
		if(!$cur) $cur = 0;
		// Create the xmlParser object
		$oXmlParser = new XmlParser();
		// Create objects for importing member information
		$this->oMemberController = getController('member');
		$this->oMemberModel = getModel('member');
		// Get a default member group
		$default_group = $this->oMemberModel->getDefaultGroup();
		$default_group_srl = $default_group->group_srl;
		// Get information of the Webmaster
		$oModuleModel = getModel('module');
		$member_config = $oModuleModel->getModuleConfig('member');
		// Open an index file
		$f = fopen($index_file,"r");
		// Pass if already read
		for($i=0;$i<$cur;$i++) fgets($f, 1024);
		// Read by each line until the condition meets
		for($idx=$cur;$idx<$cur+$this->unit_count;$idx++)
		{
			if(feof($f)) break;
			// Find a given location
			$target_file = trim(fgets($f, 1024));
			// Load and parse the file
			$xmlObj = $oXmlParser->loadXmlFile($target_file);
			FileHandler::removeFile($target_file);
			if(!$xmlObj) continue;
			// List Objects
			$obj = null;
			$obj->user_id = base64_decode($xmlObj->member->user_id->body);
			$obj->password = base64_decode($xmlObj->member->password->body);
			$obj->user_name = base64_decode($xmlObj->member->user_name->body);
			$obj->nick_name = base64_decode($xmlObj->member->nick_name->body);
			if(!$obj->user_name) $obj->user_name = $obj->nick_name;
			$obj->email = base64_decode($xmlObj->member->email->body);
			$obj->homepage = base64_decode($xmlObj->member->homepage->body);
			$obj->blog = base64_decode($xmlObj->member->blog->body);
			$obj->birthday = substr(base64_decode($xmlObj->member->birthday->body),0,8);
			$obj->allow_mailing = base64_decode($xmlObj->member->allow_mailing->body);
			$obj->point = base64_decode($xmlObj->member->point->body);
			$obj->image_nickname = base64_decode($xmlObj->member->image_nickname->buff->body);
			$obj->image_mark = base64_decode($xmlObj->member->image_mark->buff->body);
			$obj->profile_image = base64_decode($xmlObj->member->profile_image->buff->body);
			$obj->signature = base64_decode($xmlObj->member->signature->body);
			$obj->regdate = base64_decode($xmlObj->member->regdate->body);
			$obj->last_login = base64_decode($xmlObj->member->last_login->body);

			if($xmlObj->member->extra_vars)
			{
				foreach($xmlObj->member->extra_vars as $key => $val)
				{
					if(in_array($key, array('node_name','attrs','body'))) continue;
					$obj->extra_vars->{$key} = base64_decode($val->body);
				}
			}
			// Create url for homepage and blog
			if($obj->homepage && strncasecmp('http://', $obj->homepage, 7) !== 0 && strncasecmp('https://', $obj->homepage, 8) !== 0) $obj->homepage = 'http://'.$obj->homepage;
			// email address column
			$obj->email_address = $obj->email;
			list($obj->email_id, $obj->email_host) = explode('@', $obj->email);
			// Set the mailing option
			if($obj->allow_mailing!='Y') $obj->allow_mailing = 'N';
			// Set the message option
			$obj->allow_message = 'Y';
			if(!in_array($obj->allow_message, array('Y','N','F'))) $obj->allow_message= 'Y';
			// Get member-join date if the last login time is not found
			if(!$obj->last_login) $obj->last_login = $obj->regdate;
			// Get a member_srl
			$obj->member_srl = getNextSequence();
			$obj->list_order = -1 * $obj->member_srl;
			// List extra vars
			$extra_vars = $obj->extra_vars;
			unset($obj->extra_vars);
			$obj->extra_vars = serialize($extra_vars);
			// Check if the same nickname is existing
			$nick_args = new stdClass;
			$nick_args->nick_name = $obj->nick_name;
			$nick_output = executeQuery('member.getMemberSrl', $nick_args);
			if(!$nick_output->toBool()) $obj->nick_name .= '_'.$obj->member_srl;
			// Add a member
			$output = executeQuery('member.insertMember', $obj);

			if($output->toBool() && !($obj->password))
			{
				// Send a mail telling the user to reset his password.
				$oMail = new Mail();
				$oMail->setTitle("Password update for your " . getFullSiteUrl() . " account");
				$webmaster_name = $member_config->webmaster_name?$member_config->webmaster_name:'Webmaster';
				$oMail->setContent("Dear $obj->user_name, <br /><br />
						We recently migrated our phpBB forum to XpressEngine. Since you password was encrypted we could not migrate it too, so please reset it by following this link:
						<a href='" . getFullSiteUrl() . "/?act=dispMemberFindAccount' >" . getFullSiteUrl() . "?act=dispMemberFindAccount</a>. You need to enter you email address and hit the 'Find account' button. You will then receive an email with a new, generated password that you can change after login. <br /><br />

						Thank you for your understanding,<br />
						{$webmaster_name}"
				);
				$oMail->setSender($webmaster_name, $member_config->webmaster_email);
				$oMail->setReceiptor( $obj->user_name, $obj->email);
				$oMail->send();
			}

			// add group join/image name-mark-signiture and so on if a new member successfully added
			if($output->toBool())
			{
				// Join to the default group
				$obj->group_srl = $default_group_srl;
				executeQuery('member.addMemberToGroup',$obj);
				// Image name
				if($obj->image_nickname)
				{
					$target_path = sprintf('files/member_extra_info/image_name/%s/', getNumberingPath($obj->member_srl));
					$target_filename = sprintf('%s%d.gif', $target_path, $obj->member_srl);
					FileHandler::writeFile($target_filename, $obj->image_nickname);
				}
				// Image mark
				if($obj->image_mark && file_exists($obj->image_mark))
				{
					$target_path = sprintf('files/member_extra_info/image_mark/%s/', getNumberingPath($obj->member_srl));
					$target_filename = sprintf('%s%d.gif', $target_path, $obj->member_srl);
					FileHandler::writeFile($target_filename, $obj->image_mark);
				}
				// Profile image
				if($obj->profile_image)
				{
					$target_path = sprintf('files/member_extra_info/profile_image/%s/', getNumberingPath($obj->member_srl));
					$target_filename = sprintf('%s%d.gif', $target_path, $obj->member_srl);
					FileHandler::writeFile($target_filename, $obj->profile_image);
				}
				// Signiture
				if($obj->signature)
				{
					$signature = removeHackTag($obj->signature);
					$signature_buff = sprintf('<?php if(!defined("__XE__")) exit();?>%s', $signature);

					$target_path = sprintf('files/member_extra_info/signature/%s/', getNumberingPath($obj->member_srl));
					if(!is_dir($target_path)) FileHandler::makeDir($target_path);
					$target_filename = sprintf('%s%d.signature.php', $target_path, $obj->member_srl);

					FileHandler::writeFile($target_filename, $signature_buff);
				}
			}
		}

		fclose($f);

		return $idx-1;
	}

	/**
	 * Import message information parsed from a given xml file
	 * @param int $key
	 * @param int $cur
	 * @param string $index_file
	 * @return int
	 */
	function importMessage($key, $cur, $index_file)
	{
		if(!$cur) $cur = 0;
		// Create the xmlParser object
		$oXmlParser = new XmlParser();
		// Open an index file
		$f = fopen($index_file,"r");
		// Pass if already read
		for($i=0;$i<$cur;$i++) fgets($f, 1024);
		// Read each line until the condition meets
		for($idx=$cur;$idx<$cur+$this->unit_count;$idx++)
		{
			if(feof($f)) break;
			// Find a location
			$target_file = trim(fgets($f, 1024));
			// Load and parse the file
			$xmlObj = $oXmlParser->loadXmlFile($target_file);
			FileHandler::removeFile($target_file);
			if(!$xmlObj) continue;
			// List objects
			$obj = null;
			$obj->receiver = base64_decode($xmlObj->message->receiver->body);
			$obj->sender = base64_decode($xmlObj->message->sender->body);
			$obj->title = base64_decode($xmlObj->message->title->body);
			$obj->content = base64_decode($xmlObj->message->content->body);
			$obj->readed = base64_decode($xmlObj->message->readed->body)=='Y'?'Y':'N';
			$obj->regdate = base64_decode($xmlObj->message->regdate->body);
			$obj->readed_date = base64_decode($xmlObj->message->readed_date->body);
			// Get member_srl of sender/recipient (If not exists, pass)
			if(!$obj->sender) continue;
			$sender_args->user_id = $obj->sender;
			$sender_output = executeQuery('member.getMemberInfo',$sender_args);
			$sender_srl = $sender_output->data->member_srl;
			if(!$sender_srl)
			{
				unset($sender_args);
				$sender_args->email_address = $obj->sender;
				$sender_output = executeQuery('member.getMemberInfoByEmailAddress',$sender_args);
				$sender_srl = $sender_output->data->member_srl;
			}
			if(!$sender_srl) continue;

			$receiver_args->user_id = $obj->receiver;
			if(!$obj->receiver) continue;
			$receiver_output = executeQuery('member.getMemberInfo',$receiver_args);
			$receiver_srl = $receiver_output->data->member_srl;
			if(!$receiver_srl)
			{
				unset($receiver_args);
				$receiver_args->email_address = $obj->receiver;
				$receiver_output = executeQuery('member.getMemberInfoByEmailAddress',$receiver_args);
				$receiver_srl = $receiver_output->data->member_srl;
			}
			if(!$receiver_srl) continue;
			// Message to save into sender's message box
			$sender_args->sender_srl = $sender_srl;
			$sender_args->receiver_srl = $receiver_srl;
			$sender_args->message_type = 'S';
			$sender_args->title = $obj->title;
			$sender_args->content = $obj->content;
			$sender_args->readed = $obj->readed;
			$sender_args->regdate = $obj->regdate;
			$sender_args->readed_date = $obj->readed_date;
			$sender_args->related_srl = getNextSequence();
			$sender_args->message_srl = getNextSequence();
			$sender_args->list_order = $sender_args->message_srl * -1;

			$output = executeQuery('communication.sendMessage', $sender_args);
			if($output->toBool())
			{
				// Message to save into recipient's massage box
				$receiver_args->message_srl = $sender_args->related_srl;
				$receiver_args->list_order = $sender_args->related_srl*-1;
				$receiver_args->sender_srl = $sender_srl;
				if(!$receiver_args->sender_srl) $receiver_args->sender_srl = $receiver_srl;
				$receiver_args->receiver_srl = $receiver_srl;
				$receiver_args->message_type = 'R';
				$receiver_args->title = $obj->title;
				$receiver_args->content = $obj->content;
				$receiver_args->readed = $obj->readed;
				$receiver_args->regdate = $obj->regdate;
				$receiver_args->readed_date = $obj->readed_date;
				$output = executeQuery('communication.sendMessage', $receiver_args);
			}
		}

		fclose($f);

		return $idx-1;
	}

	/**
	 * Import data in module.xml format
	 * @param int $key
	 * @param int $cur
	 * @param string $index_file
	 * @param int $module_srl
	 * @return int
	 */
	function importModule($key, $cur, $index_file, $module_srl)
	{
		// Pre-create the objects needed
		$this->oXmlParser = new XmlParser();
		// Get category information of the target module
		$oDocumentController = getController('document');
		$oDocumentModel = getModel('document');
		$category_list = $category_titles = array();
		$category_list = $oDocumentModel->getCategoryList($module_srl);
		if(count($category_list)) foreach($category_list as $key => $val) $category_titles[$val->title] = $val->category_srl;
		// Extract category information
		$category_file = preg_replace('/index$/i', 'category.xml', $index_file);
		if(file_exists($category_file))
		{
			$buff = FileHandler::readFile($category_file);

			// Create the xmlParser object
			$xmlDoc = $this->oXmlParser->loadXmlFile($category_file);

			$categories = $xmlDoc->items->category;
			if($categories)
			{
				if(!is_array($categories)) $categories = array($categories);
				$match_sequence = array();
				foreach($categories as $k => $v)
				{
					$category = trim(base64_decode($v->body));
					if(!$category || $category_titles[$category]) continue;

					$sequence = $v->attrs->sequence;
					$parent = $v->attrs->parent;

					$obj = null;
					$obj->title = $category;
					$obj->module_srl = $module_srl;
					if($parent) $obj->parent_srl = $match_sequence[$parent];

					$output = $oDocumentController->insertCategory($obj);
					if($output->toBool()) $match_sequence[$sequence] = $output->get('category_srl');
				}
				$oDocumentController = getController('document');
				$oDocumentController->makeCategoryFile($module_srl);
			}
			FileHandler::removeFile($category_file);
		}

		$category_list = $category_titles = array();
		$category_list = $oDocumentModel->getCategoryList($module_srl);
		if(count($category_list)) foreach($category_list as $key => $val) $category_titles[$val->title] = $val->category_srl;

		$ek_args->module_srl = $module_srl;
		$output = executeQueryArray('document.getDocumentExtraKeys', $ek_args);
		if($output->data)
		{
			foreach($output->data as $key => $val) $extra_keys[$val->eid] = true;
		}

		if(!$cur) $cur = 0;
		// Open an index file
		$f = fopen($index_file,"r");
		// Pass if already read
		for($i=0;$i<$cur;$i++) fgets($f, 1024);
		// Read each line until the condition meets
		for($idx=$cur;$idx<$cur+$this->unit_count;$idx++)
		{
			if(feof($f)) break;
			// Find a location
			$target_file = trim(fgets($f, 1024));

			if(!file_exists($target_file)) continue;
			// Importing data from now on
			$fp = fopen($target_file,"r");
			if(!$fp) continue;

			$obj = new stdClass;
			$obj->module_srl = $module_srl;
			$obj->document_srl = getNextSequence();

			$files = array();
			$extra_vars = array();

			$started = false;
			$buff = array();
			// Start from the body data
			while(!feof($fp))
			{
				$str = fgets($fp, 1024);
				// Prepare an item
				if(trim($str) == '<post>')
				{
					$started = true;
					// Trackback inserted
				}
				else if(substr($str,0,11) == '<trackbacks')
				{
					$obj->trackback_count = $this->importTrackbacks($fp, $module_srl, $obj->document_srl);
					continue;
					// Comments inserted
				}
				else if(substr($str,0,9) == '<comments')
				{
					$obj->comment_count = $this->importComments($fp, $module_srl, $obj->document_srl);
					continue;
					// Attachment inserted
				}
				else if(substr($str,0,9) == '<attaches')
				{
					$obj->uploaded_count = $this->importAttaches($fp, $module_srl, $obj->document_srl, $files);
					continue;
					// When starting extra variabls
				}
				elseif(trim($str) == '<extra_vars>')
				{
					$extra_vars = $this->importExtraVars($fp);
					continue;
				}

				if($started) $buff[] = $str;
			}

			$xmlDoc = $this->oXmlParser->parse(implode('', $buff));

			$category = base64_decode($xmlDoc->post->category->body);
			if($category_titles[$category]) $obj->category_srl = $category_titles[$category];

			$obj->member_srl = 0;

			$obj->is_notice = base64_decode($xmlDoc->post->is_notice->body)=='Y'?'Y':'N';
			$obj->status = base64_decode($xmlDoc->post->is_secret->body)=='Y'?$oDocumentModel->getConfigStatus('secret'):$oDocumentModel->getConfigStatus('public');
			$obj->title = base64_decode($xmlDoc->post->title->body);
			$obj->content = base64_decode($xmlDoc->post->content->body);
			$obj->readed_count = base64_decode($xmlDoc->post->readed_count->body);
			$obj->voted_count = base64_decode($xmlDoc->post->voted_count->body);
			$obj->blamed_count = base64_decode($xmlDoc->post->blamed_count->body);
			$obj->password = base64_decode($xmlDoc->post->password->body);
			$obj->user_name = base64_decode($xmlDoc->post->user_name->body);
			$obj->nick_name = base64_decode($xmlDoc->post->nick_name->body);
			if(!$obj->user_name) $obj->user_name = $obj->nick_name;
			$obj->user_id = base64_decode($xmlDoc->post->user_id->body);
			$obj->email_address = base64_decode($xmlDoc->post->email->body);
			$obj->homepage = base64_decode($xmlDoc->post->homepage->body);
			if($obj->homepage && strncasecmp('http://', $obj->homepage, 7) !== 0 && strncasecmp('https://', $obj->homepage, 8) !== 0) $obj->homepage = 'http://'.$obj->homepage;
			$obj->tags = base64_decode($xmlDoc->post->tags->body);
			$obj->regdate = base64_decode($xmlDoc->post->regdate->body);
			$obj->last_update = base64_decode($xmlDoc->post->update->body);
			$obj->last_updater = base64_decode($xmlDoc->post->last_updater->body);
			if(!$obj->last_update) $obj->last_update = $obj->regdate;
			$obj->ipaddress = base64_decode($xmlDoc->post->ipaddress->body);
			$obj->list_order = $obj->update_order = $obj->document_srl*-1;
			$obj->commentStatus = base64_decode($xmlDoc->post->allow_comment->body)!='N'?'ALLOW':'DENY';
			$obj->allow_trackback = base64_decode($xmlDoc->post->allow_trackback->body)!='N'?'Y':'N';
			$obj->notify_message = base64_decode($xmlDoc->post->is_notice->body);
			// Change content information (attachment)
			if(count($files))
			{
				foreach($files as $key => $val)
				{
					$obj->content = preg_replace('/(src|href)\=(["\']?)'.preg_quote($key).'(["\']?)/i','$1="'.$val.'"',$obj->content);
					$obj->content = preg_replace('/(["\']?).\/files\/(.+)\/'.preg_quote($key).'([^"\']+)(["\']?)/i','"'.$val.'"',$obj->content);
					$obj->content = preg_replace('/(["\']?)files\/(.+)\/'.preg_quote($key).'([^"\']+)(["\']?)/i','"'.$val.'"',$obj->content);
				}
			}

			$output = executeQuery('document.insertDocument', $obj);

			if($output->toBool() && $obj->tags)
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
			// Add extra variables
			if(count($extra_vars))
			{
				foreach($extra_vars as $key => $val)
				{
					if(!$val->value) continue;
					unset($e_args);
					$e_args->module_srl = $module_srl;
					$e_args->document_srl = $obj->document_srl;
					$e_args->var_idx = $val->var_idx;
					$e_args->value = $val->value;
					$e_args->lang_code = $val->lang_code;
					$e_args->eid = $val->eid;
					// Create a key for extra vars if not exists (except vars for title and content)
					if(!preg_match('/^(title|content)_(.+)$/i',$e_args->eid) && !$extra_keys[$e_args->eid])
					{
						unset($ek_args);
						$ek_args->module_srl = $module_srl;
						$ek_args->var_idx = $val->var_idx;
						$ek_args->var_name = $val->eid;
						$ek_args->var_type = 'text';
						$ek_args->var_is_required = 'N';
						$ek_args->var_default = '';
						$ek_args->eid = $val->eid;
						$output = executeQuery('document.insertDocumentExtraKey', $ek_args);
						$extra_keys[$ek_args->eid] = true;
					}

					$output = executeQuery('document.insertDocumentExtraVar', $e_args);
				}
			}

			fclose($fp);
			FileHandler::removeFile($target_file);
		}

		fclose($f);
		// Sync category counts
		if(count($category_list)) foreach($category_list as $key => $val) $oDocumentController->updateCategoryCount($module_srl, $val->category_srl);

		return $idx-1;
	}

	/**
	 * Trackbacks
	 * @param resource $fp
	 * @param int $module_srl
	 * @param int $document_srl
	 * @return int
	 */
	function importTrackbacks($fp, $module_srl, $document_srl)
	{
		$started = false;
		$buff = null;
		$cnt = 0;

		while(!feof($fp))
		{
			$str = fgets($fp, 1024);
			// If </trackbacks> is, break
			if(trim($str) == '</trackbacks>') break;
			// If <trackback>, start importing
			if(trim($str) == '<trackback>') $started = true;

			if($started) $buff .= $str;
			// If </trackback>, insert to the DB
			if(trim($str) == '</trackback>')
			{
				$xmlDoc = $this->oXmlParser->parse($buff);

				$obj = new stdClass;
				$obj->trackback_srl = getNextSequence();
				$obj->module_srl = $module_srl;
				$obj->document_srl = $document_srl;
				$obj->url = base64_decode($xmlDoc->trackback->url->body);
				$obj->title = base64_decode($xmlDoc->trackback->title->body);
				$obj->blog_name = base64_decode($xmlDoc->trackback->blog_name->body);
				$obj->excerpt = base64_decode($xmlDoc->trackback->excerpt->body);
				$obj->regdate = base64_decode($xmlDoc->trackback->regdate->body);
				$obj->ipaddress = base64_decode($xmlDoc->trackback->ipaddress->body);
				$obj->list_order = -1*$obj->trackback_srl;
				$output = executeQuery('trackback.insertTrackback', $obj);
				if($output->toBool()) $cnt++;

				$buff = null;
				$started = false;
			}
		}
		return $cnt;
	}

	/**
	 * Comments
	 * @param resource $fp
	 * @param int $module_srl
	 * @param int $document_srl
	 * @return int
	 */
	function importComments($fp, $module_srl, $document_srl)
	{
		$started = false;
		$buff = null;
		$cnt = 0;

		$sequences = array();

		while(!feof($fp))
		{
			$str = fgets($fp, 1024);
			// If </comments> is, break
			if(trim($str) == '</comments>') break;
			// If <comment> is, start importing
			if(trim($str) == '<comment>')
			{
				$started = true;
				$obj = new stdClass;
				$obj->comment_srl = getNextSequence();
				$files = array();
			}
			// If <attaches is, start importing attachments
			if(substr($str,0,9) == '<attaches')
			{
				$obj->uploaded_count = $this->importAttaches($fp, $module_srl, $obj->comment_srl, $files);
				continue;
			}

			if($started) $buff .= $str;
			// If </comment> is, insert to the DB
			if(trim($str) == '</comment>')
			{
				$xmlDoc = $this->oXmlParser->parse($buff);

				$sequence = base64_decode($xmlDoc->comment->sequence->body);
				$sequences[$sequence] = $obj->comment_srl;
				$parent = base64_decode($xmlDoc->comment->parent->body);

				$obj->module_srl = $module_srl;

				if($parent) $obj->parent_srl = $sequences[$parent];
				else $obj->parent_srl = 0;

				$obj->document_srl = $document_srl;
				$obj->is_secret = base64_decode($xmlDoc->comment->is_secret->body)=='Y'?'Y':'N';
				$obj->notify_message = base64_decode($xmlDoc->comment->notify_message->body)=='Y'?'Y':'N';
				$obj->content = base64_decode($xmlDoc->comment->content->body);
				$obj->voted_count = base64_decode($xmlDoc->comment->voted_count->body);
				$obj->blamed_count = base64_decode($xmlDoc->comment->blamed_count->body);
				$obj->password = base64_decode($xmlDoc->comment->password->body);
				$obj->user_name =base64_decode($xmlDoc->comment->user_name->body);
				$obj->nick_name = base64_decode($xmlDoc->comment->nick_name->body);
				if(!$obj->user_name) $obj->user_name = $obj->nick_name;
				$obj->user_id = base64_decode($xmlDoc->comment->user_id->body);
				$obj->member_srl = 0;
				$obj->email_address = base64_decode($xmlDoc->comment->email->body);
				$obj->homepage = base64_decode($xmlDoc->comment->homepage->body);
				$obj->regdate = base64_decode($xmlDoc->comment->regdate->body);
				$obj->last_update = base64_decode($xmlDoc->comment->update->body);
				if(!$obj->last_update) $obj->last_update = $obj->regdate;
				$obj->ipaddress = base64_decode($xmlDoc->comment->ipaddress->body);
				$obj->status = base64_decode($xmlDoc->comment->status->body)==''?'1':base64_decode($xmlDoc->comment->status->body);
				$obj->list_order = $obj->comment_srl*-1;
				// Change content information (attachment)
				if(count($files))
				{
					foreach($files as $key => $val)
					{
						$obj->content = preg_replace('/(src|href)\=(["\']?)'.preg_quote($key).'(["\']?)/i','$1="'.$val.'"',$obj->content);
					}
				}
				// Comment list first
				$list_args = new stdClass;
				$list_args->comment_srl = $obj->comment_srl;
				$list_args->document_srl = $obj->document_srl;
				$list_args->module_srl = $obj->module_srl;
				$list_args->regdate = $obj->regdate;
				// Set data directly if parent comment doesn't exist
				if(!$obj->parent_srl)
				{
					$list_args->head = $list_args->arrange = $obj->comment_srl;
					$list_args->depth = 0;
					// Get parent_srl if parent comment exists
				}
				else
				{
					// Get parent comment infomation
					$parent_args->comment_srl = $obj->parent_srl;
					$parent_output = executeQuery('comment.getCommentListItem', $parent_args);
					// Return if parent comment doesn't exist
					if(!$parent_output->toBool() || !$parent_output->data) continue;
					$parent = $parent_output->data;

					$list_args->head = $parent->head;
					$list_args->depth = $parent->depth+1;
					if($list_args->depth<2) $list_args->arrange = $obj->comment_srl;
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
					$output = executeQuery('comment.insertComment', $obj);
					if($output->toBool()) $cnt++;
				}

				$buff = null;
				$started = false;
			}
		}
		return $cnt;
	}

	/**
	 * Import attachment
	 * @param resource $fp
	 * @param int $module_srl
	 * @param int $upload_target_srl
	 * @param array $files
	 * @return int
	 */
	function importAttaches($fp, $module_srl, $upload_target_srl, &$files)
	{
		$uploaded_count = 0;

		$started = false;
		$buff = null;

		$file_obj = new stdClass;
		while(!feof($fp))
		{
			$str = trim(fgets($fp, 1024));
			// If it ends with </attaches>, break
			if(trim($str) == '</attaches>') break;
			// If it starts with <attach>, collect attachments
			if(trim($str) == '<attach>')
			{
				$file_obj->file_srl = getNextSequence();
				$file_obj->upload_target_srl = $upload_target_srl;
				$file_obj->module_srl = $module_srl;

				$started = true;
				$buff = null;
				// If it starts with <file>, handle the attachement in xml file
			}
			else if(trim($str) == '<file>')
			{
				$file_obj->file = $this->saveTemporaryFile($fp);
				continue;
			}

			if($started) $buff .= $str;
			// If it ends with </attach>, handle attachements
			if(trim($str) == '</attach>')
			{
				$xmlDoc = $this->oXmlParser->parse($buff.$str);

				$file_obj->source_filename = base64_decode($xmlDoc->attach->filename->body);
				$file_obj->download_count = base64_decode($xmlDoc->attach->download_count->body);

				if(!$file_obj->file)
				{
					$url = base64_decode($xmlDoc->attach->url->body);
					$path = base64_decode($xmlDoc->attach->path->body);
					if($path && file_exists($path)) $file_obj->file = $path;
					else
					{
						$file_obj->file = $this->getTmpFilename();
						FileHandler::getRemoteFile($url, $file_obj->file);
					}
				}

				if(file_exists($file_obj->file))
				{
					// Set upload path by checking if the attachement is an image or other kind of file
					if(preg_match("/\.(jpe?g|gif|png|wm[va]|mpe?g|avi|swf|flv|mp[1-4]|as[fx]|wav|midi?|moo?v|qt|r[am]{1,2}|m4v)$/i", $file_obj->source_filename))
					{
						// Immediately remove the direct file if it has any kind of extensions for hacking
						$file_obj->source_filename = preg_replace('/\.(php|phtm|phar|html?|cgi|pl|exe|jsp|asp|inc)/i', '$0-x', $file_obj->source_filename);
						$file_obj->source_filename = str_replace(array('<', '>'), array('%3C', '%3E'), $file_obj->source_filename);

						$path = sprintf("./files/attach/images/%s/%s", $module_srl, getNumberingPath($upload_target_srl, 3));

						$ext = substr(strrchr($file_obj->source_filename,'.'),1);
						$_filename = md5(crypt(rand(1000000, 900000), rand(0, 100))).'.'.$ext;
						$filename  = $path.$_filename;

						$idx = 1;
						while(file_exists($filename))
						{
							$filename = $path.preg_replace('/\.([a-z0-9]+)$/i','_'.$idx.'.$1', $_filename);
							$idx++;
						}

						$file_obj->direct_download = 'Y';
					}
					else
					{
						$path = sprintf("./files/attach/binaries/%s/%s", $module_srl, getNumberingPath($upload_target_srl,3));
						$filename = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
						$file_obj->direct_download = 'N';
					}
					// Create a directory
					if(!FileHandler::makeDir($path)) continue;

					if(strncmp('./files/cache/importer/', $file_obj->file, 23) === 0)
					{
						FileHandler::rename($file_obj->file, $filename);
					}
					else
					{
						copy($file_obj->file, $filename);
					}

					// Insert the file to the DB
					unset($file_obj->file);
					if(file_exists($filename))
					{
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
							$tmp_obj->source_filename = $file_obj->source_filename;
							if($file_obj->direct_download == 'Y') $files[$file_obj->source_filename] = $file_obj->uploaded_filename;
							else $files[$file_obj->source_filename] = getUrl('','module','file','act','procFileDownload','file_srl',$file_obj->file_srl,'sid',$file_obj->sid);
						}
					}
				}
			}
		}
		return $uploaded_count;
	}

	/**
	 * Return a filename to temporarily use
	 * @return string
	 */
	function getTmpFilename()
	{
		$path = "./files/cache/importer";
		FileHandler::makeDir($path);
		$filename = sprintf("%s/%d", $path, rand(11111111,99999999));
		if(file_exists($filename)) $filename .= rand(111,999);
		return $filename;
	}

	/**
	 * Read buff until key value comes out from a specific file point
	 * @param resource $fp
	 * @return string
	 */
	function saveTemporaryFile($fp)
	{
		$temp_filename = $this->getTmpFilename();
		$f = fopen($temp_filename, "w");

		$buff = '';
		while(!feof($fp))
		{
			$str = trim(fgets($fp, 1024));
			if(trim($str) == '</file>') break;

			$buff .= $str;

			if(substr($buff,-7)=='</buff>')
			{
				fwrite($f, base64_decode(substr($buff, 6, -7)));
				$buff = '';
			}
		}
		fclose($f);
		return $temp_filename;
	}


	/**
	 * Set extra variables
	 * @param resource $fp
	 * @return array
	 */
	function importExtraVars($fp)
	{
		$buff = null;
		while(!feof($fp))
		{
			$buff .= $str = trim(fgets($fp, 1024));
			if(trim($str) == '</extra_vars>') break;
		}
		if(!$buff) return array();

		$buff = '<extra_vars>'.$buff;
		$oXmlParser = new XmlParser();
		$xmlDoc = $this->oXmlParser->parse($buff);
		if(!count($xmlDoc->extra_vars->key)) return array();

		$index = 1;
		foreach($xmlDoc->extra_vars->key as $k => $v)
		{
			unset($vobj);
			if($v->var_idx)
			{
				$vobj->var_idx = base64_decode($v->var_idx->body);
				$vobj->lang_code = base64_decode($v->lang_code->body);
				$vobj->value = base64_decode($v->value->body);
				$vobj->eid = base64_decode($v->eid->body);

			}
			else if($v->body)
			{
				$vobj->var_idx = $index;
				$vobj->lang_code = Context::getLangType();
				$vobj->value = base64_decode($v->body);
				$vobj->eid = 'extra_vars'.$index;
			}
			$extra_vars["extra_vars".$index] = $vobj;
			$index++;
		}
		return $extra_vars;
	}
}
/* End of file importer.admin.controller.php */
/* Location: ./modules/importer/importer.admin.controller.php */
