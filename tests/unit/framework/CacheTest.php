<?php

class CacheTest extends \Codeception\TestCase\Test
{
	public function _before()
	{
		if (!Rhymix\Framework\Config::get('crypto.authentication_key'))
		{
			Rhymix\Framework\Config::set('crypto.authentication_key', Rhymix\Framework\Security::getRandom(64, 'alnum'));
		}
		
		$driver = Rhymix\Framework\Cache::init(array('file'));
	}
	
	public function _after()
	{
		$driver = Rhymix\Framework\Cache::clearAll();
	}
	
	public function testInit()
	{
		$driver = Rhymix\Framework\Cache::init(array('type' => 'file'));
		$this->assertTrue($driver instanceof Rhymix\Framework\Drivers\Cache\File);
		
		$driver = Rhymix\Framework\Cache::init(array('type' => 'sqlite'));
		$this->assertTrue($driver instanceof Rhymix\Framework\Drivers\Cache\SQLite);
		
		$driver = Rhymix\Framework\Cache::init(array());
		$this->assertTrue($driver instanceof Rhymix\Framework\Drivers\Cache\Dummy);
	}
	
	public function testGetSupportedDrivers()
	{
		$drivers = Rhymix\Framework\Cache::getSupportedDrivers();
		$this->assertTrue(is_array($drivers));
		$this->assertContains('dummy', $drivers);
		$this->assertContains('file', $drivers);
		$this->assertContains('sqlite', $drivers);
	}
	
	public function testGetDriverName()
	{
		$driver = Rhymix\Framework\Cache::init(array('type' => 'dummy'));
		$this->assertEquals('dummy', Rhymix\Framework\Cache::getDriverName());
		
		$driver = Rhymix\Framework\Cache::init(array('type' => 'sqlite'));
		$this->assertEquals('sqlite', Rhymix\Framework\Cache::getDriverName());
	}
	
	public function testGetDriverInstance()
	{
		$driver = Rhymix\Framework\Cache::getDriverInstance('dummy');
		$this->assertTrue($driver instanceof Rhymix\Framework\Drivers\Cache\Dummy);
		
		$driver = Rhymix\Framework\Cache::getDriverInstance();
		$this->assertTrue($driver instanceof Rhymix\Framework\Drivers\Cache\File);
	}
	
	public function testGetPrefix()
	{
		$prefix = Rhymix\Framework\Cache::getPrefix();
		$this->assertEquals(\RX_VERSION . ':', $prefix);
	}
	
	public function testGetSet()
	{
		$value = true;
		$this->assertTrue(Rhymix\Framework\Cache::set('foobar1', $value));
		$this->assertTrue(Rhymix\Framework\Cache::get('foobar1'));
		
		$value = false;
		$this->assertTrue(Rhymix\Framework\Cache::set('foobar2', $value));
		$this->assertFalse(Rhymix\Framework\Cache::get('foobar2'));
		
		$value = 1756234;
		$this->assertTrue(Rhymix\Framework\Cache::set('foobar3', $value));
		$this->assertEquals($value, Rhymix\Framework\Cache::get('foobar3'));
		
		$value = 'Rhymix is a PHP CMS.';
		$this->assertTrue(Rhymix\Framework\Cache::set('foobar4', $value));
		$this->assertEquals($value, Rhymix\Framework\Cache::get('foobar4'));
		
		$value = array('foo' => 'bar', 'rhy' => 'mix');
		$this->assertTrue(Rhymix\Framework\Cache::set('foobar:subkey:5', $value));
		$this->assertEquals($value, Rhymix\Framework\Cache::get('foobar:subkey:5'));
		
		$value = (object)array('foo' => 'bar', 'rhy' => 'mix');
		$this->assertTrue(Rhymix\Framework\Cache::set('foobar:subkey:6', $value));
		$this->assertEquals($value, Rhymix\Framework\Cache::get('foobar:subkey:6'));
		
		$this->assertNull(Rhymix\Framework\Cache::get('foobar7'));
		$this->assertNull(Rhymix\Framework\Cache::get('foobar:subkey:8'));
	}
	
	public function testDeleteAndExists()
	{
		Rhymix\Framework\Cache::set('foo', 'FOO');
		Rhymix\Framework\Cache::set('bar', 'BAR');
		
		$this->assertTrue(Rhymix\Framework\Cache::delete('foo'));
		$this->assertFalse(Rhymix\Framework\Cache::delete('foo'));
		$this->assertFalse(Rhymix\Framework\Cache::exists('foo'));
		$this->assertTrue(Rhymix\Framework\Cache::exists('bar'));
	}
	
	public function testIncrDecr()
	{
		Rhymix\Framework\Cache::init(array('type' => 'sqlite'));
		Rhymix\Framework\Cache::set('foo', 'foo');
		Rhymix\Framework\Cache::set('bar', 42);
		$prefix = Rhymix\Framework\Cache::getPrefix();
		
		$this->assertEquals(1, Rhymix\Framework\Cache::getDriverInstance()->incr($prefix . 'foo', 1));
		$this->assertEquals(8, Rhymix\Framework\Cache::getDriverInstance()->incr($prefix . 'foo', 7));
		$this->assertEquals(-7, Rhymix\Framework\Cache::decr('foo', 15));
		$this->assertEquals(45, Rhymix\Framework\Cache::getDriverInstance()->incr($prefix . 'bar', 3));
		$this->assertEquals(60, Rhymix\Framework\Cache::incr('bar', 15));
		$this->assertEquals(20, Rhymix\Framework\Cache::getDriverInstance()->incr($prefix . 'bar', -40));
	}
	
	public function testClearAll()
	{
		$this->assertTrue(Rhymix\Framework\Cache::set('foo', 'foo'));
		$this->assertTrue(Rhymix\Framework\Cache::exists('foo'));
		$this->assertTrue(Rhymix\Framework\Cache::clearAll());
		$this->assertFalse(Rhymix\Framework\Cache::exists('foo'));
	}
	
	public function testCacheGroups()
	{
		Rhymix\Framework\Cache::init(array('type' => 'sqlite'));
		$prefix = Rhymix\Framework\Cache::getPrefix();
		
		$this->assertTrue(Rhymix\Framework\Cache::set('foobar:subkey:1234', 'rhymix'));
		$this->assertTrue(Rhymix\Framework\Cache::exists('foobar:subkey:1234'));
		$this->assertEquals('rhymix', Rhymix\Framework\Cache::get('foobar:subkey:1234'));
		$this->assertEquals('rhymix', Rhymix\Framework\Cache::getDriverInstance()->get($prefix . 'foobar#0:subkey:1234'));
		$this->assertEquals(0, Rhymix\Framework\Cache::getGroupVersion('foobar'));
		
		$this->assertTrue(Rhymix\Framework\Cache::clearGroup('foobar'));
		$this->assertFalse(Rhymix\Framework\Cache::exists('foobar:subkey:1234'));
		$this->assertTrue(Rhymix\Framework\Cache::set('foobar:subkey:1234', 'rhymix'));
		$this->assertEquals('rhymix', Rhymix\Framework\Cache::getDriverInstance()->get($prefix . 'foobar#1:subkey:1234'));
		$this->assertEquals(1, Rhymix\Framework\Cache::getGroupVersion('foobar'));
	}
	
	public function testGetRealKey()
	{
		Rhymix\Framework\Cache::init(array('type' => 'sqlite'));
		$prefix = Rhymix\Framework\Cache::getPrefix();
		
		$this->assertEquals($prefix . 'foo', Rhymix\Framework\Cache::getRealKey('foo'));
		$this->assertEquals($prefix . 'bar#0:2016', Rhymix\Framework\Cache::getRealKey('bar:2016'));
		Rhymix\Framework\Cache::clearGroup('bar');
		$this->assertEquals($prefix . 'bar#1:2016', Rhymix\Framework\Cache::getRealKey('bar:2016'));
		Rhymix\Framework\Cache::clearGroup('bar');
		$this->assertEquals($prefix . 'bar#2:2016', Rhymix\Framework\Cache::getRealKey('bar:2016'));
	}
	
	public function testCompatibility()
	{
		Rhymix\Framework\Cache::init(array('type' => 'sqlite'));
		$ch = \CacheHandler::getInstance();
		$this->assertTrue($ch instanceof \CacheHandler);
		$this->assertTrue($ch->isSupport());
		
		$this->assertEquals('rhymix', $ch->getCacheKey('rhymix'));
		$this->assertEquals('rhymix:123:456', $ch->getCacheKey('rhymix:123:456'));
		
		$this->assertTrue($ch->put('rhymix', 'foo bar buzz'));
		$this->assertEquals('foo bar buzz', $ch->get('rhymix'));
		$this->assertTrue($ch->isValid('rhymix'));
		$this->assertTrue($ch->delete('rhymix'));
		$this->assertFalse($ch->get('rhymix'));
		$this->assertFalse($ch->isValid('rhymix'));
		
		$this->assertEquals('rhymix:123:456', $ch->getGroupKey('rhymix', '123:456'));
		$this->assertTrue($ch->put('rhymix:123:456', 'rhymix rules!'));
		$this->assertEquals('rhymix rules!', $ch->get('rhymix:123:456'));
		$this->assertEquals(0, Rhymix\Framework\Cache::getGroupVersion('rhymix'));
		
		$this->assertTrue($ch->invalidateGroupKey('rhymix'));
		$this->assertTrue($ch->put('rhymix:123:456', 'rhymix rules!'));
		$this->assertEquals('rhymix rules!', $ch->get('rhymix:123:456'));
		$this->assertEquals(1, Rhymix\Framework\Cache::getGroupVersion('rhymix'));
		
		$this->assertTrue($ch->truncate());
		$this->assertFalse($ch->get('rhymix:123:456'));
	}
}
