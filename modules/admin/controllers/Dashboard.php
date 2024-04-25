<?php

namespace Rhymix\Modules\Admin\Controllers;

use Context;
use FileHandler;
use AddonAdminModel;
use DocumentAdminModel;
use DocumentModel;
use CommentModel;
use MemberAdminModel;
use MemberController;
use ModuleModel;

class Dashboard extends Base
{
	/**
	 * Easy install flag file
	 */
	public const EASYINSTALL_FLAG_FILE = 'files/env/easyinstall_last';

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

		// Retrieve the list of installed modules
		$this->checkEasyInstall();

		// Get need update from easy install
		//$oAutoinstallAdminModel = getAdminModel('autoinstall');
		//$needUpdateList = $oAutoinstallAdminModel->getNeedUpdateList();
		$needUpdateList = array();

		// Check counter addon
		$oAddonAdminModel = AddonAdminModel::getInstance();
		$counterAddonActivated = $oAddonAdminModel->isActivatedAddon('counter');
		if(!$counterAddonActivated)
		{
			$columnList = array('member_srl', 'nick_name', 'user_name', 'user_id', 'email_address');
			$args = new \stdClass;
			$args->page = 1;
			$args->list_count = 5;
			$output = executeQuery('member.getMemberList', $args, $columnList);
			Context::set('latestMemberList', $output->data);
			unset($args, $output, $columnList);
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
		Context::set('counterAddonActivated', $counterAddonActivated);

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

	/**
	 * Check easy install.
	 *
	 * @return void
	 */
	public function checkEasyInstall()
	{
		$lastTime = intval(FileHandler::readFile(self::EASYINSTALL_FLAG_FILE));
		if($lastTime > $_SERVER['REQUEST_TIME'] - 60 * 60 * 24 * 30)
		{
			return;
		}

		$oAutoinstallAdminModel = getAdminModel('autoinstall');
		$config = $oAutoinstallAdminModel->getAutoInstallAdminModuleConfig();

		$oAutoinstallModel = getModel('autoinstall');
		$params = array();
		$params["act"] = "getResourceapiLastupdate";
		$body = \XmlGenerater::generate($params);
		$buff = FileHandler::getRemoteResource($config->download_server, $body, 3, "POST", "application/xml");
		$xml_lUpdate = new \XeXmlParser();
		$lUpdateDoc = $xml_lUpdate->parse($buff);
		$updateDate = $lUpdateDoc->response->updatedate->body;

		if(!$updateDate)
		{
			$this->_updateEasyInstallFlagFile();
			return;
		}

		$item = $oAutoinstallModel->getLatestPackage();
		if(!$item || $item->updatedate < $updateDate)
		{
			$oController = getAdminController('autoinstall');
			$oController->_updateinfo();
		}
		$this->_updateEasyInstallFlagFile();
	}

	/**
	 * Update the easy install flag file.
	 *
	 * @return void
	 */
	protected function _updateEasyInstallFlagFile()
	{
		FileHandler::writeFile(self::EASYINSTALL_FLAG_FILE, \RX_TIME);
	}
}
