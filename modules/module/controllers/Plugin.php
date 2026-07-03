<?php

namespace Rhymix\Modules\Module\Controllers;

use Rhymix\Framework\DB;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Exceptions\TargetNotFound;
use Rhymix\Framework\Filters\FilenameFilter;
use Rhymix\Framework\MIME;
use Rhymix\Framework\Responses\RedirectResponse;
use Rhymix\Modules\Extravar\Models\Value as ExtraValue;
use Rhymix\Modules\Module\Models\Filebox as FileboxModel;
use Rhymix\Modules\Module\Models\Plugin as PluginModel;
use BaseObject;
use Context;
use ModuleHandler;
use stdClass;

class Plugin extends Base
{
	/**
	 * Display list of installed plugins.
	 */
	public function dispModuleAdminPlugins()
	{
		// Get the list of installed plugins.
		$plugin_list = PluginModel::getInstalledPluginList();
		Context::set('plugin_list', $plugin_list);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('plugin_list');
	}

	/**
	 * Save the list of enabled plugins.
	 */
	public function procModuleAdminSaveEnabledPlugins()
	{
		// Get the list of plugins to enable.
		$enabled_plugins = Context::get('enabled_plugins') ?: [];
		if (!is_array($enabled_plugins))
		{
			throw new InvalidRequest;
		}

		// Filter invalid plugin names.
		foreach ($enabled_plugins as $key => $plugin_name)
		{
			if (!preg_match('/^[a-zA-Z0-9_]+$/', $plugin_name))
			{
				throw new InvalidRequest;
			}
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		// Get the list of currently configured plugins.
		$configured_plugins = [];
		$output = executeQueryArray('module.getInstalledPluginList');
		foreach ($output->data as $row)
		{
			$configured_plugins[$row->plugin_name] = $row->is_enabled === 'Y';
		}

		// Insert or update the configuration for each plugin.
		foreach ($enabled_plugins as $plugin_name)
		{
			if (!isset($configured_plugins[$plugin_name]))
			{
				$output = PluginModel::insertPluginConfig($plugin_name, PluginModel::getDefaultConfig($plugin_name), true);
				if (!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
			else
			{
				$output = PluginModel::updatePluginConfig($plugin_name, null, true);
				if (!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}

		// Disable currently configured plugins that are not in the enabled list.
		foreach ($configured_plugins as $plugin_name => $is_enabled)
		{
			if ($is_enabled && !in_array($plugin_name, $enabled_plugins))
			{
				$output = PluginModel::updatePluginConfig($plugin_name, null, false);
				if (!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}

		$oDB->commit();

		$response = new RedirectResponse();
		$response->setRedirectUrl(getNotEncodedUrl(['module' => 'admin', 'act' => 'dispModuleAdminPlugins']));
		return $response;
	}

	/**
	 * Display the config page for a specific plugin.
	 */
	public function dispModuleAdminPluginConfig()
	{
		$plugin_name = Context::get('plugin');
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $plugin_name))
		{
			throw new InvalidRequest;
		}

		$plugin_info = PluginModel::getPluginInfo($plugin_name);
		if (!$plugin_info)
		{
			throw new TargetNotFound;
		}

		$plugin_config = PluginModel::getPluginConfig($plugin_name);
		$config_index = 1;
		foreach ($plugin_info->config as $key => $var)
		{
			$input = new ExtraValue(0, $config_index++, $var->name, $var->type);
			$input->parent_type = 'config';
			$input->input_name = $var->name;
			$input->input_id = $var->name;
			$input->value = $plugin_config->{$var->name} ?? $var->default;
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

		Context::set('plugin', $plugin_name);
		Context::set('plugin_info', $plugin_info);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('plugin_config');
	}

	/**
	 * Save the config for a specific plugin.
	 */
	public function procModuleAdminSavePluginConfig()
	{
		$plugin_name = Context::get('plugin');
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $plugin_name))
		{
			throw new InvalidRequest;
		}

		$plugin_info = PluginModel::getPluginInfo($plugin_name);
		if (!$plugin_info)
		{
			throw new TargetNotFound;
		}

		// Fetch the old config and prepare a new config object.
		$old_config = PluginModel::getPluginConfig($plugin_name);
		$new_config = new stdClass;
		$vars = Context::getRequestVars();

		foreach ($plugin_info->config as $key => $var)
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
						'comment' => $plugin_name . ':' . $key,
					]);
					if (!$output->toBool())
					{
						return $output;
					}

					// Store filebox information as plugin config
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

		// Insert or update the plugin configuration in the DB.
		if ($old_config === null)
		{
			$output = PluginModel::insertPluginConfig($plugin_name, $new_config, false);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		else
		{
			$output = PluginModel::updatePluginConfig($plugin_name, $new_config);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		$response = new RedirectResponse();
		$response->setRedirectUrl(getNotEncodedUrl(['module' => 'admin', 'act' => 'dispModuleAdminPlugins']));
		return $response;
	}
}
