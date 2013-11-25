<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @brief If member_srl exists in the div or span, replace to image name or nick image for each member_srl
 * */
function memberTransImageName($matches)
{
	// If member_srl < 0, then return text only in the body
	$member_srl = $matches[3];
	if($member_srl < 0)
	{
		return $matches[5];
	}
	// If member_srl=o(not a member), return the entire body
	if(!$member_srl)
	{
		return $matches[0];
	}

	$oMemberModel = getModel('member');
	$nick_name = $matches[5];

	$_tmp = &$GLOBALS['_transImageNameList'][$member_srl];
	// If pre-defined data in the global variablesm return it
	if(!$_tmp->cached)
	{
		$_tmp->cached = true;
		$image_name_file = sprintf('files/member_extra_info/image_name/%s%d.gif', getNumberingPath($member_srl), $member_srl);
		$image_mark_file = sprintf('files/member_extra_info/image_mark/%s%d.gif', getNumberingPath($member_srl), $member_srl);

		if(file_exists(_XE_PATH_ . $image_name_file))
		{
			$_tmp->image_name_file = $image_name_file;
		}
		else
		{
			$image_name_file = '';
		}

		if(file_exists(_XE_PATH_ . $image_mark_file))
		{
			$_tmp->image_mark_file = $image_mark_file;
		}
		else
		{
			$image_mark_file = '';
		}

		$site_module_info = Context::get('site_module_info');
		$group_image = $oMemberModel->getGroupImageMark($member_srl, $site_module_info->site_srl);
		$_tmp->group_image = $group_image;
	}
	else
	{
		$group_image = $_tmp->group_image;
		$image_name_file = $_tmp->image_name_file;
		$image_mark_file = $_tmp->image_mark_file;
	}

	// If image name and mark doesn't exist, set the original information
	if(!$image_name_file && !$image_mark_file && !$group_image)
	{
		return $matches[0];
	}

	// check member_config

	$config = $oMemberModel->getMemberConfig();

	if($config->image_name == 'Y' && $image_name_file)
	{
		$nick_name = sprintf('<img src="%s%s" alt="id: %s" title="id: %s" style="border:0;vertical-align:middle;margin-right:3px" />', Context::getRequestUri(), $image_name_file, strip_tags($nick_name), strip_tags($nick_name));
	}
	if($config->image_mark == 'Y' && $image_mark_file)
	{
		$nick_name = sprintf('<img src="%s%s" alt="id: %s" title="id : %s" style="border:0;vertical-align:middle;margin-right:3px"/>%s', Context::getRequestUri(), $image_mark_file, strip_tags($nick_name), strip_tags($nick_name), $nick_name);
	}

	if($group_image)
	{
		$nick_name = sprintf('<img src="%s" style="border:0;max-height:16px;vertical-align:middle;margin-right:3px" alt="%s" title="%s" />%s', $group_image->src, $group_image->title, $group_image->description, $nick_name);
	}

	return preg_replace('/' . preg_quote($matches[5], '/') . '<\/' . $matches[6] . '>$/', '', $matches[0]) . $nick_name . '</' . $matches[6] . '>';
}

/* End of file member_extra_info.lib.php */
/* Location: ./addons/member_extra_info/member_extra_info.lib.php */
