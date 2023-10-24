<?php

class DBTableParserTest extends \Codeception\Test\Unit
{
	public function testLoadXML()
	{
		$table = Rhymix\Framework\Parsers\DBTableParser::loadXML(\RX_BASEDIR . 'tests/_data/dbtable/example.xml');
		$this->assertTrue($table instanceof Rhymix\Framework\Parsers\DBTable\Table);
		$this->assertEquals('example', $table->name);
		$this->assertTrue($table->columns['example_srl'] instanceof Rhymix\Framework\Parsers\DBTable\Column);
		$this->assertEquals('bigint', $table->columns['example_srl']->type);
		$this->assertEquals('bignumber', $table->columns['example_srl']->xetype);
		$this->assertNull($table->columns['example_srl']->size);
		$this->assertTrue($table->columns['example_srl']->not_null);
		$this->assertTrue($table->columns['example_srl']->is_primary_key);
		$this->assertEquals('bigint', $table->columns['module_srl']->type);
		$this->assertEquals('number', $table->columns['module_srl']->xetype);
		$this->assertTrue($table->columns['module_srl']->is_indexed);
		$this->assertTrue($table->columns['list_order']->is_unique);
		$this->assertFalse($table->columns['geometry']->is_unique);
		$this->assertEquals('date', $table->columns['custom_date']->type);
		$this->assertEquals('none', $table->columns['custom_date']->xetype);
		$this->assertEquals('char', $table->columns['regdate']->type);
		$this->assertEquals('date', $table->columns['regdate']->xetype);

		$this->assertEquals(9, count($table->indexes));
		$this->assertEquals(['module_srl' => 0, 'document_srl' => 0], $table->indexes['idx_module_document_srl']->columns);
		$this->assertEquals(['status' => 6], $table->indexes['idx_status']->columns);
		$this->assertEquals('UNIQUE', $table->indexes['unique_dates']->type);
		$this->assertEquals(['custom_date' => 0, 'regdate' => 0], $table->indexes['unique_dates']->columns);
		$this->assertEquals('UNIQUE', $table->indexes['unique_list_order']->type);
		$this->assertEquals('SPATIAL', $table->indexes['spatial_geometry']->type);
		$this->assertEquals('FULLTEXT', $table->indexes['fulltext_description']->type);
		$this->assertEquals('WITH PARSER ngram', $table->indexes['fulltext_description']->options);

		$this->assertEquals(2, count($table->constraints));
		$this->assertEquals('FOREIGN KEY', $table->constraints[0]->type);
		$this->assertEquals('module_srl', $table->constraints[0]->column);
		$this->assertEquals('module.module_srl', $table->constraints[0]->references);
		$this->assertEquals('CASCADE', $table->constraints[0]->on_delete);
		$this->assertEquals('RESTRICT', $table->constraints[0]->on_update);
		$this->assertEquals('CHECK', $table->constraints[1]->type);
		$this->assertEquals('list_order < 0', $table->constraints[1]->condition);
	}

	public function testGetCreateQuery()
	{
		$table = Rhymix\Framework\Parsers\DBTableParser::loadXML(\RX_BASEDIR . 'tests/_data/dbtable/example.xml');
		$sql = $table->getCreateQuery('rx_');
		$this->assertStringContainsString('CREATE TABLE `rx_example` (', $sql);
		$this->assertStringContainsString('`comment_srl` BIGINT NOT NULL,', $sql);
		$this->assertStringContainsString('`status` VARCHAR(20) DEFAULT \'PUBLIC\',', $sql);
		$this->assertStringContainsString('PRIMARY KEY (`example_srl`),', $sql);
		$this->assertStringContainsString('INDEX `idx_document_srl` (`document_srl`),', $sql);
		$this->assertStringContainsString('INDEX `idx_module_document_srl` (`module_srl`, `document_srl`),', $sql);
		$this->assertStringContainsString('INDEX `idx_status` (`status`(6)),', $sql);
		$this->assertStringContainsString('UNIQUE INDEX `unique_dates` (`custom_date`, `regdate`),', $sql);
		$this->assertStringContainsString('UNIQUE INDEX `unique_list_order` (`list_order`),', $sql);
		$this->assertStringContainsString('SPATIAL INDEX `spatial_geometry` (`geometry`),', $sql);
		$this->assertStringContainsString('FULLTEXT INDEX `fulltext_description` (`description`) WITH PARSER ngram,', $sql);
		$this->assertStringContainsString('FOREIGN KEY (`module_srl`) REFERENCES `rx_module` (`module_srl`) ON DELETE CASCADE ON UPDATE RESTRICT', $sql);
		$this->assertStringContainsString('CHECK (list_order < 0)', $sql);
		$this->assertStringContainsString('CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $sql);
		$this->assertStringContainsString('ENGINE = InnoDB', $sql);
	}
}
