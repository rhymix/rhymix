<?php

define('__DEBUG__', 1);
define('_XE_PATH_', realpath(dirname(__FILE__).'/../../../'));
require _XE_PATH_.'/classes/file/FileHandler.class.php';
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
			// <load target="style.css">
			array(
				'<dummy /><load target="css/style.css" /><dummy />',
				'<dummy /><!--#Meta:--><?php ?><dummy />'
			),
		);
	}

	/**
	 * @dataProvider provider
	 */
	public function testParse($tpl, $expected)
	{
		$tmpl = new TemplateHandler();
		$tmpl->init(dirname(__FILE__), 'sample.html');
		$result = $tmpl->parse($tpl, $expected);

		$this->assertEquals($result, $this->prefix.$expected);
	}
}
