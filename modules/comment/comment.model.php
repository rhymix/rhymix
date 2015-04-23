<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * commentModel class
 * model class of the comment module
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/comment
 * @version 0.1
 */
class commentModel extends comment
{

	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{

	}

	/**
	 * display the pop-up menu of the post
	 * Print, scrap, vote-up(recommen), vote-down(non-recommend), report features added
	 * @return void
	 */
	function getCommentMenu()
	{
		// get the post's id number and the current login information
		$comment_srl = Context::get('target_srl');
		$mid = Context::get('cur_mid');
		$logged_info = Context::get('logged_info');
		$act = Context::get('cur_act');

		// array values for menu_list, "comment post, target, url"
		$menu_list = array();

		// call a trigger
		ModuleHandler::triggerCall('comment.getCommentMenu', 'before', $menu_list);

		$oCommentController = getController('comment');

		// feature that only member can do
		if($logged_info->member_srl)
		{
			$oCommentModel = getModel('comment');
			$columnList = array('comment_srl', 'module_srl', 'member_srl', 'ipaddress');
			$oComment = $oCommentModel->getComment($comment_srl, FALSE, $columnList);
			$module_srl = $oComment->get('module_srl');
			$member_srl = $oComment->get('member_srl');

			$oModuleModel = getModel('module');
			$comment_config = $oModuleModel->getModulePartConfig('document', $module_srl);

			if($comment_config->use_vote_up != 'N' && $member_srl != $logged_info->member_srl)
			{
				// Add a vote-up button for positive feedback
				$url = sprintf("doCallModuleAction('comment','procCommentVoteUp','%s')", $comment_srl);
				$oCommentController->addCommentPopupMenu($url, 'cmd_vote', '', 'javascript');
			}

			if($comment_config->use_vote_down != 'N' && $member_srl != $logged_info->member_srl)
			{
				// Add a vote-down button for negative feedback
				$url = sprintf("doCallModuleAction('comment','procCommentVoteDown','%s')", $comment_srl);
				$oCommentController->addCommentPopupMenu($url, 'cmd_vote_down', '', 'javascript');
			}

			// Add the report feature against abused posts
			$url = sprintf("doCallModuleAction('comment','procCommentDeclare','%s')", $comment_srl);
			$oCommentController->addCommentPopupMenu($url, 'cmd_declare', '', 'javascript');
		}

		// call a trigger (after)
		ModuleHandler::triggerCall('comment.getCommentMenu', 'after', $menu_list);

		if($this->grant->manager){
			$str_confirm = Context::getLang('confirm_move');
			$url = sprintf("if(!confirm('%s')) return; var params = new Array(); params['comment_srl']='%s'; params['mid']=current_mid;params['cur_url']=current_url; exec_xml('comment', 'procCommentAdminMoveToTrash', params)", $str_confirm, $comment_srl);
			$oCommentController->addCommentPopupMenu($url,'cmd_trash','','javascript');

		}

		// find a comment by IP matching if an administrator.
		if($logged_info->is_admin == 'Y')
		{
			$oCommentModel = getModel('comment');
			$oComment = $oCommentModel->getComment($comment_srl);

			if($oComment->isExists())
			{
				// Find a post of the corresponding ip address
				$url = getUrl('', 'module', 'admin', 'act', 'dispCommentAdminList', 'search_target', 'ipaddress', 'search_keyword', $oComment->getIpAddress());
				$oCommentController->addCommentPopupMenu($url, 'cmd_search_by_ipaddress', $icon_path, 'TraceByIpaddress');

				$url = sprintf("var params = new Array(); params['ipaddress_list']='%s'; exec_xml('spamfilter', 'procSpamfilterAdminInsertDeniedIP', params, completeCallModuleAction)", $oComment->getIpAddress());
				$oCommentController->addCommentPopupMenu($url, 'cmd_add_ip_to_spamfilter', '', 'javascript');
			}
		}

		// Changing a language of pop-up menu
		$menus = Context::get('comment_popup_menu_list');
		$menus_count = count($menus);

		for($i = 0; $i < $menus_count; $i++)
		{
			$menus[$i]->str = Context::getLang($menus[$i]->str);
		}

		// get a list of final organized pop-up menus
		$this->add('menus', $menus);
	}

	/**
	 * Check if you have a permission to comment_srl
	 * use only session information
	 * @param int $comment_srl
	 * @return bool
	 */
	function isGranted($comment_srl)
	{
		return $_SESSION['own_comment'][$comment_srl];
	}

	/**
	 * Returns the number of child comments
	 * @param int $comment_srl
	 * @return int
	 */
	function getChildCommentCount($comment_srl)
	{
		$args = new stdClass();
		$args->comment_srl = $comment_srl;
		$output = executeQuery('comment.getChildCommentCount', $args, NULL, 'master');
		return (int) $output->data->count;
	}

	/**
	 * Returns the number of child comments
	 * @param int $comment_srl
	 * @return int
	 */
	function getChildComments($comment_srl)
	{
		$args = new stdClass();
		$args->comment_srl = $comment_srl;
		$output = executeQueryArray('comment.getChildComments', $args, NULL, 'master');
		return $output->data;
	}

	/**
	 * Get the comment
	 * @param int $comment_srl
	 * @param bool $is_admin
	 * @param array $columnList
	 * @return commentItem
	 */
	function getComment($comment_srl = 0, $is_admin = FALSE, $columnList = array())
	{
		$oComment = new commentItem($comment_srl, $columnList);
		if($is_admin)
		{
			$oComment->setGrant();
		}

		return $oComment;
	}

	/**
	 * Get the comment list(not paginating)
	 * @param string|array $comment_srl_list
	 * @param array $columnList
	 * @return array
	 */
	function getComments($comment_srl_list, $columnList = array())
	{
		if(is_array($comment_srl_list))
		{
			$comment_srls = implode(',', $comment_srl_list);
		}

		// fetch from a database
		$args = new stdClass();
		$args->comment_srls = $comment_srls;
		$output = executeQuery('comment.getComments', $args, $columnList);
		if(!$output->toBool())
		{
			return;
		}

		$comment_list = $output->data;
		if(!$comment_list)
		{
			return;
		}
		if(!is_array($comment_list))
		{
			$comment_list = array($comment_list);
		}

		$comment_count = count($comment_list);
		foreach($comment_list as $key => $attribute)
		{
			if(!$attribute->comment_srl)
			{
				continue;
			}

			$oComment = NULL;
			$oComment = new commentItem();
			$oComment->setAttribute($attribute);
			if($is_admin)
			{
				$oComment->setGrant();
			}

			$result[$attribute->comment_srl] = $oComment;
		}
		return $result;
	}

	/**
	 * Get the total number of comments in corresponding with document_srl.
	 * @param int $document_srl
	 * @return int
	 */
	function getCommentCount($document_srl)
	{
		$args = new stdClass();
		$args->document_srl = $document_srl;

		// get the number of comments on the document module
		$oDocumentModel = getModel('document');
		$columnList = array('document_srl', 'module_srl');

		$oDocument = $oDocumentModel->getDocument($document_srl, FALSE, TRUE, $columnList);

		// return if no doc exists.
		if(!$oDocument->isExists())
		{
			return;
		}

		// get a list of comments
		$module_srl = $oDocument->get('module_srl');

		//check if module is using validation system
		$oCommentController = getController('comment');

		$using_validation = $oCommentController->isModuleUsingPublishValidation($module_srl);
		if($using_validation)
		{
			$args->status = 1;
		}

		$output = executeQuery('comment.getCommentCount', $args, NULL, 'master');
		$total_count = $output->data->count;

		return (int) $total_count;
	}

	/**
	 * Get the total number of comments in corresponding with document_srl.
	 * @param string $date
	 * @param array $moduleSrlList
	 * @return int
	 */
	function getCommentCountByDate($date = '', $moduleSrlList = array())
	{
		if($date)
		{
			$args->regDate = date('Ymd', strtotime($date));
		}

		if(count($moduleSrlList) > 0)
		{
			$args->module_srl = $moduleSrlList;
		}

		$output = executeQuery('comment.getCommentCount', $args);
		if(!$output->toBool())
		{
			return 0;
		}

		return $output->data->count;
	}

	/**
	 * Get the total number of comments in corresponding with module_srl.
	 * @param int $module_srl
	 * @param bool $published
	 * @return int
	 */
	function getCommentAllCount($module_srl, $published = null)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;

		if(is_null($published))
		{
			// check if module is using comment validation system
			$oCommentController = getController("comment");
			$is_using_validation = $oCommentController->isModuleUsingPublishValidation($module_srl);
			if($is_using_validation)
			{
				$args->status = 1;
			}
		}
		else
		{
			if($published)
			{
				$args->status = 1;
			}
			else
			{
				$args->status = 0;
			}
		}

		$output = executeQuery('comment.getCommentCount', $args);
		$total_count = $output->data->count;

		return (int) $total_count;
	}

	/**
	 * Get the module info without duplication
	 * @return array
	 */
	function getDistinctModules()
	{
		return array();

		/*
		$output = executeQueryArray('comment.getDistinctModules');
		$module_srls = $output->data;
		$oModuleModel = getModel('module');
		$result = array();
		if($module_srls)
		{
			foreach($module_srls as $module)
			{
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($module->module_srl);
				$result[$module->module_srl] = $module_info->mid;
			}
		}
		return $result;
		*/
	}

	/**
	 * Get the comment in corresponding with mid.
	 * @todo add commentItems to cache too
	 * @param object $obj
	 * @param array $columnList
	 * @return array
	 */
	function getNewestCommentList($obj, $columnList = array())
	{
		$args = new stdClass();

		if(!is_object($obj))
		{
			$obj = new stdClass();
		}

		if($obj->mid)
		{
			$oModuleModel = getModel('module');
			$obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
			unset($obj->mid);
		}

		// check if module_srl is an arrary.
		if(is_array($obj->module_srl))
		{
			$args->module_srl = implode(',', $obj->module_srl);
		}
		else
		{
			$args->module_srl = $obj->module_srl;
		}

		$args->document_srl = $obj->document_srl;
		$args->list_count = $obj->list_count;

		if(strpos($args->module_srl, ",") === false)
		{
			if($args->module_srl)
			{
				// check if module is using comment validation system
				$oCommentController = getController("comment");
				$is_using_validation = $oCommentController->isModuleUsingPublishValidation($obj->module_srl);
				if($is_using_validation)
				{
					$args->status = 1;
				}
			}
		}

		$output = executeQuery('comment.getNewestCommentList', $args, $columnList);

		if(!$output->toBool())
		{
			return $output;
		}

		$comment_list = $output->data;
		if($comment_list)
		{
			if(!is_array($comment_list))
			{
				$comment_list = array($comment_list);
			}

			$comment_count = count($comment_list);

			foreach($comment_list as $key => $attribute)
			{
				if(!$attribute->comment_srl)
				{
					continue;
				}

				$oComment = NULL;
				$oComment = new commentItem();
				$oComment->setAttribute($attribute);

				$result[$key] = $oComment;
			}
			$output->data = $result;
		}

		return $result;
	}

	/**
	 * Get a comment list of the doc in corresponding woth document_srl.
	 * @param int $document_srl
	 * @param int $page
	 * @param bool $is_admin
	 * @param int $count
	 * @return object
	 */
	function getCommentList($document_srl, $page = 0, $is_admin = FALSE, $count = 0)
	{
		if(!isset($document_srl))
		{
			return;
		}

		// get the number of comments on the document module
		$oDocumentModel = getModel('document');
		$columnList = array('document_srl', 'module_srl', 'comment_count');
		$oDocument = $oDocumentModel->getDocument($document_srl, FALSE, TRUE, $columnList);

		// return if no doc exists.
		if(!$oDocument->isExists())
		{
			return;
		}

		// return if no comment exists
		if($oDocument->getCommentCount() < 1)
		{
			return;
		}

		// get a list of comments
		$module_srl = $oDocument->get('module_srl');

		if(!$count)
		{
			$comment_config = $this->getCommentConfig($module_srl);
			$comment_count = $comment_config->comment_count;
			if(!$comment_count)
			{
				$comment_count = 50;
			}
		}
		else
		{
			$comment_count = $count;
		}

		// get a very last page if no page exists
		if(!$page)
		{
			$page = (int) ( ($oDocument->getCommentCount() - 1) / $comment_count) + 1;
		}

		// get a list of comments
		$args = new stdClass();
		$args->document_srl = $document_srl;
		$args->list_count = $comment_count;
		$args->page = $page;
		$args->page_count = 10;

		//check if module is using validation system
		$oCommentController = getController('comment');
		$using_validation = $oCommentController->isModuleUsingPublishValidation($module_srl);
		if($using_validation)
		{
			$args->status = 1;
		}

		$output = executeQueryArray('comment.getCommentPageList', $args);

		// return if an error occurs in the query results
		if(!$output->toBool())
		{
			return;
		}

		// insert data into CommentPageList table if the number of results is different from stored comments
		if(!$output->data)
		{
			$this->fixCommentList($oDocument->get('module_srl'), $document_srl);
			$output = executeQueryArray('comment.getCommentPageList', $args);
			if(!$output->toBool())
			{
				return;
			}
		}

		return $output;
	}

	/**
	 * Update a list of comments in corresponding with document_srl
	 * Take care of previously used data than GA version
	 * @param int $module_srl
	 * @param int $document_srl
	 * @return void
	 */
	function fixCommentList($module_srl, $document_srl)
	{
		// create a lock file to prevent repeated work when performing a batch job
		$lock_file = "./files/cache/tmp/lock." . $document_srl;

		if(file_exists($lock_file) && filemtime($lock_file) + 60 * 60 * 10 < $_SERVER['REQUEST_TIME'])
		{
			return;
		}

		FileHandler::writeFile($lock_file, '');

		// get a list
		$args = new stdClass();
		$args->document_srl = $document_srl;
		$args->list_order = 'list_order';
		$output = executeQuery('comment.getCommentList', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$source_list = $output->data;
		if(!is_array($source_list))
		{
			$source_list = array($source_list);
		}

		// Sort comments by the hierarchical structure
		$comment_count = count($source_list);

		$root = new stdClass;
		$list = array();
		$comment_list = array();

		// get the log-in information for logged-in users
		$logged_info = Context::get('logged_info');

		// generate a hierarchical structure of comments for loop
		for($i = $comment_count - 1; $i >= 0; $i--)
		{
			$comment_srl = $source_list[$i]->comment_srl;
			$parent_srl = $source_list[$i]->parent_srl;
			if(!$comment_srl)
			{
				continue;
			}

			// generate a list
			$list[$comment_srl] = $source_list[$i];

			if($parent_srl)
			{
				$list[$parent_srl]->child[] = &$list[$comment_srl];
			}
			else
			{
				$root->child[] = &$list[$comment_srl];
			}
		}
		$this->_arrangeComment($comment_list, $root->child, 0, NULL);

		// insert values to the database
		if(count($comment_list))
		{
			foreach($comment_list as $comment_srl => $item)
			{
				$comment_args = new stdClass();
				$comment_args->comment_srl = $comment_srl;
				$comment_args->document_srl = $document_srl;
				$comment_args->head = $item->head;
				$comment_args->arrange = $item->arrange;
				$comment_args->module_srl = $module_srl;
				$comment_args->regdate = $item->regdate;
				$comment_args->depth = $item->depth;

				executeQuery('comment.insertCommentList', $comment_args);
			}
		}

		// remove the lock file if successful.
		FileHandler::removeFile($lock_file);
	}

	/**
	 * Relocate comments in the hierarchical structure
	 * @param array $comment_list
	 * @param array $list
	 * @param int $depth
	 * @param object $parent
	 * @return void
	 */
	function _arrangeComment(&$comment_list, $list, $depth, $parent = NULL)
	{
		if(!count($list))
		{
			return;
		}

		foreach($list as $key => $val)
		{
			if($parent)
			{
				$val->head = $parent->head;
			}
			else
			{
				$val->head = $val->comment_srl;
			}

			$val->arrange = count($comment_list) + 1;

			if($val->child)
			{
				$val->depth = $depth;
				$comment_list[$val->comment_srl] = $val;
				$this->_arrangeComment($comment_list, $val->child, $depth + 1, $val);
				unset($val->child);
			}
			else
			{
				$val->depth = $depth;
				$comment_list[$val->comment_srl] = $val;
			}
		}
	}

	/**
	 * Get all the comments in time decending order(for administrators)
	 * @param object $obj
	 * @param array $columnList
	 * @return object
	 */
	function getTotalCommentList($obj, $columnList = array())
	{
		$query_id = 'comment.getTotalCommentList';

		// Variables
		$args = new stdClass();
		$args->sort_index = 'list_order';
		$args->page = $obj->page ? $obj->page : 1;
		$args->list_count = $obj->list_count ? $obj->list_count : 20;
		$args->page_count = $obj->page_count ? $obj->page_count : 10;
		$args->s_module_srl = $obj->module_srl;
		$args->exclude_module_srl = $obj->exclude_module_srl;

		// check if module is using comment validation system
		$oCommentController = getController("comment");
		$is_using_validation = $oCommentController->isModuleUsingPublishValidation($obj->module_srl);
		if($is_using_validation)
		{
			$args->s_is_published = 1;
		}

		// Search options
		$search_target = $obj->search_target ? $obj->search_target : trim(Context::get('search_target'));
		$search_keyword = $obj->search_keyword ? $obj->search_keyword : trim(Context::get('search_keyword'));
		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'content' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_content = $search_keyword;
					break;

				case 'user_id' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_user_id = $search_keyword;
					$query_id = 'comment.getTotalCommentListWithinMember';
					$args->sort_index = 'comments.list_order';
					break;

				case 'user_name' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_user_name = $search_keyword;
					break;

				case 'nick_name' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_nick_name = $search_keyword;
					break;

				case 'email_address' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_email_address = $search_keyword;
					break;

				case 'homepage' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_homepage = $search_keyword;
					break;

				case 'regdate' :
					$args->s_regdate = $search_keyword;
					break;

				case 'last_update' :
					$args->s_last_upate = $search_keyword;
					break;

				case 'ipaddress' :
					$args->s_ipaddress = $search_keyword;
					break;

				case 'is_secret' :
					$args->s_is_secret = $search_keyword;
					break;

				case 'is_published' :
					if($search_keyword == 'Y')
					{
						$args->s_is_published = 1;
					}

					if($search_keyword == 'N')
					{
						$args->s_is_published = 0;
					}

					break;

				case 'module':
					$args->s_module_srl = (int) $search_keyword;
					break;

				case 'member_srl' :
					$args->{"s_" . $search_target} = (int) $search_keyword;
					break;
			}
		}

		// comment.getTotalCommentList query execution
		$output = executeQueryArray($query_id, $args, $columnList);

		// return when no result or error occurance
		if(!$output->toBool() || !count($output->data))
		{
			return $output;
		}

		foreach($output->data as $key => $val)
		{
			unset($_oComment);
			$_oComment = new CommentItem(0);
			$_oComment->setAttribute($val);
			$output->data[$key] = $_oComment;
		}

		return $output;
	}

	/**
	 * Get all the comment count in time decending order(for administrators)
	 * @param object $obj
	 * @return int
	 */
	function getTotalCommentCount($obj)
	{
		$query_id = 'comment.getTotalCommentCountByGroupStatus';

		// Variables
		$args = new stdClass();
		$args->s_module_srl = $obj->module_srl;
		$args->exclude_module_srl = $obj->exclude_module_srl;

		// Search options
		$search_target = $obj->search_target ? $obj->search_target : trim(Context::get('search_target'));
		$search_keyword = $obj->search_keyword ? $obj->search_keyword : trim(Context::get('search_keyword'));

		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'content' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_content = $search_keyword;
					break;

				case 'user_id' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_user_id = $search_keyword;
					$query_id = 'comment.getTotalCommentCountWithinMemberByGroupStatus';
					break;

				case 'user_name' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}
					$args->s_user_name = $search_keyword;

					break;

				case 'nick_name' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_nick_name = $search_keyword;
					break;

				case 'email_address' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_email_address = $search_keyword;
					break;

				case 'homepage' :
					if($search_keyword)
					{
						$search_keyword = str_replace(' ', '%', $search_keyword);
					}

					$args->s_homepage = $search_keyword;
					break;

				case 'regdate' :
					$args->s_regdate = $search_keyword;
					break;

				case 'last_update' :
					$args->s_last_upate = $search_keyword;
					break;

				case 'ipaddress' :
					$args->s_ipaddress = $search_keyword;
					break;

				case 'is_secret' :
					$args->s_is_secret = $search_keyword;
					break;

				case 'member_srl' :
					$args->{"s_" . $search_target} = (int) $search_keyword;
					break;
			}
		}

		$output = executeQueryArray($query_id, $args);

		// return when no result or error occurance
		if(!$output->toBool() || !count($output->data))
		{
			return $output;
		}

		return $output->data;
	}

	/**
	 * Return a configuration of comments for each module
	 * @param int $module_srl
	 * @return object
	 */
	function getCommentConfig($module_srl)
	{
		$oModuleModel = getModel('module');
		$comment_config = $oModuleModel->getModulePartConfig('comment', $module_srl);
		if(!is_object($comment_config))
		{
			$comment_config = new stdClass();
		}

		if(!isset($comment_config->comment_count))
		{
			$comment_config->comment_count = 50;
		}

		return $comment_config;
	}

	/**
	 * Return a list of voting member
	 * @return void
	 */
	function getCommentVotedMemberList()
	{
		$comment_srl = Context::get('comment_srl');
		if(!$comment_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$point = Context::get('point');
		if($point != -1)
		{
			$point = 1;
		}

		$oCommentModel = getModel('comment');
		$oComment = $oCommentModel->getComment($comment_srl, FALSE, FALSE);
		$module_srl = $oComment->get('module_srl');
		if(!$module_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$oModuleModel = getModel('module');
		$comment_config = $oModuleModel->getModulePartConfig('comment', $module_srl);

		$args = new stdClass();

		if($point == -1)
		{
			if($comment_config->use_vote_down != 'S')
			{
				return new Object(-1, 'msg_invalid_request');
			}

			$args->below_point = 0;
		}
		else
		{
			if($comment_config->use_vote_up != 'S')
			{
				return new Object(-1, 'msg_invalid_request');
			}

			$args->more_point = 0;
		}

		$args->comment_srl = $comment_srl;
		$output = executeQueryArray('comment.getVotedMemberList', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$oMemberModel = getModel('member');
		if($output->data)
		{
			foreach($output->data as $k => $d)
			{
				$profile_image = $oMemberModel->getProfileImage($d->member_srl);
				$output->data[$k]->src = $profile_image->src;
			}
		}

		$this->add('voted_member_list', $output->data);
	}

	/**
	 * Return a secret status by secret field
	 * @return array
	 */
	function getSecretNameList()
	{
		global $lang;

		if(!isset($lang->secret_name_list))
		{
			return array('Y' => 'Secret', 'N' => 'Public');
		}
		else
		{
			return $lang->secret_name_list;
		}
	}

	/**
	 * Get the total number of comments in corresponding with member_srl.
	 * @param int $member_srl
	 * @return int
	 */
	function getCommentCountByMemberSrl($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('comment.getCommentCountByMemberSrl', $args);
		return (int) $output->data->count;
	}


	/**
	 * Get comment list of the doc in corresponding woth member_srl.
	 * @param int $member_srl
	 * @param array $columnList
	 * @param int $page
	 * @param bool $is_admin
	 * @param int $count
	 * @return object
	 */
	function getCommentListByMemberSrl($member_srl, $columnList = array(), $page = 0, $is_admin = FALSE, $count = 0)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->list_count = $count;
		$output = executeQuery('comment.getCommentListByMemberSrl', $args, $columnList);
		$comment_list = $output->data;

		if(!$comment_list) return array();
		if(!is_array($comment_list)) $comment_list = array($comment_list);

		return $comment_list;

	}

}
/* End of file comment.model.php */
/* Location: ./modules/comment/comment.model.php */
