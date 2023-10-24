<?php

class ModuleActionParserTest extends \Codeception\Test\Unit
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
		$this->assertEquals('GET|POST', $info->action->dispTestView->method);
		$this->assertEquals('false', $info->action->dispTestView->standalone);
		$this->assertEquals('true', $info->action->dispTestView->check_csrf);
		$this->assertEquals('false', $info->action->dispTestView->session);
		$this->assertEquals('false', $info->action->dispTestView->cache_control);
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
		$this->assertEquals('true', $info->action->dispTestWrite->session);
		$this->assertEquals('true', $info->action->dispTestWrite->cache_control);
		$this->assertEquals('controller', $info->action->procTestSubmitData->type);
		$this->assertEquals('submitData', $info->action->procTestSubmitData->ruleset);
		$this->assertEquals('POST', $info->action->procTestSubmitData->method);
		$this->assertEquals('true', $info->action->dispTestAdminIndex->standalone);
		$this->assertEquals('GET|POST', $info->action->procTestAdminSubmitData->method);
		$this->assertEquals([], $info->action->procTestAdminSubmitData->route);

		// Standalone attribute
		$this->assertEquals('auto', $info->action->dispTestStandalone1->standalone);
		$this->assertEquals('false', $info->action->dispTestStandalone2->standalone);
		$this->assertEquals('true', $info->action->dispTestStandalone3->standalone);

		// Routes
		$this->assertEquals(7, count($info->route->GET));
		$this->assertEquals('dispTestView', $info->route->GET['#^(?P<document_srl>[0-9]+)$#u']);
		$this->assertEquals('dispTestView', $info->route->GET['#^(?P<document_srl>[0-9]+)/comment/(?P<comment_srl>[0-9]+)$#u']);
		$this->assertEquals('dispTestView', $info->route->GET['#^(?P<document_srl>[0-9]+)/tag/(?P<tag>[a-zA-Z0-9_]+)$#u']);
		$this->assertEquals('dispTestWrite', $info->route->GET['#^write$#u']);
		$this->assertEquals(3, count($info->route->POST));

		// Grant
		$this->assertEquals(['view'], array_keys(get_object_vars($info->grant)));
		$this->assertContains($info->grant->view->title, ['View', '열람']);
		$this->assertEquals('guest', $info->grant->view->default);

		// Menu
		$this->assertEquals(['test'], array_keys(get_object_vars($info->menu)));
		$this->assertContains($info->menu->test->title, ['Test Menu', '테스트 메뉴']);
		$this->assertEquals('dispTestAdminIndex', $info->menu->test->index);
		$this->assertEquals(['dispTestAdminIndex'], $info->menu->test->acts);
		$this->assertEquals('all', $info->menu->test->type);

		// Error handlers
		$this->assertTrue(is_array($info->error_handlers));
		$this->assertEquals('dispTestErrorHandler', $info->error_handlers[404]);
		$this->assertEquals(['Controllers\Errors', 'dispErrorMethod'], $info->error_handlers[405]);

		// Event handlers
		$this->assertTrue(is_array($info->event_handlers));
		$this->assertTrue(is_object($info->event_handlers[0]));
		$this->assertEquals('document.insertDocument', $info->event_handlers[0]->event_name);
		$this->assertEquals('after', $info->event_handlers[0]->position);
		$this->assertEquals('\\VendorName\\Hello\\World\\Controllers\\Triggers', $info->event_handlers[0]->class_name);
		$this->assertEquals('triggerAfterInsertDocument', $info->event_handlers[0]->method);
		$this->assertTrue(is_object($info->event_handlers[1]));
		$this->assertEquals('act:document.procDocumentVoteUp', $info->event_handlers[1]->event_name);
		$this->assertEquals('before', $info->event_handlers[1]->position);
		$this->assertEquals('controller', $info->event_handlers[1]->class_name);
		$this->assertEquals('triggerBeforeDocumentVoteUp', $info->event_handlers[1]->method);

		// Custom classes
		$this->assertEquals('Custom\\DefaultClass', $info->classes['default']);
		$this->assertEquals('Custom\\InstallClass', $info->classes['install']);

		// Custom namespaces
		$this->assertTrue(is_array($info->namespaces));
		$this->assertTrue(in_array('VendorName\\Hello\\World', $info->namespaces));

		// Custom prefixes
		$this->assertTrue(is_array($info->prefixes));
		$this->assertTrue(in_array('foobar', $info->prefixes));
	}
}
