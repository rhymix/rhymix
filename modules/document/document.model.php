<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * documentModel class
 * model class of the module document
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/document
 * @version 0.1
 */
class documentModel extends document
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * document checked the permissions on the session values
	 * @param int $document_srl
	 * @return void
	 */
	function isGranted($document_srl)
	{
		return $_SESSION['own_document'][$document_srl];
	}

	/**
	 * Return document extra information from database
	 * @param array $documentSrls
	 * @return object
	 */
	function getDocumentExtraVarsFromDB($documentSrls)
	{
		if(!is_array($documentSrls) || count($documentSrls) == 0)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$args = new stdClass();
		$args->document_srl = $documentSrls;
		$output = executeQueryArray('document.getDocumentExtraVars', $args);
		return $output;
	}

	/**
	 * Extra variables for each article will not be processed bulk select and apply the macro city
	 * @return void
	 */
	function setToAllDocumentExtraVars()
	{
		static $checked_documents = array();
		$_document_list = &$GLOBALS['XE_DOCUMENT_LIST'];

		// XE XE_DOCUMENT_LIST all documents that the object referred to the global variable settings
		if(count($_document_list) <= 0) return;

		// Find all called the document object variable has been set extension
		$document_srls = array();
		foreach($_document_list as $key => $val)
		{
			if(!$val->document_srl || $checked_documents[$val->document_srl]) continue;
			$checked_documents[$val->document_srl] = true;
			$document_srls[] = $val->document_srl;
		}
		// If the document number, return detected
		if(!count($document_srls)) return;
		// Expand variables mijijeongdoen article about a current visitor to the extension of the language code, the search variable
		//$obj->document_srl = implode(',',$document_srls);
		$output = $this->getDocumentExtraVarsFromDB($document_srls);
		if($output->toBool() && $output->data)
		{
			foreach($output->data as $key => $val)
			{
				if(!isset($val->value)) continue;
				if(!$extra_vars[$val->module_srl][$val->document_srl][$val->var_idx][0]) $extra_vars[$val->module_srl][$val->document_srl][$val->var_idx][0] = trim($val->value);
				$extra_vars[$val->document_srl][$val->var_idx][$val->lang_code] = trim($val->value);
			}
		}

		$user_lang_code = Context::getLangType();
		for($i=0,$c=count($document_srls);$i<$c;$i++)
		{
			$document_srl = $document_srls[$i];
			unset($vars);

			if(!$_document_list[$document_srl] || !is_object($_document_list[$document_srl]) || !$_document_list[$document_srl]->isExists()) continue;
			$module_srl = $_document_list[$document_srl]->get('module_srl');
			$extra_keys = $this->getExtraKeys($module_srl);
			$vars = $extra_vars[$document_srl];
			$document_lang_code = $_document_list[$document_srl]->get('lang_code');
			// Expand the variable processing
			if(count($extra_keys))
			{
				foreach($extra_keys as $idx => $key)
				{
					$extra_keys[$idx] = clone($key);
					$val = $vars[$idx];
					if(isset($val[$user_lang_code])) $v = $val[$user_lang_code];
					else if(isset($val[$document_lang_code])) $v = $val[$document_lang_code];
					else if(isset($val[0])) $v = $val[0];
					else $v = null;
					$extra_keys[$idx]->value = $v;
				}
			}

			unset($evars);
			$evars = new ExtraVar($module_srl);
			$evars->setExtraVarKeys($extra_keys);
			// Title Processing
			if($vars[-1][$user_lang_code]) $_document_list[$document_srl]->add('title',$vars[-1][$user_lang_code]);
			// Information processing
			if($vars[-2][$user_lang_code]) $_document_list[$document_srl]->add('content',$vars[-2][$user_lang_code]);

			if($vars[-1][$user_lang_code] || $vars[-2][$user_lang_code])
			{
				unset($checked_documents[$document_srl]);
			}

			$GLOBALS['XE_EXTRA_VARS'][$document_srl] = $evars->getExtraVars();
		}
	}

	/**
	 * Import Document
	 * @param int $document_srl
	 * @param bool $is_admin
	 * @param bool $load_extra_vars
	 * @param array $columnList
	 * @return documentItem
	 */
	function getDocument($document_srl=0, $is_admin = false, $load_extra_vars=true, $columnList = array())
	{
		if(!$document_srl) return new documentItem();

		if(!$GLOBALS['XE_DOCUMENT_LIST'][$document_srl])
		{
			$oDocument = new documentItem($document_srl, $load_extra_vars, $columnList);
			$GLOBALS['XE_DOCUMENT_LIST'][$document_srl] = $oDocument;
			if($load_extra_vars) $this->setToAllDocumentExtraVars();
		}
		if($is_admin) $GLOBALS['XE_DOCUMENT_LIST'][$document_srl]->setGrant();

		return $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
	}

	/**
	 * Bringing multiple documents (or paging)
	 * @param array|string $document_srls
	 * @param bool $is_admin
	 * @param bool $load_extra_vars
	 * @param array $columnList
	 * @return array value type is documentItem
	 */
	function getDocuments($document_srls, $is_admin = false, $load_extra_vars=true, $columnList = array())
	{
		if(is_array($document_srls))
		{
			$list_count = count($document_srls);
			$document_srls = implode(',',$document_srls);
		}
		else
		{
			$list_count = 1;
		}
		$args = new stdClass();
		$args->document_srls = $document_srls;
		$args->list_count = $list_count;
		$args->order_type = 'asc';

		$output = executeQuery('document.getDocuments', $args, $columnList);
		$document_list = $output->data;
		if(!$document_list) return;
		if(!is_array($document_list)) $document_list = array($document_list);

		$document_count = count($document_list);
		foreach($document_list as $key => $attribute)
		{
			$document_srl = $attribute->document_srl;
			if(!$document_srl) continue;

			if(!$GLOBALS['XE_DOCUMENT_LIST'][$document_srl])
			{
				$oDocument = null;
				$oDocument = new documentItem();
				$oDocument->setAttribute($attribute, false);
				if($is_admin) $oDocument->setGrant();
				$GLOBALS['XE_DOCUMENT_LIST'][$document_srl] = $oDocument;
			}

			$result[$attribute->document_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
		}

		if($load_extra_vars) $this->setToAllDocumentExtraVars();

		$output = null;
		if(count($result))
		{
			foreach($result as $document_srl => $val)
			{
				$output[$document_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
			}
		}

		return $output;
	}

	/**
	 * Module_srl value, bringing the list of documents
	 * @param object $obj
	 * @param bool $except_notice
	 * @param bool $load_extra_vars
	 * @param array $columnList
	 * @return Object
	 */
	function getDocumentList($obj, $except_notice = false, $load_extra_vars=true, $columnList = array())
	{
		$sort_check = $this->_setSortIndex($obj, $load_extra_vars);
		$obj->sort_index = $sort_check->sort_index;
		$obj->isExtraVars = $sort_check->isExtraVars;

		$this->_setSearchOption($obj, $args, $query_id, $use_division);

		if ($sort_check->isExtraVars && substr_count($obj->search_target,'extra_vars'))
		{
			$query_id = 'document.getDocumentListWithinExtraVarsExtraSort';
			$args->sort_index = str_replace('documents.','',$args->sort_index);
			$output = executeQueryArray($query_id, $args);
		}
		elseif ($sort_check->isExtraVars)
		{
			$output = executeQueryArray($query_id, $args);
		}
		else
		{
			// document.getDocumentList query execution
			// Query_id if you have a group by clause getDocumentListWithinTag getDocumentListWithinComment or used again to perform the query because
			$groupByQuery = array('document.getDocumentListWithinComment' => 1, 'document.getDocumentListWithinTag' => 1, 'document.getDocumentListWithinExtraVars' => 1);
			if(isset($groupByQuery[$query_id]))
			{
				$group_args = clone($args);
				$group_args->sort_index = 'documents.'.$args->sort_index;
				$output = executeQueryArray($query_id, $group_args);
				if(!$output->toBool()||!count($output->data)) return $output;

				foreach($output->data as $key => $val)
				{
					if($val->document_srl) $target_srls[] = $val->document_srl;
				}

				$page_navigation = $output->page_navigation;
				$keys = array_keys($output->data);
				$virtual_number = $keys[0];

				$target_args = new stdClass();
				$target_args->document_srls = implode(',',$target_srls);
				$target_args->list_order = $args->sort_index;
				$target_args->order_type = $args->order_type;
				$target_args->list_count = $args->list_count;
				$target_args->page = 1;
				$output = executeQueryArray('document.getDocuments', $target_args);
				$output->page_navigation = $page_navigation;
				$output->total_count = $page_navigation->total_count;
				$output->total_page = $page_navigation->total_page;
				$output->page = $page_navigation->cur_page;
			}
			else
			{
				$output = executeQueryArray($query_id, $args, $columnList);
			}
		}
		// Return if no result or an error occurs
		if(!$output->toBool()||!count($output->data)) return $output;
		$idx = 0;
		$data = $output->data;
		unset($output->data);

		if(!isset($virtual_number))
		{
			$keys = array_keys($data);
			$virtual_number = $keys[0];
		}

		if($except_notice)
		{
			foreach($data as $key => $attribute)
			{
				if($attribute->is_notice == 'Y') $virtual_number --;
			}
		}

		foreach($data as $key => $attribute)
		{
			if($except_notice && $attribute->is_notice == 'Y') continue;
			$document_srl = $attribute->document_srl;
			if(!$GLOBALS['XE_DOCUMENT_LIST'][$document_srl])
			{
				$oDocument = null;
				$oDocument = new documentItem();
				$oDocument->setAttribute($attribute, false);
				if($is_admin) $oDocument->setGrant();
				$GLOBALS['XE_DOCUMENT_LIST'][$document_srl] = $oDocument;
			}

			$output->data[$virtual_number] = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
			$virtual_number--;
		}

		if($load_extra_vars) $this->setToAllDocumentExtraVars();

		if(count($output->data))
		{
			foreach($output->data as $number => $document)
			{
				$output->data[$number] = $GLOBALS['XE_DOCUMENT_LIST'][$document->document_srl];
			}
		}

		return $output;
	}

	/**
	 * Module_srl value, bringing the document's gongjisa Port
	 * @param object $obj
	 * @param array $columnList
	 * @return object|void
	 */
	function getNoticeList($obj, $columnList = array())
	{
		$args = new stdClass();
		$args->module_srl = $obj->module_srl;
		$args->category_srl= $obj->category_srl;
		$output = executeQueryArray('document.getNoticeList', $args, $columnList);
		if(!$output->toBool()||!$output->data) return;

		foreach($output->data as $key => $val)
		{
			$document_srl = $val->document_srl;
			if(!$document_srl) continue;

			if(!$GLOBALS['XE_DOCUMENT_LIST'][$document_srl])
			{
				$oDocument = null;
				$oDocument = new documentItem();
				$oDocument->setAttribute($val, false);
				$GLOBALS['XE_DOCUMENT_LIST'][$document_srl] = $oDocument;
			}
			$result->data[$document_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
		}
		$this->setToAllDocumentExtraVars();

		foreach($result->data as $document_srl => $val)
		{
			$result->data[$document_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
		}

		return $result;
	}

	/**
	 * Function to retrieve the key values of the extended variable document
	 * $Form_include: writing articles whether to add the necessary extensions of the variable input form
	 * @param int $module_srl
	 * @return array
	 */
	function getExtraKeys($module_srl)
	{
		if(!isset($GLOBALS['XE_EXTRA_KEYS'][$module_srl]))
		{
			$keys = false;
			$oCacheHandler = CacheHandler::getInstance('object', null, true);
			if($oCacheHandler->isSupport())
			{
				$object_key = 'module_document_extra_keys:' . $module_srl;
				$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
				$keys = $oCacheHandler->get($cache_key);
			}

			$oExtraVar = ExtraVar::getInstance($module_srl);

			if($keys === false)
			{
				$obj = new stdClass();
				$obj->module_srl = $module_srl;
				$obj->sort_index = 'var_idx';
				$obj->order = 'asc';
				$output = executeQueryArray('document.getDocumentExtraKeys', $obj);

				// correcting index order
				$isFixed = FALSE;
				if(is_array($output->data))
				{
					$prevIdx = 0;
					foreach($output->data as $no => $value)
					{
						// case first
						if($prevIdx == 0 && $value->idx != 1)
						{
							$args = new stdClass();
							$args->module_srl = $module_srl;
							$args->var_idx = $value->idx;
							$args->new_idx = 1;
							executeQuery('document.updateDocumentExtraKeyIdx', $args);
							executeQuery('document.updateDocumentExtraVarIdx', $args);
							$prevIdx = 1;
							$isFixed = TRUE;
							continue;
						}

						// case others
						if($prevIdx > 0 && $prevIdx + 1 != $value->idx)
						{
							$args = new stdClass();
							$args->module_srl = $module_srl;
							$args->var_idx = $value->idx;
							$args->new_idx = $prevIdx + 1;
							executeQuery('document.updateDocumentExtraKeyIdx', $args);
							executeQuery('document.updateDocumentExtraVarIdx', $args);
							$prevIdx += 1;
							$isFixed = TRUE;
							continue;
						}

						$prevIdx = $value->idx;
					}
				}

				if($isFixed)
				{
					$output = executeQueryArray('document.getDocumentExtraKeys', $obj);
				}

				$oExtraVar->setExtraVarKeys($output->data);
				$keys = $oExtraVar->getExtraVars();
				if(!$keys) $keys = array();

				if($oCacheHandler->isSupport())
				{
					$oCacheHandler->put($cache_key, $keys);
				}
			}


			$GLOBALS['XE_EXTRA_KEYS'][$module_srl] = $keys;
		}

		return $GLOBALS['XE_EXTRA_KEYS'][$module_srl];
	}

	/**
	 * A particular document to get the value of the extra variable function
	 * @param int $module_srl
	 * @param int $document_srl
	 * @return array
	 */
	function getExtraVars($module_srl, $document_srl)
	{
		if(!isset($GLOBALS['XE_EXTRA_VARS'][$document_srl]))
		{
			// Extended to extract the values of variables set
			$oDocument = $this->getDocument($document_srl, false);
			$GLOBALS['XE_DOCUMENT_LIST'][$document_srl] = $oDocument;
			$this->setToAllDocumentExtraVars();
		}
		if(is_array($GLOBALS['XE_EXTRA_VARS'][$document_srl])) ksort($GLOBALS['XE_EXTRA_VARS'][$document_srl]);
		return $GLOBALS['XE_EXTRA_VARS'][$document_srl];
	}

	/**
	 * Show pop-up menu of the selected posts
	 * Printing, scrap, recommendations and negative, reported the Add Features
	 * @return void
	 */
	function getDocumentMenu()
	{
		// Post number and the current login information requested Wanted
		$document_srl = Context::get('target_srl');
		$mid = Context::get('cur_mid');
		$logged_info = Context::get('logged_info');
		$act = Context::get('cur_act');
		// to menu_list "pyosihalgeul, target, url" put into an array
		$menu_list = array();
		// call trigger
		ModuleHandler::triggerCall('document.getDocumentMenu', 'before', $menu_list);

		$oDocumentController = getController('document');
		// Members must be a possible feature
		if($logged_info->member_srl)
		{
			$oDocumentModel = getModel('document');
			$columnList = array('document_srl', 'module_srl', 'member_srl', 'ipaddress');
			$oDocument = $oDocumentModel->getDocument($document_srl, false, false, $columnList);
			$module_srl = $oDocument->get('module_srl');
			$member_srl = $oDocument->get('member_srl');
			if(!$module_srl) return new Object(-1, 'msg_invalid_request');

			$oModuleModel = getModel('module');
			$document_config = $oModuleModel->getModulePartConfig('document',$module_srl);
			if($document_config->use_vote_up!='N' && $member_srl!=$logged_info->member_srl)
			{
				// Add a Referral Button
				$url = sprintf("doCallModuleAction('document','procDocumentVoteUp','%s')", $document_srl);
				$oDocumentController->addDocumentPopupMenu($url,'cmd_vote','','javascript');
			}

			if($document_config->use_vote_down!='N' && $member_srl!=$logged_info->member_srl)
			{
				// Add button to negative
				$url= sprintf("doCallModuleAction('document','procDocumentVoteDown','%s')", $document_srl);
				$oDocumentController->addDocumentPopupMenu($url,'cmd_vote_down','','javascript');
			}

			// Adding Report
			$url = sprintf("doCallModuleAction('document','procDocumentDeclare','%s')", $document_srl);
			$oDocumentController->addDocumentPopupMenu($url,'cmd_declare','','javascript');

			// Add Bookmark button
			$url = sprintf("doCallModuleAction('member','procMemberScrapDocument','%s')", $document_srl);
			$oDocumentController->addDocumentPopupMenu($url,'cmd_scrap','','javascript');
		}
		// Add print button
		$url = getUrl('','module','document','act','dispDocumentPrint','document_srl',$document_srl);
		$oDocumentController->addDocumentPopupMenu($url,'cmd_print','','printDocument');
		// Call a trigger (after)
		ModuleHandler::triggerCall('document.getDocumentMenu', 'after', $menu_list);
		if($this->grant->manager)
		{
			$str_confirm = Context::getLang('confirm_move');
			$url = sprintf("if(!confirm('%s')) return; var params = new Array(); params['document_srl']='%s'; params['mid']=current_mid;params['cur_url']=current_url; exec_xml('document', 'procDocumentAdminMoveToTrash', params)", $str_confirm, $document_srl);
			$oDocumentController->addDocumentPopupMenu($url,'cmd_trash','','javascript');
		}

		// If you are managing to find posts by ip
		if($logged_info->is_admin == 'Y')
		{
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);	//before setting document recycle

			if($oDocument->isExists())
			{
				// Find a post equivalent to ip address
				$url = getUrl('','module','admin','act','dispDocumentAdminList','search_target','ipaddress','search_keyword',$oDocument->getIpAddress());
				$oDocumentController->addDocumentPopupMenu($url,'cmd_search_by_ipaddress',$icon_path,'TraceByIpaddress');

				$url = sprintf("var params = new Array(); params['ipaddress_list']='%s'; exec_xml('spamfilter', 'procSpamfilterAdminInsertDeniedIP', params, completeCallModuleAction)", $oDocument->getIpAddress());
				$oDocumentController->addDocumentPopupMenu($url,'cmd_add_ip_to_spamfilter','','javascript');
			}
		}
		// Changing the language of pop-up menu
		$menus = Context::get('document_popup_menu_list');
		$menus_count = count($menus);
		for($i=0;$i<$menus_count;$i++)
		{
			$menus[$i]->str = Context::getLang($menus[$i]->str);
		}
		// Wanted to finally clean pop-up menu list
		$this->add('menus', $menus);
	}

	/**
	 * The total number of documents that are bringing
	 * @param int $module_srl
	 * @param object $search_obj
	 * @return int
	 */
	function getDocumentCount($module_srl, $search_obj = NULL)
	{
		if(is_null($search_obj)) $search_obj = new stdClass();
		$search_obj->module_srl = $module_srl;

		$output = executeQuery('document.getDocumentCount', $search_obj);
		// Return total number of
		$total_count = $output->data->count;
		return (int)$total_count;
	}

	/**
	 * the total number of documents that are bringing
	 * @param object $search_obj
	 * @return array
	 */
	function getDocumentCountByGroupStatus($search_obj = NULL)
	{
		$output = executeQuery('document.getDocumentCountByGroupStatus', $search_obj);
		if(!$output->toBool()) return array();

		return $output->data;
	}

	function getDocumentExtraVarsCount($module_srl, $search_obj = NULL)
	{
		// Additional search options
		$args->module_srl = $module_srl;

		$args->category_srl = $search_obj->category_srl;
		$args->var_idx = $search_obj->s_var_idx;
		$args->var_eid = $search_obj->s_var_eid;
		$args->var_value = $search_obj->s_var_value;
		$args->var_lang_code = Context::getLangType();

		$output = executeQuery('document.getDocumentExtraVarsCount', $args);
		// Return total number of
		$total_count = $output->data->count;
		return (int)$total_count;
	}

	/**
	 * Import page of the document, module_srl Without throughout ..
	 * @param documentItem $oDocument
	 * @param object $opt
	 * @return int
	 */
	function getDocumentPage($oDocument, $opt)
	{
		$sort_check = $this->_setSortIndex($opt, TRUE);
		$opt->sort_index = $sort_check->sort_index;
		$opt->isExtraVars = $sort_check->isExtraVars;

		$this->_setSearchOption($opt, $args, $query_id, $use_division);

		if($sort_check->isExtraVars)
		{
			return 1;
		}
		else
		{
			if($sort_check->sort_index === 'list_order' || $sort_check->sort_index === 'update_order')
			{
				if($args->order_type === 'desc')
				{
					$args->{'rev_' . $sort_check->sort_index} = $oDocument->get($sort_check->sort_index);
				}
				else
				{
					$args->{$sort_check->sort_index} = $oDocument->get($sort_check->sort_index);
				}
			}
			else
			{
				return 1;
			}
		}

		// Guhanhu total number of the article search page
		$output = executeQuery($query_id . 'Page', $args);
		$count = $output->data->count;
		$page = (int)(($count-1)/$opt->list_count)+1;
		return $page;
	}

	/**
	 * Imported Category of information
	 * @param int $category_srl
	 * @param array $columnList
	 * @return object
	 */
	function getCategory($category_srl, $columnList = array())
	{
		$args =new stdClass();
		$args->category_srl = $category_srl;
		$output = executeQuery('document.getCategory', $args, $columnList);

		$node = $output->data;
		if(!$node) return;

		if($node->group_srls)
		{
			$group_srls = explode(',',$node->group_srls);
			unset($node->group_srls);
			$node->group_srls = $group_srls;
		}
		else
		{
			unset($node->group_srls);
			$node->group_srls = array();
		}
		return $node;
	}

	/**
	 * Check whether the child has a specific category
	 * @param int $category_srl
	 * @return bool
	 */
	function getCategoryChlidCount($category_srl)
	{
		$args = new stdClass();
		$args->category_srl = $category_srl;
		$output = executeQuery('document.getChildCategoryCount',$args);
		if($output->data->count > 0) return true;
		return false;
	}

	/**
	 * Bringing the Categories list the specific module
	 * Speed and variety of categories, considering the situation created by the php script to include a list of the must, in principle, to use
	 * @param int $module_srl
	 * @param array $columnList
	 * @return array
	 */
	function getCategoryList($module_srl, $columnList = array())
	{
		// Category of the target module file swollen
		$filename = sprintf("%sfiles/cache/document_category/%s.php", _XE_PATH_, $module_srl);
		// If the target file to the cache file regeneration category
		if(!file_exists($filename))
		{
			$oDocumentController = getController('document');
			if(!$oDocumentController->makeCategoryFile($module_srl)) return array();
		}

		include($filename);

		// Cleanup of category
		$document_category = array();
		$this->_arrangeCategory($document_category, $menu->list, 0);
		return $document_category;
	}

	/**
	 * Category within a primary method to change the array type
	 * @param array $document_category
	 * @param array $list
	 * @param int $depth
	 * @return void
	 */
	function _arrangeCategory(&$document_category, $list, $depth)
	{
		if(!count($list)) return;
		$idx = 0;
		$list_order = array();
		foreach($list as $key => $val)
		{
			$obj = new stdClass;
			$obj->mid = $val['mid'];
			$obj->module_srl = $val['module_srl'];
			$obj->category_srl = $val['category_srl'];
			$obj->parent_srl = $val['parent_srl'];
			$obj->title = $obj->text = $val['text'];
			$obj->description = $val['description'];
			$obj->expand = $val['expand']=='Y'?true:false;
			$obj->color = $val['color'];
			$obj->document_count = $val['document_count'];
			$obj->depth = $depth;
			$obj->child_count = 0;
			$obj->childs = array();
			$obj->grant = $val['grant'];

			if(Context::get('mid') == $obj->mid && Context::get('category') == $obj->category_srl) $selected = true;
			else $selected = false;

			$obj->selected = $selected;

			$list_order[$idx++] = $obj->category_srl;
			// If you have a parent category of child nodes apply data
			if($obj->parent_srl)
			{
				$parent_srl = $obj->parent_srl;
				$document_count = $obj->document_count;
				$expand = $obj->expand;
				if($selected) $expand = true;

				while($parent_srl)
				{
					$document_category[$parent_srl]->document_count += $document_count;
					$document_category[$parent_srl]->childs[] = $obj->category_srl;
					$document_category[$parent_srl]->child_count = count($document_category[$parent_srl]->childs);
					if($expand) $document_category[$parent_srl]->expand = $expand;

					$parent_srl = $document_category[$parent_srl]->parent_srl;
				}
			}

			$document_category[$key] = $obj;

			if(count($val['list'])) $this->_arrangeCategory($document_category, $val['list'], $depth+1);
		}
		$document_category[$list_order[0]]->first = true;
		$document_category[$list_order[count($list_order)-1]]->last = true;
	}

	/**
	 * Wanted number of documents belonging to category
	 * @param int $module_srl
	 * @param int $category_srl
	 * @return int
	 */
	function getCategoryDocumentCount($module_srl, $category_srl)
	{
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->category_srl = $category_srl;
		$output = executeQuery('document.getCategoryDocumentCount', $args);
		return (int)$output->data->count;
	}

	/**
	 * Xml cache file of the document category return information
	 * @param int $module_srl
	 * @return string
	 */
	function getCategoryXmlFile($module_srl)
	{
		$xml_file = sprintf('files/cache/document_category/%s.xml.php',$module_srl);
		if(!file_exists($xml_file))
		{
			$oDocumentController = getController('document');
			$oDocumentController->makeCategoryFile($module_srl);
		}
		return $xml_file;
	}

	/**
	 * Php cache files in the document category return information
	 * @param int $module_srl
	 * @return string
	 */
	function getCategoryPhpFile($module_srl)
	{
		$php_file = sprintf('files/cache/document_category/%s.php',$module_srl);
		if(!file_exists($php_file))
		{
			$oDocumentController = getController('document');
			$oDocumentController->makeCategoryFile($module_srl);
		}
		return $php_file;
	}

	/**
	 * Imported post monthly archive status
	 * @param object $obj
	 * @return object
	 */
	function getMonthlyArchivedList($obj)
	{
		if($obj->mid)
		{
			$oModuleModel = getModel('module');
			$obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
			unset($obj->mid);
		}
		// Module_srl passed the array may be a check whether the array
		$args = new stdClass;
		if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
		else $args->module_srl = $obj->module_srl;

		$output = executeQuery('document.getMonthlyArchivedList', $args);
		if(!$output->toBool()||!$output->data) return $output;

		if(!is_array($output->data)) $output->data = array($output->data);

		return $output;
	}

	/**
	 * Bringing a month on the status of the daily posts
	 * @param object $obj
	 * @return object
	 */
	function getDailyArchivedList($obj)
	{
		if($obj->mid)
		{
			$oModuleModel = getModel('module');
			$obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
			unset($obj->mid);
		}
		// Module_srl passed the array may be a check whether the array
		$args = new stdClass;
		if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
		else $args->module_srl = $obj->module_srl;
		$args->regdate = $obj->regdate;

		$output = executeQuery('document.getDailyArchivedList', $args);
		if(!$output->toBool()) return $output;

		if(!is_array($output->data)) $output->data = array($output->data);

		return $output;
	}

	/**
	 * Get a list for a particular module
	 * @return void|Object
	 */
	function getDocumentCategories()
	{
		if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
		$module_srl = Context::get('module_srl');
		$categories= $this->getCategoryList($module_srl);
		$lang = Context::get('lang');
		// No additional category
		$output = "0,0,{$lang->none_category}\n";
		if($categories)
		{
			foreach($categories as $category_srl => $category)
			{
				$output .= sprintf("%d,%d,%s\n",$category_srl, $category->depth,$category->title);
			}
		}
		$this->add('categories', $output);
	}

	/**
	 * Wanted to set document information
	 * @return object
	 */
	function getDocumentConfig()
	{
		if(!$GLOBALS['__document_config__'])
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('document');

			if(!$config) $config = new stdClass();
			if(!$config->thumbnail_type) $config->thumbnail_type = 'crop';
			$GLOBALS['__document_config__'] = $config;
		}
		return $GLOBALS['__document_config__'];
	}

	/**
	 * Common:: Module extensions of variable management
	 * Expansion parameter management module in the document module instance, when using all the modules available
	 * @param int $module_srl
	 * @return string
	 */
	function getExtraVarsHTML($module_srl)
	{
		// Bringing existing extra_keys
		$extra_keys = $this->getExtraKeys($module_srl);
		Context::set('extra_keys', $extra_keys);
		$security = new Security();
		$security->encodeHTML('extra_keys..');

		// Get information of module_grants
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($this->module_path.'tpl', 'extra_keys');
	}

	/**
	 * Common:: Category parameter management module
	 * @param int $module_srl
	 * @return string
	 */
	function getCategoryHTML($module_srl)
	{
		$category_xml_file = $this->getCategoryXmlFile($module_srl);

		Context::set('category_xml_file', $category_xml_file);

		Context::loadJavascriptPlugin('ui.tree');

		// Get a list of member groups
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups($module_info->site_srl);
		Context::set('group_list', $group_list);

		$security = new Security();
		$security->encodeHTML('group_list..title');

		// Get information of module_grants
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($this->module_path.'tpl', 'category_list');
	}

	/**
	 * Certain categories of information, return the template guhanhu
	 * Manager on the page to add information about a particular menu from the server after compiling tpl compiled a direct return html
	 * @return void|Object
	 */
	function getDocumentCategoryTplInfo()
	{
		$oModuleModel = getModel('module');
		$oMemberModel = getModel('member');
		// Get information on the menu for the parameter settings
		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		// Check permissions
		$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
		if(!$grant->manager) return new Object(-1,'msg_not_permitted');

		$category_srl = Context::get('category_srl');
		$category_info = $this->getCategory($category_srl);
		if(!$category_info)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$this->add('category_info', $category_info);
	}

	/**
	 * Return docuent data by alias
	 * @param string $mid
	 * @param string $alias
	 * @return int|void
	 */
	function getDocumentSrlByAlias($mid, $alias)
	{
		if(!$mid || !$alias) return null;
		$site_module_info = Context::get('site_module_info');
		$args = new stdClass;
		$args->mid = $mid;
		$args->alias_title = $alias;
		$args->site_srl = $site_module_info->site_srl;
		$output = executeQuery('document.getDocumentSrlByAlias', $args);
		if(!$output->data) return null;
		else return $output->data->document_srl;
	}

	/**
	 * Return docuent number by document title
	 * @param int $module_srl
	 * @param string $title
	 * @return int|void
	 */
	function getDocumentSrlByTitle($module_srl, $title)
	{
		if(!$module_srl || !$title) return null;
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->title = $title;
		$output = executeQuery('document.getDocumentSrlByTitle', $args);
		if(!$output->data) return null;
		else
		{
			if(is_array($output->data)) return $output->data[0]->document_srl;
			return $output->data->document_srl;
		}
	}

	/**
	 * Return docuent's alias
	 * @param int $document_srl
	 * @return string|void
	 */
	function getAlias($document_srl)
	{
		if(!$document_srl) return null;
		$args = new stdClass;
		$args->document_srl = $document_srl;
		$output = executeQueryArray('document.getAliases', $args);

		if(!$output->data) return null;
		else return $output->data[0]->alias_title;
	}

	/**
	 * Return document's history list
	 * @param int $document_srl
	 * @param int $list_count
	 * @param int $page
	 * @return object
	 */
	function getHistories($document_srl, $list_count, $page)
	{
		$args = new stdClass;
		$args->list_count = $list_count;
		$args->page = $page;
		$args->document_srl = $document_srl;
		$output = executeQueryArray('document.getHistories', $args);
		return $output;
	}

	/**
	 * Return document's history
	 * @param int $history_srl
	 * @return object
	 */
	function getHistory($history_srl)
	{
		$args = new stdClass;
		$args->history_srl = $history_srl;
		$output = executeQuery('document.getHistory', $args);
		return $output->data;
	}

	/**
	 * Module_srl value, bringing the list of documents
	 * @param object $obj
	 * @return object
	 */
	function getTrashList($obj)
	{
		// Variable check
		$args = new stdClass;
		$args->category_srl = $obj->category_srl?$obj->category_srl:null;
		$args->sort_index = $obj->sort_index;
		$args->order_type = $obj->order_type?$obj->order_type:'desc';
		$args->page = $obj->page?$obj->page:1;
		$args->list_count = $obj->list_count?$obj->list_count:20;
		$args->page_count = $obj->page_count?$obj->page_count:10;
		// Search options
		$search_target = $obj->search_target;
		$search_keyword = $obj->search_keyword;
		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'title' :
				case 'content' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->{"s_".$search_target} = $search_keyword;
					$use_division = true;
					break;
				case 'title_content' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_title = $search_keyword;
					$args->s_content = $search_keyword;
					break;
				case 'user_id' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_id = $search_keyword;
					$args->sort_index = 'documents.'.$args->sort_index;
					break;
				case 'user_name' :
				case 'nick_name' :
				case 'email_address' :
				case 'homepage' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->{"s_".$search_target} = $search_keyword;
					break;
				case 'is_notice' :
				case 'is_secret' :
					if($search_keyword=='N') $args->statusList = array($this->getConfigStatus('public'));
					elseif($search_keyword=='Y') $args->statusList = array($this->getConfigStatus('secret'));
					break;
				case 'member_srl' :
				case 'readed_count' :
				case 'voted_count' :
				case 'blamed_count' :
				case 'comment_count' :
				case 'trackback_count' :
				case 'uploaded_count' :
					$args->{"s_".$search_target} = (int)$search_keyword;
					break;
				case 'regdate' :
				case 'last_update' :
				case 'ipaddress' :
				case 'tag' :
					$args->{"s_".$search_target} = $search_keyword;
					break;
			}
		}

		$output = executeQueryArray('document.getTrashList', $args);
		if($output->data)
		{
			foreach($output->data as $key => $attribute)
			{
				$oDocument = null;
				$oDocument = new documentItem();
				$oDocument->setAttribute($attribute, false);
				$attribute = $oDocument;
			}
		}
		return $output;
	}

	/**
	 * vote up, vote down member list in Document View page
	 * @return void|Object
	 */
	function getDocumentVotedMemberList()
	{
		$args = new stdClass;
		$document_srl = Context::get('document_srl');
		if(!$document_srl) return new Object(-1,'msg_invalid_request');

		$point = Context::get('point');
		if($point != -1) $point = 1;

		$oDocumentModel = getModel('document');
		$columnList = array('document_srl', 'module_srl');
		$oDocument = $oDocumentModel->getDocument($document_srl, false, false, $columnList);
		$module_srl = $oDocument->get('module_srl');
		if(!$module_srl) return new Object(-1, 'msg_invalid_request');

		$oModuleModel = getModel('module');
		$document_config = $oModuleModel->getModulePartConfig('document',$module_srl);
		if($point == -1)
		{
			if($document_config->use_vote_down!='S') return new Object(-1, 'msg_invalid_request');
			$args->below_point = 0;
		}
		else
		{
			if($document_config->use_vote_up!='S') return new Object(-1, 'msg_invalid_request');
			$args->more_point = 0;
		}

		$args->document_srl = $document_srl;

		$output = executeQueryArray('document.getVotedMemberList',$args);
		if(!$output->toBool()) return $output;

		$oMemberModel = getModel('member');
		if($output->data)
		{
			foreach($output->data as $k => $d)
			{
				$profile_image = $oMemberModel->getProfileImage($d->member_srl);
				$output->data[$k]->src = $profile_image->src;
			}
		}

		$this->add('voted_member_list',$output->data);
	}

	/**
	 * Return status name list
	 * @return array
	 */
	function getStatusNameList()
	{
		global $lang;
		if(!isset($lang->status_name_list))
			return array_flip($this->getStatusList());
		else return $lang->status_name_list;
	}

	/**
	 * Setting sort index
	 * @param object $obj
	 * @param bool $load_extra_vars
	 * @return object
	 */
	function _setSortIndex($obj, $load_extra_vars)
	{
		$sortIndex = $obj->sort_index;
		$isExtraVars = false;
		if(!in_array($sortIndex, array('list_order','regdate','last_update','update_order','readed_count','voted_count','blamed_count','comment_count','trackback_count','uploaded_count','title','category_srl')))
		{
			// get module_srl extra_vars list
			if ($load_extra_vars)
			{
				$extra_args = new stdClass();
				$extra_args->module_srl = $obj->module_srl;
				$extra_output = executeQueryArray('document.getGroupsExtraVars', $extra_args);
				if (!$extra_output->data || !$extra_output->toBool())
				{
					$sortIndex = 'list_order';
				}
				else
				{
					$check_array = array();
					foreach($extra_output->data as $val)
					{
						$check_array[] = $val->eid;
					}
					if(!in_array($sortIndex, $check_array)) $sortIndex = 'list_order';
					else $isExtraVars = true;
				}
			}
			else
				$sortIndex = 'list_order';
		}
		$returnObj = new stdClass();
		$returnObj->sort_index = $sortIndex;
		$returnObj->isExtraVars = $isExtraVars;

		return $returnObj;
	}

	/**
	 * 게시물 목록의 검색 옵션을 Setting함(2011.03.08 - cherryfilter)
	 * page변수가 없는 상태에서 page 값을 알아오는 method(getDocumentPage)는 검색하지 않은 값을 return해서 검색한 값을 가져오도록 검색옵션이 추가 됨.
	 * 검색옵션의 중복으로 인해 private method로 별도 분리
	 * @param object $searchOpt
	 * @param object $args
	 * @param string $query_id
	 * @param bool $use_division
	 * @return void
	 */
	function _setSearchOption($searchOpt, &$args, &$query_id, &$use_division)
	{
		// Variable check
		$args = new stdClass();
		$args->category_srl = $searchOpt->category_srl?$searchOpt->category_srl:null;
		$args->order_type = $searchOpt->order_type;
		$args->page = $searchOpt->page?$searchOpt->page:1;
		$args->list_count = $searchOpt->list_count?$searchOpt->list_count:20;
		$args->page_count = $searchOpt->page_count?$searchOpt->page_count:10;
		$args->start_date = $searchOpt->start_date?$searchOpt->start_date:null;
		$args->end_date = $searchOpt->end_date?$searchOpt->end_date:null;
		$args->member_srl = $searchOpt->member_srl;

		$logged_info = Context::get('logged_info');

		$args->sort_index = $searchOpt->sort_index;
		
		// Check the target and sequence alignment
		$orderType = array('desc' => 1, 'asc' => 1);
		if(!isset($orderType[$args->order_type])) $args->order_type = 'asc';

		// If that came across mid module_srl instead of a direct module_srl guhaejum
		if($searchOpt->mid)
		{
			$oModuleModel = getModel('module');
			$args->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
			unset($searchOpt->mid);
		}

		// Module_srl passed the array may be a check whether the array
		if(is_array($searchOpt->module_srl)) $args->module_srl = implode(',', $searchOpt->module_srl);
		else $args->module_srl = $searchOpt->module_srl;

		// Except for the test module_srl
		if(is_array($searchOpt->exclude_module_srl)) $args->exclude_module_srl = implode(',', $searchOpt->exclude_module_srl);
		else $args->exclude_module_srl = $searchOpt->exclude_module_srl;

		// only admin document list, temp document showing
		if($searchOpt->statusList) $args->statusList = $searchOpt->statusList;
		else
		{
			if($logged_info->is_admin == 'Y' && !$searchOpt->module_srl)
				$args->statusList = array($this->getConfigStatus('secret'), $this->getConfigStatus('public'), $this->getConfigStatus('temp'));
			else
				$args->statusList = array($this->getConfigStatus('secret'), $this->getConfigStatus('public'));
		}

		// Category is selected, further sub-categories until all conditions
		if($args->category_srl)
		{
			$category_list = $this->getCategoryList($args->module_srl);
			$category_info = $category_list[$args->category_srl];
			$category_info->childs[] = $args->category_srl;
			$args->category_srl = implode(',',$category_info->childs);
		}

		// Used to specify the default query id (based on several search options to query id modified)
		$query_id = 'document.getDocumentList';

		// If the search by specifying the document division naeyonggeomsaekil processed for
		$use_division = false;

		// Search options
		$search_target = $searchOpt->search_target;
		$search_keyword = $searchOpt->search_keyword;

		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'title' :
				case 'content' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->{"s_".$search_target} = $search_keyword;
					$use_division = true;
					break;
				case 'title_content' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_title = $search_keyword;
					$args->s_content = $search_keyword;
					$use_division = true;
					break;
				case 'user_id' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_id = $search_keyword;
					$args->sort_index = 'documents.'.$args->sort_index;
					break;
				case 'user_name' :
				case 'nick_name' :
				case 'email_address' :
				case 'homepage' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->{"s_".$search_target} = $search_keyword;
					break;
				case 'is_notice' :
					if($search_keyword=='N') $args->{"s_".$search_target} = 'N';
					elseif($search_keyword=='Y') $args->{"s_".$search_target} = 'Y';
					else $args->{"s_".$search_target} = '';
					break;
				case 'is_secret' :
					if($search_keyword=='N') $args->statusList = array($this->getConfigStatus('public'));
					elseif($search_keyword=='Y') $args->statusList = array($this->getConfigStatus('secret'));
					elseif($search_keyword=='temp') $args->statusList = array($this->getConfigStatus('temp'));
					break;
				case 'member_srl' :
				case 'readed_count' :
				case 'voted_count' :
				case 'comment_count' :
				case 'trackback_count' :
				case 'uploaded_count' :
					$args->{"s_".$search_target} = (int)$search_keyword;
					break;
				case 'blamed_count' :
					$args->{"s_".$search_target} = (int)$search_keyword * -1;
					break;
				case 'regdate' :
				case 'last_update' :
				case 'ipaddress' :
					$args->{"s_".$search_target} = $search_keyword;
					break;
				case 'comment' :
					$args->s_comment = $search_keyword;
					$query_id = 'document.getDocumentListWithinComment';
					$use_division = true;
					break;
				case 'tag' :
					$args->s_tags = str_replace(' ','%',$search_keyword);
					$query_id = 'document.getDocumentListWithinTag';
					break;
				case 'extra_vars':
					$args->var_value = str_replace(' ', '%', $search_keyword);
					$query_id = 'document.getDocumentListWithinExtraVars';
					break;
				default :
					if(strpos($search_target,'extra_vars')!==false) {
						$args->var_idx = substr($search_target, strlen('extra_vars'));
						$args->var_value = str_replace(' ','%',$search_keyword);
						$args->sort_index = 'documents.'.$args->sort_index;
						$query_id = 'document.getDocumentListWithExtraVars';
					}
					break;
			}
		}

		if ($searchOpt->isExtraVars)
		{
			$query_id = 'document.getDocumentListExtraSort';
		}
		else
		{
			/**
			 * list_order asc sort of division that can be used only when
			 */
			if($args->sort_index != 'list_order' || $args->order_type != 'asc') $use_division = false;

			/**
			 * If it is true, use_division changed to use the document division
			 */
			if($use_division)
			{
				// Division begins
				$division = (int)Context::get('division');

				// order by list_order and (module_srl===0 or module_srl may count), therefore case table full scan
				if($args->sort_index == 'list_order' && ($args->exclude_module_srl === '0' || count(explode(',', $args->module_srl)) > 5))
				{
					$listSqlID = 'document.getDocumentListUseIndex';
					$divisionSqlID = 'document.getDocumentDivisionUseIndex';
				}
				else
				{
					$listSqlID = 'document.getDocumentList';
					$divisionSqlID = 'document.getDocumentDivision';
				}

				// If you do not value the best division top
				if(!$division)
				{
					$division_args = new stdClass();
					$division_args->module_srl = $args->module_srl;
					$division_args->exclude_module_srl = $args->exclude_module_srl;
					$division_args->list_count = 1;
					$division_args->sort_index = $args->sort_index;
					$division_args->order_type = $args->order_type;
					$division_args->statusList = $args->statusList;

					$output = executeQuery($divisionSqlID, $division_args, array('list_order'));
					if($output->data)
					{
						$item = array_pop($output->data);
						$division = $item->list_order;
					}
					$division_args = null;
				}

				// The last division
				$last_division = (int)Context::get('last_division');

				// Division after division from the 5000 value of the specified Wanted
				if(!$last_division)
				{
					$last_division_args = new stdClass();
					$last_division_args->module_srl = $args->module_srl;
					$last_division_args->exclude_module_srl = $args->exclude_module_srl;
					$last_division_args->list_count = 1;
					$last_division_args->sort_index = $args->sort_index;
					$last_division_args->order_type = $args->order_type;
					$last_division_args->list_order = $division;
					$last_division_args->page = 5001;

					$output = executeQuery($divisionSqlID, $last_division_args, array('list_order'));
					if($output->data)
					{
						$item = array_pop($output->data);
						$last_division = $item->list_order;
					}
				}

				// Make sure that after last_division article
				if($last_division)
				{
					$last_division_args = new stdClass();
					$last_division_args->module_srl = $args->module_srl;
					$last_division_args->exclude_module_srl = $args->exclude_module_srl;
					$last_division_args->list_order = $last_division;
					$output = executeQuery('document.getDocumentDivisionCount', $last_division_args);
					if($output->data->count<1) $last_division = null;
				}

				$args->division = $division;
				$args->last_division = $last_division;
				Context::set('division', $division);
				Context::set('last_division', $last_division);
			}
		}
	}

	/**
	 * Get the total number of Document in corresponding with member_srl.
	 * @param int $member_srl
	 * @return int
	 */
	function getDocumentCountByMemberSrl($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('document.getDocumentCountByMemberSrl', $args);
		return (int) $output->data->count;
	}

	/**
	 * Get document list of the doc in corresponding woth member_srl.
	 * @param int $member_srl
	 * @param array $columnList
	 * @param int $page
	 * @param bool $is_admin
	 * @param int $count
	 * @return object
	 */
	function getDocumentListByMemberSrl($member_srl, $columnList = array(), $page = 0, $is_admin = FALSE, $count = 0 )
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->list_count = $count;
		$output = executeQuery('document.getDocumentListByMemberSrl', $args, $columnList);
		$document_list = $output->data;
		
		if(!$document_list) return array();
		if(!is_array($document_list)) $document_list = array($document_list);

		return $document_list;	
	}
}
/* End of file document.model.php */
/* Location: ./modules/document/document.model.php */
