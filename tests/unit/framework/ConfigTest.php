<?php

class ConfigTest extends \Codeception\TestCase\Test
{
	public function testConfig()
	{
		if (!file_exists(RX_BASEDIR . 'files/config/config.php'))
		{
			mkdir(RX_BASEDIR . 'files/config', 0755, true);
			copy(RX_BASEDIR . 'common/defaults/config.php', RX_BASEDIR . 'files/config/config.php');
		}
		
		Rhymix\Framework\Config::init();
		$this->assertTrue(version_compare(Rhymix\Framework\Config::get('config_version'), '2.0', '>='));
		$this->assertTrue(is_array(Rhymix\Framework\Config::get('db.master')));
		$this->assertNotEmpty(Rhymix\Framework\Config::get('db.master.host'));
		
		Rhymix\Framework\Config::set('foo.bar', $rand = mt_rand());
		$this->assertEquals(array('bar' => $rand), Rhymix\Framework\Config::get('foo'));
		$this->assertEquals($rand, Rhymix\Framework\Config::get('foo.bar'));
	}
}
