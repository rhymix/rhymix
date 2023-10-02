<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  tagAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief admin controller class of the tag module
 */
class TagAdminController extends Tag
{
	/**
	 * Save admin config.
	 */
	public function procTagAdminInsertConfig()
	{
		$config = new stdClass;
		$vars = Context::getRequestVars();

		$config->separators = [];
		foreach ($vars->separators ?? [] as $val)
		{
			if (in_array($val, ['comma', 'hash', 'space']))
			{
				$config->separators[] = $val;
			}
		}

		$oModuleController = ModuleController::getInstance();
		$output = $oModuleController->insertModuleConfig($this->module, $config);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url'));
	}

	/**
	 * @brief Delete all tags for a particular module
	 */
	function deleteModuleTags($module_srl)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		return executeQuery('tag.deleteModuleTags', $args);
	}
}
/* End of file tag.admin.controller.php */
/* Location: ./modules/tag/tag.admin.controller.php */
