<?php

namespace Rhymix\Modules\Admin\Controllers;

use Context;
use FileHandler;
use AddonModel;
use DocumentAdminModel;
use DocumentModel;
use CommentModel;
use MemberAdminModel;
use MemberController;
use ModuleModel;

class Dashboard extends Base
{
	/**
	 * Display the dashboard.
	 */
	public function dispAdminIndex()
	{
		// Get statistics
		$args = new \stdClass;
		$args->date = date('Ymd000000', \RX_TIME - 60 * 60 * 24);
		$today = date('Ymd');

		// Member Status
		$oMemberAdminModel = MemberAdminModel::getInstance();
		$status = new \stdClass;
		$status->member = new \stdClass;
		$status->member->todayCount = $oMemberAdminModel->getMemberCountByDate($today);
		$status->member->totalCount = $oMemberAdminModel->getMemberCountByDate();

		// Document Status
		$oDocumentAdminModel = DocumentAdminModel::getInstance();
		$statusList = array('PUBLIC', 'SECRET');
		$status->document = new \stdClass;
		$status->document->todayCount = $oDocumentAdminModel->getDocumentCountByDate($today, array(), $statusList);
		$status->document->totalCount = $oDocumentAdminModel->getDocumentCountByDate('', array(), $statusList);

		Context::set('status', $status);

		// Latest Document
		$args->list_count = 5;
		$columnList = array('document_srl', 'module_srl', 'category_srl', 'title', 'nick_name', 'member_srl');
		$output = DocumentModel::getDocumentList($args, false, false, $columnList);
		Context::set('latestDocumentList', $output->data);
		unset($args, $output, $columnList);

		// Latest Comment
		$args = new \stdClass;
		$args->list_count = 5;
		$columnList = array('comment_srl', 'module_srl', 'document_srl', 'content', 'nick_name', 'member_srl');
		$output = CommentModel::getNewestCommentList($args, $columnList);
		if (is_array($output))
		{
			foreach ($output as $key => $value)
			{
				$value->content = strip_tags($value->content);
			}
		}
		else
		{
			$output = [];
		}
		Context::set('latestCommentList', $output);
		unset($args, $output, $columnList);

		// Get list of modules
		$module_list = ModuleModel::getModuleList();
		$addTables = false;
		$wrongPaths = [];
		$needUpdate = false;
		if (is_array($module_list))
		{
			$priority = array(
				'module' => 1000000,
				'member' => 100000,
				'document' => 10000,
				'comment' => 1000,
				'file' => 100,
			);
			usort($module_list, function($a, $b) use($priority) {
				$a_priority = isset($priority[$a->module]) ? $priority[$a->module] : 0;
				$b_priority = isset($priority[$b->module]) ? $priority[$b->module] : 0;
				if ($a_priority == 0 && $b_priority == 0)
				{
					return strcmp($a->module, $b->module);
				}
				else
				{
					return $b_priority - $a_priority;
				}
			});
			foreach ($module_list as $value)
			{
				if ($value->need_install)
				{
					$addTables = TRUE;
				}
				if ($value->need_update)
				{
					$needUpdate = TRUE;
				}
				if (!preg_match('/^[a-z0-9_]+$/i', $value->module))
				{
					$wrongPaths[] = $value->module;
				}
			}
		}

		// Get need update from easy install
		$needUpdateList = array();

		// Check counter status
		$counter_config = ModuleModel::getModuleConfig('counter');
		if (isset($counter_config->is_enabled) && $counter_config->is_enabled == 'Y')
		{
			$use_counter = true;
		}
		else
		{
			$use_counter = AddonModel::isActivated('counter');
		}

		// If no counter, show latest members
		if (!$use_counter)
		{
			$columnList = ['member_srl', 'nick_name', 'user_name', 'user_id', 'email_address'];
			$output = executeQuery('member.getMemberList', ['list_count' => 5, 'page' => 0], $columnList);
			Context::set('latestMemberList', $output->data);
			unset($output, $columnList);
		}

		// Check unnecessary files
		$cleanup_list = Maintenance\Cleanup::getInstance()->checkFiles();

		Context::set('cleanup_list', $cleanup_list);
		Context::set('module_list', $module_list);
		Context::set('needUpdate', false);
		Context::set('addTables', $addTables);
		Context::set('wrongPaths', $wrongPaths);
		Context::set('needUpdate', $needUpdate);
		Context::set('newVersionList', $needUpdateList);
		Context::set('use_counter', $use_counter);

		$oSecurity = new \Security();
		$oSecurity->encodeHTML('module_list..', 'module_list..author..', 'newVersionList..');

		Context::set('layout', 'none');
		$this->setTemplateFile('index');
	}

	/**
	 * Admin logout action.
	 */
	public function procAdminLogout()
	{
		MemberController::getInstance()->procMemberLogout();
		header('Location: ' . getNotEncodedUrl(''));
	}
}
