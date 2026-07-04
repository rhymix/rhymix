<?php

namespace Rhymix\Modules\Layout\Controllers;

use Rhymix\Framework\DB;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Exceptions\TargetNotFound;
use Rhymix\Framework\Filters\FilenameFilter;
use Rhymix\Framework\Responses\RedirectResponse;
use Rhymix\Modules\Extravar\Models\Value as ExtraValue;
use Rhymix\Modules\Layout\Models\Theme as ThemeModel;
use Rhymix\Modules\Module\Models\Filebox as FileboxModel;
use BaseObject;
use Context;
use Layout;
use stdClass;

class Theme extends Layout
{
	/**
	 * Theme list
	 */
	public function dispLayoutAdminThemeList()
	{
		// Get the list of installed themes.
		$theme_list = ThemeModel::getInstalledThemeList();
		Context::set('theme_list', $theme_list);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('theme_list');
	}

	/**
	 * Theme config page
	 */
	public function dispLayoutAdminThemeConfig()
	{
		$theme_name = Context::get('theme');
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $theme_name))
		{
			throw new InvalidRequest;
		}

		$theme_info = ThemeModel::getThemeInfo($theme_name);
		if (!$theme_info)
		{
			throw new TargetNotFound;
		}

		$theme_config = ThemeModel::getThemeConfig($theme_name);
		$config_index = 1;
		foreach ($theme_info->config as $key => $var)
		{
			$input = new ExtraValue(0, $config_index++, $var->name, $var->type);
			$input->parent_type = 'theme';
			$input->input_name = $var->name;
			$input->input_id = $var->name;
			$input->value = $theme_config->{$var->name} ?? $var->default;
			if ($var->options)
			{
				$input->options = [];
				$input->is_dict_options = 'Y';
				foreach ($var->options as $option)
				{
					$input->options[$option->value] = $option->title;
				}
			}
			$var->input = $input->getFormHTML();
		}

		Context::set('theme', $theme_name);
		Context::set('theme_info', $theme_info);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('theme_config');
	}

	/**
	 * Save theme config
	 */
	public function procLayoutAdminSaveThemeConfig()
	{
		$theme_name = Context::get('theme');
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $theme_name))
		{
			throw new InvalidRequest;
		}

		$theme_info = ThemeModel::getThemeInfo($theme_name);
		if (!$theme_info)
		{
			throw new TargetNotFound;
		}

		$sub_name = Context::get('sub_name');
		if ($sub_name !== 'theme' && !isset($theme_info->provides[$sub_name]))
		{
			throw new TargetNotFound;
		}

		// Fetch the old config and prepare a new config object.
		$old_config = ThemeModel::getThemeConfig($theme_name, $sub_name);
		$new_config = new stdClass;
		$vars = Context::getRequestVars();

		if ($sub_name === 'theme')
		{
			$config = $theme_info->config;
		}
		else
		{
			$config = $theme_info->loadSubConfig($sub_name);
		}

		foreach ($config as $key => $var)
		{
			// Expect an array?
			$expect_array = isset(ExtraValue::ARRAY_TYPES[$var->type]) && !in_array($var->type, ['radio', 'select']);

			// Image and file uploads
			if ($var->type === 'image' || $var->type === 'file')
			{
				if (isset($vars->{'_delete_' . $key}) && $vars->{'_delete_' . $key} === 'Y')
				{
					// Delete existing file
					if (isset($old_config->{$key}) && isset($old_config->{$key}->filebox_srl))
					{
						$output = FileboxModel::deleteFile($old_config->{$key}->filebox_srl);
						if (!$output->toBool())
						{
							return $output;
						}
					}
					$new_config->{$key} = null;
				}
				elseif (isset($vars->{$key}) && is_array($vars->{$key}) && is_uploaded_file($vars->{$key}['tmp_name']))
				{
					// Check file extension
					if ($var->type === 'image')
					{
						if (!preg_match('/\.(gif|jpe?g|png|bmp|webp|svg)$/i', $vars->{$key}['name'], $match))
						{
							return new BaseObject(-1, sprintf(lang('msg_filebox_invalid_extension'), $match[1]));
						}
					}
					else
					{
						if (preg_match(FileboxModel::FORBIDDEN_EXTENSIONS, $vars->{$key}['name'], $match))
						{
							return new BaseObject(-1, sprintf(lang('msg_filebox_invalid_extension'), $match[1]));
						}
					}

					// Delete existing file
					if (isset($old_config->{$key}) && isset($old_config->{$key}->filebox_srl))
					{
						$output = FileboxModel::deleteFile($old_config->{$key}->filebox_srl);
						if (!$output->toBool())
						{
							return $output;
						}
					}

					// Save new file to filebox
					$output = FileboxModel::insertFile((object)[
						'member_srl' => $this->user->member_srl,
						'addfile' => $vars->{$key},
						'comment' => 'theme:' . $theme_name . ':' . $sub_name . ':' . $key,
					]);
					if (!$output->toBool())
					{
						return $output;
					}

					// Store filebox information as theme config
					$new_config->{$key} = (object)[
						'filebox_srl' => $output->get('module_filebox_srl'),
						'source_filename' => FilenameFilter::clean($vars->{$key}['name']),
						'uploaded_filename' => $output->get('save_filename'),
						'file_size' => intval($vars->{$key}['size']),
					];
				}
				else
				{
					$new_config->{$key} = $old_config->{$key} ?? null;
				}
			}

			// Other types of variables
			elseif (isset($vars->{$key}))
			{
				if ($expect_array)
				{
					$new_config->{$key} = is_array($vars->{$key}) ? array_values($vars->{$key}) : [strval($vars->{$key})];
				}
				else
				{
					$new_config->{$key} = strval($vars->{$key});
				}
			}
			else
			{
				$new_config->{$key} = $expect_array ? [] : '';
			}
		}

		// Insert or update the theme configuration in the DB.
		if ($old_config === null)
		{
			$output = ThemeModel::insertThemeConfig($theme_name, $sub_name, $new_config);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		else
		{
			$output = ThemeModel::updateThemeConfig($theme_name, $sub_name, $new_config);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		$response = new RedirectResponse();
		$response->setRedirectUrl(getNotEncodedUrl(['module' => 'admin', 'act' => 'dispLayoutAdminThemeList']));
		return $response;
	}

	/**
	 * Apply theme
	 */
	public function procLayoutAdminApplyTheme()
	{

	}
}
