<?php

namespace Rhymix\Framework;

use Rhymix\Modules\Layout\Models\Theme as ThemeModel;

/**
 * All widgets must extend this class.
 *
 * For backward compatibility, WidgetHandler is provided as an alias to this class.
 */
class AbstractWidget
{
	/**
	 * Name of the current widget.
	 */
	public $widget_name = '';

	/**
	 * Path to the current widget's base directory.
	 */
	public $widget_path = '';

	/**
	 * Render a template with optional variables.
	 *
	 * @param string $skin_name
	 * @param string $filename
	 * @param array $vars
	 * @return string
	 */
	public function renderWidgetSkin(string $skin_name, string $filename, array $vars = []): string
	{
		if (preg_match('/^theme:([^:]+):(.+)$/', $skin_name, $matches))
		{
			$theme_info = ThemeModel::getThemeInfo($matches[1]);
			if ($theme_info && isset($theme_info->provides[$matches[2]]))
			{
				$template_path = $theme_info->path . $theme_info->provides[$matches[2]]->path;
				if (!Storage::isDirectory($template_path))
				{
					return '';
				}
			}
			else
			{
				return '';
			}
		}
		else
		{
			$template_path = sprintf('%sskins/%s', $this->widget_path, $skin_name);
			if (!Storage::isDirectory($template_path))
			{
				return '';
			}
		}

		$oTemplate = new Template($template_path, $filename);
		if (count($vars) > 0)
		{
			$oTemplate->setVars($vars);
		}
		return $oTemplate->compile();
	}
}
