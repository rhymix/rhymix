<?php

namespace Rhymix\Plugins\Member_Icons;

use Rhymix\Framework\AbstractPlugin;
use Rhymix\Modules\Module\Models\ModuleConfig as ModuleConfigModel;
use Rhymix\Modules\Module\Models\Plugin as PluginModel;
use Context;
use MemberModel;
use PointModel;

class Plugin extends AbstractPlugin
{
	/**
	 * List of acts to exclude from processing.
	 */
	public const EXCLUDE_ACTS = [
		'dispPageAdminContentModify' => true,
	];

	/**
	 * Cache for member module config.
	 */
	protected object $_memberConfig;

	/**
	 * Cache for point module config.
	 */
	protected object $_pointConfig;

	/**
	 * Cache for various other information.
	 */
	protected array $_cache = [
		'level' => [],
	];

	/**
	 * The constructor receives plugin configuration and registers an event handler.
	 *
	 * @param object $config
	 * @return void
	 */
	public function __construct(object $config)
	{
		$this->config = $config;
		if (!isset($config->icons) || !is_array($config->icons))
		{
			$config->icons = [];
		}

		// Register own event handler.
		$this->after('display', [$this, 'beforeDisplay']);

		// Disable conflicting addons.
		PluginModel::markPluginAsLoaded('member_extra_info');
		PluginModel::markPluginAsLoaded('point_level_icon');
	}

	/**
	 * Event handler for the display (before) event.
	 *
	 * @param string &$output
	 * @return void
	 */
	public function beforeDisplay(&$output)
	{
		if (Context::getResponseMethod() !== 'HTML' || isCrawler() || $output === '')
		{
			return;
		}

		if (isset(self::EXCLUDE_ACTS[Context::get('act')]))
		{
			return;
		}

		$replaced = preg_replace_callback('!<(div|span|a)\b([^>]*\bmember_([0-9-]+)[^>]*)>(.*?)</(\1)>!is', [$this, 'replace'], $output);
		if ($replaced)
		{
			$output = $replaced;
		}
	}

	/**
	 * Callback function for the actual replacement.
	 *
	 * @param array $matches
	 * @return string
	 */
	public function replace($matches): string
	{
		// Return if there is no member_srl or it is negative.
		$member_srl = intval($matches[3]);
		if ($member_srl <= 0)
		{
			return $matches[0];
		}
		$icons = [];

		// Add level icon.
		if (in_array('level', $this->config->icons))
		{
			if (!isset($this->_pointConfig))
			{
				$this->_pointConfig = ModuleConfigModel::getModuleConfig('point') ?? new \stdClass;
			}

			$level = $this->_getLevel($member_srl);
			if ($level !== null)
			{
				$level_icon_type = $this->_pointConfig->level_icon_type ?? 'gif';
				$level_icon_name = $this->_pointConfig->level_icon ?? 'default';
				$level_icon = \RX_BASEURL . sprintf('modules/point/icons/%s/%d.%s', $level_icon_name, $level, $level_icon_type);
				$icons[] = vsprintf('<img src="%s" alt="[%s:%s]" class="xe_point_level_icon" style="vertical-align:middle;margin-right:3px;" />', [
					$level_icon, lang('level'), $level,
				]);
			}
		}

		// Add group icon (group image mark).
		if (in_array('group_mark', $this->config->icons))
		{
			$group_mark = MemberModel::getGroupImageMark($member_srl);
			if ($group_mark && isset($group_mark->src))
			{
				$icons[] = vsprintf('<img src="%s" alt="%s" style="border:0;max-height:16px;vertical-align:middle;margin-right:3px;" />', [
					$group_mark->src, escape($group_mark->title, false), escape($group_mark->description ?: $group_mark->title, false),
				]);
			}
		}

		// Add member image mark.
		if (in_array('image_mark', $this->config->icons))
		{
			if (!isset($this->_memberConfig))
			{
				$this->_memberConfig = MemberModel::getMemberConfig();
			}

			if ($this->_memberConfig->image_mark === 'Y')
			{
				$image_mark = $this->_getImageMark($member_srl);
				if ($image_mark !== '')
				{
					$icons[] = vsprintf('<img src="%s" alt="[%s:%s]" style="vertical-align:middle;margin-right:3px;" />', [
						$image_mark, lang('image_mark'), $member_srl,
					]);
				}
			}
		}

		// Replace the name itself with an image if available.
		if (in_array('image_name', $this->config->icons))
		{
			if (!isset($this->_memberConfig))
			{
				$this->_memberConfig = MemberModel::getMemberConfig();
			}

			if ($this->_memberConfig->image_name === 'Y')
			{
				$image_name = $this->_getImageName($member_srl);
				if ($image_name !== '')
				{
					$icons[] = '';
					$matches[4] = vsprintf('<img src="%s" alt="[%s:%s]" style="vertical-align:middle;margin-right:3px;" />', [
						$image_name, lang('image_name'), $member_srl,
					]);
				}
			}
		}

		// Return the HTML with icons prepended to the original text.
		if (count($icons) > 0)
		{
			return sprintf('<%s%s>%s%s</%s>', $matches[1], $matches[2], implode('', $icons), $matches[4], $matches[5]);
		}
		else
		{
			return $matches[0];
		}
	}

	/**
	 * Get level.
	 *
	 * @param int $member_srl
	 * @return ?int
	 */
	protected function _getLevel(int $member_srl): ?int
	{
		if (!isset($this->_cache['level'][$member_srl]))
		{
			$point = PointModel::getPoint($member_srl, false, $exists);
			if (!$exists)
			{
				return null;
			}

			$level = PointModel::getLevel($point, $this->_pointConfig->level_step ?? []);
			$this->_cache['level'][$member_srl] = (int)$level;
		}

		return $this->_cache['level'][$member_srl];
	}

	/**
	 * Get image name.
	 *
	 * @param int $member_srl
	 * @return string
	 */
	protected function _getImageName(int $member_srl): string
	{
		if (!isset($this->_cache['image_name'][$member_srl]))
		{
			$filename = sprintf('files/member_extra_info/image_name/%s%d.gif', getNumberingPath($member_srl), $member_srl);
			if (file_exists(\RX_BASEDIR . $filename))
			{
				$this->_cache['image_name'][$member_srl] = \RX_BASEURL . $filename . '?t=' . filemtime(\RX_BASEDIR . $filename);
			}
			else
			{
				$this->_cache['image_name'][$member_srl] = '';
			}
		}

		return $this->_cache['image_name'][$member_srl];
	}

	/**
	 * Get image mark.
	 *
	 * @param int $member_srl
	 * @return string
	 */
	protected function _getImageMark(int $member_srl): string
	{
		if (!isset($this->_cache['image_mark'][$member_srl]))
		{
			$filename = sprintf('files/member_extra_info/image_mark/%s%d.gif', getNumberingPath($member_srl), $member_srl);
			if (file_exists(\RX_BASEDIR . $filename))
			{
				$this->_cache['image_mark'][$member_srl] = \RX_BASEURL . $filename . '?t=' . filemtime(\RX_BASEDIR . $filename);
			}
			else
			{
				$this->_cache['image_mark'][$member_srl] = '';
			}
		}

		return $this->_cache['image_mark'][$member_srl];
	}
}
