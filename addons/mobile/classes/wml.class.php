<?php
/**
 * WML Library ver 0.1
 * @author NAVER (developers@xpressengine.com) / lang_select : misol
 */
class wap extends mobileXE
{
	/**
	 * @brief constructor
	 */
	function wap()
	{
		parent::mobileXE();
	}

	/**
	 * @brief wml header output
	 */
	function printHeader()
	{
		header("Content-Type: text/vnd.wap.wml");
		header("charset: ".$this->charset);
		if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
		print("<?xml version=\"1.0\" encoding=\"".$this->charset."\"?><!DOCTYPE wml PUBLIC \"-//WAPFORUM//DTD WML 1.1//EN\" \"http://www.wapforum.org/DTD/wml_1.1.xml\">\n");
		// Card Title
		printf("<wml>\n<card title=\"%s%s\">\n<p>\n",htmlspecialchars($this->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false),htmlspecialchars($titlePageStr, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
	}

	/**
	 * @brief Output title
	 */
	function printTitle()
	{
		if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
		printf('&lt;%s%s&gt;<br/>%s', htmlspecialchars($this->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false),htmlspecialchars($titlePageStr, ENT_COMPAT | ENT_HTML401, 'UTF-8', false),"\n");
	}

	/**
	 * @brief Output information
	 * hasChilds() if there is a list of content types, otherwise output
	 */
	function printContent()
	{
		if($this->hasChilds())
		{
			foreach($this->getChilds() as $key => $val)
			{
				if(!$val['link']) continue;
				printf('<do type="%s" label="%s"><go href="%s" /></do>%s', $this->getNo(), htmlspecialchars($val['text'], ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $val['href'], "\n");
				if($val['extra']) printf("%s\n",$val['extra']);
			}
		}
		else
		{
			printf('%s<br/>%s', str_replace("<br>","<br/>",$this->getContent()),"\n");
		}
		print('<br/>');
	}

	/**
	 * @brief Button to output
	 */
	function printBtn()
	{
		if($this->nextUrl)
		{
			$url = $this->nextUrl;
			printf('<do type="vnd.next" label="%s"><go href="%s"/></do>%s', $url->text, $url->url, "\n");
		}
		if($this->prevUrl)
		{
			$url = $this->prevUrl;
			printf('<do type="vnd.prev" label="%s"><go href="%s"/></do>%s', $url->text, $url->url, "\n");
		}
		// Others are not applicable in charge of the button output (array passed) type??
		if($this->etcBtn)
		{
			if(is_array($this->etcBtn))
			{
				foreach($this->etcBtn as $key=>$val)
				{
					printf('<do type="vnd.btn%s" label="%s"><go href="%s"/></do>%s', $key, $val['text'], $val['url'], "\n");
				}
			}
		}
		// Select Language
		if(!parent::isLangChange())
		{
			$url = getUrl('','lcm','1','sel_lang',Context::getLangType(),'return_uri',Context::get('current_url'));
			printf('<do type="vnd.lang" label="%s"><go href="%s"/></do>%s', 'Language : '.Context::getLang('select_lang'), $url, "\n");
		}
		else
		{
			printf('<do type="vnd.lang" label="%s"><go href="%s"/></do>%s', Context::getLang('lang_return'), Context::get('return_uri'), "\n");
		}
		if($this->homeUrl)
		{
			$url = $this->homeUrl;
			printf('<do type="access" label="%s"><go href="%s"/></do>%s', $url->text, $url->url, "\n");
		}
		if($this->upperUrl)
		{
			$url = $this->upperUrl;
			printf('<do type="vnd.up" label="%s"><go href="%s"/></do>%s', $url->text, $url->url, "\n");
		}
	}
	// Footer information output
	function printFooter()
	{
		print("</p>\n</card>\n</wml>");
	}
	// And returns a list of serial numbers in
	function getNo()
	{
		if(Context::get('mobile_skt')==1)
		{
			return "vnd.skmn".parent::getNo();
		}
		else
		{
			return parent::getNo();
		}
		return $str;
	}
}
/* End of file wml.class.php */
/* Location: ./addons/mobile/classes/wml.class.php */
