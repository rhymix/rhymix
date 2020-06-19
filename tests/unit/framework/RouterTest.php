<?php

class RouterTest extends \Codeception\TestCase\Test
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
        
	}
    
	public function testParseURL()
	{
        
	}
}
