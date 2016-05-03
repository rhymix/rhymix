<?php

class TemplateHandlerTest extends \Codeception\TestCase\Test
{
    var $prefix = '<?php if(!defined("__XE__"))exit;';

    public function testParse()
    {
        $tests = array(
            // pipe cond
            array(
                '<a href="#" class="active"|cond="$cond > 10">Link</a>',
                '?><a href="#"<?php if($__Context->cond > 10){ ?> class="active"<?php } ?>>Link</a>'
            ),
            // cond
            array(
                '<a href="#">Link1</a><a href="#cond"><span cond="$cond">say, hello</span></a>',
                '?><a href="#">Link1</a><a href="#cond"><?php if($__Context->cond){ ?><span>say, hello</span><?php } ?></a>'
            ),
            // cond
            array(
                '<a href="#">Link1</a><a href="#cond" cond="$v==$k">Link2</a>',
                '?><a href="#">Link1</a><?php if($__Context->v==$__Context->k){ ?><a href="#cond">Link2</a><?php } ?>'
            ),
            // for loop
            array(
                '<ul><li loop="$i=0;$i<$len;$i++" class="sample"><a>Link</a></li></ul>',
                '?><ul><?php for($__Context->i=0;$__Context->i<$__Context->len;$__Context->i++){ ?><li class="sample"><a>Link</a></li><?php } ?></ul>'
            ),
            // foreach loop
            array(
                '<ul><li loop="$arr=>$key,$val" class="sample"><a>Link</a><ul><li loop="$arr2=>$key2,$val2"></li></ul></li></ul>',
                '?><ul><?php $tba7582b53a5d9d2bfecc5c52511ef7ee=$__Context->arr;if($tba7582b53a5d9d2bfecc5c52511ef7ee&&count($tba7582b53a5d9d2bfecc5c52511ef7ee))foreach($tba7582b53a5d9d2bfecc5c52511ef7ee as $__Context->key=>$__Context->val){ ?><li class="sample"><a>Link</a><ul><?php $tf92ab3139c8d388f9a5f63e0fdd8f516=$__Context->arr2;if($tf92ab3139c8d388f9a5f63e0fdd8f516&&count($tf92ab3139c8d388f9a5f63e0fdd8f516))foreach($tf92ab3139c8d388f9a5f63e0fdd8f516 as $__Context->key2=>$__Context->val2){ ?><li></li><?php } ?></ul></li><?php } ?></ul>'
            ),
            // while loop
            array(
                '<ul><li loop="$item=get_loop_item()" class="sample"><a>Link</a></li></ul>',
                '?><ul><?php while($__Context->item=get_loop_item()){ ?><li class="sample"><a>Link</a></li><?php } ?></ul>'
            ),
            // <!--@if--> ~ <!--@end-->
            array(
                '<a>Link</a><!--@if($cond)--><strong>Hello, world</strong><!--@end--> <dummy />',
                '?><a>Link</a><?php if($__Context->cond){ ?><strong>Hello, world</strong><?php } ?> <dummy />'
            ),
            // <!--@if--> ~ <!--@endif-->
            array(
                '<a>Link</a><!--@if($cond)--><strong>Hello, {$world}</strong><!--@endif--><dummy />',
                '?><a>Link</a><?php if($__Context->cond){ ?><strong>Hello, <?php echo $__Context->world ?></strong><?php } ?><dummy />'
            ),
            // <!--@if--> ~ <!--@else--> ~ <!--@endif-->
            array(
                '<a>Link</a><!--@if($cond)--><strong>Hello, world</strong><!--@else--><em>Wow</em><!--@endif--><dummy />',
                '?><a>Link</a><?php if($__Context->cond){ ?><strong>Hello, world</strong><?php }else{ ?><em>Wow</em><?php } ?><dummy />'
            ),
            // <!--@if--> ~ <!--@elseif--> ~ <!--@else--> ~ <!--@endif-->
            array(
                '<a>Link</a><!--@if($cond)--><strong>Hello, world</strong><!--@elseif($cond2)--><u>HaHa</u><!--@else--><em>Wow</em><!--@endif--><dummy />',
                '?><a>Link</a><?php if($__Context->cond){ ?><strong>Hello, world</strong><?php }elseif($__Context->cond2){ ?><u>HaHa</u><?php }else{ ?><em>Wow</em><?php } ?><dummy />'
            ),
            // <!--@for--> ~ <!--@endfor-->
            array(
                '<!--@for($i=0;$i<$len;$i++)--><li>Repeat this</li><!--@endfor-->',
                PHP_EOL.'for($__Context->i=0;$__Context->i<$__Context->len;$__Context->i++){ ?><li>Repeat this</li><?php } ?>'
            ),
            // <!--@foreach--> ~ <!--@endforeach-->
            array(
                '<!--@foreach($arr as $key=>$val)--><li>item{$key} : {$val}</li><!--@endfor-->',
                PHP_EOL . 'if($__Context->arr&&count($__Context->arr))foreach($__Context->arr as $__Context->key=>$__Context->val){ ?><li>item<?php echo $__Context->key ?> : <?php echo $__Context->val ?></li><?php } ?>'
            ),
            // <!--@while--> ~ <!--@endwhile-->
            array(
                '<!--@while($item=$list->getItem())--><a href="{$v->link}">{$v->text}</a><!--@endwhile-->',
                PHP_EOL.'while($__Context->item=$__Context->list->getItem()){ ?><a href="<?php echo $__Context->v->link ?>"><?php echo $__Context->v->text ?></a><?php } ?>'
            ),
            // <!--@switch--> ~ <!--@case--> ~ <!--@break--> ~ <!--@default --> ~ <!--@endswitch-->
            array(
                '<dummy /><!--@switch($var)--> <!--@case("A")--> A<!--@break--> <!--@case(\'B\')-->B<!--@break--><!--@default-->C<!--@endswitch--><dummy />',
                '?><dummy /><?php switch($__Context->var){;'.PHP_EOL.'case "A": ?> A<?php break;'.PHP_EOL.'case \'B\': ?>B<?php break;'.PHP_EOL.'default : ?>C<?php } ?><dummy />'
            ),
            // invalid block statement
            array(
                '<dummy /><!--@xe($var)--><dummy />',
                '?><dummy /><dummy />'
            ),
            // {@ ...PHP_CODE...}
            array(
                '<before />{@$list_page = $page_no}<after />',
                '?><before /><?php $__Context->list_page = $__Context->page_no ?><after />'
            ),
            // %load_js_plugin
            array(
                '<dummy /><!--%load_js_plugin("ui")--><dummy />',
                '?><dummy /><!--#JSPLUGIN:ui--><?php Context::loadJavascriptPlugin(\'ui\'); ?><dummy />'
            ),
            // #include
            array(
                '<dummy /><!--#include("sample.html")--><div>This is another dummy</div>',
                '?><dummy /><?php $__tpl=TemplateHandler::getInstance();echo $__tpl->compile(\'tests/unit/classes/template\',\'sample.html\') ?><div>This is another dummy</div>'
            ),
            // <include target="file">
            array(
                '<dummy /><include target="../sample.html" /><div>This is another dummy</div>',
                '?><dummy /><?php $__tpl=TemplateHandler::getInstance();echo $__tpl->compile(\'tests/unit/classes\',\'sample.html\') ?><div>This is another dummy</div>'
            ),
            // <load target="../../modules/page/lang/lang.xml">
            array(
                '<dummy /><load target="../../../../modules/page/lang/lang.xml" /><dummy />',
                '?><dummy /><?php Context::loadLang(\'modules/page/lang\'); ?><dummy />'
            ),
            // <load target="style.css">
            array(
                '<dummy /><load target="css/style.css" /><dummy />',
                '?><dummy /><!--#Meta:tests/unit/classes/template/css/style.css--><?php $__tmp=array(\'tests/unit/classes/template/css/style.css\',\'\',\'\',\'\',array());Context::loadFile($__tmp);unset($__tmp); ?><dummy />'
            ),
            // <unload target="style.css">
            array(
                '<dummy /><unload target="css/style.css" /><dummy />',
                '?><dummy /><?php Context::unloadFile(\'tests/unit/classes/template/css/style.css\',\'\',\'\'); ?><dummy />'
            ),
            // <!--%import("../../modules/page/tpl/filter/insert_config.xml")-->
            array(
                '<dummy /><!--%import("../../../../modules/page/tpl/filter/insert_config.xml")--><dummy />',
                '?><dummy /><?php require_once(\'./classes/xml/XmlJsFilter.class.php\');$__xmlFilter=new XmlJsFilter(\'modules/page/tpl/filter\',\'insert_config.xml\');$__xmlFilter->compile(); ?><dummy />'
            ),
            // <!--%import("../script.js",type="body")-->
            array(
                '<dummy /><!--%import("../script.js",type="body")--><dummy />',
                '?><dummy /><!--#Meta:tests/unit/classes/script.js--><?php $__tmp=array(\'tests/unit/classes/script.js\',\'body\',\'\',\'\');Context::loadFile($__tmp);unset($__tmp); ?><dummy />'
            ),
            // <!--%unload("../script.js",type="body")-->
            array(
                '<dummy /><!--%unload("../script.js",type="body")--><dummy />',
                '?><dummy /><?php Context::unloadFile(\'tests/unit/classes/script.js\',\'\'); ?><dummy />'
            ),
            // comment
            array(
                '<dummy_before /><!--// this is a comment--><dummy_after />',
                '?><dummy_before /><dummy_after />'
            ),
            // self-closing tag
            array(
                '<meta charset="utf-8" cond="$foo">',
                PHP_EOL . 'if($__Context->foo){ ?><meta charset="utf-8"><?php } ?>'
            ),
            // relative path1
            array(
                '<img src="http://naver.com/naver.gif"><input type="image" src="../local.gif" />',
                '?><img src="http://naver.com/naver.gif"><input type="image" src="/rhymix/tests/unit/classes/local.gif" />'
            ),
            // relative path2
            array(
                '<img src="http://naver.com/naver.gif"><input type="image" src="../../../dir/local.gif" />',
                '?><img src="http://naver.com/naver.gif"><input type="image" src="/rhymix/tests/dir/local.gif" />'
            ),
            // error case
            array(
                '<a href="{$layout_info->index_url}" cond="$layout_info->logo_image"><img src="{$layout_info->logo_image}" alt="logo" border="0" /></a>',
                PHP_EOL . 'if($__Context->layout_info->logo_image){ ?><a href="<?php echo $__Context->layout_info->index_url ?>"><img src="<?php echo $__Context->layout_info->logo_image ?>" alt="logo" border="0" /></a><?php } ?>'
            ),
            // error case - ignore stylesheets
            array(
                '<style>body{background-color:black}</style>',
                '?><style>body{background-color:black}</style>'
            ),
            // error case - ignore json
            array(
                '<script>var json = {hello:"world"};</script>',
                '?><script>var json = {hello:"world"};</script>'
            ),
            // error case - inline javascript
            array(
                '<form onsubmit="jQuery(this).find(\'input\').each(function(){if(this.title==this.value)this.value=\'\';}); return procFilter(this, insert_comment)"></form>',
                '?><form onsubmit="jQuery(this).find(\'input\').each(function(){if(this.title==this.value)this.value=\'\';}); return procFilter(this, insert_comment)"><input type="hidden" name="error_return_url" value="<?php echo htmlspecialchars(getRequestUriByServerEnviroment(), ENT_COMPAT | ENT_HTML401, \'UTF-8\', false) ?>" /><input type="hidden" name="act" value="<?php echo $__Context->act ?>" /><input type="hidden" name="mid" value="<?php echo $__Context->mid ?>" /><input type="hidden" name="vid" value="<?php echo $__Context->vid ?>" /></form>'
            ),
            // issue 103
            array(
                '<load target="http://aaa.com/aaa.js" />',
                '?><!--#Meta:http://aaa.com/aaa.js--><?php $__tmp=array(\'http://aaa.com/aaa.js\',\'\',\'\',\'\');Context::loadFile($__tmp);unset($__tmp); ?>'
            ),
            // issue 135
            array(
                '<block loop="$_m_list_all=>$key,$val"><p>{$key}</p><div>Loop block {$val}</div></block>',
                PHP_EOL . '$t1b443c9e474abcf95c0ff70798d10a4c=$__Context->_m_list_all;if($t1b443c9e474abcf95c0ff70798d10a4c&&count($t1b443c9e474abcf95c0ff70798d10a4c))foreach($t1b443c9e474abcf95c0ff70798d10a4c as $__Context->key=>$__Context->val){ ?><p><?php echo $__Context->key ?></p><div>Loop block <?php echo $__Context->val ?></div><?php } ?>'
            ),
            // issue 136
            array(
                '<br cond="$var==\'foo\'" />bar',
                PHP_EOL . 'if($__Context->var==\'foo\'){ ?><br /><?php } ?>bar'
            ),
            // issue 188
            array(
                '<div cond="$ii < $nn" loop="$dummy => $k, $v">Hello, world!</div>',
                PHP_EOL . 'if($__Context->ii < $__Context->nn){;' . PHP_EOL . '$te536ab402425aa07f37ece1808639b07=$__Context->dummy;if($te536ab402425aa07f37ece1808639b07&&count($te536ab402425aa07f37ece1808639b07))foreach($te536ab402425aa07f37ece1808639b07 as $__Context->k=>$__Context->v){ ?><div>Hello, world!</div><?php }} ?>'
            ),
            // issue 190
            array(
                '<div cond="!($i >= $n)" loop="$dummy => $k, $v">Hello, world!</div>',
                PHP_EOL . 'if(!($__Context->i >= $__Context->n)){;' . PHP_EOL . '$tcf235db237866cd5f280e0a7fdb2dca8=$__Context->dummy;if($tcf235db237866cd5f280e0a7fdb2dca8&&count($tcf235db237866cd5f280e0a7fdb2dca8))foreach($tcf235db237866cd5f280e0a7fdb2dca8 as $__Context->k=>$__Context->v){ ?><div>Hello, world!</div><?php }} ?>'
            ),
            // issue 183
            array(
                '<table><thead><tr><th loop="$vvvls => $vvv">{$vvv}</th></tr></thead>'."\n".'<tbody><tr><td>C</td><td>D</td></tr></tbody></table>',
                '?><table><thead><tr><?php $t605f930a4a05d6efbed70a9059f688fe=$__Context->vvvls;if($t605f930a4a05d6efbed70a9059f688fe&&count($t605f930a4a05d6efbed70a9059f688fe))foreach($t605f930a4a05d6efbed70a9059f688fe as $__Context->vvv){ ?><th><?php echo $__Context->vvv ?></th><?php } ?></tr></thead>'."\n".'<tbody><tr><td>C</td><td>D</td></tr></tbody></table>'
            ),
            // issue 512 - ignores <marquee>
            array(
                '<div class="topimgContex"><marquee direction="up" scrollamount="1" height="130" loop="infinity" behavior="lscro">{$lang->sl_show_topimgtext}</marquee></div>',
                '?><div class="topimgContex"><marquee direction="up" scrollamount="1" height="130" loop="infinity" behavior="lscro"><?php echo $__Context->lang->sl_show_topimgtext ?></marquee></div>'
            ),
            // issue 584
            array(
                '<img cond="$oBodex->display_extra_images[\'mobile\'] && $arr_extra && $arr_extra->bodex->mobile" src="./images/common/mobile.gif" title="mobile" alt="mobile" />',
                PHP_EOL . 'if($__Context->oBodex->display_extra_images[\'mobile\'] && $__Context->arr_extra && $__Context->arr_extra->bodex->mobile){ ?><img src="/rhymix/tests/unit/classes/template/images/common/mobile.gif" title="mobile" alt="mobile" /><?php } ?>'
            ),
            // issue 831
            array(
                "<li <!--@if(in_array(\$act, array(\n'dispNmsAdminGroupList',\n'dispNmsAdminInsertGroup',\n'dispNmsAdminGroupInfo',\n'dispNmsAdminDeleteGroup')))-->class=\"on\"<!--@endif-->>",
                "?><li <?php if(in_array(\$__Context->act, array(\n'dispNmsAdminGroupList',\n'dispNmsAdminInsertGroup',\n'dispNmsAdminGroupInfo',\n'dispNmsAdminDeleteGroup'))){ ?>class=\"on\"<?php } ?>>"
            ),
            // issue 746
            array(
                '<img src="../whatever/img.png" />',
                '?><img src="/rhymix/tests/unit/classes/whatever/img.png" />'
            ),
            // issue 696
            array(
                '{@ eval(\'$val = $document_srl;\')}',
                PHP_EOL . 'eval(\'$__Context->val = $__Context->document_srl;\') ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img cond="$foo->bar" src="../common/mobile.gif" />',
                PHP_EOL . 'if($__Context->foo->bar){ ?><img src="/rhymix/tests/unit/classes/common/mobile.gif" /><?php } ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img cond="$foo->bar > 100" alt="a!@#$%^&*()_-=[]{}?/" src="../common/mobile.gif" />',
                PHP_EOL . 'if($__Context->foo->bar > 100){ ?><img alt="a!@#$%^&*()_-=[]{}?/" src="/rhymix/tests/unit/classes/common/mobile.gif" /><?php } ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img src="../common/mobile.gif" cond="$foo->bar" />',
                PHP_EOL . 'if($__Context->foo->bar){ ?><img src="/rhymix/tests/unit/classes/common/mobile.gif" /><?php } ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img class="tmp_class" cond="!$module_info->title" src="../img/common/blank.gif" />',
                PHP_EOL . 'if(!$__Context->module_info->title){ ?><img class="tmp_class" src="/rhymix/tests/unit/classes/img/common/blank.gif" /><?php } ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img cond="$mi->title" class="tmp_class"|cond="$mi->use" src="../img/common/blank.gif" />',
                PHP_EOL . 'if($__Context->mi->title){ ?><img<?php if($__Context->mi->use){ ?> class="tmp_class"<?php } ?> src="/rhymix/tests/unit/classes/img/common/blank.gif" /><?php } ?>'
            ),
            array(
                '<input foo="bar" /> <img cond="$foo->bar" alt="alt"   src="../common/mobile.gif" />',
                '?><input foo="bar" /> <?php if($__Context->foo->bar){ ?><img alt="alt"   src="/rhymix/tests/unit/classes/common/mobile.gif" /><?php } ?>'
            ),
            array(
                '<input foo="bar" />' . "\n" . '<input foo="bar" /> <img cond="$foo->bar" alt="alt"   src="../common/mobile.gif" />',
                '?><input foo="bar" />' . PHP_EOL . '<input foo="bar" /> <?php if($__Context->foo->bar){ ?><img alt="alt"   src="/rhymix/tests/unit/classes/common/mobile.gif" /><?php } ?>'
            ),
            array(
                'asf <img src="{$foo->bar}" />',
                '?>asf <img src="<?php echo $__Context->foo->bar ?>" />'
            ),
            array(
                '<img alt="" '.PHP_EOL.' src="../whatever/img.png" />',
                '?><img alt="" '.PHP_EOL.' src="/rhymix/tests/unit/classes/whatever/img.png" />'
            ),
            array(
                '<input>asdf src="../img/img.gif" asdf</input> <img alt="src" src="../whatever/img.png" /> <input>asdf src="../img/img.gif" asdf</input>',
                '?><input>asdf src="../img/img.gif" asdf</input> <img alt="src" src="/rhymix/tests/unit/classes/whatever/img.png" /> <input>asdf src="../img/img.gif" asdf</input>'
            ),
            array(
                '<input>asdf src="../img/img.gif" asdf</input>',
                '?><input>asdf src="../img/img.gif" asdf</input>'
            ),
        );

        foreach ($tests as $test)
        {
            $tmpl = new TemplateHandlerWrapper;
            $tmpl->init(__DIR__ . '/template', 'no_file.html');
            $result = $tmpl->parse($test[0]);
            $this->assertEquals($this->prefix . $test[1], $result);
        }
    }

    public function testParseNoContent()
    {
        $tmpl = new TemplateHandlerWrapper;
        $tmpl->init(__DIR__ . '/template', 'no_file.html');
        $result = $tmpl->parse($tpl);

        $this->assertEquals('', $result);
    }

    public function testCompileDirect()
    {
        $tmpl = TemplateHandler::getInstance();
        $result = $tmpl->compileDirect(__DIR__ . '/template', 'sample.html');
        $result = trim($result);

        $this->assertEquals($result, $this->prefix.PHP_EOL.'if($__Context->has_blog){ ?><a href="http://mygony.com">Taggon\'s blog</a><?php } ?>'.PHP_EOL.'<!--#Meta://external.host/js.js--><?php $__tmp=array(\'//external.host/js.js\',\'\',\'\',\'\');Context::loadFile($__tmp);unset($__tmp); ?>');
    }
}


class TemplateHandlerWrapper extends \TemplateHandler {
    private $inst;

    function __construct() {
        $this->inst = parent::getInstance();
    }

    public function init($tpl_path, $tpl_filename, $tpl_file = '') {
        call_user_func(array($this->inst, 'init'), $tpl_path, $tpl_filename, $tpl_file);
    }

    public function parse($buff = null) {
        return call_user_func(array($this->inst, 'parse'), $buff);
    }
}
