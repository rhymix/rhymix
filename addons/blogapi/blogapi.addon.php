<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined('__XE__'))
	exit();

/**
 * @file blogapicounter.addon.php
 * @author NAVER (developers@xpressengine.com)
 * @brief Add blogAPI
 *
 * It enables to write a post by using an external tool such as ms live writer, firefox performancing, zoundry and so on.
 * It should be called before executing the module(before_module_proc). If not, it is forced to shut down.
 * */
// Insert a rsd tag when called_position is after_module_proc
if($called_position == 'after_module_proc')
{
	// Create rsd address of the current module
	$site_module_info = Context::get('site_module_info');
	$rsd_url = getFullSiteUrl($site_module_info->domain, '', 'mid', $this->module_info->mid, 'act', 'api');
	// Insert rsd tag into the header
	Context::addHtmlHeader("    " . '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="' . $rsd_url . '" />');
}
// If act isnot api, just return
if($_REQUEST['act'] != 'api')
{
	return;
}

// Read func file
require_once(_XE_PATH_ . 'addons/blogapi/blogapi.func.php');

$xml = $GLOBALS['HTTP_RAW_POST_DATA'];

// If HTTP_RAW_POST_DATA is NULL, Print error message
if(!$xml)
{
	$content = getXmlRpcFailure(1, 'Invalid Method Call');
	printContent($content);
}

// xmlprc parsing
// Parse the requested xmlrpc
if(Security::detectingXEE($xml))
{
	header("HTTP/1.0 400 Bad Request");
	exit;
}

if(version_compare(PHP_VERSION, '5.2.11', '<=')) libxml_disable_entity_loader(true);
$xml = new SimpleXMLElement($xml, LIBXML_NONET | LIBXML_NOENT);

$method_name = (string)$xml->methodName;
$params = $xml->params->param;

// Compatible with some of methodname
if(in_array($method_name, array('metaWeblog.deletePost', 'metaWeblog.getUsersBlogs', 'metaWeblog.getUserInfo')))
{
	$method_name = str_replace('metaWeblog.', 'blogger.', $method_name);
}

// Get user_id, password and attempt log-in
$user_id = trim((string)$params[1]->value->string);
$password = trim((string)$params[2]->value->string);

// Before executing the module, authentication is processed.
if($called_position == 'before_module_init')
{
	// Attempt log-in by using member controller
	if($user_id && $password)
	{
		$oMemberController = getController('member');
		$output = $oMemberController->doLogin($user_id, $password);
		// If login fails, an error message appears
		if(!$output->toBool())
		{
			$content = getXmlRpcFailure(1, $output->getMessage());
			printContent($content);
		}
	}
	else
	{
		$content = getXmlRpcFailure(1, 'not logged');
		printContent($content);
	}
}

// Before module processing, handle requests from blogapi tool and then terminate.
if($called_position == 'before_module_proc')
{
	// Check writing permission 
	if(!$this->grant->write_document)
	{
		printContent(getXmlRpcFailure(1, 'no permission'));
	}

	// Get information of the categories
	$oDocumentModel = getModel('document');
	$category_list = $oDocumentModel->getCategoryList($this->module_srl);

	// Specifies a temporary file storage
	$tmp_uploaded_path = sprintf(_XE_PATH_ . 'files/cache/blogapi/%s/%s/', $this->mid, $user_id);
	$uploaded_target_path = sprintf(_XE_PATH_ . 'files/cache/blogapi/%s/%s/', $this->mid, $user_id);

	switch($method_name)
	{
		// Blog information
		case 'blogger.getUsersBlogs' :
			$obj = new stdClass();
			$obj->url = getFullSiteUrl('');
			$obj->blogid = $this->mid;
			$obj->blogName = $this->module_info->browser_title;
			$blog_list = array($obj);

			$content = getXmlRpcResponse($blog_list);
			printContent($content);
			break;

		// Return a list of categories
		case 'metaWeblog.getCategories' :
			$category_obj_list = array();
			if($category_list)
			{
				foreach($category_list as $category_srl => $category_info)
				{
					$obj = new stdClass();
					$obj->description = $category_info->title;
					//$obj->htmlUrl = Context::getRequestUri().$this->mid.'/1';
					//$obj->rssUrl= Context::getRequestUri().'rss/'.$this->mid.'/1';
					$obj->title = $category_info->title;
					$obj->categoryid = $category_srl;
					$category_obj_list[] = $obj;
				}
			}

			$content = getXmlRpcResponse($category_obj_list);
			printContent($content);
			break;

		// Upload file
		case 'metaWeblog.newMediaObject' :
			// Check a file upload permission
			$oFileModel = getModel('file');
			$file_module_config = $oFileModel->getFileModuleConfig($this->module_srl);
			if(is_array($file_module_config->download_grant) && count($file_module_config->download_grant) > 0)
			{
				$logged_info = Context::get('logged_info');
				if($logged_info->is_admin != 'Y')
				{
					$is_permitted = false;
					for($i = 0; $i < count($file_module_config->download_grant); $i++)
					{
						$group_srl = $file_module_config->download_grant[$i];
						if($logged_info->group_list[$group_srl])
						{
							$is_permitted = true;
							break;
						}
					}
					if(!$is_permitted){
						printContent(getXmlRpcFailure(1, 'no permission'));
					}
				}
			}

			$fileinfo = $params[3]->value->struct->member;
			foreach($fileinfo as $key => $val)
			{
				$nodename = (string)$val->name;
				if($nodename == 'bits')
					$filedata = base64_decode((string)$val->value->base64);
				elseif($nodename == 'name')
					$filename = (string)$val->value->string;
			}

			$tmp_arr = explode('/', $filename);
			$filename = array_pop($tmp_arr);

			FileHandler::makeDir($tmp_uploaded_path);

			$target_filename = sprintf('%s%s', $tmp_uploaded_path, $filename);
			FileHandler::writeFile($target_filename, $filedata);
			$obj = new stdClass();
			$obj->url = Context::getRequestUri() . $target_filename;

			$content = getXmlRpcResponse($obj);
			printContent($content);
			break;
		// Get posts
		case 'metaWeblog.getPost' :
			$document_srl = (string)$params[0]->value->string;
			if(!$document_srl)
			{
				printContent(getXmlRpcFailure(1, 'no permission'));
			}
			else
			{
				$oDocumentModel = getModel('document');
				$oDocument = $oDocumentModel->getDocument($document_srl);
				if(!$oDocument->isExists() || !$oDocument->isGranted())
				{
					printContent(getXmlRpcFailure(1, 'no permission'));
				}
				else
				{
					// Get a list of categories and set Context
					$category = "";
					if($oDocument->get('category_srl'))
					{
						$oDocumentModel = getModel('document');
						$category_list = $oDocumentModel->getCategoryList($oDocument->get('module_srl'));
						if($category_list[$oDocument->get('category_srl')])
						{
							$category = $category_list[$oDocument->get('category_srl')]->title;
						}
					}

					$content = sprintf(
							'<?xml version="1.0" encoding="utf-8"?>' .
							'<methodResponse>' .
							'<params>' .
								'<param>' .
									'<value>' .
										'<struct>' .
											'<member><name>categories</name><value><array><data><value><![CDATA[%s]]></value></data></array></value></member>' .
											'<member><name>dateCreated</name><value><dateTime.iso8601>%s</dateTime.iso8601></value></member>' .
											'<member><name>description</name><value><![CDATA[%s]]></value></member>' .
											'<member><name>link</name><value>%s</value></member>' .
											'<member><name>postid</name><value><string>%s</string></value></member>' .
											'<member><name>title</name><value><![CDATA[%s]]></value></member>' .
											'<member><name>publish</name><value><boolean>1</boolean></value></member>' .
										'</struct>' .
									'</value>' .
								'</param>' .
							'</params>' .
							'</methodResponse>', 
							$category,
							date("Ymd", $oDocument->getRegdateTime()) . 'T' . date("H:i:s", $oDocument->getRegdateTime()),
							$oDocument->getContent(false, false, true, false),
							getFullUrl('', 'document_srl', $oDocument->document_srl),
							$oDocument->document_srl,
							$oDocument->getTitleText()
					);
					printContent($content);
				}
			}
			break;

		// Write a new post
		case 'metaWeblog.newPost' :
			$obj = new stdClass();
			$info = $params[3];
			// Get information of post, title, and category
			foreach($info->value->struct->member as $val)
			{
				switch((string)$val->name)
				{
					case 'title' :
						$obj->title = (string)$val->value->string;
						break;
					case 'description' :
						$obj->content = (string)$val->value->string;
						break;
					case 'categories' :
						$categories = $val->value->array->data->value;
						$category = (string)$categories[0]->string;
						if($category && $category_list)
						{
							foreach($category_list as $category_srl => $category_info)
							{
								if($category_info->title == $category)
									$obj->category_srl = $category_srl;
							}
						}
						break;
					case 'tagwords' :
						$tags = $val->value->array->data->value;
						foreach($tags as $tag)
						{
							$tag_list[] = (string)$tag->string;
						}
						if(count($tag_list))
							$obj->tags = implode(',', $tag_list);
						break;
				}
			}

			// Set document srl
			$document_srl = getNextSequence();
			$obj->document_srl = $document_srl;
			$obj->module_srl = $this->module_srl;

			// Attachment
			if(is_dir($tmp_uploaded_path))
			{
				$file_list = FileHandler::readDir($tmp_uploaded_path);
				$file_count = count($file_list);
				if($file_count)
				{
					$oFileController = getController('file');
					for($i = 0; $i < $file_count; $i++)
					{
						$file_info['tmp_name'] = sprintf('%s%s', $tmp_uploaded_path, $file_list[$i]);
						$file_info['name'] = $file_list[$i];
						$fileOutput = $oFileController->insertFile($file_info, $this->module_srl, $document_srl, 0, true);
						$uploaded_filename = $fileOutput->get('uploaded_filename');
						$source_filename = $fileOutput->get('source_filename');
						$obj->content = str_replace($uploaded_target_path . $source_filename, sprintf('/files/attach/images/%s/%s%s', $this->module_srl, getNumberingPath($document_srl, 3), $uploaded_filename), $obj->content);
					}
					$obj->uploaded_count = $file_count;
				}
			}

			$oDocumentController = getController('document');
			$obj->commentStatus = 'ALLOW';
			$obj->allow_trackback = 'Y';
			
			$logged_info = Context::get('logged_info');
			$obj->member_srl = $logged_info->member_srl;
			$obj->user_id = $logged_info->user_id;
			$obj->user_name = $logged_info->user_name;
			$obj->nick_name = $logged_info->nick_name;
			$obj->email_address = $logged_info->email_address;
			$obj->homepage = $logged_info->homepage;
			$output = $oDocumentController->insertDocument($obj, TRUE);

			if(!$output->toBool())
			{
				$content = getXmlRpcFailure(1, $output->getMessage());
			}
			else
			{
				$content = getXmlRpcResponse(strval($document_srl));
			}
			FileHandler::removeDir($tmp_uploaded_path);

			printContent($content);
			break;

		// Edit post
		case 'metaWeblog.editPost' :
			$tmp_val = (string)$params[0]->value->string;
			if(!$tmp_val)
				$tmp_val = (string)$params[0]->value->i4;
			if(!$tmp_val)
			{
				$content = getXmlRpcFailure(1, 'no permission');
				break;
			}
			$tmp_arr = explode('/', $tmp_val);
			$document_srl = array_pop($tmp_arr);
			if(!$document_srl)
			{
				$content = getXmlRpcFailure(1, 'no permission');
				break;
			}

			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);
			// Check if a permission to modify a document is granted
			if(!$oDocument->isGranted())
			{
				$content = getXmlRpcFailure(1, 'no permission');
				break;
			}

			$obj = $oDocument->getObjectVars();

			$info = $params[3];
			// Get information of post, title, and category
			foreach($info->value->struct->member as $val)
			{
				switch((string)$val->name)
				{
					case 'title' :
						$obj->title = (string)$val->value->string;
						break;
					case 'description' :
						$obj->content = (string)$val->value->string;
						break;
					case 'categories' :
						$categories = $val->value->array->data->value;
						$category = (string)$categories[0]->string;
						if($category && $category_list)
						{
							foreach($category_list as $category_srl => $category_info)
							{
								if($category_info->title == $category)
									$obj->category_srl = $category_srl;
							}
						}
						break;
					case 'tagwords' :
						$tags = $val->value->array->data->value;
						foreach($tags as $tag)
						{
							$tag_list[] = (string)$tag->string;
						}
						if(count($tag_list))
							$obj->tags = implode(',', $tag_list);
						break;
				}
			}
			// Document srl
			$obj->document_srl = $document_srl;
			$obj->module_srl = $this->module_srl;
			// Attachment
			if(is_dir($tmp_uploaded_path))
			{
				$file_list = FileHandler::readDir($tmp_uploaded_path);
				$file_count = count($file_list);
				if($file_count)
				{
					$oFileController = getController('file');
					for($i = 0; $i < $file_count; $i++)
					{
						$file_info['tmp_name'] = sprintf('%s%s', $tmp_uploaded_path, $file_list[$i]);
						$file_info['name'] = $file_list[$i];

						$moved_filename = sprintf('./files/attach/images/%s/%s/%s', $this->module_srl, $document_srl, $file_info['name']);
						if(file_exists($moved_filename))
							continue;

						$fileOutput = $oFileController->insertFile($file_info, $this->module_srl, $document_srl, 0, true);
						$uploaded_filename = $fileOutput->get('uploaded_filename');
						$source_filename = $fileOutput->get('source_filename');
						$obj->content = str_replace($uploaded_target_path . $source_filename, sprintf('/files/attach/images/%s/%s%s', $this->module_srl, getNumberingPath($document_srl, 3), $uploaded_filename), $obj->content);
					}
					$obj->uploaded_count += $file_count;
				}
			}

			$oDocumentController = getController('document');
			$output = $oDocumentController->updateDocument($oDocument, $obj, TRUE);

			if(!$output->toBool())
			{
				$content = getXmlRpcFailure(1, $output->getMessage());
			}
			else
			{
				$content = getXmlRpcResponse(true);
				FileHandler::removeDir($tmp_uploaded_path);
			}

			printContent($content);
			break;
		// Delete the post
		case 'blogger.deletePost' :
			$tmp_val = (string)$params[1]->value->string;
			$tmp_arr = explode('/', $tmp_val);
			$document_srl = array_pop($tmp_arr);
			// Get a document
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);
			// If the document exists
			if(!$oDocument->isExists())
			{
				$content = getXmlRpcFailure(1, 'not exists');
				// Check if a permission to delete a document is granted
			}
			elseif(!$oDocument->isGranted())
			{
				$content = getXmlRpcFailure(1, 'no permission');
				break;
				// Delete
			}
			else
			{
				$oDocumentController = getController('document');
				$output = $oDocumentController->deleteDocument($document_srl);
				if(!$output->toBool())
					$content = getXmlRpcFailure(1, $output->getMessage());
				else
					$content = getXmlRpcResponse(true);
			}

			printContent($content);
			break;
		// Get recent posts
		case 'metaWeblog.getRecentPosts' :
			// Options to get a list
			$args = new stdClass();
			$args->module_srl = $this->module_srl; // /< module_srl of the current module
			$args->page = 1;
			$args->list_count = 20;
			$args->sort_index = 'list_order'; // /< Sorting values
			$logged_info = Context::get('logged_info');
			$args->search_target = 'member_srl';
			$args->search_keyword = $logged_info->member_srl;
			$output = $oDocumentModel->getDocumentList($args);
			if(!$output->toBool() || !$output->data)
			{
				$content = getXmlRpcFailure(1, 'post not founded');
			}
			else
			{
				$oEditorController = getController('editor');

				$posts = array();
				foreach($output->data as $key => $oDocument)
				{
					$post = new stdClass();
					$post->categories = array();
					$post->dateCreated = date("Ymd", $oDocument->getRegdateTime()) . 'T' . date("H:i:s", $oDocument->getRegdateTime());
					$post->description = sprintf('<![CDATA[%s]]>',$oEditorController->transComponent($oDocument->getContent(false, false, true, false)));
					$post->link = $post->permaLink = getFullUrl('', 'document_srl', $oDocument->document_srl);
					$post->postid = $oDocument->document_srl;
					$post->title = htmlspecialchars($oDocument->get('title'), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
					$post->publish = 1;
					$post->userid = $oDocument->get('user_id');
					$post->mt_allow_pings = 0;
					$post->mt_allow_comments = $oDocument->allowComment() ? 1 : 0;
					$posts[] = $post;
				}
				$content = getXmlRpcResponse($posts);
				printContent($content);
			}
			break;

		// Display RSD if there is no request
		default :
			$homepagelink = getUrl('', 'mid', $this->mid);
			$site_module_info = Context::get('site_module_info');
			$api_url = getFullSiteUrl($site_module_info->domain, '', 'mid', $site_module_info->mid, 'act', 'api');
			$content = <<<RSDContent
<?xml version="1.0" ?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd" >
<service>
    <engineName>XpressEngine</engineName>
    <engineLink>http://www.xpressengine.com/ </engineLink>
    <homePageLink>{$homepagelink}</homePageLink>
    <apis>
        <api name="MetaWeblog" preferred="true" apiLink="{$api_url}" blogID="" />
    </apis>
</service>
</rsd>
RSDContent;
			printContent($content);
			break;
	}
}
/* End of file blogapi.addon.php */
/* Location: ./addons/blogapi/blogapi.addon.php */
