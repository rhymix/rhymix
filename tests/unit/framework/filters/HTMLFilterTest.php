<?php

class HTMLFilterTest extends \Codeception\TestCase\Test
{
	public function testHTMLFilterClean()
	{
		$tests = array(
			// remove iframe
			array(
				'<div class="frame"><iframe src="path/to/file.html"></iframe><p><a href="#iframe">IFrame</a></p></div>',
				'<div><iframe></iframe><p><a href="#iframe">IFrame</a></p></div>'
			),
			// expression
			array(
				'<div class="dummy" style="xss:expr/*XSS*/ession(alert(\'XSS\'))">',
				'<div></div>'
			),
			// no quotes and no semicolon - http://ha.ckers.org/xss.html
			array(
				'<img src=javascript:alert(\'xss\')>',
				''
			),
			// embedded encoded tab to break up XSS - http://ha.ckers.org/xss.html
			array(
				'<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">',
				'<img src="jav%20ascript%3Aalert(\'XSS\');" alt="" />'
			),
			// issue 178
			array(
				'<img src="invalid.jpg"\nonerror="alert(1)" />',
				'<img src="invalid.jpg" alt="" />'
			),
			// issue 534
			array(
				'<img src=\'as"df dummy=\'"1234\'" 4321\' asdf/*/>*/"  onerror="console.log(\'Yet another XSS\')">',
				'<img src="as" alt="" />*/"  onerror="console.log(\'Yet another XSS\')"&gt;'
			),
			// issue 602
			array(
				'<img alt="test" src="(http://static.naver.com/www/u/2010/0611/nmms_215646753.gif" onload="eval(String.fromCharCode(105,61,49,48,48,59,119,104,105,108,101, 40,105,62,48,41,97,108,101,114,116,40,40,105,45,45,41,43,39,48264,47564,32, 45908,32,53364,47533,54616,49464,50836,39,41,59));">',
				'<img alt="test" src="(http%3A//static.naver.com/www/u/2010/0611/nmms_215646753.gif" />'
			),
			// issue #1813 https://github.com/xpressengine/xe-core/issues/1813
			array(
				'<img src="?act=dispLayoutPreview" alt="dummy" />',
				'<img src="" alt="dummy" />'
			),
			array(
				'<img src="?act =dispLayoutPreview" alt="dummy" />',
				'<img src="" alt="dummy" />'
			),
			array(
				"<img src=\"?act\n=dispLayoutPreview\" alt=\"dummy\" />",
				'<img src="" alt="dummy" />'
			),
			array(
				"<img src=\"?pam=act&a\nct  =\r\n\tdispLayoutPreview\" alt=\"dummy\" />",
				'<img src="" alt="dummy" />'
			)
		);
		
		config('mediafilter.classes', array());
		foreach ($tests as $test)
		{
			$this->assertEquals($test[1], Rhymix\Framework\Filters\HTMLFilter::clean($test[0]));
		}
	}
	
	public function testHTMLFilterHTML5()
	{
		$source = '<div><audio autoplay="autoplay" src="./foo/bar.mp3"></audio></div>';
		$target = '<div><audio autoplay="" src="./foo/bar.mp3"></audio></div>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<video autoplay width="320" height="240"><source src="./foo/bar.mp4" type="video/mp4" /></video>';
		$target = '<video autoplay="" width="320" height="240"><source src="./foo/bar.mp4" type="video/mp4" /></video>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<nav>123</nav><section>456</section><article>789</article><aside>0</aside>';
		$target = '<nav>123</nav><section>456</section><article>789</article><aside>0</aside>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<div contenteditable="true"><div contenteditable="false"><p contenteditable="false"></p></div></div>';
		$target = '<div><div contenteditable="false"><p></p></div></div>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
	}
	
	public function testHTMLFilterCSS3()
	{
		$source = '<div style="display:flex;border-radius:1px 2px 3px 4px;"></div>';
		$target = '<div style="display:flex;border-radius:1px 2px 3px 4px;"></div>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<div style="box-sizing:border-box;box-shadow:5px 5px 2px #123456;"></div>';
		$target = '<div style="box-sizing:border-box;box-shadow:5px 5px 2px #123456;"></div>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<div style="overflow-x:auto;overflow-y:scroll;left:-500px;"></div>';
		$target = '<div style="overflow-x:auto;overflow-y:scroll;"></div>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
	}
	
	public function testHTMLFilterEmbeddedMedia()
	{
		$source = '<iframe title="Video Test" width="640" height="360" src="http://videofarm.daum.net/controller/video/viewer/Video.html?vid=s474b7BR2zzREo0g7OT7EKo&amp;play_loc=undefined&amp;alert=true" frameborder="0" scrolling="no"></iframe>';
		$target = '<iframe title="Video Test" width="640" height="360" src="http://videofarm.daum.net/controller/video/viewer/Video.html?vid=s474b7BR2zzREo0g7OT7EKo&amp;play_loc=undefined&amp;alert=true" frameborder="0" scrolling="no"></iframe>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<iframe title="Video Test" width="640" height="360" src="http://not-allowed.com/whatever-video.mp4" frameborder="0" scrolling="no"></iframe>';
		$target = '<iframe title="Video Test" width="640" height="360" frameborder="0" scrolling="no"></iframe>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<object type="application/x-shockwave-flash" width="640px" height="360px" align="middle" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,3,0,0">' .
			'<param name="movie" value="http://videofarm.daum.net/controller/player/VodPlayer.swf" />' .
			'<param name="allowScriptAccess" value="always" />' .
			'<param name="allowFullScreen" value="true" />' .
			'<param name="bgcolor" value="#000000" />' .
			'<param name="wmode" value="window" />' .
			'<param name="flashvars" value="vid=s474b7BR2zzREo0g7OT7EKo&playLoc=undefined&alert=true" />' .
			'<embed src="http://videofarm.daum.net/controller/player/VodPlayer.swf" width="640px" height="360px" allowScriptAccess="always" type="application/x-shockwave-flash" allowFullScreen="true" bgcolor="#000000" flashvars="vid=s474b7BR2zzREo0g7OT7EKo&playLoc=undefined&alert=true"></embed>' .
			'</object>';
		$target = '<object type="application/x-shockwave-flash" width="640" height="360" data="http://videofarm.daum.net/controller/player/VodPlayer.swf">' .
			'<param name="allowScriptAccess" value="never" />' .
			'<param name="allowNetworking" value="internal" />' .
			'<param name="movie" value="http://videofarm.daum.net/controller/player/VodPlayer.swf" />' .
			'<param name="allowFullScreen" value="true" />' .
			'<param name="wmode" value="window" />' .
			'<param name="flashvars" value="vid=s474b7BR2zzREo0g7OT7EKo&amp;playLoc=undefined&amp;alert=true" />' .
			'<embed src="http://videofarm.daum.net/controller/player/VodPlayer.swf" width="640" height="360" type="application/x-shockwave-flash" flashvars="vid=s474b7BR2zzREo0g7OT7EKo&amp;playLoc=undefined&amp;alert=true" allowscriptaccess="never" allownetworking="internal" />' .
			'</object>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<audio src="https://www.youtube.com/whatever"></audio>';
		$target = '<audio src="https://www.youtube.com/whatever"></audio>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<audio src="https://www-youtube.com/whatever"></audio>';
		$target = '<audio src=""></audio>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<video width="320" height="240"><source src="http://api.v.daum.net/something" type="video/mp4" /></video>';
		$target = '<video width="320" height="240"><source src="http://api.v.daum.net/something" type="video/mp4" /></video>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<video width="320" height="240"><source src="http://wrong-site.net/" type="video/mp4" /></video>';
		$target = '<video width="320" height="240"><source src="" type="video/mp4" /></video>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
	}
	
	public function testHTMLFilterAllowedClasses()
	{
		config('mediafilter.classes', array());
		$source = '<p class="mytest">Hello World</p>';
		$target = '<p>Hello World</p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		config('mediafilter.classes', array('mytest'));
		$source = '<p class="mytest">Hello World</p>';
		$target = '<p class="mytest">Hello World</p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		config('mediafilter.classes', array());
		$source = '<p class="whatever">Hello World</p>';
		$target = '<p class="whatever">Hello World</p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source, true));
		
		$source = '<p class="foobar whatever">Hello World</p>';
		$target = '<p class="foobar">Hello World</p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source, array('foobar')));
	}
	
	public function testHTMLFilterEditorComponent()
	{
		$source = '<img somekey="somevalue" otherkey="othervalue" onmouseover="alert(\'xss\');" editor_component="component_name" src="./foo/bar.jpg" alt="My Picture" style="width:320px;height:240px;" width="320" height="240" />';
		$target = '<img somekey="somevalue" otherkey="othervalue" editor_component="component_name" src="./foo/bar.jpg" alt="My Picture" style="width:320px;height:240px;" width="320" height="240" />';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<img somekey="somevalue" otherkey="othervalue" onkeypress="alert(\'xss\');" editor_component="component_name" />';
		$target = '<img somekey="somevalue" otherkey="othervalue" src="" editor_component="component_name" alt="" />';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<div somekey="somevalue" otherkey="othervalue" onload="alert(\'xss\');" id="foo" class="bar" editor_component="component_name"></div>';
		$target = '<div somekey="somevalue" otherkey="othervalue" id="user_content_foo" editor_component="component_name"></div>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<div editor_component="component_name" style="width:400px;height:300px;" draggable dropzone contextmenu="whatever"></div>';
		$target = '<div editor_component="component_name" style="width:400px;height:300px;"></div>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<img somekey="somevalue" otherkey="othervalue" onmouseover="alert(\'xss\');" editor_component="component_name" src="./foo/bar.jpg" alt="My Picture" style="width:320px;height:240px;" width="320" height="240" />';
		$target = '<img src="./foo/bar.jpg" alt="My Picture" style="width:320px;height:240px;" width="320" height="240" />';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source, false, false));
		
		$source = '<img src="./foo/bar.jpg" alt="Picture" editor_component="component_name" editor_component_property="java Script:alert()" />';
		$target = '<img src="./foo/bar.jpg" alt="Picture" editor_component="component_name" />';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<img src="./foo/bar.jpg" alt="Picture" editor_component="component_name" rx_encoded_properties="alert()" />';
		$target = '<img src="./foo/bar.jpg" alt="Picture" editor_component="component_name" />';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<img somekey="somevalue" otherkey="othervalue" onkeypress="alert(\'xss\');" editor_component="component_name" />';
		$target = '';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source, false, false));
	}
	
	public function testHTMLFilterWidgetCode()
	{
		$source = '<p>Hello World</p><img class="zbxe_widget_output" widget="content" skin="default" colorset="white" widget_sequence="1234" widget_cache="1m" content_type="document" module_srls="56" list_type="normal" tab_type="none" markup_type="table" page_count="1" option_view="title,regdate,nickname" show_browser_title="Y" show_comment_count="Y" show_trackback_count="Y" show_category="Y" show_icon="Y" show_secret="N" order_target="regdate" order_type="desc" thumbnail_type="crop" />';
		$target = '<p>Hello World</p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<p>Hello World</p><img class="zbxe_widget_output" widget="content" skin="default" colorset="white" widget_sequence="1234" widget_cache="1m" content_type="document" module_srls="56" list_type="normal" tab_type="none" markup_type="table" page_count="1" option_view="title,regdate,nickname" show_browser_title="Y" show_comment_count="Y" show_trackback_count="Y" show_category="Y" show_icon="Y" show_secret="N" order_target="regdate" order_type="desc" thumbnail_type="crop" />';
		$target = '<p>Hello World</p><img widget="content" skin="default" colorset="white" widget_sequence="1234" widget_cache="1m" content_type="document" module_srls="56" list_type="normal" tab_type="none" markup_type="table" page_count="1" option_view="title,regdate,nickname" show_browser_title="Y" show_comment_count="Y" show_trackback_count="Y" show_category="Y" show_icon="Y" show_secret="N" order_target="regdate" order_type="desc" thumbnail_type="crop" src="" class="zbxe_widget_output" alt="" />';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source, true, true, true));
		
		$source = '<p>Hello World</p><img class="zbxe_widget_output" widget="content" onmouseover="alert(\'xss\');" skin="default" colorset="white" widget_sequence="1234" widget_cache="1m" content_type="document" module_srls="56" list_type="normal" tab_type="none" markup_type="table" page_count="1" option_view="title,regdate,nickname" show_browser_title="Y" show_comment_count="Y" show_trackback_count="Y" show_category="Y" show_icon="Y" show_secret="N" order_target="regdate" order_type="desc" thumbnail_type="crop" />';
		$target = '<p>Hello World</p><img widget="content" skin="default" colorset="white" widget_sequence="1234" widget_cache="1m" content_type="document" module_srls="56" list_type="normal" tab_type="none" markup_type="table" page_count="1" option_view="title,regdate,nickname" show_browser_title="Y" show_comment_count="Y" show_trackback_count="Y" show_category="Y" show_icon="Y" show_secret="N" order_target="regdate" order_type="desc" thumbnail_type="crop" src="" class="zbxe_widget_output" alt="" />';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source, true, true, true));
	}
	
	public function testHTMLFilterUserContentID()
	{
		$source = '<p id="foobar">Hello World!</p>';
		$target = '<p id="user_content_foobar">Hello World!</p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<p id="user_content_foobar">Hello World!</p>';
		$target = '<p id="user_content_foobar">Hello World!</p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
	}
	
	public function testHTMLFilterMiscellaneous()
	{
		// data-file-srl attribute
		$source = '<p><img src="foo.jpg" alt="foobar" data-file-srl="1234" /></p>';
		$target = '<p><img src="foo.jpg" alt="foobar" data-file-srl="1234" /></p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
		
		$source = '<p><img src="foo.jpg" alt="foobar" data-file-srl="javascript:xss()" /></p>';
		$target = '<p><img src="foo.jpg" alt="foobar" /></p>';
		$this->assertEquals($target, Rhymix\Framework\Filters\HTMLFilter::clean($source));
	}
}
