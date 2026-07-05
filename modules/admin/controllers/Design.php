<?php

namespace Rhymix\Modules\Admin\Controllers;

use Context;
use Rhymix\Framework\DB;
use Rhymix\Framework\Storage;
use Rhymix\Modules\Layout\Models\Theme as ThemeModel;

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

		$siteDesignFile = \RX_BASEDIR . 'files/site_design/design_0.php';
		$layoutTarget = 'layout_srl';
		$skinTarget = 'skin';
		if ($vars->target_type == 'M')
		{
			$layoutTarget = 'mlayout_srl';
			$skinTarget = 'mskin';
		}

		if (Storage::isReadable($siteDesignFile))
		{
			include $siteDesignFile;
		}
		if (!isset($designInfo) || !is_object($designInfo))
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

		ThemeModel::setDefaultDesignConfig($designInfo);
	}

	/**
	 * Subroutine for the above;
	 *
	 * @deprecated
	 * @param object $designInfo
	 * @return void
	 */
	public function makeDefaultDesignFile(object $designInfo): void
	{
		ThemeModel::setDefaultDesignConfig($designInfo);
	}
}
