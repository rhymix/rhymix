<?php

class DBQueryParserTest extends \Codeception\Test\Unit
{
	public function testLoadXML()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectTest1.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals('selectTest1', $query->name);
		$this->assertEquals('SELECT', $query->type);
		$this->assertTrue($query->select_distinct);

		$this->assertTrue($query->tables['documents'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertNull($query->tables['documents']->alias);
		$this->assertEquals('documents', $query->tables['documents']->name);

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
		$this->assertEquals('DESC', $query->navigation->orderby[0]->order_default);
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
		$args = array('member_srl' => 1234, 'regdate_more' => '20200707120000', 'page' => 3, 'order_type' => 'asc');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();

		$this->assertEquals('SELECT DISTINCT * FROM `rx_documents` AS `documents` ' .
			'WHERE `member_srl` IN (?) AND (`regdate` >= ? OR `status` = ?) ' .
			'ORDER BY `list_order` ASC LIMIT 40, 20', $sql);
		$this->assertEquals(['1234', '20200707120000', 'PUBLIC'], $params);

		$sql = $query->getQueryString('rx_', $args, [], 1);
		$params = $query->getQueryParams();

		$this->assertEquals('SELECT COUNT(*) AS `count` FROM (SELECT DISTINCT * FROM `rx_documents` AS `documents` ' .
			'WHERE `member_srl` IN (?) AND (`regdate` >= ? OR `status` = ?)) AS `subquery`', $sql);
		$this->assertEquals(['1234', '20200707120000', 'PUBLIC'], $params);

		unset($args['page']);
		$sql = $query->getQueryString('rx_', $args);
		$this->assertEquals('SELECT DISTINCT * FROM `rx_documents` AS `documents` ' .
			'WHERE `member_srl` IN (?) AND (`regdate` >= ? OR `status` = ?) ' .
			'ORDER BY `list_order` ASC LIMIT 20', $sql);

		$args['list_count'] = 0;
		$sql = $query->getQueryString('rx_', $args);
		$this->assertEquals('SELECT DISTINCT * FROM `rx_documents` AS `documents` ' .
			'WHERE `member_srl` IN (?) AND (`regdate` >= ? OR `status` = ?) ' .
			'ORDER BY `list_order` ASC', $sql);
	}

	public function testSelectWithExpressions()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectTest2.xml');
		$args = array('voted_count' => 20, 'date' => '20201021');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();

		$this->assertEquals('documents', $query->tables['d']->name);
		$this->assertEquals('SELECT readed_count + trackback_count AS `count` ' .
			'FROM `rx_documents` AS `d` WHERE `voted_count` + `blamed_count` >= ? AND LEFT(`regdate`, 8) = ?', $sql);
		$this->assertEquals([20, '20201021'], $params);
	}

	public function testSelectWithSearch()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectTest3.xml');

		$args = array('s_content' => '"I love you" -"I hate you"');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE (`content` LIKE ? AND `content` NOT LIKE ?)', $sql);
		$this->assertEquals(['%I love you%', '%I hate you%'], $params);

		$args = array('s_content' => '(foo AND bar) -baz "Rhymix is the best"');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE ((`content` LIKE ? AND `content` LIKE ?) AND `content` NOT LIKE ? AND `content` LIKE ?)', $sql);
		$this->assertEquals(['%foo%', '%bar%', '%baz%', '%Rhymix is the best%'], $params);

		$args = array('s_content' => 'revenue +3.5% -"apos\'tro_phe"');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE (`content` LIKE ? AND `content` LIKE ? AND `content` NOT LIKE ?)', $sql);
		$this->assertEquals(['%revenue%', '%+3.5\\%%', '%apos\'tro\\_phe%'], $params);

		$args = array('s_content' => '(search keyword\\Z) -"-42"');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE ((`content` LIKE ? AND `content` LIKE ?) AND `content` NOT LIKE ?)', $sql);
		$this->assertEquals(['%search%', '%keyword\\\\Z%', '%-42%'], $params);

		$args = array('s_content' => '"한글" AND -&quot;검색&quot; (-키워드 OR 라이믹스)');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE (`content` LIKE ? AND `content` NOT LIKE ? AND (`content` NOT LIKE ? OR `content` LIKE ?))', $sql);
		$this->assertEquals(['%한글%', '%검색%', '%키워드%', '%라이믹스%'], $params);

		$args = array('s_content' => '검색 OR (키워드 AND -"라이믹스 유닛테스트")');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE (`content` LIKE ? OR (`content` LIKE ? AND `content` NOT LIKE ?))', $sql);
		$this->assertEquals(['%검색%', '%키워드%', '%라이믹스 유닛테스트%'], $params);
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

		$this->assertEquals(4, count($query->conditions));
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

		$args = array(
			'document_srl_list' => [12, 34, 56], 'exclude_member_srl' => 4,
			'if_table' => true, 'if_column' => true, 'if_condition1' => true, 'if_groupby' => true,
		);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();

		$this->assertEquals('SELECT `member`.`member_srl`, COUNT(*) AS `count` FROM `rx_documents` AS `documents`, `rx_member` AS `member` ' .
			'WHERE `documents`.`member_srl` = `member`.`member_srl` AND `documents`.`member_srl` = `member`.`member_srl` ' .
			'AND `documents`.`document_srl` IN (?, ?, ?) GROUP BY `member`.`member_srl` HAVING `member`.`member_srl` != ?', $sql);
		$this->assertEquals(['12', '34', '56', '4'], $params);

		$args = array(
			'document_srl_list' => [12, 34, 56], 'exclude_member_srl' => 4, 'exclude_document_srl_list' => '78,90',
			'if_table' => true, 'if_column' => true, 'if_condition2' => true,
		);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();

		$this->assertEquals('SELECT `member`.`member_srl`, COUNT(*) AS `count` FROM `rx_documents` AS `documents`, `rx_member` AS `member` ' .
			'WHERE `documents`.`member_srl` = `member`.`member_srl` AND `documents`.`document_srl` IN (?, ?, ?) ' .
			'AND `documents`.`document_srl` NOT IN (?, ?)', $sql);
		$this->assertEquals(['12', '34', '56', '78', '90'], $params);

		$args = array(
			'document_srl_list' => [12, 34, 56], 'exclude_member_srl' => 4,
		);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();

		$this->assertEquals('SELECT `member`.`member_srl` FROM `rx_documents` AS `documents` ' .
			'WHERE `documents`.`member_srl` = `member`.`member_srl` AND `documents`.`document_srl` IN (?, ?, ?)', $sql);
		$this->assertEquals(['12', '34', '56'], $params);
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
		$this->assertEquals(3, count($query->tables['m']->columns));
		$this->assertEquals(1, count($query->columns));
		$this->assertEquals('documents.member_srl', $query->conditions[0]->column);
		$this->assertEquals('m.member_srl', $query->conditions[0]->default);

		$sql = $query->getQueryString('rx_', ['nick_name' => 'foobar']);
		$params = $query->getQueryParams();

		$this->assertEquals('SELECT `documents`.* FROM `rx_documents` AS `documents`, ' .
			'(SELECT `member_srl`, `nick_name`, `regdate` FROM `rx_member` AS `member` ' .
			'WHERE `documents`.`nick_name` = ?) AS `m` ' .
			'WHERE `documents`.`member_srl` = `m`.`member_srl`', $sql);
		$this->assertEquals(['foobar'], $params);
	}

	public function testSubquery2()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectSubqueryTest2.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals(1, count($query->tables));
		$this->assertTrue($query->tables['member'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertEquals(2, count($query->columns));
		$this->assertTrue($query->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnRead);
		$this->assertTrue($query->columns[0]->is_expression);
		$this->assertTrue($query->columns[0]->is_wildcard);
		$this->assertTrue($query->columns[1] instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertTrue($query->columns[1]->tables['documents'] instanceof Rhymix\Framework\Parsers\DBQuery\Table);
		$this->assertTrue($query->columns[1]->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnRead);
		$this->assertFalse($query->columns[1]->columns[0]->is_expression);
		$this->assertFalse($query->columns[1]->columns[0]->is_wildcard);
		$this->assertTrue($query->columns[1]->columns[1]->is_expression);
		$this->assertTrue($query->columns[1]->columns[1]->is_wildcard);
		$this->assertTrue($query->columns[1]->columns[2]->is_expression);
		$this->assertFalse($query->columns[1]->columns[2]->is_wildcard);

		$sql = $query->getQueryString('rx_', []);
		$params = $query->getQueryParams();

		$this->assertEquals('SELECT `member`.*, (SELECT `documents`.`document_srl`, `documents`.*, COUNT(*) AS `count` FROM `rx_documents` AS `documents` ' .
			'WHERE `member`.`member_srl` = `documents`.`member_srl`) AS `document_count` ' .
			'FROM `rx_member` AS `member`', $sql);
		$this->assertEquals([], $params);

		// Test count-only query (#1575)
		$sql = $query->getQueryString('rx_', [], [], 1);
		$params = $query->getQueryParams();

		$this->assertEquals('SELECT COUNT(*) AS `count` FROM (SELECT 1, (SELECT `documents`.`document_srl`, 1, COUNT(*) AS `count` FROM `rx_documents` AS `documents` ' .
			'WHERE `member`.`member_srl` = `documents`.`member_srl`) AS `document_count` ' .
			'FROM `rx_member` AS `member`) AS `subquery`', $sql);
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

	public function testCountQuery()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectCountTest1.xml');
		$sql = $query->getQueryString('rx_', ['exclude_member_srl' => 4], [], true);
		$this->assertEquals('SELECT COUNT(*) AS `count` FROM (SELECT 1 FROM `rx_documents` AS `documents`, `rx_member` AS `member` ' .
			'WHERE `documents`.`member_srl` = `member`.`member_srl` GROUP BY `member`.`member_srl` HAVING `member`.`member_srl` != ?) ' .
			'AS `subquery`', $sql);

		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectCountTest2.xml');
		$sql = $query->getQueryString('rx_', ['document_srl_list' => [100, 110, 120]], [], true);
		$this->assertEquals('SELECT COUNT(*) AS `count` FROM (SELECT DISTINCT `module_srl` FROM `rx_documents` AS `documents` ' .
			'WHERE `document_srl` IN (?, ?, ?)) AS `subquery`', $sql);
	}

	public function testIndexHintQuery()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/indexHintTest1.xml');
		$sql = $query->getQueryString('rx_', ['module_srl' => 82]);
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` USE INDEX (`idx_module_srl`) ' .
			'WHERE `module_srl` = ? ORDER BY `list_order` DESC LIMIT 20', $sql);

		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/indexHintTest2.xml');
		$sql = $query->getQueryString('rx_', ['module_srl' => 82]);
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` USE INDEX (`idx_module_list_order`) ' .
			'WHERE `module_srl` = ? ORDER BY `list_order` DESC LIMIT 20', $sql);

		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/indexHintTest2.xml');
		$sql = $query->getQueryString('rx_', ['module_srl' => 82, 'index_hint1' => 'idx_regdate']);
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` FORCE INDEX (`idx_regdate`) USE INDEX (`idx_module_list_order`) ' .
			'WHERE `module_srl` = ? ORDER BY `list_order` DESC LIMIT 20', $sql);

		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/indexHintTest2.xml');
		$sql = $query->getQueryString('rx_', ['module_srl' => 82, 'index_hint1' => 'idx_regdate', 'index_hint2' => 'idx_member_srl']);
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` FORCE INDEX (`idx_regdate`) USE INDEX (`idx_member_srl`) ' .
			'WHERE `module_srl` = ? ORDER BY `list_order` DESC LIMIT 20', $sql);
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
		$this->assertEquals(2, count($query->tables));
		$this->assertEquals('documents', $query->tables['documents']->name);
		$this->assertNull($query->tables['documents']->alias);
		$this->assertEquals('comments', $query->tables['c']->name);
		$this->assertEquals('c', $query->tables['c']->alias);
		$this->assertEquals(6, count($query->columns));
		$this->assertTrue($query->columns[0] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnWrite);
		$this->assertEquals('member_srl', $query->columns[0]->name);
		$this->assertEquals('member_srl', $query->columns[0]->var);
		$this->assertEquals('number', $query->columns[0]->filter);
		$this->assertEquals('0', $query->columns[0]->default);
		$this->assertTrue($query->columns[1] instanceof Rhymix\Framework\Parsers\DBQuery\ColumnWrite);
		$this->assertEquals('nick_name', $query->columns[1]->name);
		$this->assertEquals('nick_name', $query->columns[1]->var);
		$this->assertNull($query->columns[1]->filter);
		$this->assertEquals('null', $query->columns[1]->default);
		$this->assertEquals(1, count($query->conditions));
		$this->assertTrue($query->conditions[0] instanceof Rhymix\Framework\Parsers\DBQuery\Condition);
		$this->assertEquals('equal', $query->conditions[0]->operation);
		$this->assertEquals('document_srl', $query->conditions[0]->column);
		$this->assertNull($query->groupby);
		$this->assertNull($query->navigation);

		$args = array('document_srl' => 123, 'nick_name' => '닉네임', 'member_srl' => 456, 'voted_count' => 5);
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();

		$this->assertEquals('UPDATE `rx_documents`, `rx_comments` AS `c` SET `member_srl` = ?, `nick_name` = ?, `voted_count` = `voted_count` + ?, `regdate` = ?, `last_update` = ? WHERE `document_srl` = ?', $sql);
		$this->assertEquals(['456', '닉네임', '5'], array_slice($params, 0, 3));
		$this->assertRegexp('/^20[0-9]{2}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $params[3]);
		$this->assertRegexp('/^[0-9]{2}.[0-9]{2}.[0-9]{2}$/', $params[4]);

		$args = array('document_srl' => 123, 'member_srl' => 456, 'voted_count' => 5, 'regdate' => 'foo', 'last_update' => 'bar');
		$sql = $query->getQueryString('rx_', $args);
		$params = $query->getQueryParams();

		$this->assertEquals('UPDATE `rx_documents`, `rx_comments` AS `c` SET `member_srl` = ?, `nick_name` = NULL, `voted_count` = `voted_count` + ?, `regdate` = ?, `last_update` = ? WHERE `document_srl` = ?', $sql);
		$this->assertEquals(['456', '5', 'foo', 'bar', '123'], $params);
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

	public function testEmptyString()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/emptyStringTest1.xml');

		$sql = $query->getQueryString('rx_', array(
			'nick_name' => '',
			'document_srl' => 1234,
		));
		$this->assertEquals('UPDATE `rx_documents` SET `nick_name` = ? WHERE `document_srl` = ?', $sql);
		$this->assertEquals(['', 1234], $query->getQueryParams());

		$sql = $query->getQueryString('rx_', array(
			'nick_name' => new \Rhymix\Framework\Parsers\DBQuery\EmptyString,
			'document_srl' => 1234,
		));
		$this->assertEquals('UPDATE `rx_documents` SET `nick_name` = \'\' WHERE `document_srl` = ?', $sql);
		$this->assertEquals([1234], $query->getQueryParams());

		$sql = $query->getQueryString('rx_', array(
			'nick_name' => new \Rhymix\Framework\Parsers\DBQuery\EmptyString,
			'document_srl' => '',
		));
		$this->assertEquals('UPDATE `rx_documents` SET `nick_name` = \'\'', $sql);
		$this->assertEquals([], $query->getQueryParams());

		$sql = $query->getQueryString('rx_', array(
			'nick_name' => new \Rhymix\Framework\Parsers\DBQuery\EmptyString,
			'document_srl' => new \Rhymix\Framework\Parsers\DBQuery\EmptyString,
		));
		$this->assertEquals('UPDATE `rx_documents` SET `nick_name` = \'\' WHERE `document_srl` = \'\'', $sql);
		$this->assertEquals([], $query->getQueryParams());

		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/emptyStringTest2.xml');

		$sql = $query->getQueryString('rx_', array(
			'category_srl' => 77,
			'nick_name' => '',
		));
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE `category_srl` = ?', $sql);
		$this->assertEquals([77], $query->getQueryParams());

		$sql = $query->getQueryString('rx_', array(
			'category_srl' => 88,
			'nick_name' => new \Rhymix\Framework\Parsers\DBQuery\EmptyString,
		));
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE `category_srl` = ? AND `nick_name` = \'\'', $sql);
		$this->assertEquals([88], $query->getQueryParams());
	}

	public function testNullValue()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/nullValueTest1.xml');

		$sql = $query->getQueryString('rx_', array(
			'user_name' => null,
			'nick_name' => 'TEST',
			'document_srl' => 1234,
		));
		$this->assertEquals('UPDATE `rx_documents` SET `nick_name` = ? WHERE `document_srl` = ?', $sql);
		$this->assertEquals(['TEST', 1234], $query->getQueryParams());

		$sql = $query->getQueryString('rx_', array(
			'user_name' => new \Rhymix\Framework\Parsers\DBQuery\NullValue,
			'nick_name' => 'TEST',
			'document_srl' => 1234,
		));
		$this->assertEquals('UPDATE `rx_documents` SET `user_name` = NULL, `nick_name` = ? WHERE `document_srl` = ?', $sql);
		$this->assertEquals(['TEST', 1234], $query->getQueryParams());

		$this->tester->expectThrowable('Exception', function() use($query) {
			$query->getQueryString('rx_', array(
				'nick_name' => new \Rhymix\Framework\Parsers\DBQuery\NullValue,
				'document_srl' => 1234,
			));
		});

		$this->tester->expectThrowable('Exception', function() use($query) {
			$query->getQueryString('rx_', array(
				'nick_name' => null,
				'document_srl' => 1234,
			));
		});

		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/nullValueTest2.xml');

		$sql = $query->getQueryString('rx_', array(
			'member_srl' => null,
			'module_srl' => 456,
		));
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE `module_srl` = ?', $sql);
		$this->assertEquals([456], $query->getQueryParams());

		$sql = $query->getQueryString('rx_', array(
			'member_srl' => new \Rhymix\Framework\Parsers\DBQuery\NullValue,
			'module_srl' => new \Rhymix\Framework\Parsers\DBQuery\NullValue,
		));
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE `module_srl` IS NULL AND `member_srl` IS NOT NULL', $sql);
		$this->assertEquals([], $query->getQueryParams());
	}

	public function testIfVar()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/ifVarTest.xml');

		$sql = $query->getQueryString('rx_', array(
			'if_table' => true,
			'module_srl' => 1234,
		));
		$this->assertEquals('SELECT `module_srl` FROM `rx_documents` AS `documents`', $sql);

		$sql = $query->getQueryString('rx_', array(
			'if_table' => true,
			'if_column' => true,
			'if_condition' => true,
		));
		$this->assertEquals('SELECT `module_srl`, `document_srl` FROM `rx_documents` AS `documents`', $sql);

		$sql = $query->getQueryString('rx_', array(
			'if_table' => true,
			'if_column' => true,
			'if_condition' => true,
			'module_srl' => 1234,
		));
		$this->assertEquals('SELECT `module_srl`, `document_srl` FROM `rx_documents` AS `documents` WHERE `module_srl` = ?', $sql);
		$this->assertFalse($query->requires_pagination);

		$sql = $query->getQueryString('rx_', array(
			'if_table' => true,
			'if_sort_index' => true,
		));
		$this->assertEquals('SELECT `module_srl` FROM `rx_documents` AS `documents` ORDER BY `list_order` DESC', $sql);
		$this->assertFalse($query->requires_pagination);

		$sql = $query->getQueryString('rx_', array(
			'if_table' => true,
			'if_sort_index' => true,
			'if_list_count' => true,
			'if_page_count' => true,
			'if_page' => true,
		));
		$this->assertEquals('SELECT `module_srl` FROM `rx_documents` AS `documents` ORDER BY `list_order` DESC LIMIT 40, 20', $sql);
		$this->assertTrue($query->requires_pagination);
	}

	public function testSortIndex()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/sortIndexTest.xml');

		$sql = $query->getQueryString('rx_', array());
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE `status` = ? ORDER BY RAND() DESC', $sql);

		$sql = $query->getQueryString('rx_', array(
			'sort_index' => 'list_order',
			'order_type' => 'asc',
		));
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE `status` = ? ORDER BY `list_order` ASC', $sql);

		$sql = $query->getQueryString('rx_', array(
			'sort_index' => 'voted_count + blamed_count',
			'order_type' => 'desc',
		));
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE `status` = ?', $sql);

		$sql = $query->getQueryString('rx_', array(
			'sort_index' => 'RAND()',
		));
		$this->assertEquals('SELECT * FROM `rx_documents` AS `documents` WHERE `status` = ?', $sql);
	}
}
