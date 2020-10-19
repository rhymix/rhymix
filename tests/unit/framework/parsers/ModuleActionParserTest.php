<?php

class ModuleActionParserTest extends \Codeception\TestCase\Test
{
	public function testLoadXML()
	{
		// Basic info
		$info = Rhymix\Framework\Parsers\ModuleActionParser::loadXML(\RX_BASEDIR . 'tests/_data/module/module.xml');
		$this->assertTrue(is_object($info));
		$this->assertEquals('dispTestView', $info->default_index_act);
		$this->assertEquals('dispTestAdminIndex', $info->admin_index_act);
		$this->assertEquals('', $info->setup_index_act);
		
		// Actions
		$this->assertEquals('view', $info->action->dispTestView->type);
		$this->assertEquals('guest', $info->action->dispTestView->grant);
		$this->assertEquals((object)['target' => 'view', 'check_var' => '', 'check_type' => ''], $info->action->dispTestView->permission);
		$this->assertEquals('GET', $info->action->dispTestView->method);
		$this->assertEquals('false', $info->action->dispTestView->standalone);
		$this->assertEquals('true', $info->action->dispTestView->check_csrf);
		$this->assertEquals(3, count($info->action->dispTestView->route));
		$this->assertEquals(100, $info->action->dispTestView->route['$document_srl']['priority']);
		$this->assertEquals(['document_srl' => 'int'], $info->action->dispTestView->route['$document_srl']['vars']);
		$this->assertEquals(70, $info->action->dispTestView->route['$document_srl/comment/$comment_srl']['priority']);
		$this->assertEquals(['document_srl' => 'int', 'comment_srl' => 'int'], $info->action->dispTestView->route['$document_srl/comment/$comment_srl']['vars']);
		$this->assertEquals(50, $info->action->dispTestView->route['$document_srl/tag/$tag']['priority']);
		$this->assertEquals(['document_srl' => 'int', 'tag' => 'word'], $info->action->dispTestView->route['$document_srl/tag/$tag']['vars']);
		$this->assertEquals(['write' => ['priority' => 0, 'vars' => []]], $info->action->dispTestWrite->route);
		$this->assertEquals('true', $info->action->dispTestWrite->meta_noindex);
		$this->assertEquals('true', $info->action->dispTestWrite->global_route);
		$this->assertEquals('controller', $info->action->procTestSubmitData->type);
		$this->assertEquals('submitData', $info->action->procTestSubmitData->ruleset);
		$this->assertEquals('POST', $info->action->procTestSubmitData->method);
		$this->assertEquals('true', $info->action->dispTestAdminIndex->standalone);
		$this->assertEquals('GET|POST', $info->action->procTestAdminSubmitData->method);
		$this->assertEquals([], $info->action->procTestAdminSubmitData->route);
		
		// Routes
		$this->assertEquals(4, count($info->route->GET));
		$this->assertEquals('dispTestView', $info->route->GET['#^(?P<document_srl>[0-9]+)$#u']);
		$this->assertEquals('dispTestView', $info->route->GET['#^(?P<document_srl>[0-9]+)/comment/(?P<comment_srl>[0-9]+)$#u']);
		$this->assertEquals('dispTestView', $info->route->GET['#^(?P<document_srl>[0-9]+)/tag/(?P<tag>[a-zA-Z0-9_]+)$#u']);
		$this->assertEquals('dispTestWrite', $info->route->GET['#^write$#u']);
		$this->assertEquals(0, count($info->route->POST));
		
		// Grant
		$this->assertEquals(['view'], array_keys(get_object_vars($info->grant)));
		$this->assertEquals('View', $info->grant->view->title);
		$this->assertEquals('guest', $info->grant->view->default);
		
		// Menu
		$this->assertEquals(['test'], array_keys(get_object_vars($info->menu)));
		$this->assertEquals('Test Menu', $info->menu->test->title);
		$this->assertEquals('dispTestAdminIndex', $info->menu->test->index);
		$this->assertEquals(['dispTestAdminIndex'], $info->menu->test->acts);
		$this->assertEquals('all', $info->menu->test->type);
		
		// Error handlers
		$this->assertTrue(is_array($info->error_handlers));
		$this->assertEquals('dispTestErrorHandler', $info->error_handlers[404]);
	}
}
