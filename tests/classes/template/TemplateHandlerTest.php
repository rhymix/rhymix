<?php

if(!defined('__XE__')) require dirname(__FILE__).'/../../Bootstrap.php';

require_once _XE_PATH_.'classes/file/FileHandler.class.php';
require_once _XE_PATH_.'classes/template/TemplateHandler.class.php';

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
				'<a href="#">Link1</a><a href="#cond" cond="$v==$k">Link2</a>',
				'<a href="#">Link1</a><?php if($__Context->v==$__Context->k){ ?><a href="#cond">Link2</a><?php } ?>'
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
				'<a>Link</a><!--@if($cond)--><strong>Hello, world</strong><!--@end--> <dummy />',
				'<a>Link</a><?php if($__Context->cond){ ?><strong>Hello, world</strong><?php } ?> <dummy />'
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
				'<dummy /><!--@switch($var)--> <!--@case("A")--> A<!--@break--> <!--@case(\'B\')-->B<!--@break--><!--@default-->C<!--@endswitch--><dummy />',
				'<dummy /><?php switch($__Context->var){ ?><?php case "A": ?> A<?php break; ?><?php case \'B\': ?>B<?php break; ?><?php default : ?>C<?php } ?><dummy />'
			),
			// invalid block statement
			array(
				'<dummy /><!--@xe($var)--><dummy />',
				'<dummy /><dummy />'
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
				'<dummy /><?php $__tpl=TemplateHandler::getInstance();echo $__tpl->compile(\'tests/classes/template\',\'sample.html\') ?><div>This is another dummy</div>'
			),
			// <include target="file">
			array(
				'<dummy /><include target="../sample.html" /><div>This is another dummy</div>',
				'<dummy /><?php $__tpl=TemplateHandler::getInstance();echo $__tpl->compile(\'tests/classes\',\'sample.html\') ?><div>This is another dummy</div>'
			),
			// <load target="../../../modules/page/lang/lang.xml">
			array(
				'<dummy /><load target="../../../modules/page/lang/lang.xml" /><dummy />',
				'<dummy /><?php Context::loadLang(\'modules/page/lang\'); ?><dummy />'
			),
			// <load target="style.css">
			array(
				'<dummy /><load target="css/style.css" /><dummy />',
				'<dummy /><!--#Meta:tests/classes/template/css/style.css--><?php $__tmp=array(\'tests/classes/template/css/style.css\',\'\',\'\',\'\');Context::loadFile($__tmp,\'\',\'\',\'\');unset($__tmp); ?><dummy />'
			),
			// <unload target="style.css">
			array(
				'<dummy /><unload target="css/style.css" /><dummy />',
				'<dummy /><?php Context::unloadFile(\'tests/classes/template/css/style.css\',\'\',\'\'); ?><dummy />'
			),
			// <!--%import("../../../modules/page/tpl/filter/insert_config.xml")-->
			array(
				'<dummy /><!--%import("../../../modules/page/tpl/filter/insert_config.xml")--><dummy />',
				'<dummy /><?php require_once(\'./classes/xml/XmlJsFilter.class.php\');$__xmlFilter=new XmlJsFilter(\'modules/page/tpl/filter\',\'insert_config.xml\');$__xmlFilter->compile(); ?><dummy />'
			),
			// <!--%import("../script.js",type="body")-->
			array(
				'<dummy /><!--%import("../script.js",type="body")--><dummy />',
				'<dummy /><!--#Meta:tests/classes/script.js--><?php $__tmp=array(\'tests/classes/script.js\',\'body\',\'\',\'\');Context::loadFile($__tmp,\'\',\'\',\'\');unset($__tmp); ?><dummy />'
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
			// error case - ignore stylesheets
			array(
				'<style>body{background-color:black}</style>',
				'<style>body{background-color:black}</style>'
			),
			// error case - ignore json
			array(
				'<script type="text/javascript">var json = {hello:"world"};</script>',
				'<script type="text/javascript">var json = {hello:"world"};</script>'
			),
			// error case - inline javascript
			array(
				'<form onsubmit="jQuery(this).find(\'input\').each(function(){if(this.title==this.value)this.value=\'\';}); return procFilter(this, insert_comment)"></form>',
				'<form onsubmit="jQuery(this).find(\'input\').each(function(){if(this.title==this.value)this.value=\'\';}); return procFilter(this, insert_comment)"><input type="hidden" name="error_return_url" value="<?php echo getRequestUriByServerEnviroment() ?>" /><input type="hidden" name="act" value="<?php echo $__Context->act ?>"><input type="hidden" name="mid" value="<?php echo $__Context->mid ?>"><input type="hidden" name="vid" value="<?php echo $__Context->vid ?>"></form>'
			),
			// issue 103
			array(
				'<load target="http://aaa.com/aaa.js" />',
				'<!--#Meta:http://aaa.com/aaa.js--><?php $__tmp=array(\'http://aaa.com/aaa.js\',\'\',\'\',\'\');Context::loadFile($__tmp,\'\',\'\',\'\');unset($__tmp); ?>'
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
			// issue 188
			array(
				'<div cond="$ii < $nn" loop="$dummy => $k, $v">Hello, world!</div>',
				'<?php if($__Context->ii < $__Context->nn){ ?><?php if($__Context->dummy&&count($__Context->dummy))foreach($__Context->dummy as $__Context->k=>$__Context->v){ ?><div>Hello, world!</div><?php }} ?>'
			),
			// issue 190
			array(
				'<div cond="!($i >= $n)" loop="$dummy => $k, $v">Hello, world!</div>',
				'<?php if(!($__Context->i >= $__Context->n)){ ?><?php if($__Context->dummy&&count($__Context->dummy))foreach($__Context->dummy as $__Context->k=>$__Context->v){ ?><div>Hello, world!</div><?php }} ?>'
			),
			// issue 183
			array(
				'<table><thead><tr><th loop="$vvvls => $vvv">{$vvv}</th></tr></thead>'."\n".'<tbody><tr><td>C</td><td>D</td></tr></tbody></table>',
				'<table><thead><tr><?php if($__Context->vvvls&&count($__Context->vvvls))foreach($__Context->vvvls as $__Context->vvv){ ?><th><?php echo $__Context->vvv ?></th><?php } ?></tr></thead>'."\n".'<tbody><tr><td>C</td><td>D</td></tr></tbody></table>'
			),
			// issue 512 - ignores <marquee>
			array(
				'<div class="topimgContex"><marquee direction="up" scrollamount="1" height="130" loop="infinity" behavior="lscro">{$lang->sl_show_topimgtext}</marquee></div>',
				'<div class="topimgContex"><marquee direction="up" scrollamount="1" height="130" loop="infinity" behavior="lscro"><?php echo $__Context->lang->sl_show_topimgtext ?></marquee></div>'
			),
			// issue 584
			array(
				'<img cond="$oBodex->display_extra_images[\'mobile\'] && $arr_extra && $arr_extra->bodex->mobile" src="./images/common/mobile.gif" title="mobile" alt="mobile" />',
				'<?php if($__Context->oBodex->display_extra_images[\'mobile\'] && $__Context->arr_extra && $__Context->arr_extra->bodex->mobile){ ?><img src="/xe/tests/classes/template/images/common/mobile.gif" title="mobile" alt="mobile" /><?php } ?>'
			),
			// issue 831
			array(
				"<li <!--@if(in_array(\$act, array(\n'dispNmsAdminGroupList',\n'dispNmsAdminInsertGroup',\n'dispNmsAdminGroupInfo',\n'dispNmsAdminDeleteGroup')))-->class=\"on\"<!--@endif-->>",
				"<li <?php if(in_array(\$__Context->act, array(\n'dispNmsAdminGroupList',\n'dispNmsAdminInsertGroup',\n'dispNmsAdminGroupInfo',\n'dispNmsAdminDeleteGroup'))){ ?>class=\"on\"<?php } ?>>"
			),
			// issue 746
			array(
				'<img src="../myxe/xe/img.png" />',
				'<img src="/xe/tests/classes/myxe/xe/img.png" />'
			),
			// issue 696
			array(
				'{@ eval(\'$val = $document_srl;\')}',
				'<?php  eval(\'$__Context->val = $__Context->document_srl;\') ?>'
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
		$result = $tmpl->parse($tpl);

		$this->assertEquals($result, $this->prefix.$expected);
	}

	public function testParse2()
	{
		$tmpl = TemplateHandler::getInstance();
		$tmpl->init(dirname(__FILE__), 'no_file.html');

		$result = $tmpl->parse();
		$this->assertEquals($result, '');
	}

	public function testCompileDirect()
	{
		$tmpl = TemplateHandler::getInstance();
		$result = $tmpl->compileDirect(dirname(__FILE__), 'sample.html');
		$result = trim($result);

 		$this->assertEquals($result, $this->prefix.'<?php if($__Context->has_blog){ ?><a href="http://mygony.com">Taggon\'s blog</a><?php } ?>');
	}
}
