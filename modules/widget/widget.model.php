<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  widgetModel
 * @author NAVER (developers@xpressengine.com)
 * @version 0.1
 * @brief Model class for widget modules
 */
class WidgetModel extends Widget
{
	/**
	 * @brief Wanted widget's path
	 */
	public static function getWidgetPath($widget_name)
	{
		$path = sprintf('./widgets/%s/', $widget_name);
		if(is_dir($path)) return $path;
		return "";
	}

	/**
	 * @brief Wanted widget style path
	 */
	public static function getWidgetStylePath($widgetStyle_name)
	{
		$path = sprintf('./widgetstyles/%s/', $widgetStyle_name);
		if(is_dir($path)) return $path;
		return "";
	}

	/**
	 * @brief Wanted widget style path
	 */
	public static function getWidgetStyleTpl($widgetStyle_name)
	{
		$path = self::getWidgetStylePath($widgetStyle_name);
		$tpl = sprintf('%swidgetstyle.html', $path);
		return $tpl;
	}

	/**
	 * @brief Wanted photos of the type and information
	 * Download a widget with type (generation and other means)
	 */
	public static function getDownloadedWidgetList()
	{
		$oAutoinstallModel = getModel('autoinstall');

		// 've Downloaded the widget and the widget's list of installed Wanted
		$searched_list = FileHandler::readDir('./widgets');
		$searched_count = count($searched_list);
		if(!$searched_count) return;
		sort($searched_list);
		// D which pertain to the list of widgets loop spins return statement review the information you need
		for($i=0;$i<$searched_count;$i++)
		{
			// The name of the widget
			$widget = $searched_list[$i];
			// Wanted information on the Widget
			$widget_info = self::getWidgetInfo($widget);
			if (!$widget_info)
			{
				continue;
			}

			// get easyinstall remove url
			$packageSrl = $oAutoinstallModel->getPackageSrlByPath($widget_info->path);
			$widget_info->remove_url = $oAutoinstallModel->getRemoveUrlByPackageSrl($packageSrl);

			// get easyinstall need update
			$package = $oAutoinstallModel->getInstalledPackages($packageSrl);
			$widget_info->need_update = $package[$packageSrl]->need_update ?? 'N';

			// get easyinstall update url
			if ($widget_info->need_update == 'Y')
			{
				$widget_info->update_url = $oAutoinstallModel->getUpdateUrlByPackageSrl($packageSrl);
			}

			$list[] = $widget_info;
		}
		return $list;
	}

	/**
	 * @brief Wanted photos of the type and information
	 * Download a widget with type (generation and other means)
	 */
	public static function getDownloadedWidgetStyleList()
	{
		// 've Downloaded the widget and the widget's list of installed Wanted
		$searched_list = FileHandler::readDir('./widgetstyles');
		$searched_count = count($searched_list);
		if(!$searched_count) return;
		sort($searched_list);
		// D which pertain to the list of widgets loop spins return statement review the information you need
		for($i=0;$i<$searched_count;$i++)
		{
			// The name of the widget
			$widgetStyle = $searched_list[$i];
			// Wanted information on the Widget
			$widgetStyle_info = self::getWidgetStyleInfo($widgetStyle);
			if ($widgetStyle_info)
			{
				$list[] = $widgetStyle_info;
			}
		}
		return $list;
	}

	/**
	 * @brief Modules conf/info.xml wanted to read the information
	 * It uses caching to reduce time for xml parsing ..
	 */
	public static function getWidgetInfo($widget)
	{
		// Check the widget path.
		$widget = preg_replace('/[^a-zA-Z0-9-_]/', '', $widget);
		$widget_path = self::getWidgetPath($widget);
		if (!$widget_path)
		{
			return;
		}

		// Check the XML file.
		$xml_file = sprintf("%sconf/info.xml", $widget_path);
		if (!file_exists($xml_file))
		{
			return;
		}

		// Check the local cache.
		$xml_mtime = filemtime($xml_file);
		if (isset($GLOBALS['__widget_info__'][$widget][$xml_mtime]))
		{
			return $GLOBALS['__widget_info__'][$widget][$xml_mtime];
		}

		// Check the system cache.
		$cache_key = sprintf('widget_info:%s:%d', $widget, $xml_mtime);
		$widget_info = Rhymix\Framework\Cache::get($cache_key);
		if ($widget_info)
		{
			$GLOBALS['__widget_info__'][$widget][$xml_mtime] = $widget_info;
			return $widget_info;
		}

		// Parse the XML file and store the result in the cache.
		$widget_info = Rhymix\Framework\Parsers\WidgetInfoParser::loadXML($xml_file, $widget);
		if (!$widget_info)
		{
			return;
		}

		Rhymix\Framework\Cache::set($cache_key, $widget_info);
		$GLOBALS['__widget_info__'][$widget][$xml_mtime] = $widget_info;
		return $widget_info;
	}

	/**
	 * @brief Modules conf/info.xml wanted to read the information
	 * It uses caching to reduce time for xml parsing ..
	 */
	public static function getWidgetStyleInfo($widgetStyle)
	{
		// Check the widget style path.
		$widgetStyle = preg_replace('/[^a-zA-Z0-9-_]/', '', (string)$widgetStyle);
		$widgetStyle_path = self::getWidgetStylePath($widgetStyle);
		if (!$widgetStyle_path)
		{
			return;
		}

		// Check the XML file.
		$xml_file = $widgetStyle_path . 'skin.xml';
		if (!file_exists($xml_file))
		{
			return;
		}

		// Check the local cache.
		$xml_mtime = filemtime($xml_file);
		if (isset($GLOBALS['__widgetstyle_info__'][$widgetStyle][$xml_mtime]))
		{
			return $GLOBALS['__widgetstyle_info__'][$widgetStyle][$xml_mtime];
		}

		// Check the system cache.
		$cache_key = sprintf('widgetstyle_info:%s:%d', $widgetStyle, $xml_mtime);
		$widgetStyle_info = Rhymix\Framework\Cache::get($cache_key);
		if ($widgetStyle_info)
		{
			$GLOBALS['__widgetstyle_info__'][$widgetStyle][$xml_mtime] = $widgetStyle_info;
			return $widgetStyle_info;
		}

		// Parse the XML file and store the result in the cache.
		$widgetStyle_info = Rhymix\Framework\Parsers\WidgetStyleInfoParser::loadXML($xml_file, $widgetStyle);
		if (!$widgetStyle_info)
		{
			return;
		}

		Rhymix\Framework\Cache::set($cache_key, $widgetStyle_info);
		$GLOBALS['__widgetstyle_info__'][$widgetStyle][$xml_mtime] = $widgetStyle_info;
		return $widgetStyle_info;
	}
}
/* End of file widget.model.php */
/* Location: ./modules/widget/widget.model.php */
