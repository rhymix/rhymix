<?php

class RouterTest extends \Codeception\Test\Unit
{
    public function testGetRewriteLevel()
    {
        Rhymix\Framework\Config::set('url.rewrite', null);
        Rhymix\Framework\Config::set('use_rewrite', false);
        $this->assertEquals(0, Rhymix\Framework\Router::getRewriteLevel());

        Rhymix\Framework\Config::set('url.rewrite', 1);
        Rhymix\Framework\Config::set('use_rewrite', false);
        $this->assertEquals(1, Rhymix\Framework\Router::getRewriteLevel());

        Rhymix\Framework\Config::set('url.rewrite', 1);
        Rhymix\Framework\Config::set('use_rewrite', true);
        $this->assertEquals(1, Rhymix\Framework\Router::getRewriteLevel());

        Rhymix\Framework\Config::set('url.rewrite', 2);
        Rhymix\Framework\Config::set('use_rewrite', true);
        $this->assertEquals(2, Rhymix\Framework\Router::getRewriteLevel());
    }

	public function testGetURL()
	{
        $info = Rhymix\Framework\Parsers\ModuleActionParser::loadXML(\RX_BASEDIR . 'modules/board/conf/module.xml');
        $this->assertEquals('dispBoardContent', $info->default_index_act);
        $this->assertFalse(isset($info->permission));
        $this->assertGreaterThan(0, count($info->route->GET));
        $this->assertGreaterThan(0, count($info->action->dispBoardContent->route));

        getController('module')->registerActionForwardRoutes('member');

        $args = array('mid' => 'board', 'act' => 'dispBoardContent');
        $this->assertEquals('board', Rhymix\Framework\Router::getURL($args, 2));
        $this->assertEquals('index.php?mid=board&act=dispBoardContent', Rhymix\Framework\Router::getURL($args, 1));
        $this->assertEquals('index.php?mid=board&act=dispBoardContent', Rhymix\Framework\Router::getURL($args, 0));

        $args = array('mid' => 'board', 'page' => 3);
        $this->assertEquals('board/page/3', Rhymix\Framework\Router::getURL($args, 2));
        $this->assertEquals('board?page=3', Rhymix\Framework\Router::getURL($args, 1));
        $this->assertEquals('index.php?mid=board&page=3', Rhymix\Framework\Router::getURL($args, 0));

        $args = array('mid' => 'board', 'document_srl' => 123);
        $this->assertEquals('board/123', Rhymix\Framework\Router::getURL($args, 2));
        $this->assertEquals('board/123', Rhymix\Framework\Router::getURL($args, 1));
        $this->assertEquals('index.php?mid=board&document_srl=123', Rhymix\Framework\Router::getURL($args, 0));

        $args = array('mid' => 'board', 'act' => 'dispBoardWrite', 'document_srl' => 123);
        $this->assertEquals('board/123/edit', Rhymix\Framework\Router::getURL($args, 2));
        $this->assertEquals('index.php?mid=board&act=dispBoardWrite&document_srl=123', Rhymix\Framework\Router::getURL($args, 1));
        $this->assertEquals('index.php?mid=board&act=dispBoardWrite&document_srl=123', Rhymix\Framework\Router::getURL($args, 0));

        $args = array('mid' => 'board', 'act' => 'dispBoardWrite');
        $this->assertEquals('board/write', Rhymix\Framework\Router::getURL($args, 2));
        $this->assertEquals('index.php?mid=board&act=dispBoardWrite', Rhymix\Framework\Router::getURL($args, 1));
        $this->assertEquals('index.php?mid=board&act=dispBoardWrite', Rhymix\Framework\Router::getURL($args, 0));

        $args = array('mid' => 'board', 'act' => 'dispBoardModifyComment', 'document_srl' => 123, 'comment_srl' => 456, 'extra_param' => 'foo bar');
        $this->assertEquals('board/comment/456/edit?extra_param=foo+bar', Rhymix\Framework\Router::getURL($args, 2));
        $this->assertEquals('index.php?mid=board&act=dispBoardModifyComment&document_srl=123&comment_srl=456&extra_param=foo+bar', Rhymix\Framework\Router::getURL($args, 1));
        $this->assertEquals('index.php?mid=board&act=dispBoardModifyComment&document_srl=123&comment_srl=456&extra_param=foo+bar', Rhymix\Framework\Router::getURL($args, 0));

        $args = array('mid' => 'board', 'act' => 'dispMemberInfo');
        $this->assertEquals('board/member_info', Rhymix\Framework\Router::getURL($args, 2));
        $this->assertEquals('index.php?mid=board&act=dispMemberInfo', Rhymix\Framework\Router::getURL($args, 1));
        $this->assertEquals('index.php?mid=board&act=dispMemberInfo', Rhymix\Framework\Router::getURL($args, 0));

        $args = array('module' => 'document', 'act' => 'procDocumentVoteUp');
        $this->assertEquals('document/procDocumentVoteUp', Rhymix\Framework\Router::getURL($args, 2));
        $this->assertEquals('index.php?module=document&act=procDocumentVoteUp', Rhymix\Framework\Router::getURL($args, 1));
        $this->assertEquals('index.php?module=document&act=procDocumentVoteUp', Rhymix\Framework\Router::getURL($args, 0));
	}

	public function testParseURL()
	{
        $args = array('mid' => 'board', 'act' => 'dispBoardContent', 'document_srl' => '123');
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'board/123', 2)->args);
        $this->assertEquals('board/123', Rhymix\Framework\Router::parseURL('GET', 'board/123', 2)->url);
        $this->assertEquals('board', Rhymix\Framework\Router::parseURL('GET', 'board/123', 2)->mid);
        $this->assertEquals('dispBoardContent', Rhymix\Framework\Router::parseURL('GET', 'board/123', 2)->act);

        $args = array('mid' => 'board', 'document_srl' => '123');
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'board/123', 1)->args);
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'index.php?mid=board&document_srl=123', 0)->args);
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'board/123', 1)->args);
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'index.php?mid=board&document_srl=123', 0)->args);

        $args = array('mid' => 'board', 'act' => 'dispBoardModifyComment', 'comment_srl' => '456', 'extra_param' => 'foo bar');
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'board/comment/456/edit?extra_param=foo+bar', 2)->args);
        $this->assertEquals('board', Rhymix\Framework\Router::parseURL('GET', 'board/comment/456/edit?extra_param=foo+bar', 2)->mid);
        $this->assertEquals('dispBoardModifyComment', Rhymix\Framework\Router::parseURL('GET', 'board/comment/456/edit?extra_param=foo+bar', 2)->act);
        $this->assertEquals('', Rhymix\Framework\Router::parseURL('GET', 'board/comment/456/edit?extra_param=foo+bar', 2)->document_srl ?? '');
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'index.php?mid=board&act=dispBoardModifyComment&comment_srl=456&extra_param=foo+bar', 1)->args);
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'index.php?mid=board&act=dispBoardModifyComment&comment_srl=456&extra_param=foo+bar', 0)->args);

        $args = array('mid' => 'board', 'act' => 'dispMemberInfo');
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'board/member_info', 2)->args);
        $this->assertEquals('board', Rhymix\Framework\Router::parseURL('GET', 'board/member_info', 2)->mid);

        $args = array('mid' => 'board', 'act' => 'dispMemberLoginForm');
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('GET', 'board/login', 2)->args);
        $this->assertEquals('member', Rhymix\Framework\Router::parseURL('GET', 'board/login', 2)->module);

        $args = array('mid' => 'board', 'act' => 'procMemberLogin');
        $this->assertEquals($args, Rhymix\Framework\Router::parseURL('POST', 'board/login', 2)->args);
        $this->assertEquals('member', Rhymix\Framework\Router::parseURL('POST', 'board/login', 2)->module);

	}
}
