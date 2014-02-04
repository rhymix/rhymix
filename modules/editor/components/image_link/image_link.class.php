<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  image_link
 * @author NAVER (developers@xpressengine.com)
 * @brief Add an image, or to modify the properties of components
 */
class image_link extends EditorHandler
{
	// editor_sequence from the editor must attend mandatory wearing ....
	var $editor_sequence = 0;
	var $component_path = '';

	/**
	 * @brief editor_sequence and components out of the path
	 */
	function image_link($editor_sequence, $component_path)
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
		$src = $xml_obj->attrs->src;
		$width = $xml_obj->attrs->width;
		$height = $xml_obj->attrs->height;
		$align = $xml_obj->attrs->align;
		$alt = $xml_obj->attrs->alt;
		$title = $xml_obj->attrs->title;
		$border = (int)$xml_obj->attrs->border;
		$link_url = $xml_obj->attrs->link_url;
		$open_window = $xml_obj->attrs->open_window;
		$style = $xml_obj->attrs->style;
		$margin = (int)$xml_obj->attrs->margin;

		$src = str_replace(array('&','"'), array('&amp;','&qout;'), $src);
		$src = str_replace('&amp;amp;', '&amp;', $src);

		// Image containing the address to the address conversion request uri (rss output, etc. purposes)
		$temp_src = explode('/', $src);
		if(substr($src, 0,2)=='./') $src = Context::getRequestUri().substr($src, 2);
		else if(substr($src , 0, 1)=='/')
		{
			if($_SERVER['HTTPS']=='on') $http_src = 'https://';
			else $http_src = 'http://';
			$src = $http_src.$_SERVER['HTTP_HOST'].$src;
		}
		else if(!strpos($temp_src[0],':') && $src) $src = Context::getRequestUri().$src;

		$attr_output = array();
		$attr_output = array("src=\"".$src."\"");
		$attr_output[] = "alt=\"".$alt."\"";

		if($title)
		{
			$attr_output[] = "title=\"".$title."\"";
		}
		if($margin)
		{
			$style = trim(preg_replace('/margin[a-z\-]*[ ]*:[ ]*[0-9 a-z]+(;| )/i','', $style)).';';
			$style = str_replace(';;',';',$style);
			if($style == ';') $style = '';
			$style .= ' margin:'.$margin.'px;';
		}
		if($align) $attr_output[] = "align=\"".$align."\"";
		if($width) $attr_output[] = 'width="'.$width.'"';
		if($height) $attr_output[] = 'height="'.$height.'"';
		if($border)
		{
			$style = trim(preg_replace('/border[a-z\-]*[ ]*:[ ]*[0-9 a-z]+(;| )/i','', $style)).';';
			$style = str_replace(';;',';',$style);
			if($style == ';') $style = '';
			$style .= ' border-style: solid; border-width:'.$border.'px;';
		}

		$code = sprintf("<img %s style=\"%s\" />", implode(' ',$attr_output), $style);

		if($link_url)
		{
			if($open_window =='Y') $code = sprintf('<a href="%s" onclick="window.open(this.href);return false;">%s</a>', $link_url, $code);
			else $code = sprintf('<a href="%s" >%s</a>', $link_url, $code);
		}
		return $code;
	}
}
/* End of file image_link.class.php */
/* Location: ./modules/editor/components/image_link/image_link.class.php */
