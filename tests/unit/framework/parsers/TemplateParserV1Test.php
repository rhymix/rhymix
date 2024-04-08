<?php

class TemplateParserV1Test extends \Codeception\Test\Unit
{
	private $baseurl;
    private $prefix = '<?php if (!defined("RX_VERSION")) exit();';

	public function _before()
	{
		\Rhymix\Framework\Debug::disable();
		$this->baseurl = '/' . basename(dirname(dirname(dirname(dirname(__DIR__))))) . '/';
	}

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
                '?><a href="#">Link1</a><a href="#cond"><?php if($__Context->cond ?? false){ ?><span>say, hello</span><?php } ?></a>'
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
                '?><ul><?php $__loop_tmp=$__Context->arr;if($__loop_tmp)foreach($__loop_tmp as $__Context->key=>$__Context->val){ ?><li class="sample"><a>Link</a><ul><?php $__loop_tmp=$__Context->arr2;if($__loop_tmp)foreach($__loop_tmp as $__Context->key2=>$__Context->val2){ ?><li></li><?php } ?></ul></li><?php } ?></ul>'
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
                '?><a>Link</a><?php if($__Context->cond){ ?><strong>Hello, <?php echo $__Context->world ?? \'\' ?></strong><?php } ?><dummy />'
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
                'for($__Context->i=0;$__Context->i<$__Context->len;$__Context->i++){ ?><li>Repeat this</li><?php } ?>'
            ),
            // <!--@foreach--> ~ <!--@endforeach-->
            array(
                '<!--@foreach($arr as $key=>$val)--><li>item{$key} : {$val}</li><!--@endfor-->',
                'if($__Context->arr)foreach($__Context->arr as $__Context->key=>$__Context->val){ ?><li>item<?php echo $__Context->key ?? \'\' ?> : <?php echo $__Context->val ?? \'\' ?></li><?php } ?>'
            ),
            // <!--@while--> ~ <!--@endwhile-->
            array(
                '<!--@while($item=$list->getItem())--><a href="{$v->link}">{$v->text}</a><!--@endwhile-->',
                'while($__Context->item=$__Context->list->getItem()){ ?><a href="<?php echo $__Context->v->link ?? \'\' ?>"><?php echo $__Context->v->text ?? \'\' ?></a><?php } ?>'
            ),
            // <!--@switch--> ~ <!--@case--> ~ <!--@break--> ~ <!--@default --> ~ <!--@endswitch-->
            array(
                '<dummy /><!--@switch($var)--> <!--@case("A")--> A<!--@break--> <!--@case(\'B\')-->B<!--@break--><!--@default-->C<!--@endswitch--><dummy />',
                '?><dummy /><?php switch($__Context->var){ case "A": ?> A<?php break; case \'B\': ?>B<?php break; default : ?>C<?php } ?><dummy />'
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
                '?><dummy /><?php $__tpl=TemplateHandler::getInstance();echo $__tpl->compile(\'tests/_data/template\',\'sample.html\') ?><div>This is another dummy</div>'
            ),
            // <include target="file">
            array(
                '<dummy /><include target="../sample.html" /><div>This is another dummy</div>',
                '?><dummy /><?php $__tpl=TemplateHandler::getInstance();echo $__tpl->compile(\'tests/_data\',\'sample.html\') ?><div>This is another dummy</div>'
            ),
            // <load target="../../modules/page/lang/lang.xml">
            array(
                '<dummy /><load target="../../../modules/page/lang/lang.xml" /><dummy />',
                '?><dummy /><?php Context::loadLang(\'modules/page/lang\'); ?><dummy />'
            ),
            // <load target="style.css">
            array(
                '<dummy /><load target="css/style.css" /><dummy />',
                '?><dummy /><!--#Meta:tests/_data/template/css/style.css--><?php Context::loadFile([\'tests/_data/template/css/style.css\', \'\', \'\', \'\', []]); ?><dummy />'
            ),
            // <load target="https://fonts.googleapis.com/css?family=Montserrat&display=swap">
            array(
                '<dummy /><load target="https://fonts.googleapis.com/css?family=Montserrat&display=swap" /><dummy />',
                '?><dummy /><!--#Meta:https://fonts.googleapis.com/css?family=Montserrat&display=swap--><?php Context::loadFile([\'https://fonts.googleapis.com/css?family=Montserrat&display=swap\', \'\', \'tests\', \'\', []]); ?><dummy />'
            ),
            // <load target="//fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap">
            array(
                '<dummy /><load target="//fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap" /><dummy />',
                '?><dummy /><!--#Meta://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap--><?php Context::loadFile([\'//fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap\', \'\', \'tests\', \'\', []]); ?><dummy />'
            ),
            // <unload target="style.css">
            array(
                '<dummy /><unload target="css/style.css" /><dummy />',
                '?><dummy /><?php Context::unloadFile(\'tests/_data/template/css/style.css\', \'\', \'\'); ?><dummy />'
            ),
            // <!--%import("../../modules/page/tpl/filter/insert_config.xml")-->
            array(
                '<dummy /><!--%import("../../../modules/page/tpl/filter/insert_config.xml")--><dummy />',
                '?><dummy /><?php require_once(\'./classes/xml/XmlJsFilter.class.php\');$__xmlFilter=new XmlJsFilter(\'modules/page/tpl/filter\',\'insert_config.xml\');$__xmlFilter->compile(); ?><dummy />'
            ),
            // <!--%import("../script.js",type="body")-->
            array(
                '<dummy /><!--%import("../script.js",type="body")--><dummy />',
                '?><dummy /><!--#Meta:tests/_data/script.js--><?php Context::loadFile([\'tests/_data/script.js\', \'body\', \'\', \'\']); ?><dummy />'
            ),
            // <!--%unload("../script.js",type="body")-->
            array(
                '<dummy /><!--%unload("../script.js",type="body")--><dummy />',
                '?><dummy /><?php Context::unloadFile(\'tests/_data/script.js\', \'\'); ?><dummy />'
            ),
            // comment
            array(
                '<dummy_before /><!--// this is a comment--><dummy_after />',
                '?><dummy_before /><dummy_after />'
            ),
            // self-closing tag
            array(
                '<meta charset="utf-8" cond="$foo">',
                'if($__Context->foo ?? false){ ?><meta charset="utf-8"><?php } ?>'
            ),
            // relative path1
            array(
                '<img src="http://naver.com/naver.gif"><input type="image" src="../local.gif" />',
                '?><img src="http://naver.com/naver.gif"><input type="image" src="' . $this->baseurl . 'tests/_data/local.gif" />'
            ),
            // relative path2
            array(
                '<img src="http://naver.com/naver.gif"><input type="image" src="../../dir/local.gif" />',
                '?><img src="http://naver.com/naver.gif"><input type="image" src="' . $this->baseurl . 'tests/dir/local.gif" />'
            ),
            // error case
            array(
                '<a href="{$layout_info->index_url}" cond="$layout_info->logo_image"><img src="{$layout_info->logo_image}" alt="logo" border="0" /></a>',
                'if($__Context->layout_info->logo_image ?? false){ ?><a href="<?php echo $__Context->layout_info->index_url ?? \'\' ?>"><img src="<?php echo $__Context->layout_info->logo_image ?? \'\' ?>" alt="logo" border="0" /></a><?php } ?>'
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
                '?><form onsubmit="jQuery(this).find(\'input\').each(function(){if(this.title==this.value)this.value=\'\';}); return procFilter(this, insert_comment)"><input type="hidden" name="error_return_url" value="<?php echo escape(getRequestUriByServerEnviroment(), false); ?>" /><input type="hidden" name="act" value="<?php echo $__Context->act ?? \'\'; ?>" /><input type="hidden" name="mid" value="<?php echo $__Context->mid ?? \'\'; ?>" /></form>'
            ),
            // issue 103
            array(
                '<load target="http://aaa.com/aaa.js" />',
                '?><!--#Meta:http://aaa.com/aaa.js--><?php Context::loadFile([\'http://aaa.com/aaa.js\', \'\', \'tests\', \'\']); ?>'
            ),
            // issue 135
            array(
                '<block loop="$_m_list_all=>$key,$val"><p>{$key}</p><div>Loop block {$val}</div></block>',
                '$__loop_tmp=$__Context->_m_list_all;if($__loop_tmp)foreach($__loop_tmp as $__Context->key=>$__Context->val){ ?><p><?php echo $__Context->key ?? \'\' ?></p><div>Loop block <?php echo $__Context->val ?? \'\' ?></div><?php } ?>'
            ),
            // issue 136
            array(
                '<br cond="$var==\'foo\'" />bar',
                'if($__Context->var==\'foo\'){ ?><br /><?php } ?>bar'
            ),
            // issue 188
            array(
                '<div cond="$ii < $nn" loop="$dummy => $k, $v">Hello, world!</div>',
                'if($__Context->ii < $__Context->nn){ ' . '$__loop_tmp=$__Context->dummy;if($__loop_tmp)foreach($__loop_tmp as $__Context->k=>$__Context->v){ ?><div>Hello, world!</div><?php }} ?>'
            ),
            // issue 190
            array(
                '<div cond="!($i >= $n)" loop="$dummy => $k, $v">Hello, world!</div>',
                'if(!($__Context->i >= $__Context->n)){ ' . '$__loop_tmp=$__Context->dummy;if($__loop_tmp)foreach($__loop_tmp as $__Context->k=>$__Context->v){ ?><div>Hello, world!</div><?php }} ?>'
            ),
            // issue 183
            array(
                '<table><thead><tr><th loop="$vvvls => $vvv">{$vvv}</th></tr></thead>'."\n".'<tbody><tr><td>C</td><td>D</td></tr></tbody></table>',
                '?><table><thead><tr><?php $__loop_tmp=$__Context->vvvls;if($__loop_tmp)foreach($__loop_tmp as $__Context->vvv){ ?><th><?php echo $__Context->vvv ?? \'\' ?></th><?php } ?></tr></thead>'."\n".'<tbody><tr><td>C</td><td>D</td></tr></tbody></table>'
            ),
            // issue 512 - ignores <marquee>
            array(
                '<div class="topimgContex"><marquee direction="up" scrollamount="1" height="130" loop="infinity" behavior="lscro">{$lang->sl_show_topimgtext}</marquee></div>',
                '?><div class="topimgContex"><marquee direction="up" scrollamount="1" height="130" loop="infinity" behavior="lscro"><?php echo $__Context->lang->sl_show_topimgtext ?></marquee></div>'
            ),
            // issue 584
            array(
                '<img cond="$oBodex->display_extra_images[\'mobile\'] && $arr_extra && $arr_extra->bodex->mobile" src="./images/common/mobile.gif" title="mobile" alt="mobile" />',
                'if($__Context->oBodex->display_extra_images[\'mobile\'] && $__Context->arr_extra && $__Context->arr_extra->bodex->mobile){ ?><img src="' . $this->baseurl . 'tests/_data/template/images/common/mobile.gif" title="mobile" alt="mobile" /><?php } ?>'
            ),
            // issue 831
            array(
                "<li <!--@if(in_array(\$act, array(\n'dispNmsAdminGroupList',\n'dispNmsAdminInsertGroup',\n'dispNmsAdminGroupInfo',\n'dispNmsAdminDeleteGroup')))-->class=\"on\"<!--@endif-->>",
                "?><li <?php if(in_array(\$__Context->act, array(\n'dispNmsAdminGroupList',\n'dispNmsAdminInsertGroup',\n'dispNmsAdminGroupInfo',\n'dispNmsAdminDeleteGroup'))){ ?>class=\"on\"<?php } ?>>"
            ),
            // issue 746
            array(
                '<img src="../whatever/img.png" />',
                '?><img src="' . $this->baseurl . 'tests/_data/whatever/img.png" />'
            ),
            // issue 696
            array(
                '{@ eval(\'$val = $document_srl;\')}',
                ' eval(\'$__Context->val = $__Context->document_srl;\') ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img cond="$foo->bar" src="../common/mobile.gif" />',
                'if($__Context->foo->bar ?? false){ ?><img src="' . $this->baseurl . 'tests/_data/common/mobile.gif" /><?php } ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img cond="$foo->bar > 100" alt="a!@#$%^&*()_-=[]{}?/" src="../common/mobile.gif" />',
                'if($__Context->foo->bar > 100){ ?><img alt="a!@#$%^&*()_-=[]{}?/" src="' . $this->baseurl . 'tests/_data/common/mobile.gif" /><?php } ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img src="../common/mobile.gif" cond="$foo->bar" />',
                'if($__Context->foo->bar ?? false){ ?><img src="' . $this->baseurl . 'tests/_data/common/mobile.gif" /><?php } ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img class="tmp_class" cond="!$module_info->title" src="../img/common/blank.gif" />',
                'if(!$__Context->module_info->title){ ?><img class="tmp_class" src="' . $this->baseurl . 'tests/_data/img/common/blank.gif" /><?php } ?>'
            ),
            // https://github.com/xpressengine/xe-core/issues/1510
            array(
                '<img cond="$mi->title" class="tmp_class"|cond="$mi->use" src="../img/common/blank.gif" />',
                'if($__Context->mi->title ?? false){ ?><img<?php if($__Context->mi->use){ ?> class="tmp_class"<?php } ?> src="' . $this->baseurl . 'tests/_data/img/common/blank.gif" /><?php } ?>'
            ),
            array(
                '<input foo="bar" /> <img cond="$foo->bar" alt="alt"   src="../common/mobile.gif" />',
                '?><input foo="bar" /> <?php if($__Context->foo->bar ?? false){ ?><img alt="alt"   src="' . $this->baseurl . 'tests/_data/common/mobile.gif" /><?php } ?>'
            ),
            array(
                '<input foo="bar" />' . "\r\n" . '<input foo="bar" /> <img cond="$foo->bar" alt="alt"   src="../common/mobile.gif" />',
                '?><input foo="bar" />' . "\n" . '<input foo="bar" /> <?php if($__Context->foo->bar ?? false){ ?><img alt="alt"   src="' . $this->baseurl . 'tests/_data/common/mobile.gif" /><?php } ?>'
            ),
            array(
                'asf <img src="{$foo->bar}" />',
                '?>asf <img src="<?php echo $__Context->foo->bar ?? \'\' ?>" />'
            ),
            array(
                '<img alt="" '.PHP_EOL.' src="../whatever/img.png" />',
                '?><img alt="" '.PHP_EOL.' src="' . $this->baseurl . 'tests/_data/whatever/img.png" />'
            ),
            array(
                '<input>asdf src="../img/img.gif" asdf</input> <img alt="src" src="../whatever/img.png" /> <input>asdf src="../img/img.gif" asdf</input>',
                '?><input>asdf src="../img/img.gif" asdf</input> <img alt="src" src="' . $this->baseurl . 'tests/_data/whatever/img.png" /> <input>asdf src="../img/img.gif" asdf</input>'
            ),
            array(
                '<input>asdf src="../img/img.gif" asdf</input>',
                '?><input>asdf src="../img/img.gif" asdf</input>'
            ),
			array(
				'<img src="data:image/png;base64,AAAAAAAAAAA=" />',
				'?><img src="data:image/png;base64,AAAAAAAAAAA=" />'
			),
            // srcset (PR #1544)
            array(
                '<img src="./img/sticker_banner_960w.png" alt="this is a test image." srcset="https://abc.com/static/img/test@2x.png 2x,  http://abc.com/static/test@2.5x.png 2.5x,../img/test@3x.png 3x, ../img/test_960w.png  960w, {$mid}/image.png 480w">',
                '?><img src="' . $this->baseurl . 'tests/_data/template/img/sticker_banner_960w.png" alt="this is a test image." srcset="https://abc.com/static/img/test@2x.png 2x, http://abc.com/static/test@2.5x.png 2.5x, ' . $this->baseurl . 'tests/_data/img/test@3x.png 3x, ' . $this->baseurl . 'tests/_data/img/test_960w.png  960w, <?php echo $__Context->mid ?? \'\' ?>/image.png 480w">'
            ),
			// Rhymix improvements (PR #604)
            array(
                '<span>{$_SERVER["REMOTE_ADDR"]}</span>',
                '?><span><?php echo $_SERVER["REMOTE_ADDR"] ?? \'\' ?></span>'
            ),
            array(
                '<span>{escape($_COOKIE[$var], false)}</span>',
                '?><span><?php echo escape($_COOKIE[$__Context->var], false) ?></span>'
            ),
            array(
                '<span>{$GLOBALS[$__Context->rhymix->rules]}</span>',
                '?><span><?php echo $GLOBALS[$__Context->rhymix->rules] ?></span>'
            ),
            array(
                '<span>{$FOOBAR}</span>',
                '?><span><?php echo $__Context->FOOBAR ?? \'\' ?></span>'
            ),
            array(
                '<span>{RX_BASEDIR}</span>',
                '?><span>{RX_BASEDIR}</span>'
            ),
            array(
                '<span>{\RX_BASEDIR}</span>',
                '?><span><?php echo \RX_BASEDIR ?></span>'
            ),
			// Rhymix improvements: object attributes enclosed in curly braces
            array(
                '<div>{$foo->$bar[$bazz]}</div>',
                '?><div><?php echo $__Context->foo->{$__Context->bar}[$__Context->bazz] ?></div>'
            ),
            array(
                '<!--@if($foo->$bar)--><div></div><!--@endif-->',
                'if($__Context->foo->{$__Context->bar}){ ?><div></div><?php } ?>'
            ),
            array(
                '<aside cond="$foo->$bar"><img src="" /></aside>',
                'if($__Context->foo->{$__Context->bar}){ ?><aside><img src="" /></aside><?php } ?>'
            ),
            array(
                '<ul loop="$foo->$bar => $key, $val" class="test"|cond="$foo->$key"><li>{$val}</li></ul>',
                '$__loop_tmp=$__Context->foo->{$__Context->bar};if($__loop_tmp)foreach($__loop_tmp as $__Context->key=>$__Context->val){ ?><ul<?php if($__Context->foo->{$__Context->key}){ ?> class="test"<?php } ?>><li><?php echo $__Context->val ?? \'\' ?></li></ul><?php } ?>'
            ),
			// Rhymix autoescape
            array(
                '<config autoescape="on" />{$foo}',
                '$this->config->autoescape = true; ' . 'echo ($this->config->autoescape ? htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', false) : ($__Context->foo ?? \'\')) ?>'
            ),
            array(
                '<config autoescape="off" />{$foo}',
                '$this->config->autoescape = false; ' . 'echo ($this->config->autoescape ? htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', false) : ($__Context->foo ?? \'\')) ?>'
            ),
            array(
                '<config autoescape="yes" />{$foo|auto}',
                '$this->config->autoescape = true; ' . 'echo ($this->config->autoescape ? htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', false) : ($__Context->foo ?? \'\')) ?>'
            ),
            array(
                '<config autoescape="no" />{$foo->$bar|auto}',
                '$this->config->autoescape = false; ' . 'echo ($this->config->autoescape ? htmlspecialchars($__Context->foo->{$__Context->bar}, ENT_QUOTES, \'UTF-8\', false) : ($__Context->foo->{$__Context->bar})) ?>'
            ),
            array(
                '<config autoescape="true" />{$foo|autoescape}',
                '$this->config->autoescape = true; ' . 'echo htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', false) ?>'
            ),
            array(
                '<config autoescape="false" />{$foo|autoescape}',
                '$this->config->autoescape = false; ' . 'echo htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', false) ?>'
            ),
            array(
                '<config autoescape="1" />{$foo|escape}',
                '$this->config->autoescape = true; ' . 'echo htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', true) ?>'
            ),
            array(
                '<config autoescape="0" />{$foo|escape}',
                '$this->config->autoescape = false; ' . 'echo htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', true) ?>'
            ),
            array(
                '<config autoescape="Y" />{$foo|noescape}',
                '$this->config->autoescape = true; ' . 'echo $__Context->foo ?? \'\' ?>'
            ),
            array(
                '<config autoescape="N" />{$foo|noescape}',
                '$this->config->autoescape = false; ' . 'echo $__Context->foo ?? \'\' ?>'
            ),
			// Rhymix filters
            array(
                '<p>{$foo|escape}</p>',
                '?><p><?php echo htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', true) ?></p>'
            ),
            array(
                '<p>{$foo|json}</p>',
                '?><p><?php echo json_encode($__Context->foo) ?></p>'
            ),
            array(
                '<p>{$foo|urlencode}</p>',
                '?><p><?php echo rawurlencode($__Context->foo) ?></p>'
            ),
            array(
                '<p>{$foo|lower|nl2br}</p>',
                '?><p><?php echo nl2br(strtolower($__Context->foo)) ?></p>'
            ),
            array(
                '<p>{$foo|join:/|upper}</p>',
                '?><p><?php echo strtoupper(implode(\'/\', $__Context->foo)) ?></p>'
            ),
            array(
                '<p>{$foo|join:\||upper}</p>',
                '?><p><?php echo strtoupper(implode(\'|\', $__Context->foo)) ?></p>'
            ),
            array(
                '<p>{$foo|join:$separator}</p>',
                '?><p><?php echo implode($__Context->separator, $__Context->foo) ?></p>'
            ),
            array(
                '<p>{$foo|strip}</p>',
                '?><p><?php echo strip_tags($__Context->foo) ?></p>'
            ),
            array(
                '<p>{$foo|strip:<br>}</p>',
                '?><p><?php echo strip_tags($__Context->foo, \'<br>\') ?></p>'
            ),
            array(
                '<p>{$foo|strip:$mytags}</p>',
                '?><p><?php echo strip_tags($__Context->foo, $__Context->mytags) ?></p>'
            ),
            array(
                '<p>{$foo|strip:myfunc($mytags)}</p>',
                '?><p><?php echo strip_tags($__Context->foo, myfunc($__Context->mytags)) ?></p>'
            ),
            array(
                '<p>{$foo|trim|date}</p>',
                '?><p><?php echo getDisplayDateTime(ztime(trim($__Context->foo)), \'Y-m-d H:i:s\') ?></p>'
            ),
            array(
                '<p>{$foo|date:His}</p>',
                '?><p><?php echo getDisplayDateTime(ztime($__Context->foo), \'His\') ?></p>'
            ),
            array(
                '<p>{$foo|format:2}</p>',
                '?><p><?php echo number_format($__Context->foo, \'2\') ?></p>'
            ),
            array(
                '<p>{$foo->$bar|shorten}</p>',
                '?><p><?php echo number_shorten($__Context->foo->{$__Context->bar}) ?></p>'
            ),
            array(
                '<p>{$foo|shorten:2}</p>',
                '?><p><?php echo number_shorten($__Context->foo, \'2\') ?></p>'
            ),
            array(
                '<p>{$foo|date:His}</p>',
                '?><p><?php echo getDisplayDateTime(ztime($__Context->foo), \'His\') ?></p>'
            ),
            array(
                '<p>{$foo[$bar]|link}</p>',
                '?><p><?php echo \'<a href="\' . ($__Context->foo[$__Context->bar]) . \'">\' . ($__Context->foo[$__Context->bar]) . \'</a>\' ?></p>'
            ),
            array(
                '<p>{$foo|link:http://www.rhymix.org}</p>',
                '?><p><?php echo \'<a href="\' . (\'http://www.rhymix.org\') . \'">\' . ($__Context->foo ?? \'\') . \'</a>\' ?></p>'
            ),
            array(
                '<p>{$foo|link:$url}</p>',
                '?><p><?php echo \'<a href="\' . ($__Context->url ?? \'\') . \'">\' . ($__Context->foo ?? \'\') . \'</a>\' ?></p>'
            ),
            array(
                '<config autoescape="on" /><p>{$foo|link:$url}</p>',
                '$this->config->autoescape = true; ?><p><?php echo \'<a href="\' . (($this->config->autoescape ? htmlspecialchars($__Context->url ?? \'\', ENT_QUOTES, \'UTF-8\', false) : ($__Context->url ?? \'\'))) . \'">\' . (($this->config->autoescape ? htmlspecialchars($__Context->foo ?? \'\', ENT_QUOTES, \'UTF-8\', false) : ($__Context->foo ?? \'\'))) . \'</a>\' ?></p>'
            ),
			// Rhymix filters (reject malformed filters)
            array(
                '<p>{$foo|dafuq}</p>',
                '?><p><?php echo \'INVALID FILTER (dafuq)\' ?></p>'
            ),
            array(
                '<p>{$foo|4}</p>',
                '?><p><?php echo $__Context->foo|4 ?></p>'
            ),
            array(
                '<p>{$foo|a+7|lower}</p>',
                '?><p><?php echo strtolower($__Context->foo|a+7) ?></p>'
            ),
            array(
                '<p>{$foo|Filter}</p>',
                '?><p><?php echo $__Context->foo|Filter ?></p>'
            ),
            array(
                '<p>{$foo|filter++}</p>',
                '?><p><?php echo $__Context->foo|filter++ ?></p>'
            ),
            array(
                '<p>{$foo|filter:}</p>',
                '?><p><?php echo $__Context->foo|filter: ?></p>'
            ),
            array(
                '<p>{$foo|$bar}</p>',
                '?><p><?php echo $__Context->foo|$__Context->bar ?></p>'
            ),
            array(
                '<p>{$foo||bar}</p>',
                '?><p><?php echo $__Context->foo||bar ?></p>'
            ),
            array(
                '<p>{htmlspecialchars($var, ENT_COMPAT | ENT_HTML401)}</p>',
                '?><p><?php echo htmlspecialchars($__Context->var, ENT_COMPAT | ENT_HTML401) ?></p>'
            ),
            array(
                '<p>{$foo | $bar}</p>',
                '?><p><?php echo $__Context->foo | $__Context->bar ?></p>'
            ),
        );

        foreach ($tests as $test)
        {
            $tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'empty.html');
            $result = $tmpl->parse($test[0]);
			$between = str_starts_with($test[1], '?>') ? '' : ' ';
            $this->assertEquals($this->prefix . $between . $test[1], $result);
        }
    }

    public function testParseNoContent()
    {
        $tmpl = new \Rhymix\Framework\Template('./tests/_data/template', 'empty.html');
        $result = $tmpl->parse(null);
        $this->assertEquals('', $result);
    }

    public function testCompileDirect()
    {
        $tmpl = new \Rhymix\Framework\Template();
        $result = $tmpl->compileDirect('./tests/_data/template', 'v1example.html');
        $result = trim($result);

        $this->assertEquals($this->prefix . ' if($__Context->has_blog ?? false){ ?><a href="http://mygony.com">Taggon\'s blog</a><?php } ?>'.PHP_EOL.'<!--#Meta://external.host/js.js--><?php Context::loadFile([\'//external.host/js.js\', \'\', \'tests\', \'\']); ?>', $result);
    }
}
