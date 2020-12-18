<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  poll_maker
 * @author NAVER (developers@xpressengine.com)
 * @brief Editor provides the ability to link to the url.
 */
class poll_maker extends EditorHandler
{
	// editor_sequence from the editor must attend mandatory wearing ....
	var $editor_sequence = 0;
	var $component_path = '';

	/**
	 * @brief editor_sequence and components out of the path
	 */
	function __construct($editor_sequence, $component_path)
	{
		$this->editor_sequence = $editor_sequence;
		$this->component_path = $component_path;
	}

	/**
	 * @brief popup window to display in popup window request is to add content
	 */
	function getPopupContent()
	{
		// Wanted Skins survey
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins(RX_BASEDIR . 'widgets/pollWidget/');
		Context::set('skin_list', $skin_list);
		// Pre-compiled source code to compile template return to
		$tpl_path = $this->component_path.'tpl';
		$tpl_file = 'popup.html';

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
		$args = new stdClass();

		$args->poll_srl = intval($xml_obj->attrs->poll_srl);
		$skin = $xml_obj->attrs->skin;
		if(!$skin) $skin = 'default';
		$args->skin = $skin;

		preg_match('/width([^[:digit:]]+)([0-9]+)/i',$xml_obj->attrs->style,$matches);
		$width = $matches[2];
		if(!$width) $width = 400;
		$args->style = sprintf('width:%dpx', $width);

		// Set a path of the template skin (values of skin, colorset settings)
		$tpl_path = sprintf('%sskins/%s', RX_BASEDIR . 'widgets/pollWidget/', $args->skin);
		$tpl_file = 'pollview';

		// Get the information related to the survey
		$oPollModel = getModel('poll');
		$poll_data = $oPollModel->_getPollinfo($args->poll_srl);

		Context::set('poll_data', $poll_data);
		Context::set('colorset', $args->colorset);
		Context::set('poll_srl', $args->poll_srl);
		Context::set('style', $args->style);

		// Compile a template
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
}
/* End of file poll_maker.class.php */
/* Location: ./modules/editor/components/poll_maker/poll_maker.class.php */
