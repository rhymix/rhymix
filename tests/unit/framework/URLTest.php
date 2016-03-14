<?php

class URLTest extends \Codeception\TestCase\Test
{
	public function testGetCurrentURL()
	{
		$protocol = \RX_SSL ? 'https://' : 'http://';
		$_SERVER['HTTP_HOST'] = 'www.rhymix.org';
		$_SERVER['REQUEST_URI'] = '/index.php?foo=bar&xe=sucks';
		$full_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		
		// Getting the current URL
		$this->assertEquals($full_url, Rhymix\Framework\URL::getCurrentURL());
		
		// Adding items to the query string
		$this->assertEquals($full_url . '&var=1&arr%5B0%5D=2&arr%5B1%5D=3', Rhymix\Framework\URL::getCurrentURL(array('var' => '1', 'arr' => array(2, 3))));
		
		// Removing item from the query string
		$this->assertEquals($protocol . $_SERVER['HTTP_HOST'] . '/index.php?xe=sucks', Rhymix\Framework\URL::getCurrentURL(array('foo' => null)));
		
		// Removing all items from the query string
		$this->assertEquals($protocol . $_SERVER['HTTP_HOST'] . '/index.php', Rhymix\Framework\URL::getCurrentURL(array('foo' => null, 'xe' => null)));
		
		// Adding and removing parameters at the same time
		$this->assertEquals($protocol . $_SERVER['HTTP_HOST'] . '/index.php?xe=sucks&l=ko', Rhymix\Framework\URL::getCurrentURL(array('l' => 'ko', 'foo' => null)));
	}
	
	public function testGetCanonicalURL()
	{
		$protocol = \RX_SSL ? 'https://' : 'http://';
		$_SERVER['HTTP_HOST'] = 'www.rhymix.org';
		
        $tests = array(
        	'foo/bar' => $protocol . $_SERVER['HTTP_HOST'] . \RX_BASEURL . 'foo/bar',
        	'./foo/bar' => $protocol . $_SERVER['HTTP_HOST'] . \RX_BASEURL . 'foo/bar',
        	'/foo/bar' => $protocol . $_SERVER['HTTP_HOST'] . \RX_BASEURL . 'foo/bar',
        	'//www.example.com/foo' => $protocol . 'www.example.com/foo',
        	'http://xn--cg4bkiv2oina.com/' => 'http://삼성전자.com/',
		);
		
		foreach ($tests as $from => $to)
		{
			$this->assertEquals($to, Rhymix\Framework\URL::getCanonicalURL($from));
		}
	}
	
	public function testGetDomainFromURL()
	{
        $tests = array(
        	'https://www.rhymix.org/foo/bar' => 'www.rhymix.org',
        	'https://www.rhymix.org:8080/foo/bar' => 'www.rhymix.org',
        	'http://xn--cg4bkiv2oina.com/' => '삼성전자.com',
		);
		
		foreach ($tests as $from => $to)
		{
			$this->assertEquals($to, Rhymix\Framework\URL::getDomainFromURL($from));
		}
	}
	
	public function testModifyURL()
	{
		$protocol = \RX_SSL ? 'https://' : 'http://';
		$_SERVER['HTTP_HOST'] = 'www.rhymix.org';
		$url = $protocol . $_SERVER['HTTP_HOST'] . \RX_BASEURL . 'index.php?foo=bar';
		
		// Conversion to absolute
		$this->assertEquals($url, Rhymix\Framework\URL::modifyURL('./index.php?foo=bar'));
		
		// Adding items to the query string
		$this->assertEquals($url . '&var=1&arr%5B0%5D=2&arr%5B1%5D=3', Rhymix\Framework\URL::modifyURL($url, array('var' => '1', 'arr' => array(2, 3))));
		
		// Removing item from the query string
		$this->assertEquals($protocol . $_SERVER['HTTP_HOST'] . \RX_BASEURL . 'index.php', Rhymix\Framework\URL::modifyURL($url, array('foo' => null)));
		
		// Adding and removing parameters at the same time
		$this->assertEquals($protocol . $_SERVER['HTTP_HOST'] . \RX_BASEURL . 'index.php?l=ko', Rhymix\Framework\URL::modifyURL($url, array('l' => 'ko', 'foo' => null)));
	}
	
	public function testIsInternalURL()
	{
		// This function is checked in Security::checkCSRF()
	}
	
	public function testEncodeIdna()
	{
		$this->assertEquals('xn--9i1bl3b186bf9e.xn--3e0b707e', Rhymix\Framework\URL::encodeIdna('퓨니코드.한국'));
	}
	
	public function testDecodeIdna()
	{
		$this->assertEquals('퓨니코드.한국', Rhymix\Framework\URL::decodeIdna('xn--9i1bl3b186bf9e.xn--3e0b707e'));
	}
}
