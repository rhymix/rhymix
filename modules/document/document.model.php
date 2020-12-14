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
	protected static $_config;

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
	public static function isGranted($document_srl)
	{
		return $_SESSION['granted_document'][$document_srl];
	}
	
	/**
	 * Return document extra information from database
	 * @param array $document_srls
	 * @return object
	 */
	public static function getDocumentExtraVarsFromDB($document_srls)
	{
		$args = new stdClass;
		$args->document_srl = $document_srls;
		return executeQueryArray('document.getDocumentExtraVars', $args);
	}
	
	/**
	 * Extra variables for each article will not be processed bulk select and apply the macro city
	 * @return void
	 */
	public static function setToAllDocumentExtraVars()
	{
		// get document list
		$_document_list = &$GLOBALS['XE_DOCUMENT_LIST'];
		if(empty($_document_list))
		{
			return;
		}
		
		static $checked = array();
		static $module_extra_keys = array();
		
		// check documents
		$document_srls = array();
		foreach($_document_list as $document_srl => $oDocument)
		{
			if(isset($checked[$document_srl]) || !($oDocument instanceof documentItem) || !$oDocument->isExists())
			{
				continue;
			}
			
			$checked[$document_srl] = true;
			$document_srls[] = $document_srl;
		}
		
		if(!$document_srls)
		{
			return;
		}
		
		// get extra values of documents
		$extra_values = array();
		$output = self::getDocumentExtraVarsFromDB($document_srls);
		foreach($output->data as $key => $val)
		{
			if(strval($val->value) === '')
			{
				continue;
			}
			
			$extra_values[$val->document_srl][$val->var_idx][$val->lang_code] = trim($val->value);
		}
		
		// set extra variables and document language
		$user_lang_code = Context::getLangType();
		foreach($document_srls as $document_srl)
		{
			$oDocument = $_document_list[$document_srl];
			$module_srl = $oDocument->get('module_srl');
			$document_lang_code = $oDocument->get('lang_code');
			$document_extra_values = $extra_values[$document_srl] ?? [];
			
			// set XE_EXTRA_VARS
			if(!isset($GLOBALS['XE_EXTRA_VARS'][$document_srl]))
			{
				// get extra keys of the module
				if(!isset($module_extra_keys[$module_srl]))
				{
					$module_extra_keys[$module_srl] = self::getExtraKeys($module_srl);
				}
				
				// set extra variables of the document
				if($module_extra_keys[$module_srl])
				{
					$document_extra_vars = array();
					foreach($module_extra_keys[$module_srl] as $idx => $key)
					{
						$document_extra_vars[$idx] = clone($key);
						
						// set variable value in user language
						if(isset($document_extra_values[$idx][$user_lang_code]))
						{
							$document_extra_vars[$idx]->setValue($document_extra_values[$idx][$user_lang_code]);
						}
						elseif(isset($document_extra_values[$idx][$document_lang_code]))
						{
							$document_extra_vars[$idx]->setValue($document_extra_values[$idx][$document_lang_code]);
						}
					}
					
					$GLOBALS['XE_EXTRA_VARS'][$document_srl] = $document_extra_vars;
				}
			}
			
			// set RX_DOCUMENT_LANG
			if(!isset($GLOBALS['RX_DOCUMENT_LANG'][$document_srl]) && $document_lang_code !== $user_lang_code)
			{
				if(isset($document_extra_values[-1][$user_lang_code]))
				{
					$oDocument->add('title', $document_extra_values[-1][$user_lang_code]);
					$GLOBALS['RX_DOCUMENT_LANG'][$document_srl]['title'] = $document_extra_values[-1][$user_lang_code];
				}
				if(isset($document_extra_values[-2][$user_lang_code]))
				{
					$oDocument->add('content', $document_extra_values[-2][$user_lang_code]);
					$GLOBALS['RX_DOCUMENT_LANG'][$document_srl]['content'] = $document_extra_values[-2][$user_lang_code];
				}
			}
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
	public static function getDocument($document_srl = 0, $is_admin = false, $load_extra_vars = true, $columnList = array())
	{
		if(!$document_srl)
		{
			return new documentItem();
		}
		if(!isset($GLOBALS['XE_DOCUMENT_LIST'][$document_srl]))
		{
			$oDocument = new documentItem($document_srl, $load_extra_vars, $columnList);
			if(!$oDocument->isExists())
			{
				return $oDocument;
			}
		}
		if($is_admin)
		{
			trigger_error('Called DocumentModel::getDocument() with $is_admin = true', \E_USER_WARNING);
			$GLOBALS['XE_DOCUMENT_LIST'][$document_srl]->setGrant();
		}
		
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
	public static function getDocuments($document_srls, $is_admin = false, $load_extra_vars = true, $columnList = array())
	{
		if (!is_array($document_srls))
		{
			$document_srls = $document_srls ? explode(',', $document_srls) : array();
		}
		if (!count($document_srls))
		{
			return array();
		}

		$args = new stdClass();
		$args->document_srls = $document_srls;
		$args->list_count = is_array($document_srls) ? count($document_srls) : 1;
		$args->order_type = 'asc';
		$output = executeQueryArray('document.getDocuments', $args, $columnList);
		
		$documents = array();
		foreach($output->data as $attribute)
		{
			if(!isset($GLOBALS['XE_DOCUMENT_LIST'][$attribute->document_srl]))
			{
				$oDocument = new documentItem();
				$oDocument->setAttribute($attribute, false);
			}
			if($is_admin)
			{
				trigger_error('Called DocumentModel::getDocuments() with $is_admin = true', \E_USER_WARNING);
				$GLOBALS['XE_DOCUMENT_LIST'][$attribute->document_srl]->setGrant();
			}
			
			$documents[$attribute->document_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$attribute->document_srl];
		}
		
		if($load_extra_vars)
		{
			self::setToAllDocumentExtraVars();
		}
		
		return $documents;
	}

	/**
	 * Module_srl value, bringing the list of documents
	 * @param object $obj
	 * @param bool $except_notice
	 * @param bool $load_extra_vars
	 * @param array $columnList
	 * @return Object
	 */
	public static function getDocumentList($obj, $except_notice = false, $load_extra_vars = true, $columnList = array())
	{
		$sort_check = self::_setSortIndex($obj, $load_extra_vars);
		$obj->sort_index = $sort_check->sort_index;
		$obj->isExtraVars = $sort_check->isExtraVars;
		$obj->except_notice = $except_notice;
		$obj->columnList = $columnList;
		
		// Call trigger (before)
		// This trigger can be used to set an alternative output using a different search method
		unset($obj->use_alternate_output);
		$output = ModuleHandler::triggerCall('document.getDocumentList', 'before', $obj);
		if($output instanceof BaseObject && !$output->toBool())
		{
			return $output;
		}
		
		// If an alternate output is set, use it instead of running the default queries
		if (isset($obj->use_alternate_output) && $obj->use_alternate_output instanceof BaseObject)
		{
			$output = $obj->use_alternate_output;
		}
		// execute query
		else
		{
			self::_setSearchOption($obj, $args, $query_id, $use_division);
			$output = executeQueryArray($query_id, $args, $args->columnList);
		}
		
		// Return if no result or an error occurs
		if(!$output->toBool() || !$result = $output->data)
		{
			return $output;
		}
		
		$output->data = array();
		foreach($result as $key => $attribute)
		{
			if(!isset($GLOBALS['XE_DOCUMENT_LIST'][$attribute->document_srl]))
			{
				$oDocument = new documentItem();
				$oDocument->setAttribute($attribute, false);
			}
			$output->data[$key] = $GLOBALS['XE_DOCUMENT_LIST'][$attribute->document_srl];
		}
		
		if($load_extra_vars)
		{
			self::setToAllDocumentExtraVars();
		}
		
		// Call trigger (after)
		// This trigger can be used to modify search results
		ModuleHandler::triggerCall('document.getDocumentList', 'after', $output);
		return $output;
	}

	/**
	 * Module_srl value, bringing the document's gongjisa Port
	 * @param object $obj
	 * @param array $columnList
	 * @return object|void
	 */
	public static function getNoticeList($obj, $columnList = array())
	{
		$args = new stdClass();
		$args->module_srl = $obj->module_srl;
		$args->category_srl = $obj->category_srl ?? null;
		$output = executeQueryArray('document.getNoticeList', $args, $columnList);
		if(!$output->toBool() || !$result = $output->data)
		{
			return;
		}
		
		$output->data = array();
		foreach($result as $attribute)
		{
			if(!isset($GLOBALS['XE_DOCUMENT_LIST'][$attribute->document_srl]))
			{
				$oDocument = new documentItem();
				$oDocument->setAttribute($attribute, false);
			}
			
			$output->data[$attribute->document_srl] = $GLOBALS['XE_DOCUMENT_LIST'][$attribute->document_srl];
		}
		self::setToAllDocumentExtraVars();
		
		return $output;
	}

	/**
	 * Function to retrieve the key values of the extended variable document
	 * $Form_include: writing articles whether to add the necessary extensions of the variable input form
	 * @param int $module_srl
	 * @return array
	 */
	public static function getExtraKeys($module_srl)
	{
		if(!isset($GLOBALS['XE_EXTRA_KEYS'][$module_srl]))
		{
			$keys = Rhymix\Framework\Cache::get("site_and_module:module_document_extra_keys:$module_srl");
			$oExtraVar = ExtraVar::getInstance($module_srl);

			if($keys === null)
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

				Rhymix\Framework\Cache::set("site_and_module:module_document_extra_keys:$module_srl", $keys, 0, true);
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
	public static function getExtraVars($module_srl, $document_srl)
	{
		if(!isset($GLOBALS['XE_EXTRA_VARS'][$document_srl]))
		{
			self::getDocument($document_srl);
			self::setToAllDocumentExtraVars();
		}
		if(empty($GLOBALS['XE_EXTRA_VARS'][$document_srl]) || !is_array($GLOBALS['XE_EXTRA_VARS'][$document_srl]))
		{
			return array();
		}
		
		ksort($GLOBALS['XE_EXTRA_VARS'][$document_srl]);
		
		return $GLOBALS['XE_EXTRA_VARS'][$document_srl];
	}

	/**
	 * Show pop-up menu of the selected posts
	 * Printing, scrap, recommendations and negative, reported the Add Features
	 * @return void
	 */
	public function getDocumentMenu()
	{
		// Post number and the current login information requested Wanted
		$document_srl = Context::get('target_srl');
		// to menu_list "pyosihalgeul, target, url" put into an array
		$menu_list = array();
		// call trigger
		ModuleHandler::triggerCall('document.getDocumentMenu', 'before', $menu_list);

		$oDocumentController = getController('document');
		// Members must be a possible feature
		if($this->user->member_srl)
		{
			$columnList = array('document_srl', 'module_srl', 'member_srl', 'ipaddress');
			$oDocument = self::getDocument($document_srl, false, false, $columnList);
			$module_srl = $oDocument->get('module_srl');
			$member_srl = abs($oDocument->get('member_srl'));
			if(!$module_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

			$document_config = ModuleModel::getModulePartConfig('document',$module_srl);
			$oDocumentisVoted = $oDocument->getMyVote();
			if($document_config->use_vote_up!='N' && $member_srl!=$this->user->member_srl)
			{
				if($oDocumentisVoted === false || $oDocumentisVoted < 0)
				{
					$url = sprintf("doCallModuleAction('document','procDocumentVoteUp','%s')", $document_srl);
					$oDocumentController->addDocumentPopupMenu($url,'cmd_vote','','javascript');
				}
				elseif($oDocumentisVoted > 0)
				{
					$url = sprintf("doCallModuleAction('document','procDocumentVoteUpCancel','%s')", $document_srl);
					$oDocumentController->addDocumentPopupMenu($url,'cmd_cancel_vote','','javascript');
				}
			}

			if($document_config->use_vote_down!='N' && $member_srl!=$this->user->member_srl)
			{
				if($oDocumentisVoted === false || $oDocumentisVoted > 0)
				{
					$url = sprintf("doCallModuleAction('document','procDocumentVoteDown','%s')", $document_srl);
					$oDocumentController->addDocumentPopupMenu($url,'cmd_vote_down','','javascript');
				}
				else if($oDocumentisVoted < 0)
				{
					$url = sprintf("doCallModuleAction('document','procDocumentVoteDownCancel','%s')", $document_srl);
					$oDocumentController->addDocumentPopupMenu($url,'cmd_cancel_vote_down','','javascript');
				}
			}

			// Adding Report
			if($oDocument->getDeclared())
			{
				$url = getUrl('', 'act', 'dispDocumentDeclare', 'target_srl', $document_srl, 'type', 'cancel');
				$oDocumentController->addDocumentPopupMenu($url,'cmd_cancel_declare','','popup');
			}
			else
			{
				$url = getUrl('', 'act', 'dispDocumentDeclare', 'target_srl', $document_srl);
				$oDocumentController->addDocumentPopupMenu($url,'cmd_declare','','popup');
			}

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
			$str_confirm = lang('confirm_move');
			$url = sprintf("if(!confirm('%s')) return; var params = new Array(); params['document_srl']='%s'; params['mid']=current_mid;params['cur_url']=current_url; exec_xml('document', 'procDocumentAdminMoveToTrash', params)", $str_confirm, $document_srl);
			$oDocumentController->addDocumentPopupMenu($url,'cmd_trash','','javascript');
		}

		// If you are managing to find posts by ip
		if($this->user->is_admin == 'Y')
		{
			$oDocument = self::getDocument($document_srl);	//before setting document recycle

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
			$menus[$i]->str = lang($menus[$i]->str);
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
	public static function getDocumentCount($module_srl, $search_obj = NULL)
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
	public static function getDocumentCountByGroupStatus($search_obj = NULL)
	{
		$output = executeQuery('document.getDocumentCountByGroupStatus', $search_obj);
		if(!$output->toBool()) return array();

		return $output->data;
	}

	public static function getDocumentExtraVarsCount($module_srl, $search_obj = NULL)
	{
		// Additional search options
		$args = new stdClass();
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
	public static function getDocumentPage($oDocument, $opt)
	{
		$sort_check = self::_setSortIndex($opt);
		$opt->sort_index = $sort_check->sort_index;
		$opt->isExtraVars = $sort_check->isExtraVars;

		self::_setSearchOption($opt, $args, $query_id, $use_division);

		if($sort_check->isExtraVars || !$opt->list_count)
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
			elseif($sort_check->sort_index === 'regdate')
			{

				if($args->order_type === 'asc')
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
	public static function getCategory($category_srl, $columnList = array())
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
	public static function getCategoryChlidCount($category_srl)
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
	public static function getCategoryList($module_srl, $columnList = array())
	{
		// Category of the target module file swollen
		$module_srl = intval($module_srl);
		$filename = sprintf("%sfiles/cache/document_category/%d.php", _XE_PATH_, $module_srl);
		// If the target file to the cache file regeneration category
		if(!file_exists($filename))
		{
			$oDocumentController = getController('document');
			if(!$oDocumentController->makeCategoryFile($module_srl)) return array();
		}

		include($filename);

		// Cleanup of category
		$document_category = array();
		self::_arrangeCategory($document_category, $menu->list, 0);
		return $document_category;
	}

	/**
	 * Category within a primary method to change the array type
	 * @param array $document_category
	 * @param array $list
	 * @param int $depth
	 * @return void
	 */
	public static function _arrangeCategory(&$document_category, $list, $depth)
	{
		if(!countobj($list)) return;
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

			if(count($val['list'])) self::_arrangeCategory($document_category, $val['list'], $depth+1);
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
	public static function getCategoryDocumentCount($module_srl, $category_srl)
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
	public static function getCategoryXmlFile($module_srl)
	{
		$module_srl = intval($module_srl);
		$xml_file = sprintf('files/cache/document_category/%d.xml.php',$module_srl);
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
	public static function getCategoryPhpFile($module_srl)
	{
		$module_srl = intval($module_srl);
		$php_file = sprintf('files/cache/document_category/%d.php',$module_srl);
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
	public static function getMonthlyArchivedList($obj)
	{
		if($obj->mid)
		{
			$obj->module_srl = ModuleModel::getModuleSrlByMid($obj->mid);
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
	public static function getDailyArchivedList($obj)
	{
		if($obj->mid)
		{
			$obj->module_srl = ModuleModel::getModuleSrlByMid($obj->mid);
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
	public function getDocumentCategories()
	{
		if(!Context::get('is_logged')) throw new Rhymix\Framework\Exceptions\NotPermitted;
		$module_srl = intval(Context::get('module_srl'));
		$categories= self::getCategoryList($module_srl);
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
	public static function getDocumentConfig()
	{
		if (self::$_config === null)
		{
			self::$_config = ModuleModel::getModuleConfig('document') ?: new stdClass;;
		}
		return self::$_config;
	}

	/**
	 * Common:: Module extensions of variable management
	 * Expansion parameter management module in the document module instance, when using all the modules available
	 * @param int $module_srl
	 * @return string
	 */
	public function getExtraVarsHTML($module_srl)
	{
		// Bringing existing extra_keys
		$extra_keys = self::getExtraKeys($module_srl);
		Context::set('extra_keys', $extra_keys);
		$security = new Security();
		$security->encodeHTML('extra_keys..', 'selected_var_idx');

		// Get information of module_grants
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($this->module_path.'tpl', 'extra_keys');
	}

	/**
	 * Common:: Category parameter management module
	 * @param int $module_srl
	 * @return string
	 */
	public function getCategoryHTML($module_srl)
	{
		$category_xml_file = self::getCategoryXmlFile($module_srl);

		Context::set('category_xml_file', $category_xml_file);

		Context::loadJavascriptPlugin('ui.tree');

		// Get a list of member groups
		$group_list = MemberModel::getGroups();
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
	public function getDocumentCategoryTplInfo()
	{
		// Get information on the menu for the parameter settings
		$module_srl = Context::get('module_srl');
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		// Check permissions
		$grant = ModuleModel::getGrant($module_info, Context::get('logged_info'));
		if(!$grant->manager) throw new Rhymix\Framework\Exceptions\NotPermitted;

		$category_srl = Context::get('category_srl');
		$category_info = self::getCategory($category_srl);
		if(!$category_info)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$this->add('category_info', $category_info);
	}

	/**
	 * Return docuent data by alias
	 * @param string $mid
	 * @param string $alias
	 * @return int|void
	 */
	public static function getDocumentSrlByAlias($mid, $alias)
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
	public static function getDocumentSrlByTitle($module_srl, $title)
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
	public static function getAlias($document_srl)
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
	public static function getHistories($document_srl, $list_count, $page)
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
	public static function getHistory($history_srl)
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
	public static function getTrashList($obj)
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
					if($search_keyword=='N') $args->statusList = array(self::getConfigStatus('public'));
					elseif($search_keyword=='Y') $args->statusList = array(self::getConfigStatus('secret'));
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
	public function getDocumentVotedMemberList()
	{
		$args = new stdClass;
		$document_srl = Context::get('document_srl');
		if(!$document_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$point = Context::get('point');
		if($point != -1) $point = 1;

		$columnList = array('document_srl', 'module_srl');
		$oDocument = self::getDocument($document_srl, false, false, $columnList);
		$module_srl = $oDocument->get('module_srl');
		if(!$module_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$document_config = ModuleModel::getModulePartConfig('document',$module_srl);
		if($point == -1)
		{
			if($document_config->use_vote_down!='S') throw new Rhymix\Framework\Exceptions\FeatureDisabled;
			$args->below_point = 0;
		}
		else
		{
			if($document_config->use_vote_up!='S') throw new Rhymix\Framework\Exceptions\FeatureDisabled;
			$args->more_point = 0;
		}

		$args->document_srl = $document_srl;

		$output = executeQueryArray('document.getVotedMemberList',$args);
		if(!$output->toBool()) return $output;

		if($output->data)
		{
			foreach($output->data as $k => $d)
			{
				$profile_image = MemberModel::getProfileImage($d->member_srl);
				$output->data[$k]->src = $profile_image->src;
			}
		}

		$this->add('voted_member_list',$output->data);
	}

	/**
	 * Return status name list
	 * @return array
	 */
	public static function getStatusNameList()
	{
		global $lang;
		if(!isset($lang->status_name_list))
		{
			return array_flip(self::getStatusList());
		}
		else
		{
			return $lang->status_name_list;
		}
	}
	
	/**
	 * Setting sort index
	 * @param object $obj
	 * @param bool $load_extra_vars
	 * @return object
	 */
	public static function _setSortIndex($obj, $load_extra_vars = true)
	{
		$args = new stdClass;
		$args->sort_index = $obj->sort_index ?? null;
		$args->isExtraVars = false;
		
		// check it's default sort
		$default_sort = array('list_order', 'regdate', 'last_update', 'update_order', 'readed_count', 'voted_count', 'blamed_count', 'comment_count', 'trackback_count', 'uploaded_count', 'title', 'category_srl');
		if(in_array($args->sort_index, $default_sort))
		{
			return $args;
		}
		
		// check it can use extra variable
		if(!$load_extra_vars || !$extra_keys = self::getExtraKeys($obj->module_srl))
		{
			$args->sort_index = 'list_order';
			return $args;
		}
		
		$eids = array();
		foreach($extra_keys as $idx => $key)
		{
			$eids[] = $key->eid;
		}
		
		// check it exists in extra keys of the module
		if(!in_array($args->sort_index, $eids))
		{
			$args->sort_index = 'list_order';
			return $args;
		}
		
		$args->isExtraVars = true;
		return $args;
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
	public static function _setSearchOption($searchOpt, &$args, &$query_id, &$use_division)
	{
		$args = new stdClass;
		$args->module_srl = $searchOpt->module_srl ?? null;
		$args->exclude_module_srl = $searchOpt->exclude_module_srl ?? null;
		$args->category_srl = $searchOpt->category_srl ?? null;
		$args->member_srl = $searchOpt->member_srl ?? ($searchOpt->member_srls ?? null);
		$args->order_type = (isset($searchOpt->order_type) && $searchOpt->order_type === 'desc') ? 'desc' : 'asc';
		$args->sort_index = $searchOpt->sort_index;
		$args->page = $searchOpt->page ?? 1;
		$args->list_count = $searchOpt->list_count ?? 20;
		$args->page_count = $searchOpt->page_count ?? 10;
		$args->start_date = $searchOpt->start_date ?? null;
		$args->end_date = $searchOpt->end_date ?? null;
		$args->s_is_notice = $searchOpt->except_notice ? 'N' : null;
		$args->statusList = $searchOpt->statusList ?? array(self::getConfigStatus('public'), self::getConfigStatus('secret'));
		$args->columnList = $searchOpt->columnList ?? array();
		
		// get directly module_srl by mid
		if(isset($searchOpt->mid) && $searchOpt->mid)
		{
			$args->module_srl = ModuleModel::getModuleSrlByMid($searchOpt->mid);
		}
		
		// add subcategories
		if(isset($args->category_srl) && $args->category_srl)
		{
			$category_list = self::getCategoryList($args->module_srl);
			if(isset($category_list[$args->category_srl]))
			{
				$categories = $category_list[$args->category_srl]->childs;
				$categories[] = $args->category_srl;
				$args->category_srl = $categories;
			}
		}
		
		// default
		$query_id = null;
		$use_division = false;
		$search_target = $searchOpt->search_target ?? null;
		$search_keyword = trim($searchOpt->search_keyword ?? null) ?: null;
		
		// search
		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'title' :
				case 'content' :
				case 'comment' :
				case 'tag' :
				case 'title_content' :
					$use_division = true;
					$search_keyword = trim(utf8_normalize_spaces($search_keyword));
					if($search_target == 'title_content')
					{
						$args->s_title = $search_keyword;
						$args->s_content = $search_keyword;
					}
					else
					{
						if($search_target == 'comment')
						{
							$query_id = 'document.getDocumentListWithinComment';
						}
						elseif($search_target == 'tag')
						{
							$query_id = 'document.getDocumentListWithinTag';
						}
						$args->{'s_' . $search_target} = $search_keyword;
					}
					break;
				case 'user_id' :
				case 'user_name' :
				case 'nick_name' :
				case 'email_address' :
				case 'homepage' :
				case 'regdate' :
				case 'last_update' :
				case 'ipaddress' :
					$args->{'s_' . $search_target} = str_replace(' ', '%', $search_keyword);
					break;
				case 'member_srl' :
				case 'readed_count' :
				case 'voted_count' :
				case 'comment_count' :
				case 'trackback_count' :
				case 'uploaded_count' :
					$args->{'s_' . $search_target} = (int)$search_keyword;
					break;
				case 'blamed_count' :
					$args->{'s_' . $search_target} = (int)$search_keyword * -1;
					break;
				case 'is_notice' :
					$args->{'s_' . $search_target} = $search_keyword == 'Y' ? 'Y' : 'N';
					break;
				case 'is_secret' :
					if($search_keyword == 'N')
					{
						$args->statusList = array(self::getConfigStatus('public'));
					}
					elseif($search_keyword == 'Y')
					{
						$args->statusList = array(self::getConfigStatus('secret'));
					}
					elseif($search_keyword == 'temp')
					{
						$args->statusList = array(self::getConfigStatus('temp'));
					}
					break;
				default :
					// search extra variable
					if(preg_match('/^extra_vars([0-9]+)?$/', $search_target, $matches))
					{
						$args->var_idx = !empty($matches[1]) ? $matches[1] : null;
						$args->var_value = str_replace(' ', '%', $search_keyword);
					}
					break;
			}
			
			// exclude secret documents in searching if current user does not have privilege
			if(!$args->member_srl || !Context::get('is_logged') || $args->member_srl !== Context::get('logged_info')->member_srl)
			{
				$module_info = ModuleModel::getModuleInfoByModuleSrl($args->module_srl);
				if(!ModuleModel::getGrant($module_info, Context::get('logged_info'))->manager)
				{
					$args->comment_is_secret = 'N';
					$args->statusList = array(self::getConfigStatus('public'));
				}
			}
		}
		
		// set query
		if(!$query_id)
		{
			// by extra variable
			if($searchOpt->isExtraVars || !empty($args->var_value))
			{
				if($searchOpt->isExtraVars)
				{
					$args->sort_eid = $args->sort_index;
					$args->sort_lang = Context::getLangType();
					$args->sort_index = 'extra_sort.value';
				}
				$query_id = 'document.getDocumentListWithExtraVars';
			}
			else
			{
				$query_id = 'document.getDocumentList';
			}
		}
		// other queries not support to sort extra variable 
		elseif($searchOpt->isExtraVars)
		{
			$args->sort_index = 'list_order';
		}
		
		// division search by 5,000
		if($use_division)
		{
			$args->order_type = 'asc';
			$args->sort_index = 'list_order';
			$args->division = (int)Context::get('division');
			$args->last_division = (int)Context::get('last_division');
			
			$division_args = new stdClass;
			$division_args->module_srl = $args->module_srl;
			$division_args->exclude_module_srl = $args->exclude_module_srl;
			
			// get start point of first division
			if(Context::get('division') === null)
			{
				$args->division = (int)executeQuery('document.getDocumentDivision', $division_args)->data->list_order;
			}
			
			// get end point of the division
			if(Context::get('last_division') === null && $args->division)
			{
				$division_args->offset = 5000;
				$division_args->list_order = $args->division;
				$args->last_division = (int)executeQuery('document.getDocumentDivision', $division_args)->data->list_order;
			}
			
			Context::set('division', $args->division);
			Context::set('last_division', $args->last_division);
		}
		
		// add default prefix
		if($args->sort_index && strpos($args->sort_index, '.') === false)
		{
			$args->sort_index = 'documents.' . $args->sort_index;
		}
		foreach($args->columnList as $key => $column)
		{
			if(strpos($column, '.') !== false)
			{
				continue;
			}
			$args->columnList[$key] = 'documents.' . $column;
		}
	}

	/**
	 * Get the total number of Document in corresponding with member_srl.
	 * @param int $member_srl
	 * @return int
	 */
	public static function getDocumentCountByMemberSrl($member_srl)
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
	public static function getDocumentListByMemberSrl($member_srl, $columnList = array(), $page = 0, $is_admin = FALSE, $count = 0)
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

	public static function getDocumentUpdateLog($document_srl)
	{
		$args = new stdClass();
		$args->document_srl = $document_srl;
		$output = executeQueryArray('document.getDocumentUpdateLog', $args);

		return $output;
	}

	public static function getUpdateLog($update_id)
	{
		$args = new stdClass();
		$args->update_id = $update_id;
		$output = exeCuteQuery('document.getUpdateLog', $args);
		$update_log = $output->data;

		return $update_log;
	}

	public static function getUpdateLogAdminisExists($document_srl = null)
	{
		if($document_srl == null)
		{
			return;
		}
		$args = new stdClass();
		$args->document_srl = $document_srl;
		$args->is_admin = 'Y';
		$output = executeQuery('document.getUpdateLogAdminisExists', $args);

		if($output->data->count > 0)
		{
			return true;
		}

		return false;
	}

	public static function getDocumentExtraImagePath()
	{
		$documentConfig = self::getDocumentConfig();
		if(Mobile::isFromMobilePhone())
		{
			$iconSkin = $documentConfig->micons;
		}
		else
		{
			$iconSkin = $documentConfig->icons;
		}
		$path = sprintf('%s%s',getUrl(), "modules/document/tpl/icons/$iconSkin/");

		return $path;
	}
}
/* End of file document.model.php */
/* Location: ./modules/document/document.model.php */
