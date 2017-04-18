<?php
class seoAdminController extends seo
{
	function procSeoAdminSaveSetting()
	{
		$oModuleController = getController('module');

		$vars = Context::getRequestVars();
		$config = $this->getConfig();

		if ($vars->setting_section == 'general')
		{
			// 기본 설정
			$config->enable = ($vars->enable === 'Y') ? 'Y' : 'N';
			$config->use_optimize_title = $vars->use_optimize_title;
			$config->site_name = $vars->site_name;
			$config->site_slogan = $vars->site_slogan;
			$config->site_description = $vars->site_description;
			$config->site_keywords = $vars->site_keywords;

			if ($vars->site_image)
			{
				$path = _XE_PATH_ . 'files/attach/site_image/';
				$ext = strtolower(array_pop(explode('.', $vars->site_image['name'])));
				$timestamp = time();
				$filename = "site_image.{$timestamp}.{$ext}";
				FileHandler::copyFile($vars->site_image['tmp_name'], $path . $filename);
				$config->site_image = $filename;
				
				list($width, $height) = @getimagesize($path . $filename);
				$site_image_dimension = array('width' => $width, 'height' => $height);
				Rhymix\Framework\Cache::set('seo:site_image', $site_image_dimension, 0, true);
			}
		}
		elseif ($vars->setting_section == 'analytics')
		{
			// analytics

			// Google
			$config->ga_id = trim($vars->ga_id);
			$config->ga_except_admin = $vars->ga_except_admin;

			// Naver
			$config->na_id = trim($vars->na_id);
			$config->na_except_admin = $vars->na_except_admin;
		}
		elseif ($vars->setting_section == 'miscellaneous')
		{
			// miscellaneous

			// Facebook
			$config->fb_app_id = trim($vars->fb_app_id);
			$config->fb_admins = trim($vars->fb_admins);
		}

		$config->site_image_url = NULL;

		$oModuleController->updateModuleConfig('seo', $config);

		if($config->enable === 'Y')
		{
			$this->moduleUpdate();
		}
		else
		{
			// Delete Triggers
			$oModuleController = getController('module');
			$oModuleController->deleteModuleTriggers('seo');
		}

		$this->setMessage('success_updated');
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
	}
}
/* !End of file */
