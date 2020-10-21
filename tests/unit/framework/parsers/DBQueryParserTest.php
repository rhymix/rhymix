<?php

class DBQueryParserTest extends \Codeception\TestCase\Test
{
	public function testLoadXML()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectTest1.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals('selectTest1', $query->name);
		$this->assertEquals('SELECT', $query->type);
		$this->assertTrue($query->select_distinct);
		
		$this->assertTrue($query->tables['documents'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertEquals('documents', $query->tables['documents']->alias);
		
		$this->assertEquals(1, count($query->columns));
		$this->assertEquals('*', $query->columns[0]->name);
		$this->assertTrue($query->columns[0]->is_expression);
		$this->assertTrue($query->columns[0]->is_wildcard);
		
		$this->assertEquals(2, count($query->conditions));
		$this->assertTrue($query->conditions[0] instanceof Rhymix\Framework\Parsers\DBQuery\Condition);
		$this->assertTrue($query->conditions[1] instanceof Rhymix\Framework\Parsers\DBQuery\ConditionGroup);
		$this->assertTrue($query->conditions[1]->conditions[0] instanceof Rhymix\Framework\Parsers\DBQuery\Condition);
		$this->assertTrue($query->conditions[1]->conditions[1] instanceof Rhymix\Framework\Parsers\DBQuery\Condition);
		$this->assertEquals('regdate', $query->conditions[1]->conditions[0]->column);
		$this->assertEquals('gte', $query->conditions[1]->conditions[0]->operation);
		$this->assertTrue($query->conditions[1]->conditions[0]->not_null);
		$this->assertEquals('status_list', $query->conditions[1]->conditions[1]->var);
		$this->assertEquals('PUBLIC', $query->conditions[1]->conditions[1]->default);
		$this->assertFalse($query->conditions[1]->conditions[1]->not_null);
		$this->assertEquals('OR', $query->conditions[1]->conditions[1]->pipe);
		
		$this->assertTrue($query->navigation instanceof Rhymix\Framework\Parsers\DBQuery\Navigation);
		$this->assertTrue($query->navigation->orderby[0] instanceof Rhymix\Framework\Parsers\DBQuery\OrderBy);
		$this->assertEquals('sort_index', $query->navigation->orderby[0]->var);
		$this->assertEquals('list_order', $query->navigation->orderby[0]->default);
		$this->assertEquals('order_type', $query->navigation->orderby[0]->order_var);
		$this->assertEquals('ASC', $query->navigation->orderby[0]->order_default);
		$this->assertTrue($query->navigation->list_count instanceof Rhymix\Framework\Parsers\DBQuery\VariableBase);
		$this->assertEquals('list_count', $query->navigation->list_count->var);
		$this->assertEquals('20', $query->navigation->list_count->default);
		$this->assertTrue($query->navigation->page instanceof Rhymix\Framework\Parsers\DBQuery\VariableBase);
		$this->assertEquals('page', $query->navigation->page->var);
		$this->assertEquals('1', $query->navigation->page->default);
	}
	
	public function testSimpleSelect()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectTest1.xml');
		$args = array('member_srl' => 1234, 'regdate_more' => '20200707120000', 'page' => 3);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		
		$this->assertEquals('SELECT DISTINCT * FROM `rx_documents` AS `documents` ' .
			'WHERE `member_srl` IN (?) AND (`regdate` >= ? OR `status` = ?) ' .
			'ORDER BY `list_order` ASC LIMIT 40, 20', $sql);
		$this->assertEquals(['1234', '20200707120000', 'PUBLIC'], $params);
	}
	
	public function testSelectWithExpressions()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectTest2.xml');
		$args = array('voted_count' => 20, 'date' => '20201021');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		
		$this->assertEquals('SELECT readed_count + trackback_count AS `count` ' .
			'FROM `rx_documents` AS `documents` WHERE `voted_count` + `blamed_count` >= ? AND LEFT(`regdate`, 8) = ?', $sql);
		$this->assertEquals([20, '20201021'], $params);
	}
	
	public function testJoin1()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectJoinTest1.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals(2, count($query->tables));
		$this->assertTrue($query->tables['documents'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertTrue($query->tables['member'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertNull($query->tables['member']->join_type);
		$this->assertEmpty($query->tables['member']->join_conditions);
		
		$this->assertEquals(2, count($query->columns));
		$this->assertEquals('member.member_srl', $query->columns[0]->name);
		$this->assertFalse($query->columns[0]->is_expression);
		$this->assertFalse($query->columns[0]->is_wildcard);
		$this->assertEquals('COUNT(*)', $query->columns[1]->name);
		$this->assertEquals('count', $query->columns[1]->alias);
		$this->assertTrue($query->columns[1]->is_expression);
		$this->assertFalse($query->columns[1]->is_wildcard);
		
		$this->assertEquals(3, count($query->conditions));
		$this->assertEquals('documents.member_srl', $query->conditions[0]->column);
		$this->assertEquals('member.member_srl', $query->conditions[0]->default);
		$this->assertNull($query->conditions[0]->var);
		$this->assertEquals('documents.member_srl', $query->conditions[1]->column);
		$this->assertEquals('member.member_srl', $query->conditions[1]->default);
		$this->assertNull($query->conditions[1]->var);
		$this->assertEquals('AND', $query->conditions[1]->pipe);
		
		$this->assertTrue($query->groupby instanceof Rhymix\Framework\Parsers\DBQuery\GroupBy);
		$this->assertEquals('member.member_srl', $query->groupby->columns[0]);
		$this->assertEquals(1, count($query->groupby->having));
		$this->assertTrue($query->groupby->having[0] instanceof Rhymix\Framework\Parsers\DBQuery\Condition);
		$this->assertEquals('member.member_srl', $query->groupby->having[0]->column);
		$this->assertEquals('notequal', $query->groupby->having[0]->operation);
		
		$args = array('document_srl_list' => [12, 34, 56], 'exclude_member_srl' => 4);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		
		$this->assertEquals('SELECT `member`.`member_srl`, COUNT(*) AS `count` FROM `rx_documents` AS `documents`, `rx_member` AS `member` ' .
			'WHERE `documents`.`member_srl` = `member`.`member_srl` AND `documents`.`member_srl` = `member`.`member_srl` ' .
			'AND `documents`.`document_srl` IN (?, ?, ?) GROUP BY `member`.`member_srl` HAVING `member`.`member_srl` != ?', $sql);
		$this->assertEquals(['12', '34', '56', '4'], $params);
	}
	
	public function testJoin2()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectJoinTest2.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals(2, count($query->tables));
		$this->assertTrue($query->tables['documents'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertTrue($query->tables['member'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertEquals('LEFT JOIN', $query->tables['member']->join_type);
		$this->assertEquals(1, count($query->tables['member']->join_conditions));
		$this->assertTrue($query->tables['member']->join_conditions[0] instanceof Rhymix\Framework\Parsers\DBQuery\Condition);
		$this->assertEquals('documents.member_srl', $query->tables['member']->join_conditions[0]->column);
		$this->assertEquals('member.member_srl', $query->tables['member']->join_conditions[0]->default);
		$this->assertNull($query->tables['member']->join_conditions[0]->var);
		
		$this->assertEquals(2, count($query->columns));
		$this->assertEquals('documents.*', $query->columns[0]->name);
		$this->assertTrue($query->columns[0]->is_expression);
		$this->assertTrue($query->columns[0]->is_wildcard);
		$this->assertEquals('member.regdate', $query->columns[1]->name);
		$this->assertEquals('member_regdate', $query->columns[1]->alias);
		$this->assertFalse($query->columns[1]->is_expression);
		$this->assertFalse($query->columns[1]->is_wildcard);
		
		$args = array('document_srl_list' => [12, 34, 56]);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		
		$this->assertEquals('SELECT `documents`.*, `member`.`regdate` AS `member_regdate` FROM `rx_documents` AS `documents` ' .
			'LEFT JOIN `rx_member` AS `member` ON `documents`.`member_srl` = `member`.`member_srl` ' .
			'WHERE `documents`.`document_srl` IN (?, ?, ?)', $sql);
		$this->assertEquals(['12', '34', '56'], $params);
	}
	
	public function testSubquery1()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectSubqueryTest1.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals(2, count($query->tables));
		$this->assertTrue($query->tables['documents'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertTrue($query->tables['m'] instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals(1, count($query->tables['m']->tables));
		$this->assertEquals('member', $query->tables['m']->tables['member']->name);
		$this->assertEquals(2, count($query->tables['m']->columns));
		$this->assertEquals(1, count($query->columns));
		$this->assertEquals('documents.member_srl', $query->conditions[0]->column);
		$this->assertEquals('m.member_srl', $query->conditions[0]->default);
		
		$sql = $query->getQueryString('rx_', []);
		$params = $query->getQueryParams();
		
		$this->assertEquals('SELECT `documents`.* FROM `rx_documents` AS `documents`, ' .
			'(SELECT `member_srl`, `nick_name` FROM `rx_member` AS `member`) AS `m` ' .
			'WHERE `documents`.`member_srl` = `m`.`member_srl`', $sql);
		$this->assertEquals([], $params);
	}
	
	public function testSubquery2()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectSubqueryTest2.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals(1, count($query->tables));
		$this->assertTrue($query->tables['member'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertEquals(2, count($query->columns));
		$this->assertTrue($query->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnRead);
		$this->assertTrue($query->columns[1] instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertTrue($query->columns[1]->tables['documents'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertTrue($query->columns[1]->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnRead);
		$this->assertTrue($query->columns[1]->columns[0]->is_expression);
		$this->assertFalse($query->columns[1]->columns[0]->is_wildcard);
		
		$sql = $query->getQueryString('rx_', []);
		$params = $query->getQueryParams();
		
		$this->assertEquals('SELECT `member`.*, (SELECT COUNT(*) AS `count` FROM `rx_documents` AS `documents` WHERE `member`.`member_srl` = `documents`.`member_srl`) AS `document_count` ' .
			'FROM `rx_member` AS `member`', $sql);
		$this->assertEquals([], $params);
	}
	
	public function testSubquery3()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectSubqueryTest3.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals(1, count($query->tables));
		$this->assertTrue($query->tables['member'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertEquals(1, count($query->columns));
		$this->assertTrue($query->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnRead);
		$this->assertEquals(2, count($query->conditions));
		$this->assertTrue($query->conditions[1] instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertTrue($query->conditions[1]->tables['documents'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertTrue($query->conditions[1]->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnRead);
		$this->assertTrue($query->conditions[1]->columns[0]->is_expression);
		$this->assertEquals('member.member_srl', $query->conditions[1]->conditions[0]->column);
		$this->assertEquals('OR', $query->conditions[1]->pipe);
		
		$sql = $query->getQueryString('rx_', ['is_admin' => 'Y']);
		$params = $query->getQueryParams();
		
		$this->assertEquals('SELECT * FROM `rx_member` AS `member` WHERE `is_admin` != ? OR `regdate` = ' .
			'(SELECT MAX(regdate) AS `max_regdate` FROM `rx_documents` AS `documents` ' .
			'WHERE `member`.`member_srl` = `documents`.`member_srl`)', $sql);
		$this->assertEquals(['Y'], $params);
	}
	
	public function testInsertQuery()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/insertTest.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals('INSERT', $query->type);
		$this->assertEquals(1, count($query->tables));
		$this->assertEquals('document_voted_log', $query->tables['document_voted_log']->name);
		$this->assertEquals(5, count($query->columns));
		$this->assertTrue($query->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnWrite);
		$this->assertEquals('document_srl', $query->columns[0]->name);
		$this->assertEquals('document_srl', $query->columns[0]->var);
		$this->assertEquals('number', $query->columns[0]->filter);
		$this->assertTrue($query->columns[0]->not_null);
		$this->assertEquals('ipaddress', $query->columns[2]->name);
		$this->assertEquals('ipaddress()', $query->columns[2]->default);
		$this->assertTrue($query->update_duplicate);
		$this->assertNull($query->groupby);
		
		$args = array('document_srl' => 123, 'member_srl' => 456, 'point' => 7);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		
		$this->assertEquals('INSERT INTO `rx_document_voted_log` SET `document_srl` = ?, `member_srl` = ?, `ipaddress` = ?, `regdate` = ?, `point` = ? ' .
			'ON DUPLICATE KEY UPDATE `document_srl` = ?, `member_srl` = ?, `ipaddress` = ?, `regdate` = ?, `point` = ?', $sql);
		$this->assertEquals(10, count($params));
		$this->assertEquals('127.0.0.1', $params[2]);
		$this->assertRegexp('/20[0-9]{12}/', $params[3]);
		$this->assertEquals('7', $params[4]);
		$this->assertEquals(array_slice($params, 0, 5), array_slice($params, 5, 5));
	}
	
	public function testUpdateQuery()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/updateTest.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals('UPDATE', $query->type);
		$this->assertEquals(1, count($query->tables));
		$this->assertEquals('documents', $query->tables['documents']->name);
		$this->assertEquals(3, count($query->columns));
		$this->assertTrue($query->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnWrite);
		$this->assertEquals('member_srl', $query->columns[0]->name);
		$this->assertEquals('member_srl', $query->columns[0]->var);
		$this->assertEquals('number', $query->columns[0]->filter);
		$this->assertEquals('0', $query->columns[0]->default);
		$this->assertEquals(1, count($query->conditions));
		$this->assertTrue($query->conditions[0] instanceof Rhymix\Framework\Parsers\DBQuery\Condition);
		$this->assertEquals('equal', $query->conditions[0]->operation);
		$this->assertEquals('document_srl', $query->conditions[0]->column);
		$this->assertNull($query->groupby);
		$this->assertNull($query->navigation);
		
		$args = array('document_srl' => 123, 'member_srl' => 456, 'voted_count' => 5);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		
		$this->assertEquals('UPDATE `rx_documents` SET `member_srl` = ?, `voted_count` = `voted_count` + ? WHERE `document_srl` = ?', $sql);
		$this->assertEquals(['456', '5', '123'], $params);
	}
	
	public function testDeleteQuery()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/deleteTest.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals('DELETE', $query->type);
		$this->assertEquals(1, count($query->tables));
		$this->assertEquals('documents', $query->tables['alias']->name);
		$this->assertEquals(1, count($query->conditions));
		$this->assertTrue($query->conditions[0] instanceof Rhymix\Framework\Parsers\DBQuery\Condition);
		$this->assertEquals('in', $query->conditions[0]->operation);
		$this->assertEquals('document_srl_list', $query->conditions[0]->var);
		
		$args = array('document_srl_list' => [12, 34, 56]);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		
		$this->assertEquals('DELETE FROM `rx_documents` WHERE `document_srl` IN (?, ?, ?)', $sql);
		$this->assertEquals(['12', '34', '56'], $params);
	}
}
