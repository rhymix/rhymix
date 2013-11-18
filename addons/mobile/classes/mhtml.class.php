<?php
include './mobile.class.php';
/**
 * mhtml Library ver 0.1
 * @author NAVER (developers@xpressengine.com) / lang_select : misol
 */
class wap extends mobileXE
{
	/**
	 * @brief constructor
	 **/
	function wap()
	{
		parent::mobileXE();
	}

	/**
	 * @brief hdml header output
	 **/
	function printHeader()
	{
		print("<html><head>\n");
		if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
		printf("<title>%s%s</title></head><body>\n", htmlspecialchars($this->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false),htmlspecialchars($titlePageStr, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
	}
	// Output title
	function printTitle()
	{
		if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
		printf('&lt;%s%s&gt;<br>%s', htmlspecialchars($this->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false),htmlspecialchars($titlePageStr, ENT_COMPAT | ENT_HTML401, 'UTF-8', false),"\n");
	}

	/**
	 * @brief Output information
	 * hasChilds() if there is a list of content types, otherwise output
	 **/
	function printContent()
	{
		if($this->hasChilds())
		{
			foreach($this->getChilds() as $key => $val)
			{
				if(!$val['link']) continue;
				printf('<a href="%s" accesskey="%s">%s</a><br>%s', $val['href'], $this->getNo(), $val['text'], "\n");
				if($val['extra']) printf("<br>%s\n",str_replace('<br/>','<br>',$val['extra']));
			}
		}
		else
		{
			print(str_replace('<br/>','<br>',$this->getContent())."\n");
		}
		print "<hr><br>";
	}

	/**
	 * @brief Button to output
	 **/
	function printBtn()
	{
		if($this->nextUrl)
		{
			$url = $this->nextUrl;
			printf('<a href="%s">%s</a><br>%s', $url->url, $url->text, "\n");
		}
		if($this->prevUrl)
		{
			$url = $this->prevUrl;
			printf('<a href="%s">%s</a><br>%s', $url->url, $url->text, "\n");
		}
		// Select Language
		if(!parent::isLangChange())
		{
			$url = getUrl('','lcm','1','sel_lang',Context::getLangType(),'return_uri',Context::get('current_url'));
			printf('<a href="%s">%s</a><br>%s', $url, 'Language : '.Context::getLang('select_lang'), "\n");
		}
		else
		{
			printf('<a href="%s">%s</a><br>%s', Context::get('return_uri'), Context::getLang('lang_return'), "\n");
		}
		if($this->upperUrl)
		{
			$url = $this->upperUrl;
			printf('<btn href="%s" name="%s">%s', $url->url, $url->text, "\n");
		}
		if($this->homeUrl)
		{
			$url = $this->homeUrl;
			printf('<a btn="%s" href="%s">%s</a><br>%s', $url->text, $url->url, $url->text, "\n");
		}
	}
	// Footer information output
	function printFooter()
	{
		print("</body></html>\n");
	}
}
/* End of file mhtml.class.php */
/* Location: ./addons/mobile/classes/mhtml.class.php */
