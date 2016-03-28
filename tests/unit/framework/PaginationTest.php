<?php

class PaginationTest extends \Codeception\TestCase\Test
{
	public function testCountPages()
	{
		$this->assertEquals(1, Rhymix\Framework\Pagination::countPages(0, 20));
		$this->assertEquals(1, Rhymix\Framework\Pagination::countPages(10, 20));
		$this->assertEquals(1, Rhymix\Framework\Pagination::countPages(20, 20));
		$this->assertEquals(2, Rhymix\Framework\Pagination::countPages(21, 20));
	}
	
	public function testCreateLinks()
	{
		$links = Rhymix\Framework\Pagination::createLinks('index.php?page=', 27, 3);
		$this->assertContains('<div class="pagination">', $links);
		$this->assertContains('<a href="index.php?page=3">', $links);
		$this->assertContains('<span class="page_number">1</span>', $links);
		$this->assertContains('<span class="page_number">10</span>', $links);
		
		$links = Rhymix\Framework\Pagination::createLinks('/foo/bar/page/', 27, 13);
		$this->assertContains('<div class="pagination">', $links);
		$this->assertContains('<a href="/foo/bar/page/13">', $links);
		$this->assertContains('<span class="page_number">11</span>', $links);
		$this->assertContains('<span class="page_number">20</span>', $links);
		
		$links = Rhymix\Framework\Pagination::createLinks('/rhymix?page=$PAGE&foo=bar', 27, 25);
		$this->assertContains('<div class="pagination">', $links);
		$this->assertContains('<a href="/rhymix?page=27&amp;foo=bar">', $links);
		$this->assertContains('<span class="page_number">21</span>', $links);
		$this->assertContains('<span class="page_number">27</span>', $links);
		
		$links = Rhymix\Framework\Pagination::createLinks('p', 27, 3, 10, Rhymix\Framework\Pagination::COUNT_STYLE_CONTINUOUS);
		$this->assertContains('<div class="pagination">', $links);
		$this->assertContains('<span class="page_number">1</span>', $links);
		$this->assertContains('<span class="page_number">10</span>', $links);
		
		$links = Rhymix\Framework\Pagination::createLinks('p', 27, 13, 10, Rhymix\Framework\Pagination::COUNT_STYLE_CONTINUOUS);
		$this->assertContains('<div class="pagination">', $links);
		$this->assertContains('<span class="page_number">9</span>', $links);
		$this->assertContains('<span class="page_number">18</span>', $links);
		
		$links = Rhymix\Framework\Pagination::createLinks('p', 27, 25, 10, Rhymix\Framework\Pagination::COUNT_STYLE_CONTINUOUS);
		$this->assertContains('<div class="pagination">', $links);
		$this->assertContains('<span class="page_number">18</span>', $links);
		$this->assertContains('<span class="page_number">27</span>', $links);
	}
}
