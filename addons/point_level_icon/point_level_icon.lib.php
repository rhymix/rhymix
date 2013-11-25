<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @brief Function to change point icon.
 */
function pointLevelIconTrans($matches)
{
	$member_srl = $matches[3];
	if($member_srl < 1)
	{
		return $matches[0];
	}

	$orig_text = preg_replace('/' . preg_quote($matches[5], '/') . '<\/' . $matches[6] . '>$/', '', $matches[0]);

	// Check Group Image Mark
	$oMemberModel = getModel('member');
	if($oMemberModel->getGroupImageMark($member_srl))
	{
		return $orig_text . $matches[5] . '</' . $matches[6] . '>';
	}

	if(!isset($GLOBALS['_pointLevelIcon'][$member_srl]))
	{
		// Get point configuration
		if(!$GLOBALS['_pointConfig'])
		{
			$oModuleModel = getModel('module');
			$GLOBALS['_pointConfig'] = $oModuleModel->getModuleConfig('point');
		}
		$config = $GLOBALS['_pointConfig'];

		// Get point model
		if(!$GLOBALS['_pointModel'])
		{
			$GLOBALS['_pointModel'] = getModel('point');
		}
		$oPointModel = &$GLOBALS['_pointModel'];

		// Get points
		if(!$oPointModel->isExistsPoint($member_srl))
		{
			return $matches[0];
		}

		$point = $oPointModel->getPoint($member_srl);

		// Get level
		$level = $oPointModel->getLevel($point, $config->level_step);
		$text = $matches[5];

		// Get a path where level icon is
		$level_icon = sprintf('%smodules/point/icons/%s/%d.gif', Context::getRequestUri(), $config->level_icon, $level);

		// Get per to go to the next level if not a top level
		$per = NULL;
		if($level < $config->max_level)
		{
			$next_point = $config->level_step[$level + 1];
			$present_point = $config->level_step[$level];
			if($next_point > 0)
			{
				$per = (int) (($point - $present_point) / ($next_point - $present_point) * 100);
				$per = $per . '%';
			}
		}

		$title = sprintf('%s:%s%s%s, %s:%s/%s', Context::getLang('point'), $point, $config->point_name, $per ? ' (' . $per . ')' : '', Context::getLang('level'), $level, $config->max_level);
		$alt = sprintf('[%s:%s]', Context::getLang('level'), $level);

		$GLOBALS['_pointLevelIcon'][$member_srl] = sprintf('<img src="%s" alt="%s" title="%s" class="xe_point_level_icon" style="vertical-align:middle;margin-right:3px;" />', $level_icon, $alt, $title);
	}
	$text = $GLOBALS['_pointLevelIcon'][$member_srl];

	return $orig_text . $text . $matches[5] . '</' . $matches[6] . '>';
}

/* End of file point_level_icon.lib.php */
/* Location: ./addons/point_level_icon/point_level_icon.lib.php */
