<?php

class DBQueryParserTest extends \Codeception\TestCase\Test
{
	public function testLoadXML()
	{
		$query = Rhymix\Framework\Parsers\DBQueryParser::loadXML(\RX_BASEDIR . 'tests/_data/dbquery/selectTest.xml');
		$this->assertTrue($query instanceof Rhymix\Framework\Parsers\DBQuery\Query);
		$this->assertEquals('selectTest', $query->name);
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
	}
	
	public function testSubquery()
	{
		
	}
	
	public function testInsertQuery()
	{
		
	}
	
	public function testUpdateQuery()
	{
		
	}
	
	public function testDeleteQuery()
	{
		
	}
}
