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
use MenuAdminModel;
use stdClass;

class Theme extends Layout
{
	/**
	 * Theme list
	 */
	public function dispLayoutAdminThemeList()
	{
		// Get the list of installed themes.
		$theme_list = ThemeModel::getThemeList();
		Context::set('theme_list', $theme_list);

		// Get the currently active theme.
		$default_design_config = ThemeModel::getDefaultDesignConfig();
		Context::set('active_theme', $default_design_config->theme);

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

		// Load config for the theme itself.
		$theme_config = ThemeModel::getThemeConfig($theme_name);
		$config_index = 1;
		foreach ($theme_info->config as $var)
		{
			$input = new ExtraValue(0, $config_index++, $var->name, $var->type);
			$input->parent_type = 'theme';
			$input->input_name = 'theme__' . $var->name;
			$input->input_id = 'theme__' . $var->name;
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

		// Load config for each layout and skin provided by the theme.
		$sub_infos = [];
		$sub_menus = [];
		foreach ($theme_info->provides as $sub_name => $sub_info)
		{
			$sub_info = $theme_info->loadSubConfig($sub_name);
			if (!$sub_info)
			{
				continue;
			}

			foreach ($sub_info->config as $var)
			{
				$input = new ExtraValue(0, $config_index++, $var->name, $var->type);
				$input->parent_type = 'theme';
				$input->input_name = $sub_name . '__' . $var->name;
				$input->input_id = $sub_name . '__' . $var->name;
				$input->value = $theme_config->{$sub_name}->{$var->name} ?? $var->default;
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

			if ($sub_info->type === 'layout')
			{
				$sub_menus[$sub_name] = get_object_vars($theme_config->{$sub_name}->menus ?? new stdClass);
			}

			$sub_infos[$sub_name] = $sub_info;
		}

		// Load available menu list.
		$menu_list = MenuAdminModel::getInstance()->getMenus();

		Context::set('theme', $theme_name);
		Context::set('theme_info', $theme_info);
		Context::set('sub_infos', $sub_infos);
		Context::set('sub_menus', $sub_menus);
		Context::set('menu_list', $menu_list);

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

		$vars = Context::getRequestVars();

		$oDB = DB::getInstance();
		$oDB->begin();

		// Update the config for the theme itself and for each layout and skin.
		$theme_config = ThemeModel::getThemeConfig($theme_name);
		$new_config = new stdClass;
		$sub_names = array_keys($theme_info->provides);
		array_unshift($sub_names, 'theme');
		foreach ($sub_names as $sub_name)
		{
			// Load the existing config and info for this sub name.
			if ($sub_name === 'theme')
			{
				$old_config = $theme_config;
				$sub_info = $theme_info;
			}
			else
			{
				$old_config = $theme_config->{$sub_name} ?? new stdClass;
				$sub_info = $theme_info->loadSubConfig($sub_name);
				if (!$sub_info)
				{
					continue;
				}
			}

			// Create a new object to hold the new config for this sub name.
			$sub_config = new stdClass;
			foreach ($sub_info->config as $key => $var)
			{
				// Combine the sub name with the key to get the actual submitted value.
				$value = $vars->{$sub_name . '__' . $key} ?? null;
				$del_value = $vars->{'_delete_' . $sub_name . '__' . $key} ?? null;

				// Expect an array?
				$expect_array = isset(ExtraValue::ARRAY_TYPES[$var->type]) && !in_array($var->type, ['radio', 'select']);

				// Image and file uploads
				if ($var->type === 'image' || $var->type === 'file')
				{
					if (isset($del_value) && $del_value === 'Y')
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
						$sub_config->{$key} = null;
					}
					elseif (isset($value) && is_array($value) && is_uploaded_file($value['tmp_name']))
					{
						// Check file extension
						if ($var->type === 'image')
						{
							if (!preg_match('/\.(gif|jpe?g|png|bmp|webp|svg)$/i', $value['name'], $match))
							{
								return new BaseObject(-1, sprintf(lang('msg_filebox_invalid_extension'), $match[1]));
							}
						}
						else
						{
							if (preg_match(FileboxModel::FORBIDDEN_EXTENSIONS, $value['name'], $match))
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
							'addfile' => $value,
							'comment' => 'theme:' . $theme_name . ':' . $sub_name . ':' . $key,
						]);
						if (!$output->toBool())
						{
							return $output;
						}

						// Store filebox information as theme config
						$sub_config->{$key} = (object)[
							'filebox_srl' => $output->get('module_filebox_srl'),
							'source_filename' => FilenameFilter::clean($value['name']),
							'uploaded_filename' => $output->get('save_filename'),
							'file_size' => intval($value['size']),
						];
					}
					else
					{
						$sub_config->{$key} = $old_config->{$key} ?? null;
					}
				}

				// Other types of variables
				elseif (isset($value))
				{
					if ($expect_array)
					{
						$sub_config->{$key} = is_array($value) ? array_values($value) : [strval($value)];
					}
					else
					{
						$sub_config->{$key} = strval($value);
					}
				}
				else
				{
					$sub_config->{$key} = $expect_array ? [] : '';
				}
			}

			// Add menu settings for layouts
			if (isset($sub_info->type) && $sub_info->type === 'layout')
			{
				$sub_config->menus = new stdClass;
				foreach ($sub_info->menus as $menu)
				{
					$menu_value = $vars->{$sub_name . '__menus__' . $menu->name} ?? null;
					if (isset($menu_value))
					{
						$sub_config->menus->{$menu->name} = intval($menu_value);
					}
					else
					{
						$sub_config->menus->{$menu->name} = 0;
					}
				}
			}

			// Add the new sub config to the new config for the theme.
			if ($sub_name === 'theme')
			{
				$new_config = $sub_config;
			}
			else
			{
				$new_config->{$sub_name} = $sub_config;
			}
		}


		// Insert or update the theme configuration in the DB.
		if ($theme_config === null)
		{
			$output = ThemeModel::insertThemeConfig($theme_name, $new_config);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		else
		{
			$output = ThemeModel::updateThemeConfig($theme_name, $new_config);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		$oDB->commit();

		$response = new RedirectResponse();
		$response->setRedirectUrl(getNotEncodedUrl(['module' => 'admin', 'act' => 'dispLayoutAdminThemeList']));
		return $response;
	}

	/**
	 * Apply theme as default
	 */
	public function procLayoutAdminApplyTheme()
	{
		$theme_name = Context::get('active_theme');
		if ($theme_name !== null && !preg_match('/^[a-zA-Z0-9_]+$/', $theme_name))
		{
			throw new InvalidRequest;
		}
		if ($theme_name !== null && !ThemeModel::getThemeInfo($theme_name))
		{
			throw new TargetNotFound;
		}

		$default_design_config = ThemeModel::getDefaultDesignConfig();
		$default_design_config->theme = $theme_name ?? '';
		ThemeModel::setDefaultDesignConfig($default_design_config);

		$response = new RedirectResponse();
		$response->setRedirectUrl(getNotEncodedUrl(['module' => 'admin', 'act' => 'dispLayoutAdminThemeList']));
		return $response;
	}
}
