<?php

class URLTest extends \Codeception\Test\Unit
{
	private $baseurl;
	private $relurl;

	public function _before()
	{
		$this->baseurl = 'https://www.rhymix.org/' . basename(dirname(dirname(dirname(__DIR__)))) . '/';
		$this->relurl = basename(dirname(dirname(dirname(__DIR__))));
	}

	public function testGetCurrentURL()
	{
		$old_request_uri = $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = '/' . $this->relurl . '/index.php?foo=bar&xe=sucks';

		// Getting the current URL
		$this->assertEquals($this->baseurl . 'index.php?foo=bar&xe=sucks', Rhymix\Framework\URL::getCurrentURL());

		// Adding items to the query string
		$this->assertEquals($this->baseurl . 'index.php?foo=bar&xe=sucks&var=1&arr%5B0%5D=2&arr%5B1%5D=3', Rhymix\Framework\URL::getCurrentURL(array('var' => '1', 'arr' => array(2, 3))));

		// Removing item from the query string
		$this->assertEquals($this->baseurl . 'index.php?xe=sucks', Rhymix\Framework\URL::getCurrentURL(array('foo' => null)));

		// Removing all items from the query string
		$this->assertEquals($this->baseurl . 'index.php', Rhymix\Framework\URL::getCurrentURL(array('foo' => null, 'xe' => null)));

		// Adding and removing parameters at the same time
		$this->assertEquals($this->baseurl . 'index.php?xe=sucks&l=ko', Rhymix\Framework\URL::getCurrentURL(array('l' => 'ko', 'foo' => null)));

		// Removing invalid characters in the current URL
		$_SERVER['REQUEST_URI'] = '/' . $this->relurl . '/?foo="bar"';
		$this->assertEquals($this->baseurl . '?foo=bar', Rhymix\Framework\URL::getCurrentURL());
		$_SERVER['REQUEST_URI'] = '/' . $this->relurl . '/?foo=<bar&baz=rhymix>';
		$this->assertEquals($this->baseurl . '?foo=bar&baz=rhymix', Rhymix\Framework\URL::getCurrentURL());
		$this->assertEquals($this->baseurl . '?baz=rhymix&l=ko', Rhymix\Framework\URL::getCurrentURL(array('l' => 'ko', 'foo' => null)));

		$_SERVER['REQUEST_URI'] = $old_request_uri;
	}

	public function testGetCurrentDomain()
	{
		$original_host = $_SERVER['HTTP_HOST'];

		$_SERVER['HTTP_HOST'] = 'rhymix.org';
		$this->assertEquals('rhymix.org', Rhymix\Framework\URL::getCurrentDomain());
		$this->assertEquals('rhymix.org', Rhymix\Framework\URL::getCurrentDomain(true));

		$_SERVER['HTTP_HOST'] = 'xn--oi2b48fl0g0pf.RHYMIX.ORG:443';
		$this->assertEquals('라이믹스.rhymix.org', Rhymix\Framework\URL::getCurrentDomain());
		$this->assertEquals('라이믹스.rhymix.org:443', Rhymix\Framework\URL::getCurrentDomain(true));

		$_SERVER['HTTP_HOST'] = 'rhymix.org:8080';
		$this->assertEquals('rhymix.org', Rhymix\Framework\URL::getCurrentDomain());
		$this->assertEquals('rhymix.org:8080', Rhymix\Framework\URL::getCurrentDomain(true));

		$_SERVER['HTTP_HOST'] = $original_host;
	}

	public function testGetCurrentDomainURL()
	{
		$this->assertEquals('https://www.rhymix.org/', Rhymix\Framework\URL::getCurrentDomainURL());
		$this->assertEquals('https://www.rhymix.org/', Rhymix\Framework\URL::getCurrentDomainURL('/'));
		$this->assertEquals('https://www.rhymix.org/foo/bar', Rhymix\Framework\URL::getCurrentDomainURL('/foo/bar'));
		$this->assertEquals($this->baseurl . 'index.php?foo=bar', Rhymix\Framework\URL::getCurrentDomainURL($this->relurl . '/index.php?foo=bar'));
	}

	public function testGetCanonicalURL()
	{
        $tests = array(
        	'foo/bar' => $this->baseurl . 'foo/bar',
        	'./foo/bar' => $this->baseurl . 'foo/bar',
        	'/foo/bar' => $this->baseurl . 'foo/bar',
        	'//www.example.com/foo' => 'https://www.example.com/foo',
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
		// Conversion to absolute
		$this->assertEquals($this->baseurl . 'index.php?foo=bar', $url = Rhymix\Framework\URL::modifyURL('./index.php?foo=bar'));

		// Adding items to the query string
		$this->assertEquals($this->baseurl . 'index.php?foo=bar&var=1&arr%5B0%5D=2&arr%5B1%5D=3', Rhymix\Framework\URL::modifyURL($url, array('var' => '1', 'arr' => array(2, 3))));

		// Removing item from the query string
		$this->assertEquals($this->baseurl . 'index.php', Rhymix\Framework\URL::modifyURL($url, array('foo' => null)));

		// Adding and removing parameters at the same time
		$this->assertEquals($this->baseurl . 'index.php?l=ko', Rhymix\Framework\URL::modifyURL($url, array('l' => 'ko', 'foo' => null)));
	}

	public function testIsInternalURL()
	{
		// This function is checked in Security::checkCSRF()
	}

	public function testURLFromServerPath()
	{
		$this->assertEquals($this->baseurl . '', Rhymix\Framework\URL::fromServerPath(\RX_BASEDIR));
		$this->assertEquals($this->baseurl . 'index.php', Rhymix\Framework\URL::fromServerPath(\RX_BASEDIR . 'index.php'));
		$this->assertEquals($this->baseurl . 'foo/bar', Rhymix\Framework\URL::fromServerPath(\RX_BASEDIR . '/foo/bar'));
		$this->assertEquals(false, Rhymix\Framework\URL::fromServerPath(dirname(dirname(\RX_BASEDIR))));
		$this->assertEquals(false, Rhymix\Framework\URL::fromServerPath('C:/Windows'));
	}

	public function testURLToServerPath()
	{
		$this->assertEquals(\RX_BASEDIR . 'index.php', Rhymix\Framework\URL::toServerPath($this->baseurl . 'index.php'));
		$this->assertEquals(\RX_BASEDIR . 'foo/bar', Rhymix\Framework\URL::toServerPath($this->baseurl . 'foo/bar?arg=baz'));
		$this->assertEquals(\RX_BASEDIR . 'foo/bar', Rhymix\Framework\URL::toServerPath('./foo/bar'));
		$this->assertEquals(\RX_BASEDIR . 'foo/bar', Rhymix\Framework\URL::toServerPath('foo/bar/../bar'));
		$this->assertEquals(false, Rhymix\Framework\URL::toServerPath('http://other.domain.com/'));
		$this->assertEquals(false, Rhymix\Framework\URL::toServerPath('//other.domain.com/'));
	}

	public function testEncodeIdna()
	{
		$this->assertEquals('xn--9i1bl3b186bf9e.xn--3e0b707e', Rhymix\Framework\URL::encodeIdna('퓨니코드.한국'));
		$this->assertEquals('http://xn--9i1bl3b186bf9e.xn--3e0b707e', Rhymix\Framework\URL::encodeIdna('http://퓨니코드.한국'));
		$this->assertEquals('//xn--9i1bl3b186bf9e.xn--3e0b707e#한글부분', Rhymix\Framework\URL::encodeIdna('//퓨니코드.한국#한글부분'));
		$this->assertEquals('https://xn--9i1bl3b186bf9e.xn--3e0b707e/hello/world/라이믹스.php?i=4', Rhymix\Framework\URL::encodeIdna('https://퓨니코드.한국/hello/world/라이믹스.php?i=4'));
	}

	public function testDecodeIdna()
	{
		$this->assertEquals('퓨니코드.한국', Rhymix\Framework\URL::decodeIdna('xn--9i1bl3b186bf9e.xn--3e0b707e'));
		$this->assertEquals('http://퓨니코드.한국', Rhymix\Framework\URL::decodeIdna('http://xn--9i1bl3b186bf9e.xn--3e0b707e'));
		$this->assertEquals('//퓨니코드.한국#한글부분', Rhymix\Framework\URL::decodeIdna('//xn--9i1bl3b186bf9e.xn--3e0b707e#한글부분'));
		$this->assertEquals('https://퓨니코드.한국/hello/world/라이믹스.php?i=4', Rhymix\Framework\URL::decodeIdna('https://xn--9i1bl3b186bf9e.xn--3e0b707e/hello/world/라이믹스.php?i=4'));
	}
}
