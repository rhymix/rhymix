<?php

namespace Rhymix\Modules\Admin\Controllers;

use Context;
use FileHandler;
use Rhymix\Framework\DB;

class Design extends Base
{
	/**
	 * Save the default design configuration.
	 */
	public function procAdminInsertDefaultDesignInfo()
	{
		$vars = Context::getRequestVars();
		$this->updateDefaultDesignInfo($vars);
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * Subroutine for the above.
	 *
	 * @param object $vars
	 * @return void
	 */
	public function updateDefaultDesignInfo(object $vars): void
	{
		$vars->module_skin = json_decode($vars->module_skin);

		$siteDesignPath = \RX_BASEDIR . 'files/site_design/';
		if (!is_dir($siteDesignPath))
		{
			FileHandler::makeDir($siteDesignPath);
		}

		$siteDesignFile = \RX_BASEDIR . 'files/site_design/design_0.php';
		$layoutTarget = 'layout_srl';
		$skinTarget = 'skin';
		if ($vars->target_type == 'M')
		{
			$layoutTarget = 'mlayout_srl';
			$skinTarget = 'mskin';
		}

		if (is_readable($siteDesignFile))
		{
			include $siteDesignFile;
		}
		else
		{
			$designInfo = new \stdClass;
		}

		$layoutSrl = (!$vars->layout_srl) ? 0 : $vars->layout_srl;
		$designInfo->{$layoutTarget} = $layoutSrl;

		foreach ($vars->module_skin as $moduleName => $skinName)
		{
			if ($moduleName == 'ARTICLE')
			{
				$moduleName = 'page';
			}
			if (!isset($designInfo->module))
			{
				$designInfo->module = new \stdClass;
			}
			if (!isset($designInfo->module->{$moduleName}))
			{
				$designInfo->module->{$moduleName} = new \stdClass;
			}
			$designInfo->module->{$moduleName}->{$skinTarget} = $skinName;
		}

		$this->makeDefaultDesignFile($designInfo);
	}

	/**
	 * Subroutine for the above;
	 *
	 * @param object $designInfo
	 * @return void
	 */
	public function makeDefaultDesignFile(object $designInfo): void
	{
		$buff = array();
		$buff[] = '<?php if(!defined("__XE__")) exit();';
		$buff[] = '$designInfo = new stdClass;';

		if($designInfo->layout_srl)
		{
			$buff[] = sprintf('$designInfo->layout_srl = %d; ', intval($designInfo->layout_srl));
		}

		if($designInfo->mlayout_srl)
		{
			$buff[] = sprintf('$designInfo->mlayout_srl = %d;', intval($designInfo->mlayout_srl));
		}

		$buff[] = '$designInfo->module = new stdClass;';

		foreach($designInfo->module as $moduleName => $skinInfo)
		{
			$buff[] = sprintf('$designInfo->module->{%s} = new stdClass;', var_export(strval($moduleName), true));
			foreach($skinInfo as $target => $skinName)
			{
				$buff[] = sprintf('$designInfo->module->{%s}->{%s} = %s;', var_export(strval($moduleName), true), var_export(strval($target), true), var_export(strval($skinName), true));
			}
		}

		$siteDesignFile = \RX_BASEDIR . 'files/site_design/design_0.php';
		FileHandler::writeFile($siteDesignFile, implode(\PHP_EOL, $buff));
	}
}
