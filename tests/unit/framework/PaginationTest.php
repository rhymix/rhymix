<?php

class PaginationTest extends \Codeception\Test\Unit
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
		$this->assertStringContainsString('<div class="pagination">', $links);
		$this->assertStringContainsString('<a href="index.php?page=3">', $links);
		$this->assertStringContainsString('<span class="page_number">1</span>', $links);
		$this->assertStringContainsString('<span class="page_number">10</span>', $links);

		$links = Rhymix\Framework\Pagination::createLinks('/foo/bar/page/', 27, 13);
		$this->assertStringContainsString('<div class="pagination">', $links);
		$this->assertStringContainsString('<a href="/foo/bar/page/13">', $links);
		$this->assertStringContainsString('<span class="page_number">11</span>', $links);
		$this->assertStringContainsString('<span class="page_number">20</span>', $links);

		$links = Rhymix\Framework\Pagination::createLinks('/rhymix?page=$PAGE&foo=bar', 27, 25);
		$this->assertStringContainsString('<div class="pagination">', $links);
		$this->assertStringContainsString('<a href="/rhymix?page=27&amp;foo=bar">', $links);
		$this->assertStringContainsString('<span class="page_number">21</span>', $links);
		$this->assertStringContainsString('<span class="page_number">27</span>', $links);

		$links = Rhymix\Framework\Pagination::createLinks('p', 27, 3, 10, Rhymix\Framework\Pagination::COUNT_STYLE_CONTINUOUS);
		$this->assertStringContainsString('<div class="pagination">', $links);
		$this->assertStringContainsString('<span class="page_number">1</span>', $links);
		$this->assertStringContainsString('<span class="page_number">10</span>', $links);

		$links = Rhymix\Framework\Pagination::createLinks('p', 27, 13, 10, Rhymix\Framework\Pagination::COUNT_STYLE_CONTINUOUS);
		$this->assertStringContainsString('<div class="pagination">', $links);
		$this->assertStringContainsString('<span class="page_number">9</span>', $links);
		$this->assertStringContainsString('<span class="page_number">18</span>', $links);

		$links = Rhymix\Framework\Pagination::createLinks('p', 27, 25, 10, Rhymix\Framework\Pagination::COUNT_STYLE_CONTINUOUS);
		$this->assertStringContainsString('<div class="pagination">', $links);
		$this->assertStringContainsString('<span class="page_number">18</span>', $links);
		$this->assertStringContainsString('<span class="page_number">27</span>', $links);
	}
}
