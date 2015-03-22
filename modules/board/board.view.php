<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardView
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module View class
 **/
class boardView extends board
{
	var $listConfig;
	var $columnList;

	/**
	 * @brief initialization
	 * board module can be used in either normal mode or admin mode.\n
	 **/
	function init()
	{
		$oSecurity = new Security();
		$oSecurity->encodeHTML('document_srl', 'comment_srl', 'vid', 'mid', 'page', 'category', 'search_target', 'search_keyword', 'sort_index', 'order_type', 'trackback_srl');

		/**
		 * setup the module general information
		 **/
		if($this->module_info->list_count)
		{
			$this->list_count = $this->module_info->list_count;
		}
		if($this->module_info->search_list_count)
		{
			$this->search_list_count = $this->module_info->search_list_count;
		}
		if($this->module_info->page_count)
		{
			$this->page_count = $this->module_info->page_count;
		}
		$this->except_notice = $this->module_info->except_notice == 'N' ? FALSE : TRUE;

		// $this->_getStatusNameListecret option backward compatibility
		$oDocumentModel = getModel('document');

		$statusList = $this->_getStatusNameList($oDocumentModel);
		if(isset($statusList['SECRET']))
		{
			$this->module_info->secret = 'Y';
		}

		// use_category <=1.5.x, hide_category >=1.7.x
		$count_category = count($oDocumentModel->getCategoryList($this->module_info->module_srl));
		if($count_category)
		{
			if($this->module_info->hide_category)
			{
				$this->module_info->use_category = ($this->module_info->hide_category == 'Y') ? 'N' : 'Y';
			}
			else if($this->module_info->use_category)
			{
				$this->module_info->hide_category = ($this->module_info->use_category == 'Y') ? 'N' : 'Y';
			}
			else
			{
				$this->module_info->hide_category = 'N';
				$this->module_info->use_category = 'Y';
			}
		}
		else
		{
			$this->module_info->hide_category = 'Y';
			$this->module_info->use_category = 'N';
		}

		/**
		 * check the consultation function, if the user is admin then swich off consultation function
		 * if the user is not logged, then disppear write document/write comment./ view document
		 **/
		if($this->module_info->consultation == 'Y' && !$this->grant->manager)
		{
			$this->consultation = TRUE;
			if(!Context::get('is_logged'))
			{
				$this->grant->list = FALSE;
				$this->grant->write_document = FALSE;
				$this->grant->write_comment = FALSE;
				$this->grant->view = FALSE;
			}
		}
		else
		{
			$this->consultation = FALSE;
		}

		/**
		 * setup the template path based on the skin
		 * the default skin is default
		 **/
		$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		if(!is_dir($template_path)||!$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($template_path);

		/**
		 * use context::set to setup extra variables
		 **/
		$oDocumentModel = getModel('document');
		$extra_keys = $oDocumentModel->getExtraKeys($this->module_info->module_srl);
		Context::set('extra_keys', $extra_keys);

		/**
		 * add extra variables to order(sorting) target
		 **/
		if (is_array($extra_keys))
		{
			foreach($extra_keys as $val)
			{
				$this->order_target[] = $val->eid;
			}
		}
		/**
		 * load javascript, JS filters
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'input_password.xml');
		Context::addJsFile($this->module_path.'tpl/js/board.js');

		// remove [document_srl]_cpage from get_vars
		$args = Context::getRequestVars();
		foreach($args as $name => $value)
		{
			if(preg_match('/[0-9]+_cpage/', $name))
			{
				Context::set($name, '', TRUE);
				Context::set($name, $value);
			}
		}
	}

	/**
	 * @brief display board contents
	 **/
	function dispBoardContent()
	{
		/**
		 * check the access grant (all the grant has been set by the module object)
		 **/
		if(!$this->grant->access || !$this->grant->list)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		/**
		 * display the category list, and then setup the category list on context
		 **/
		$this->dispBoardCategoryList();

		/**
		 * display the search options on the screen
		 * add extra vaiables to the search options
		 **/
		// use search options on the template (the search options key has been declared, based on the language selected)
		foreach($this->search_option as $opt) $search_option[$opt] = Context::getLang($opt);
		$extra_keys = Context::get('extra_keys');
		if($extra_keys)
		{
			foreach($extra_keys as $key => $val)
			{
				if($val->search == 'Y') $search_option['extra_vars'.$val->idx] = $val->name;
			}
		}
		// remove a search option that is not public in member config
		$memberConfig = getModel('module')->getModuleConfig('member');
		foreach($memberConfig->signupForm as $signupFormElement)
		{
			if(in_array($signupFormElement->title, $search_option))
			{
				if($signupFormElement->isPublic == 'N')
					unset($search_option[$signupFormElement->name]);
			}
		}
		Context::set('search_option', $search_option);

		$oDocumentModel = getModel('document');
		$statusNameList = $this->_getStatusNameList($oDocumentModel);
		if(count($statusNameList) > 0)
		{
			Context::set('status_list', $statusNameList);
		}

		// display the board content
		$this->dispBoardContentView();

		// list config, columnList setting
		$oBoardModel = getModel('board');
		$this->listConfig = $oBoardModel->getListConfig($this->module_info->module_srl);
		if(!$this->listConfig) $this->listConfig = array();
		$this->_makeListColumnList();

		// display the notice list
		$this->dispBoardNoticeList();

		// list
		$this->dispBoardContentList();

		/**
		 * add javascript filters
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'search.xml');

		$oSecurity = new Security();
		$oSecurity->encodeHTML('search_option.');

		// setup the tmeplate file
		$this->setTemplateFile('list');
	}

	/**
	 * @brief display the category list
	 **/
	function dispBoardCategoryList(){
		// check if the use_category option is enabled
		if($this->module_info->use_category=='Y')
		{
			// check the grant
			if(!$this->grant->list)
			{
				Context::set('category_list', array());
				return;
			}

			$oDocumentModel = getModel('document');
			Context::set('category_list', $oDocumentModel->getCategoryList($this->module_srl));

			$oSecurity = new Security();
			$oSecurity->encodeHTML('category_list.', 'category_list.childs.');
		}
	}

	/**
	 * @brief display the board conent view
	 **/
	function dispBoardContentView(){
		// get the variable value
		$document_srl = Context::get('document_srl');
		$page = Context::get('page');

		// generate document model object
		$oDocumentModel = getModel('document');

		/**
		 * if the document exists, then get the document information
		 **/
		if($document_srl)
		{
			$oDocument = $oDocumentModel->getDocument($document_srl, false, true);

			// if the document is existed
			if($oDocument->isExists())
			{
				// if the module srl is not consistent
				if($oDocument->get('module_srl')!=$this->module_info->module_srl )
				{
					return $this->stop('msg_invalid_request');
				}

				// check the manage grant
				if($this->grant->manager) $oDocument->setGrant();

				// if the consultation function is enabled, and the document is not a notice
				if($this->consultation && !$oDocument->isNotice())
				{
					$logged_info = Context::get('logged_info');
					if($oDocument->get('member_srl')!=$logged_info->member_srl)
					{
						$oDocument = $oDocumentModel->getDocument(0);
					}
				}

				// if the document is TEMP saved, check Grant
				if($oDocument->getStatus() == 'TEMP')
				{
					if(!$oDocument->isGranted())
					{
						$oDocument = $oDocumentModel->getDocument(0);
					}
				}

			}
			else
			{
				// if the document is not existed, then alert a warning message
				Context::set('document_srl','',true);
				$this->alertMessage('msg_not_founded');
			}

		/**
		 * if the document is not existed, get an empty document
		 **/
		}
		else
		{
			$oDocument = $oDocumentModel->getDocument(0);
		}

		/**
		 *check the document view grant
		 **/
		if($oDocument->isExists())
		{
			if(!$this->grant->view && !$oDocument->isGranted())
			{
				$oDocument = $oDocumentModel->getDocument(0);
				Context::set('document_srl','',true);
				$this->alertMessage('msg_not_permitted');
			}
			else
			{
				// add the document title to the browser
				Context::addBrowserTitle($oDocument->getTitleText());

				// update the document view count (if the document is not secret)
				if(!$oDocument->isSecret() || $oDocument->isGranted())
				{
					$oDocument->updateReadedCount();
				}

				// disappear the document if it is secret
				if($oDocument->isSecret() && !$oDocument->isGranted())
				{
					$oDocument->add('content',Context::getLang('thisissecret'));
				}
			}
		}

		// setup the document oject on context
		$oDocument->add('module_srl', $this->module_srl);
		Context::set('oDocument', $oDocument);

		/**
		 * add javascript filters
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');

//            return new Object();
	}

	/**
	 * @brief  display the document file list (can be used by API)
	 **/
	function dispBoardContentFileList(){
		/**
		 * check the access grant (all the grant has been set by the module object)
		 **/
		if(!$this->grant->access)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		// check document view grant
		$this->dispBoardContentView();

		// Check if a permission for file download is granted
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');
		$file_module_config = $oModuleModel->getModulePartConfig('file',$this->module_srl);
		
		$downloadGrantCount = 0;
		if(is_array($file_module_config->download_grant))
		{
			foreach($file_module_config->download_grant AS $value)
				if($value) $downloadGrantCount++;
		}

		if(is_array($file_module_config->download_grant) && $downloadGrantCount>0)
		{
			if(!Context::get('is_logged')) return $this->stop('msg_not_permitted_download');
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')
			{
				$oModuleModel =& getModel('module');
				$columnList = array('module_srl', 'site_srl');
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($this->module_srl, $columnList);

				if(!$oModuleModel->isSiteAdmin($logged_info, $module_info->site_srl))
				{
					$oMemberModel =& getModel('member');
					$member_groups = $oMemberModel->getMemberGroups($logged_info->member_srl, $module_info->site_srl);

					$is_permitted = false;
					for($i=0;$i<count($file_module_config->download_grant);$i++)
					{
						$group_srl = $file_module_config->download_grant[$i];
						if($member_groups[$group_srl])
						{
							$is_permitted = true;
							break;
						}
					}
					if(!$is_permitted) return $this->stop('msg_not_permitted_download');
				}
			}
		}

		$oDocumentModel = getModel('document');
		$document_srl = Context::get('document_srl');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		Context::set('file_list',$oDocument->getUploadedFiles());

		$oSecurity = new Security();
		$oSecurity->encodeHTML('file_list..source_filename');
	}

	/**
	 * @brief display the document comment list (can be used by API)
	 **/
	function dispBoardContentCommentList(){
		// check document view grant
		$this->dispBoardContentView();

		$oDocumentModel = getModel('document');
		$document_srl = Context::get('document_srl');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		$comment_list = $oDocument->getComments();

		// setup the comment list
		if(is_array($comment_list))
		{
			foreach($comment_list as $key => $val)
			{
				if(!$val->isAccessible())
				{
					$val->add('content',Context::getLang('thisissecret'));
				}
			}
		}
		Context::set('comment_list',$comment_list);

	}

	/**
	 * @brief display notice list (can be used by API)
	 **/
	function dispBoardNoticeList(){
		// check the grant
		if(!$this->grant->list)
		{
			Context::set('notice_list', array());
			return;
		}

		$oDocumentModel = getModel('document');
		$args = new stdClass();
		$args->module_srl = $this->module_srl;
		$notice_output = $oDocumentModel->getNoticeList($args, $this->columnList);
		Context::set('notice_list', $notice_output->data);
	}

	/**
	 * @brief display board content list
	 **/
	function dispBoardContentList(){
		// check the grant
		if(!$this->grant->list)
		{
			Context::set('document_list', array());
			Context::set('total_count', 0);
			Context::set('total_page', 1);
			Context::set('page', 1);
			Context::set('page_navigation', new PageHandler(0,0,1,10));
			return;
		}

		$oDocumentModel = getModel('document');

		// setup module_srl/page number/ list number/ page count
		$args = new stdClass();
		$args->module_srl = $this->module_srl;
		$args->page = Context::get('page');
		$args->list_count = $this->list_count;
		$args->page_count = $this->page_count;

		// get the search target and keyword
		$args->search_target = Context::get('search_target');
		$args->search_keyword = Context::get('search_keyword');

		$search_option = Context::get('search_option');
		if($search_option==FALSE)
		{
			$search_option = $this->search_option;
		}
		if(isset($search_option[$args->search_target])==FALSE)
		{
			$args->search_target = '';
		}

		// if the category is enabled, then get the category
		if($this->module_info->use_category=='Y')
		{
			$args->category_srl = Context::get('category');
		}

		// setup the sort index and order index
		$args->sort_index = Context::get('sort_index');
		$args->order_type = Context::get('order_type');
		if(!in_array($args->sort_index, $this->order_target))
		{
			$args->sort_index = $this->module_info->order_target?$this->module_info->order_target:'list_order';
		}
		if(!in_array($args->order_type, array('asc','desc')))
		{
			$args->order_type = $this->module_info->order_type?$this->module_info->order_type:'asc';
		}

		// set the current page of documents
		$document_srl = Context::get('document_srl');
		if(!$args->page && $document_srl)
		{
			$oDocument = $oDocumentModel->getDocument($document_srl);
			if($oDocument->isExists() && !$oDocument->isNotice())
			{
				$page = $oDocumentModel->getDocumentPage($oDocument, $args);
				Context::set('page', $page);
				$args->page = $page;
			}
		}

		// setup the list count to be serach list count, if the category or search keyword has been set
		if($args->category_srl || $args->search_keyword)
		{
			$args->list_count = $this->search_list_count;
		}

		// if the consultation function is enabled,  the get the logged user information
		if($this->consultation)
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}

		// setup the list config variable on context
		Context::set('list_config', $this->listConfig);
		// setup document list variables on context
		$output = $oDocumentModel->getDocumentList($args, $this->except_notice, TRUE, $this->columnList);
		Context::set('document_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
	}

	function _makeListColumnList()
	{
		$configColumList = array_keys($this->listConfig);
		$tableColumnList = array('document_srl', 'module_srl', 'category_srl', 'lang_code', 'is_notice',
				'title', 'title_bold', 'title_color', 'content', 'readed_count', 'voted_count',
				'blamed_count', 'comment_count', 'trackback_count', 'uploaded_count', 'password', 'user_id',
				'user_name', 'nick_name', 'member_srl', 'email_address', 'homepage', 'tags', 'extra_vars',
				'regdate', 'last_update', 'last_updater', 'ipaddress', 'list_order', 'update_order',
				'allow_trackback', 'notify_message', 'status', 'comment_status');
		$this->columnList = array_intersect($configColumList, $tableColumnList);

		if(in_array('summary', $configColumList)) array_push($this->columnList, 'content');

		// default column list add
		$defaultColumn = array('document_srl', 'module_srl', 'category_srl', 'lang_code', 'member_srl', 'last_update', 'comment_count', 'trackback_count', 'uploaded_count', 'status', 'regdate', 'title_bold', 'title_color');

		//TODO guestbook, blog style supports legacy codes.
		if($this->module_info->skin == 'xe_guestbook' || $this->module_info->default_style == 'blog')
		{
			$defaultColumn = $tableColumnList;
		}

		if (in_array('last_post', $configColumList)){
			array_push($this->columnList, 'last_updater');
		}

		// add is_notice
		if ($this->except_notice)
		{
			array_push($this->columnList, 'is_notice');
		}
		$this->columnList = array_unique(array_merge($this->columnList, $defaultColumn));

		// add table name
		foreach($this->columnList as $no => $value)
		{
			$this->columnList[$no] = 'documents.' . $value;
		}
	}

	/**
	 * @brief display tag list
	 **/
	function dispBoardTagList()
	{
		// check if there is not grant fot view list, then alert an warning message
		if(!$this->grant->list)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		// generate the tag module model object
		$oTagModel = getModel('tag');

		$obj = new stdClass;
		$obj->mid = $this->module_info->mid;
		$obj->list_count = 10000;
		$output = $oTagModel->getTagList($obj);

		// automatically order
		if(count($output->data))
		{
			$numbers = array_keys($output->data);
			shuffle($numbers);

			if(count($output->data))
			{
				foreach($numbers as $k => $v)
				{
					$tag_list[] = $output->data[$v];
				}
			}
		}

		Context::set('tag_list', $tag_list);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('tag_list.');

		$this->setTemplateFile('tag_list');
	}

	/**
	 * @brief display document write form
	 **/
	function dispBoardWrite()
	{
		// check grant
		if(!$this->grant->write_document)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		$oDocumentModel = getModel('document');

		/**
		 * check if the category option is enabled not not
		 **/
		if($this->module_info->use_category=='Y')
		{
			// get the user group information
			if(Context::get('is_logged'))
			{
				$logged_info = Context::get('logged_info');
				$group_srls = array_keys($logged_info->group_list);
			}
			else
			{
				$group_srls = array();
			}
			$group_srls_count = count($group_srls);

			// check the grant after obtained the category list
			$normal_category_list = $oDocumentModel->getCategoryList($this->module_srl);
			if(count($normal_category_list))
			{
				foreach($normal_category_list as $category_srl => $category)
				{
					$is_granted = TRUE;
					if($category->group_srls)
					{
						$category_group_srls = explode(',',$category->group_srls);
						$is_granted = FALSE;
						if(count(array_intersect($group_srls, $category_group_srls))) $is_granted = TRUE;

					}
					if($is_granted) $category_list[$category_srl] = $category;
				}
			}
			Context::set('category_list', $category_list);
		}

		// GET parameter document_srl from request
		$document_srl = Context::get('document_srl');
		$oDocument = $oDocumentModel->getDocument(0, $this->grant->manager);
		$oDocument->setDocument($document_srl);

		if($oDocument->get('module_srl') == $oDocument->get('member_srl')) $savedDoc = TRUE;
		$oDocument->add('module_srl', $this->module_srl);

		if($oDocument->isExists() && $this->module_info->protect_content=="Y" && $oDocument->get('comment_count')>0 && $this->grant->manager==false)
		{
			return new Object(-1, 'msg_protect_content');
		}

		// if the document is not granted, then back to the password input form
		$oModuleModel = getModel('module');
		if($oDocument->isExists()&&!$oDocument->isGranted())
		{
			return $this->setTemplateFile('input_password_form');
		}

		if(!$oDocument->isExists())
		{
			$point_config = $oModuleModel->getModulePartConfig('point',$this->module_srl);
			$logged_info = Context::get('logged_info');
			$oPointModel = getModel('point');
			$pointForInsert = $point_config["insert_document"];
			if($pointForInsert < 0)
			{
				if( !$logged_info )
				{
					return $this->dispBoardMessage('msg_not_permitted');
				}
				else if (($oPointModel->getPoint($logged_info->member_srl) + $pointForInsert )< 0 )
				{
					return $this->dispBoardMessage('msg_not_enough_point');
				}
			}
		}
		if(!$oDocument->get('status')) $oDocument->add('status', $oDocumentModel->getDefaultStatus());

		$statusList = $this->_getStatusNameList($oDocumentModel);
		if(count($statusList) > 0) Context::set('status_list', $statusList);

		// get Document status config value
		Context::set('document_srl',$document_srl);
		Context::set('oDocument', $oDocument);

		// apply xml_js_filter on header
		$oDocumentController = getController('document');
		$oDocumentController->addXmlJsFilter($this->module_info->module_srl);

		// if the document exists, then setup extra variabels on context
		if($oDocument->isExists() && !$savedDoc) Context::set('extra_keys', $oDocument->getExtraVars());

		/**
		 * add JS filters
		 **/
		if(Context::get('logged_info')->is_admin=='Y') Context::addJsFilter($this->module_path.'tpl/filter', 'insert_admin.xml');
		else Context::addJsFilter($this->module_path.'tpl/filter', 'insert.xml');

		$oSecurity = new Security();
		$oSecurity->encodeHTML('category_list.text', 'category_list.title');

		$this->setTemplateFile('write_form');
	}

	function _getStatusNameList(&$oDocumentModel)
	{
		$resultList = array();
		if(!empty($this->module_info->use_status))
		{
			$statusNameList = $oDocumentModel->getStatusNameList();
			$statusList = explode('|@|', $this->module_info->use_status);

			if(is_array($statusList))
			{
				foreach($statusList as $key => $value)
				{
					$resultList[$value] = $statusNameList[$value];
				}
			}
		}
		return $resultList;
	}

	/**
	 * @brief display board module deletion form
	 **/
	function dispBoardDelete()
	{
		// check grant
		if(!$this->grant->write_document)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		// get the document_srl from request
		$document_srl = Context::get('document_srl');

		// if document exists, get the document information
		if($document_srl)
		{
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);
		}

		// if the document is not existed, then back to the board content page
		if(!$oDocument->isExists())
		{
			return $this->dispBoardContent();
		}

		// if the document is not granted, then back to the password input form
		if(!$oDocument->isGranted())
		{
			return $this->setTemplateFile('input_password_form');
		}

		if($this->module_info->protect_content=="Y" && $oDocument->get('comment_count')>0 && $this->grant->manager==false)
		{
			return $this->dispBoardMessage('msg_protect_content');
		}

		Context::set('oDocument',$oDocument);

		/**
		 * add JS filters
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'delete_document.xml');

		$this->setTemplateFile('delete_form');
	}

	/**
	 * @brief display comment wirte form
	 **/
	function dispBoardWriteComment()
	{
		$document_srl = Context::get('document_srl');

		// check grant
		if(!$this->grant->write_comment)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		// get the document information
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($document_srl);
		if(!$oDocument->isExists())
		{
			return $this->dispBoardMessage('msg_invalid_request');
		}

		// Check allow comment
		if(!$oDocument->allowComment())
		{
			return $this->dispBoardMessage('msg_not_allow_comment');
		}

		// obtain the comment (create an empty comment document for comment_form usage)
		$oCommentModel = getModel('comment');
		$oSourceComment = $oComment = $oCommentModel->getComment(0);
		$oComment->add('document_srl', $document_srl);
		$oComment->add('module_srl', $this->module_srl);

		// setup document variables on context
		Context::set('oDocument',$oDocument);
		Context::set('oSourceComment',$oSourceComment);
		Context::set('oComment',$oComment);

		/**
		 * add JS filter
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');

		$this->setTemplateFile('comment_form');
	}

	/**
	 * @brief display comment replies page
	 **/
	function dispBoardReplyComment()
	{
		// check grant
		if(!$this->grant->write_comment)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		// get the parent comment ID
		$parent_srl = Context::get('comment_srl');

		// if the parent comment is not existed
		if(!$parent_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// get the comment
		$oCommentModel = getModel('comment');
		$oSourceComment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

		// if the comment is not existed, opoup an error message
		if(!$oSourceComment->isExists())
		{
			return $this->dispBoardMessage('msg_invalid_request');
		}
		if(Context::get('document_srl') && $oSourceComment->get('document_srl') != Context::get('document_srl'))
		{
			return $this->dispBoardMessage('msg_invalid_request');
		}

		// Check allow comment
		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($oSourceComment->get('document_srl'));
		if(!$oDocument->allowComment())
		{
			return $this->dispBoardMessage('msg_not_allow_comment');
		}

		// get the comment information
		$oComment = $oCommentModel->getComment();
		$oComment->add('parent_srl', $parent_srl);
		$oComment->add('document_srl', $oSourceComment->get('document_srl'));

		// setup comment variables
		Context::set('oSourceComment',$oSourceComment);
		Context::set('oComment',$oComment);
		Context::set('module_srl',$this->module_info->module_srl);

		/**
		 * add JS filters
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');

		$this->setTemplateFile('comment_form');
	}

	/**
	 * @brief display the comment modification from
	 **/
	function dispBoardModifyComment()
	{
		// check grant
		if(!$this->grant->write_comment)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		// get the document_srl and comment_srl
		$document_srl = Context::get('document_srl');
		$comment_srl = Context::get('comment_srl');

		// if the comment is not existed
		if(!$comment_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// get comment information
		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl, $this->grant->manager);

		// if the comment is not exited, alert an error message
		if(!$oComment->isExists())
		{
			return $this->dispBoardMessage('msg_invalid_request');
		}

		// if the comment is not granted, then back to the password input form
		if(!$oComment->isGranted())
		{
			return $this->setTemplateFile('input_password_form');
		}

		// setup the comment variables on context
		Context::set('oSourceComment', $oCommentModel->getComment());
		Context::set('oComment', $oComment);

		/**
		 * add JS fitlers
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');

		$this->setTemplateFile('comment_form');
	}

	/**
	 * @brief display the delete comment  form
	 **/
	function dispBoardDeleteComment()
	{
		// check grant
		if(!$this->grant->write_comment)
		{
			return $this->dispBoardMessage('msg_not_permitted');
		}

		// get the comment_srl to be deleted
		$comment_srl = Context::get('comment_srl');

		// if the comment exists, then get the comment information
		if($comment_srl)
		{
			$oCommentModel = getModel('comment');
			$oComment = $oCommentModel->getComment($comment_srl, $this->grant->manager);
		}

		// if the comment is not existed, then back to the board content page
		if(!$oComment->isExists() )
		{
			return $this->dispBoardContent();
		}

		// if the comment is not granted, then back to the password input form
		if(!$oComment->isGranted())
		{
			return $this->setTemplateFile('input_password_form');
		}

		Context::set('oComment',$oComment);

		/**
		 * add JS filters
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'delete_comment.xml');

		$this->setTemplateFile('delete_comment_form');
	}

	/**
	 * @brief display the delete trackback form
	 **/
	function dispBoardDeleteTrackback()
	{
		$oTrackbackModel = getModel('trackback');

		if(!$oTrackbackModel)
		{
			return;
		}

		// get the trackback_srl
		$trackback_srl = Context::get('trackback_srl');

		// get the trackback data
		$columnList = array('trackback_srl');
		$output = $oTrackbackModel->getTrackback($trackback_srl, $columnList);
		$trackback = $output->data;

		// if no trackback, then display the board content
		if(!$trackback)
		{
			return $this->dispBoardContent();
		}

		//Context::set('trackback',$trackback);	//perhaps trackback variables not use in UI

		/**
		 * add JS filters
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'delete_trackback.xml');

		$this->setTemplateFile('delete_trackback_form');
	}

	/**
	 * @brief display board message
	 **/
	function dispBoardMessage($msg_code)
	{
		$msg = Context::getLang($msg_code);
		if(!$msg) $msg = $msg_code;
		Context::set('message', $msg);
		$this->setTemplateFile('message');
	}

	/**
	 * @brief the method for displaying the warning messages
	 * display an error message if it has not  a special design
	 **/
	function alertMessage($message)
	{
		$script =  sprintf('<script> jQuery(function(){ alert("%s"); } );</script>', Context::getLang($message));
		Context::addHtmlFooter( $script );
	}

}
