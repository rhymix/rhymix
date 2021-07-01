<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @brief Function to change point icon.
 */
function pointLevelIconTrans($matches, $addon_info)
{
	$member_srl = $matches[3];
	// If anonymous or not member_srl go to Hide Point Icon
	if($member_srl < 1||!$member_srl)
	{
		return $matches[0];
	}

	if(!isset($addon_info->icon_duplication) || $addon_info->icon_duplication !== 'N')
	{
		// Check Group Image Mark
		$oMemberModel = getModel('member');
		if($oMemberModel->getGroupImageMark($member_srl))
		{
			return $matches[0];
		}
	}

	$orig_text = preg_replace('/' . preg_quote($matches[5], '/') . '<\/' . $matches[6] . '>$/', '', $matches[0]);

	if(!isset($GLOBALS['_pointLevelIcon'][$member_srl]))
	{
		// Get point configuration
		if(!isset($GLOBALS['_pointConfig']))
		{
			$GLOBALS['_pointConfig'] = getModel('module')->getModuleConfig('point') ?? new stdClass;
		}
		$config = $GLOBALS['_pointConfig'];

		// Get point model
		if(!isset($GLOBALS['_pointModel']))
		{
			$GLOBALS['_pointModel'] = getModel('point');
		}
		$oPointModel = $GLOBALS['_pointModel'];

		// Get points
		$exists = false;
		$point = $oPointModel->getPoint($member_srl, false, $exists);
		if(!$exists)
		{
			return $matches[0];
		}

		// Get level
		$level = $oPointModel->getLevel($point, $config->level_step);
		$text = $matches[5];

		// Get a path where level icon is
		$level_icon_type = $config->level_icon_type ?? 'gif';
		$level_icon = sprintf('%smodules/point/icons/%s/%d.%s', Context::getRequestUri(), $config->level_icon, $level, $level_icon_type);

		// Get per to go to the next level if not a top level
		$per = NULL;
		if($level < $config->max_level)
		{
			$next_point = $config->level_step[$level + 1];
			$present_point = $config->level_step[$level] ?? 0;
			if($next_point > 0)
			{
				$per = (int) (($point - $present_point) / ($next_point - $present_point) * 100);
				$per = $per . '%';
			}
		}

		$title = sprintf('%s:%s%s%s, %s:%s/%s', lang('point'), $point, $config->point_name, $per ? ' (' . $per . ')' : '', lang('level'), $level, $config->max_level);
		$alt = sprintf('[%s:%s]', lang('level'), $level);

		$GLOBALS['_pointLevelIcon'][$member_srl] = sprintf('<img src="%s" alt="%s" title="%s" class="xe_point_level_icon" style="vertical-align:middle;margin-right:3px;" />', $level_icon, $alt, $title);
	}
	$text = $GLOBALS['_pointLevelIcon'][$member_srl];

	return $orig_text . $text . $matches[5] . '</' . $matches[6] . '>';
}

/* End of file point_level_icon.lib.php */
/* Location: ./addons/point_level_icon/point_level_icon.lib.php */
