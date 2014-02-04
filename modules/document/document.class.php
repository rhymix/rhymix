<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
require_once(_XE_PATH_.'modules/document/document.item.php');

/**
 * document class
 * @brief document the module's high class
 * {@internal Silently adds one extra Foo to compensate for lack of Foo }
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/document
 * @version 0.1
 */
class document extends ModuleObject
{
	/**
	 * Search option to use in admin page
	 * @var array
	 */
	var $search_option = array('title','content','title_content','user_name',); // /< Search options
	/**
	 * Status list
	 * @var array
	 */
	var $statusList = array('private'=>'PRIVATE', 'public'=>'PUBLIC', 'secret'=>'SECRET', 'temp'=>'TEMP');

	/**
	 * Implement if additional tasks are necessary when installing
	 * @return Object
	 */
	function moduleInstall()
	{
		// Register action forward (to use in administrator mode)
		$oModuleController = getController('module');

		$oDB = &DB::getInstance();
		$oDB->addIndex("documents","idx_module_list_order", array("module_srl","list_order"));
		$oDB->addIndex("documents","idx_module_update_order", array("module_srl","update_order"));
		$oDB->addIndex("documents","idx_module_readed_count", array("module_srl","readed_count"));
		$oDB->addIndex("documents","idx_module_voted_count", array("module_srl","voted_count"));
		$oDB->addIndex("documents","idx_module_notice", array("module_srl","is_notice"));
		$oDB->addIndex("documents","idx_module_document_srl", array("module_srl","document_srl"));
		$oDB->addIndex("documents","idx_module_blamed_count", array("module_srl","blamed_count"));
		$oDB->addIndex("document_aliases", "idx_module_title", array("module_srl","alias_title"), true);
		$oDB->addIndex("document_extra_vars", "unique_extra_vars", array("module_srl","document_srl","var_idx","lang_code"), true);
		// 2007. 10. 17 Add a trigger to delete all posts together when the module is deleted
		$oModuleController->insertTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after');

		// 2009. 01. 29 Added a trigger for additional setup
		$oModuleController->insertTrigger('module.dispAdditionSetup', 'document', 'view', 'triggerDispDocumentAdditionSetup', 'before');

		return new Object();
	}

	/**
	 * A method to check if successfully installed
	 * @return bool
	 */
	function checkUpdate() {
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');

		// 2007. 7. 25: Add a column(notify_message) for notification
		if(!$oDB->isColumnExists("documents","notify_message")) return true;

		// 2007. 8. 23: create a clustered index in the document table
		if(!$oDB->isIndexExists("documents","idx_module_list_order")) return true;
		if(!$oDB->isIndexExists("documents","idx_module_update_order")) return true;
		if(!$oDB->isIndexExists("documents","idx_module_readed_count")) return true;
		if(!$oDB->isIndexExists("documents","idx_module_voted_count")) return true;
		// 2007. 10. 17 Add a trigger to delete all posts together when the module is deleted
		if(!$oModuleModel->getTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after')) return true;
		// 2007. 10. 25 add parent_srl, expand to the document category
		if(!$oDB->isColumnExists("document_categories","parent_srl")) return true;
		if(!$oDB->isColumnExists("document_categories","expand")) return true;
		if(!$oDB->isColumnExists("document_categories","group_srls")) return true;
		// 2007. 11. 20 create a composite index on the columns(module_srl + is_notice)
		if(!$oDB->isIndexExists("documents","idx_module_notice")) return true;
		// 2008. 02. 18 create a composite index on the columns(module_srl + document_srl) (checked by Manian))
		if(!$oDB->isIndexExists("documents","idx_module_document_srl")) return true;

		// 2007. 12. 03: Add if the colume(extra_vars) doesn't exist
		if(!$oDB->isColumnExists("documents","extra_vars")) return true;
		// 2008. 04. 23 Add a column(blamed_count)
		if(!$oDB->isColumnExists("documents", "blamed_count")) return true;
		if(!$oDB->isIndexExists("documents","idx_module_blamed_count")) return true;
		if(!$oDB->isColumnExists("document_voted_log", "point")) return true;
		// 2008-12-15 Add a column(color)
		if(!$oDB->isColumnExists("document_categories", "color")) return true;

		/**
		 * 2009. 01. 29: Add a column(lang_code) if not exist in the document_extra_vars table
		 */
		if(!$oDB->isColumnExists("document_extra_vars","lang_code")) return true;

		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'document', 'view', 'triggerDispDocumentAdditionSetup', 'before')) return true;
		// 2009. 03. 09 Add a column(lang_code) to the documnets table
		if(!$oDB->isColumnExists("documents","lang_code")) return true;
		// 2009. 03. 11 check the index in the document_extra_vars table
		if(!$oDB->isIndexExists("document_extra_vars", "unique_extra_vars")) return true;

		// 2009. 03. 19: Add a column(eid) if not exist in the table
		if(!$oDB->isColumnExists("document_extra_keys","eid")) return true;
		if(!$oDB->isColumnExists("document_extra_vars","eid")) return true;

		// 2011. 03. 30 Cubrid index Check the index in the document_extra_vars table
		if(!$oDB->isIndexExists("document_extra_vars", "idx_document_list_order")) return true;

		//2011. 04. 07 adding description column to document categories
		if(!$oDB->isColumnExists("document_categories","description")) return true;

		//2011. 05. 23 adding status column to document
		if(!$oDB->isColumnExists('documents', 'status')) return true;

		//2011. 06. 07 check comment status update
		if($oDB->isColumnExists('documents', 'allow_comment') || $oDB->isColumnExists('documents', 'lock_comment')) return true;

		// 2011. 10. 25 status index check
		if(!$oDB->isIndexExists("documents", "idx_module_status")) return true;

		// 2012. 02. 27 Add a trigger to copy extra keys when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'document', 'controller', 'triggerCopyModuleExtraKeys', 'after')) return true;

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'document', 'controller', 'triggerCopyModule', 'after')) return true;

		return false;
	}

	/**
	 * Execute update
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		// 2007. 7. 25: Add a column(notify_message) for notification
		if(!$oDB->isColumnExists("documents","notify_message"))
		{
			$oDB->addColumn('documents',"notify_message","char",1);
		}

		// 2007. 8. 23: create a clustered index in the document table
		if(!$oDB->isIndexExists("documents","idx_module_list_order"))
		{
			$oDB->addIndex("documents","idx_module_list_order", array("module_srl","list_order"));
		}

		if(!$oDB->isIndexExists("documents","idx_module_update_order"))
		{
			$oDB->addIndex("documents","idx_module_update_order", array("module_srl","update_order"));
		}

		if(!$oDB->isIndexExists("documents","idx_module_readed_count"))
		{
			$oDB->addIndex("documents","idx_module_readed_count", array("module_srl","readed_count"));
		}

		if(!$oDB->isIndexExists("documents","idx_module_voted_count"))
		{
			$oDB->addIndex("documents","idx_module_voted_count", array("module_srl","voted_count"));
		}
		// 2007. 10. 17 Add a trigger to delete all posts together when the module is deleted
		if(!$oModuleModel->getTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after'))
			$oModuleController->insertTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after');
		// 2007. 10. 25 add columns(parent_srl, expand) 
		if(!$oDB->isColumnExists("document_categories","parent_srl")) $oDB->addColumn('document_categories',"parent_srl","number",12,0);
		if(!$oDB->isColumnExists("document_categories","expand")) $oDB->addColumn('document_categories',"expand","char",1,"N");
		if(!$oDB->isColumnExists("document_categories","group_srls")) $oDB->addColumn('document_categories',"group_srls","text");
		// 2007. 11. 20 create a composite index on the columns(module_srl + is_notice)
		if(!$oDB->isIndexExists("documents","idx_module_notice")) $oDB->addIndex("documents","idx_module_notice", array("module_srl","is_notice"));

		// 2007. 12. 03: Add if the colume(extra_vars) doesn't exist
		if(!$oDB->isColumnExists("documents","extra_vars")) $oDB->addColumn('documents','extra_vars','text');

		// 2008. 02. 18 create a composite index on the columns(module_srl + document_srl) (checked by Manian))
		if(!$oDB->isIndexExists("documents","idx_module_document_srl")) $oDB->addIndex("documents","idx_module_document_srl", array("module_srl","document_srl"));
		// 2008. 04. 23 Add a column(blamed count)
		if(!$oDB->isColumnExists("documents", "blamed_count"))
		{
			$oDB->addColumn('documents', 'blamed_count', 'number', 11, 0, true);
			$oDB->addIndex('documents', 'idx_blamed_count', array('blamed_count'));
		}

		if(!$oDB->isIndexExists("documents","idx_module_blamed_count"))
		{
			$oDB->addIndex('documents', 'idx_module_blamed_count', array('module_srl', 'blamed_count'));
		}

		if(!$oDB->isColumnExists("document_voted_log", "point"))
			$oDB->addColumn('document_voted_log', 'point', 'number', 11, 0, true);


		if(!$oDB->isColumnExists("document_categories","color")) $oDB->addColumn('document_categories',"color","char",7);

		// 2009. 01. 29: Add a column(lang_code) if not exist in the document_extra_vars table
		if(!$oDB->isColumnExists("document_extra_vars","lang_code")) $oDB->addColumn('document_extra_vars',"lang_code","varchar",10);

		// 2009. 01. 29 Added a trigger for additional setup
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'document', 'view', 'triggerDispDocumentAdditionSetup', 'before'))
			$oModuleController->insertTrigger('module.dispAdditionSetup', 'document', 'view', 'triggerDispDocumentAdditionSetup', 'before');
		// 2009. 03. 09 Add a column(lang_code) to the documnets table
		if(!$oDB->isColumnExists("documents","lang_code"))
		{
			$db_info = Context::getDBInfo();
			$oDB->addColumn('documents',"lang_code","varchar",10, $db_info->lang_code);
			$obj->lang_code = $db_info->lang_type;
			executeQuery('document.updateDocumentsLangCode', $obj);
		}
		// 2009. 03. 11 Check the index in the document_extra_vars table
		if(!$oDB->isIndexExists("document_extra_vars", "unique_extra_vars"))
		{
			$oDB->addIndex("document_extra_vars", "unique_extra_vars", array("module_srl","document_srl","var_idx","lang_code"), true);
		}

		if($oDB->isIndexExists("document_extra_vars", "unique_module_vars"))
		{
			$oDB->dropIndex("document_extra_vars", "unique_module_vars", true);
		}

		// 2009. 03. 19: Add a column(eid)
		// 2009. 04. 12: Fixed the issue(#17922959) that changes another column values when adding eid column
		if(!$oDB->isColumnExists("document_extra_keys","eid"))
		{
			$oDB->addColumn("document_extra_keys","eid","varchar",40);

			$output = executeQuery('document.getGroupsExtraKeys', $obj);
			if($output->toBool() && $output->data && count($output->data)) {
				foreach($output->data as $extra_keys) {
					$args->module_srl = $extra_keys->module_srl;
					$args->var_idx = $extra_keys->idx;
					$args->new_eid = "extra_vars".$extra_keys->idx;
					$output = executeQuery('document.updateDocumentExtraKeyEid', $args);
				}
			}
		}

		if(!$oDB->isColumnExists("document_extra_vars","eid"))
		{
			$oDB->addColumn("document_extra_vars","eid","varchar",40);
			$obj->var_idx = '-1,-2';
			$output = executeQuery('document.getGroupsExtraVars', $obj);
			if($output->toBool() && $output->data && count($output->data))
			{
				foreach($output->data as $extra_vars)
				{
					$args->module_srl = $extra_vars->module_srl;
					$args->var_idx = $extra_vars->idx;
					$args->new_eid = "extra_vars".$extra_vars->idx;
					$output = executeQuery('document.updateDocumentExtraVarEid', $args);
				}
			}
		}

		// 2011. 03. 30 Cubrid index Check the index in the document_extra_vars table
		if(!$oDB->isIndexExists("document_extra_vars", "idx_document_list_order"))
		{
			$oDB->addIndex("document_extra_vars", "idx_document_list_order", array("document_srl","module_srl","var_idx"), false);
		}

		//2011. 04. 07 adding description column to document categories
		if(!$oDB->isColumnExists("document_categories","description")) $oDB->addColumn('document_categories',"description","varchar",200,0);

		//2011. 05. 23 adding status column to document
		if(!$oDB->isColumnExists('documents', 'status'))
		{
			$oDB->addColumn('documents', 'status', 'varchar', 20, 'PUBLIC');
			$args->is_secret = 'Y';
			$output = executeQuery('document.updateDocumentStatus', $args);
		}

		// 2011. 09. 08 drop column document is_secret
		if($oDB->isColumnExists('documents', 'status') && $oDB->isColumnExists('documents', 'is_secret'))
			$oDB->dropColumn('documents', 'is_secret');

		//2011. 06. 07 merge column, allow_comment and lock_comment
		if($oDB->isColumnExists('documents', 'allow_comment') || $oDB->isColumnExists('documents', 'lock_comment'))
		{
			$oDB->addColumn('documents', 'comment_status', 'varchar', 20, 'ALLOW');

			$args->commentStatus = 'DENY';

			// allow_comment='Y', lock_comment='Y'
			$args->allowComment = 'Y';
			$args->lockComment = 'Y';
			$output = executeQuery('document.updateDocumentCommentStatus', $args);

			// allow_comment='N', lock_comment='Y'
			$args->allowComment = 'N';
			$args->lockComment = 'Y';
			$output = executeQuery('document.updateDocumentCommentStatus', $args);

			// allow_comment='N', lock_comment='N'
			$args->allowComment = 'N';
			$args->lockComment = 'N';
			$output = executeQuery('document.updateDocumentCommentStatus', $args);
		}

		if($oDB->isColumnExists('documents', 'allow_comment') && $oDB->isColumnExists('documents', 'comment_status'))
			$oDB->dropColumn('documents', 'allow_comment');

		if($oDB->isColumnExists('documents', 'lock_comment') && $oDB->isColumnExists('documents', 'comment_status'))
			$oDB->dropColumn('documents', 'lock_comment');

		if(!$oDB->isIndexExists("documents", "idx_module_status"))
			$oDB->addIndex("documents", "idx_module_status", array("module_srl","status"));

		// 2012. 02. 27 Add a trigger to copy extra keys when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'document', 'controller', 'triggerCopyModuleExtraKeys', 'after'))
		{
			$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'document', 'controller', 'triggerCopyModuleExtraKeys', 'after');
		}

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'document', 'controller', 'triggerCopyModule', 'after'))
		{
			$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'document', 'controller', 'triggerCopyModule', 'after');
		}

		return new Object(0,'success_updated');
	}

	/**
	 * Re-generate the cache file
	 * @return void
	 */
	function recompileCache()
	{
	}

	/**
	 * Document Status List
	 * @return array
	 */
	function getStatusList()
	{
		return $this->statusList;
	}

	/**
	 * Return default status
	 * @return string
	 */
	function getDefaultStatus()
	{
		return $this->statusList['public'];
	}

	/**
	 * Return status by key
	 * @return string
	 */
	function getConfigStatus($key)
	{
		if(array_key_exists(strtolower($key), $this->statusList)) return $this->statusList[$key];
		else $this->getDefaultStatus();
	}
}
/* End of file document.class.php */
/* Location: ./modules/document/document.class.php */
