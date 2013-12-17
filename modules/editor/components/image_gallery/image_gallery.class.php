<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  image_gallery
 * @author NAVER (developers@xpressengine.com)
 * @brief Making images uploaded to the image gallery
 */
class image_gallery extends EditorHandler
{
	// editor_sequence from the editor must attend mandatory wearing ....
	var $editor_sequence = 0;
	var $component_path = '';

	/**
	 * @brief editor_sequence and components out of the path
	 */
	function image_gallery($editor_sequence, $component_path)
	{
		$this->editor_sequence = $editor_sequence;
		$this->component_path = $component_path;
	}

	/**
	 * @brief popup window to display in popup window request is to add content
	 */
	function getPopupContent()
	{
		// Pre-compiled source code to compile template return to
		$tpl_path = $this->component_path.'tpl';
		$tpl_file = 'popup.html';

		Context::set("tpl_path", $tpl_path);

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief Editor of the components separately if you use a unique code to the html code for a method to change
	 *
	 * Images and multimedia, seolmundeung unique code is required for the editor component added to its own code, and then
	 * DocumentModule:: transContent() of its components transHtml() method call to change the html code for your own
	 */
	function transHTML($xml_obj)
	{
		$gallery_info = new stdClass;
		$gallery_info->srl = rand(111111,999999);
		$gallery_info->border_thickness = $xml_obj->attrs->border_thickness;
		$gallery_info->gallery_style = $xml_obj->attrs->gallery_style;
		$color_preg = "/^([a-fA-F0-9]{6})/";
		$gallery_info->border_color = preg_replace($color_preg,"#$1",$xml_obj->attrs->border_color);
		$gallery_info->bg_color = preg_replace($color_preg,"#$1",$xml_obj->attrs->bg_color);
		$gallery_info->gallery_align = $xml_obj->attrs->gallery_align;

		$images_list = $xml_obj->attrs->images_list;
		$images_list = preg_replace('/\.(gif|jpg|jpeg|png) /i',".\\1\n",$images_list);
		$gallery_info->images_list = explode("\n",trim($images_list));
		// If you set the output to output the XML code generated a list of the image
		if(Context::getResponseMethod() == 'XMLRPC')
		{
			$output = array();
			for($i=0;$i<count($gallery_info->images_list);$i++)
			{
				$output[] = sprintf('<img src="%s" alt="" />', $gallery_info->images_list[$i]);
			}
			$output[] = '<br />';
			return implode('<br />', $output);
		}
		// HTML gallery output, the output settings via the template for the conversion to generate the html code should
		preg_match_all('/(width|height)([^[:digit:]]+)([0-9]+)/i',$xml_obj->attrs->style,$matches);
		$gallery_info->width = trim($matches[3][0]);
		if(!$gallery_info->width) $gallery_info->width = 400;

		Context::set('gallery_info', $gallery_info);

		$tpl_path = $this->component_path.'tpl';
		Context::set("tpl_path", $tpl_path);

		if($gallery_info->gallery_style == "list") $tpl_file = 'list_gallery.html';
		else $tpl_file = 'slide_gallery.html';

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
}
/* End of file image_gallery.class.php */
/* Location: ./modules/editor/components/image_gallery/image_gallery.class.php */
