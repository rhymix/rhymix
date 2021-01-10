<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  emoticon
 * @author NAVER (developers@xpressengine.com)
 * @brief Emoticons image connected components
 */
class emoticon extends EditorHandler
{
	// editor_sequence from the editor must attend mandatory wearing ....
	var $editor_sequence = 0;
	var $component_path = '';
	var $emoticon_path = '';

	/**
	 * @brief editor_sequence and components out of the path
	 */
	function __construct($editor_sequence, $component_path)
	{
		$this->editor_sequence = $editor_sequence;
		$this->component_path = $component_path;
		$this->emoticon_path = $this->component_path . 'tpl/images';
	}

	/**
	 * @brief Returns a list of emoticons file
	 */
	function getEmoticonList()
	{
		$emoticon = Context::get('emoticon');
		if(!$emoticon || !preg_match('/^[a-z0-9\_]+$/i', $emoticon))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}
		
		$this->add('emoticons', $this->getEmoticons($emoticon));
	}

	/**
	 * @brief Likely to be recursively emoticons will search all the files to a subdirectory. 8000 gaekkajineun ran tests whether the stack and raise beef pro-overs and Unsure. (06/09/2007, Benny)
	 */
	function getEmoticons($emoticon)
	{
		$emoticon_path = sprintf('%s/%s', $this->emoticon_path, $emoticon);
		$emoticon_url = RX_BASEURL . preg_replace('@^\./@', '', $emoticon_path) . '/';
		if(!$emoticon_files = Rhymix\Framework\Storage::readDirectory($emoticon_path))
		{
			return array();
		}
		
		$output = array();
		foreach($emoticon_files as $file)
		{
			if(!preg_match('/\.(jpg|jpeg|gif|png)$/i', $file))
			{
				continue;
			}
			
			$file_name = basename($file);
			list($file_width, $file_height) = getimagesize($file);
			$svg_name = preg_replace('@\.[^.]+$@', '', $file_name) . '.svg';
			
			$output[] = array(
				'url' => $emoticon_url . $file_name,
				'width' => $file_width,
				'height' => $file_height,
				'svg' => file_exists(sprintf('%s/svg/%s', $emoticon_path, $svg_name)) ? ($emoticon_url . 'svg/' . $svg_name) : '',
				'alt' => $file_name,
			);
		}
		asort($output);
		return $output;
	}

	/**
	 * @brief popup window to display in popup window request is to add content
	 */
	function getPopupContent()
	{
		// Bringing a list of emoticons directory
		$emoticon_dirs = FileHandler::readDir($this->emoticon_path);
		$emoticon_list = array();
		if($emoticon_dirs)
		{
			foreach($emoticon_dirs as $emoticon)
			{
				if(preg_match("/^([a-z0-9\_]+)$/i", $emoticon)) {
					$oModuleModel = getModel('module');
					$skin_info = $oModuleModel->loadSkinInfo($this->component_path, $emoticon, 'tpl/images');
					$emoticon_list[$emoticon] = (is_object($skin_info) && $skin_info->title) ? $skin_info->title : $emoticon;
				}
			}
		}
		Context::set('emoticon_list', $emoticon_list);
		// The first emoticon image files in the directory Wanted
		$emoticons = $this->getEmoticons($emoticon_list[0]);
		Context::set('emoticons', $emoticons);
		// Pre-compiled source code to compile template return to
		$tpl_path = $this->component_path.'tpl';
		$tpl_file = 'popup.html';

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief Emoticon of the path were added to solve the problem. (06/09/2007 Benny)
	 */
	function transHTML($xml_obj)
	{
		$src = $xml_obj->attrs->src;
		$alt = $xml_obj->attrs->alt;
		$width = intval($xml_obj->attrs->width);
		$height = intval($xml_obj->attrs->height);

		if(!$alt)
		{
			$tmp_arr = explode('/',$src);
			$alt = array_pop($tmp_arr);
		}

		$src = str_replace(array('&','"'), array('&amp;','&qout;'), $src);
		if(!$alt) $alt = $src;

		$attr_output = array();
		$attr_output = array("src=\"".$src."\"");

		if($alt)
		{
			$attr_output[] = "alt=\"".htmlspecialchars($alt)."\"";
		}

		if($width && $height)
		{
			$attr_output[] = "width=\"".$width."\" height=\"".$height."\"";
		}

		$code = sprintf("<img %s style=\"border:0px\" />", implode(" ",$attr_output));

		return $code;
	}
}
/* End of file emoticon.class.php */
/* Location: ./modules/editor/components/emoticon/emoticon.class.php */
