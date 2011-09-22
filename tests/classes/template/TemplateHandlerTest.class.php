<?php

define('__DEBUG__', 1);
define('_XE_PATH_', realpath(dirname(__FILE__).'/../../../'));
require _XE_PATH_.'/classes/template/TemplateHandler.class.php';

$_SERVER['SCRIPT_NAME'] = '/xe/index.php';

class TemplateHandlerTest extends PHPUnit_Framework_TestCase
{
	var $prefix = '<?php if(!defined("__XE__"))exit;?>';

	static public function provider()
	{
		return array(
			// pipe cond
			array(
				'<a href="#" class="active"|cond="$cond > 10">Link</a>',
				'<a href="#"<?php if($__Context->cond > 10){ ?> class="active"<?php } ?>>Link</a>'
			),
			// cond
			array(
				'<a href="#">Link1</a><a href="#cond"><span cond="$cond">say, hello</span></a>',
				'<a href="#">Link1</a><a href="#cond"><?php if($__Context->cond){ ?><span>say, hello</span><?php } ?></a>'
			),
			// cond
			array(
				'<a href="#">Link1</a><a href="#cond" cond="$var==$key">Link2</a>',
				'<a href="#">Link1</a><?php if($__Context->var==$__Context->key){ ?><a href="#cond">Link2</a><?php } ?>'
			),
			// for loop
			array(
				'<ul><li loop="$i=0;$i<$len;$i++" class="sample"><a>Link</a></li></ul>',
				'<ul><?php for($__Context->i=0;$__Context->i<$__Context->len;$__Context->i++){ ?><li class="sample"><a>Link</a></li><?php } ?></ul>'
			),
			// foreach loop
			array(
				'<ul><li loop="$arr=>$key,$val" class="sample"><a>Link</a><ul><li loop="$arr2=>$key2,$val2"></li></ul></li></ul>',
				'<ul><?php if($__Context->arr&&count($__Context->arr))foreach($__Context->arr as $__Context->key=>$__Context->val){ ?><li class="sample"><a>Link</a><ul><?php if($__Context->arr2&&count($__Context->arr2))foreach($__Context->arr2 as $__Context->key2=>$__Context->val2){ ?><li></li><?php } ?></ul></li><?php } ?></ul>'
			),
			// while loop
			array(
				'<ul><li loop="$item=get_loop_item()" class="sample"><a>Link</a></li></ul>',
				'<ul><?php while($__Context->item=get_loop_item()){ ?><li class="sample"><a>Link</a></li><?php } ?></ul>'
			),
			// <!--@if--> ~ <!--@end-->
			array(
				'<a>Link</a><!--@if($cond)--><strong>Hello, world</strong><!--@end--><dummy />',
				'<a>Link</a><?php if($__Context->cond){ ?><strong>Hello, world</strong><?php } ?><dummy />'
			),
			// <!--@if--> ~ <!--@endif-->
			array(
				'<a>Link</a><!--@if($cond)--><strong>Hello, {$world}</strong><!--@endif--><dummy />',
				'<a>Link</a><?php if($__Context->cond){ ?><strong>Hello, <?php echo $__Context->world ?></strong><?php } ?><dummy />'
			),
			// <!--@if--> ~ <!--@else--> ~ <!--@endif-->
			array(
				'<a>Link</a><!--@if($cond)--><strong>Hello, world</strong><!--@else--><em>Wow</em><!--@endif--><dummy />',
				'<a>Link</a><?php if($__Context->cond){ ?><strong>Hello, world</strong><?php }else{ ?><em>Wow</em><?php } ?><dummy />'
			),
			// <!--@if--> ~ <!--@elseif--> ~ <!--@else--> ~ <!--@endif-->
			array(
				'<a>Link</a><!--@if($cond)--><strong>Hello, world</strong><!--@elseif($cond2)--><u>HaHa</u><!--@else--><em>Wow</em><!--@endif--><dummy />',
				'<a>Link</a><?php if($__Context->cond){ ?><strong>Hello, world</strong><?php }elseif($__Context->cond2){ ?><u>HaHa</u><?php }else{ ?><em>Wow</em><?php } ?><dummy />'
			),
			// <!--@for--> ~ <!--@endfor-->
			array(
				'<!--@for($i=0;$i<$len;$i++)--><li>Repeat this</li><!--@endfor-->',
				'<?php for($__Context->i=0;$__Context->i<$__Context->len;$__Context->i++){ ?><li>Repeat this</li><?php } ?>'
			),
			// <!--@foreach--> ~ <!--@endforeach-->
			array(
				'<!--@foreach($arr as $key=>$val)--><li>item{$key} : {$val}</li><!--@endfor-->',
				'<?php if($__Context->arr&&count($__Context->arr))foreach($__Context->arr as $__Context->key=>$__Context->val){ ?><li>item<?php echo $__Context->key ?> : <?php echo $__Context->val ?></li><?php } ?>'
			),
			// <!--@while--> ~ <!--@endwhile-->
			array(
				'<!--@while($item=$list->getItem())--><a href="{$v->link}">{$v->text}</a><!--@endwhile-->',
				'<?php while($__Context->item=$__Context->list->getItem()){ ?><a href="<?php echo $__Context->v->link ?>"><?php echo $__Context->v->text ?></a><?php } ?>'
			),
			// <!--@switch--> ~ <!--@case--> ~ <!--@break--> ~ <!--@default --> ~ <!--@endswitch-->
			array(
				'<dummy /><!--@switch($var)--><!--@case("A")-->A<!--@break--><!--@case("B")-->B<!--@break--><!--@default-->C<!--@endswitch--><dummy />',
				'<dummy /><?php switch($__Context->var){ ?><?php case "A": ?>A<?php break; ?><?php case "B": ?>B<?php break; ?><?php default : ?>C<?php } ?><dummy />'
			),
			// {@ ...PHP_CODE...}
			array(
				'<before />{@$list_page = $page_no}<after />',
				'<before /><?php $__Context->list_page = $__Context->page_no ?><after />'
			),
			// %load_js_plugin
			array(
				'<dummy /><!--%load_js_plugin("ui")--><dummy />',
				'<dummy /><?php Context::loadJavascriptPlugin(\'ui\'); ?><dummy />'
			),
			// #include
			array(
				'<dummy /><!--#include("sample.html")--><div>This is another dummy</div>',
				'<dummy /><?php echo TemplateHandler::getInstance()->compile(\'tests/classes/template\',\'sample.html\') ?><div>This is another dummy</div>'
			),
			// <include target="file">
			array(
				'<dummy /><include target="../sample.html" /><div>This is another dummy</div>',
				'<dummy /><?php echo TemplateHandler::getInstance()->compile(\'tests/classes\',\'sample.html\') ?><div>This is another dummy</div>'
			),
			// <load target="../../../modules/page/lang/lang.xml">
			array(
				'<dummy /><load target="../../../modules/page/lang/lang.xml" /><dummy />',
				'<dummy /><?php Context::loadLang(\'modules/page/lang\'); ?><dummy />'
			),
			// <load target="style.css">
			array(
				'<dummy /><load target="css/style.css" /><dummy />',
				'<dummy /><!--#Meta:tests/classes/template/css/style.css--><?php $__tmp=array(\'tests/classes/template/css/style.css\',\'\',\'\',\'\',\'\',\'\',\'\');Context::loadFile($__tmp);unset($__tmp); ?><dummy />'
			),
			// <unload target="style.css">
			array(
				'<dummy /><unload target="css/style.css" /><dummy />',
				'<dummy /><?php Context::unloadFile(\'tests/classes/template/css/style.css\',\'\',\'\'); ?><dummy />'
			),
			// <!--%import("../script.js",type="body")-->
			array(
				'<dummy /><!--%import("../script.js",type="body")--><dummy />',
				'<dummy /><!--#Meta:tests/classes/script.js--><?php $__tmp=array(\'tests/classes/script.js\',\'body\',\'\',\'\',\'\',\'\',\'\');Context::loadFile($__tmp);unset($__tmp); ?><dummy />'
			),
			// <!--%unload("../script.js",type="body")-->
			array(
				'<dummy /><!--%unload("../script.js",type="body")--><dummy />',
				'<dummy /><?php Context::unloadFile(\'tests/classes/script.js\',\'\'); ?><dummy />'
			),
			// comment
			array(
				'<dummy_before /><!--// this is a comment--><dummy_after />',
				'<dummy_before /><dummy_after />'
			),
			// self-closing tag
			array(
				'<meta charset="utf-8" cond="$foo">',
				'<?php if($__Context->foo){ ?><meta charset="utf-8"><?php } ?>'
			),
			// relative path1
			array(
				'<img src="http://naver.com/naver.gif"><input type="image" src="../local.gif" />',
				'<img src="http://naver.com/naver.gif"><input type="image" src="/xe/tests/classes/local.gif" />'
			),
			// relative path2
			array(
				'<img src="http://naver.com/naver.gif"><input type="image" src="../../dir/local.gif" />',
				'<img src="http://naver.com/naver.gif"><input type="image" src="/xe/tests/dir/local.gif" />'
			),
			// error case
			array(
				'<a href="{$layout_info->index_url}" cond="$layout_info->logo_image"><img src="{$layout_info->logo_image}" alt="logo" border="0" class="iePngFix" /></a>',
				'<?php if($__Context->layout_info->logo_image){ ?><a href="<?php echo $__Context->layout_info->index_url ?>"><img src="<?php echo $__Context->layout_info->logo_image ?>" alt="logo" border="0" class="iePngFix" /></a><?php } ?>'
			),
			// issue 103
			array(
				'<load target="http://aaa.com/aaa.js" />',
				'<!--#Meta:http://aaa.com/aaa.js--><?php $__tmp=array(\'http://aaa.com/aaa.js\',\'\',\'\',\'\',\'\',\'\',\'\');Context::loadFile($__tmp);unset($__tmp); ?>'
			),
			// issue 135
			array(
				'<block loop="$_m_list_all=>$key,$val"><p>{$key}</p><div>Loop block {$val}</div></block>',
				'<?php if($__Context->_m_list_all&&count($__Context->_m_list_all))foreach($__Context->_m_list_all as $__Context->key=>$__Context->val){ ?><p><?php echo $__Context->key ?></p><div>Loop block <?php echo $__Context->val ?></div><?php } ?>'
			),
			// issue 136
			array(
				'<br cond="$var==\'foo\'" />bar',
				'<?php if($__Context->var==\'foo\'){ ?><br /><?php } ?>bar'
			),
		);
	}

	/**
	 * @dataProvider provider
	 */
	public function testParse($tpl, $expected)
	{
		$tmpl = TemplateHandler::getInstance();
		$tmpl->init(dirname(__FILE__), 'sample.html');
		$result = $tmpl->parse($tpl, $expected);

		$this->assertEquals($result, $this->prefix.$expected);
	}
}
