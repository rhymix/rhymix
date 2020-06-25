<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class boardMobile extends boardView
{
	function getBoardCommentPage()
	{
		$this->dispBoardCommentPage();
		$oTemplate = TemplateHandler::getInstance();
		$html = $oTemplate->compile($this->getTemplatePath(), 'comment.html');
		$this->add('html', $html);
	}
	
	function dispBoardMessage($msg_code)
	{
		$msg = lang($msg_code);
		$oMessageObject = &ModuleHandler::getModuleInstance('message','mobile');
		$oMessageObject->setError(-1);
		$oMessageObject->setMessage($msg);
		$oMessageObject->dispMessage();

		$this->setTemplatePath($oMessageObject->getTemplatePath());
		$this->setTemplateFile($oMessageObject->getTemplateFile());
	}
}
