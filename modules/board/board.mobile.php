<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class BoardMobile extends BoardView
{
	function getBoardCommentPage()
	{
		$this->dispBoardCommentPage();
		$oTemplate = TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->getTemplatePath(), 'comment.html');
		$this->add('html', $html);
	}
}
