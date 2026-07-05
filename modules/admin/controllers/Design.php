<?php

namespace Rhymix\Modules\Admin\Controllers;

use Context;
use Rhymix\Framework\DB;
use Rhymix\Framework\Storage;

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
		// Clean up the object.
		$valid_keys = ['layout_srl', 'mlayout_srl', 'module', 'theme'];
		foreach ($designInfo as $key => $val)
		{
			if (!in_array($key, $valid_keys))
			{
				unset($designInfo->{$key});
			}
		}
		$designInfo->theme = preg_replace('/[^a-zA-Z0-9:_-]/', '', $designInfo->theme ?? '');
		$designInfo->layout_srl = intval($designInfo->layout_srl);
		$designInfo->mlayout_srl = intval($designInfo->mlayout_srl);
		foreach ($designInfo->module ?? [] as $moduleName => $skinInfo)
		{
			$skinInfo = (object)[
				'skin' => $skinInfo->skin ?? '',
				'mskin' => $skinInfo->mskin ?? '',
			];
			$designInfo->module->{$moduleName} = $skinInfo;
		}

		// Write the object to a PHP file.
		$siteDesignFile = \RX_BASEDIR . 'files/site_design/design_0.php';
		$content = preg_replace('/=>\s+\(object\) array\(/', '=> (object) array(', var_export($designInfo, true));
		Storage::write($siteDesignFile, "<?php\n\n" . '$designInfo = ' . $content . ";\n");
	}
}
