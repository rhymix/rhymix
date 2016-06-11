<?php

use Rhymix\Framework\Helpers\ConfigHelper;

class ConfigHelperTest extends \Codeception\TestCase\Test
{
	public function testConsolidate()
	{
		$member_config = getModel('module')->getModuleConfig('member');
		$consolidated = ConfigHelper::consolidate(array(
			'dbtype' => array('common:db.type', 'member:nosuchconfig'),
			'member' => array('common:no.such.config', 'member:enable_join', 'tobool'),
			'nosuch' => array('common:no.such.config', 'member:no.such.config.either', 'intval'),
			'single' => 'member:identifier',
		));
		
		$this->assertEquals(config('db.type'), $consolidated['dbtype']);
		$this->assertEquals(tobool($member_config->enable_join), $consolidated['member']);
		$this->assertEquals(0, $consolidated['nosuch']);
		$this->assertEquals($member_config->identifier, $consolidated['single']);
	}
}
