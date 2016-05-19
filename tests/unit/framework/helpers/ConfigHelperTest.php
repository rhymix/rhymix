<?php

use Rhymix\Framework\Helpers\ConfigHelper;

class ConfigHelperTest extends \Codeception\TestCase\Test
{
	public function testConsolidate()
	{
		$member_config = getModel('module')->getModuleConfig('member');
		$consolidated = ConfigHelper::consolidate(array(
			'dbtype' => array('db.type', 'member:nosuchconfig'),
			'member' => array('no.such.config', 'member:enable_join'),
			'nosuch' => array('no.such.config', 'member:no.such.config.either'),
			'single' => 'member:identifier',
		));
		
		$this->assertEquals(config('db.type'), $consolidated['dbtype']);
		$this->assertEquals($member_config->enable_join, $consolidated['member']);
		$this->assertNull($consolidated['nosuch']);
		$this->assertEquals($member_config->identifier, $consolidated['single']);
	}
}
