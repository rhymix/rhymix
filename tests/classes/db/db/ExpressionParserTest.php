<?php

	class ExpressionParserTest extends PHPUnit_Framework_TestCase {
		/* Escape char for:
		 * CUBRID		""
		 * MySql		``
		 * SqlServer	[]
		 */
		var $dbLeftEscapeChar = '[';
		var $dbRightEscapeChar = ']';

		function _test($column_name, $alias, $expected){
			$expressionParser = new DBParser($this->dbLeftEscapeChar,$this->dbRightEscapeChar);
			$actual = $expressionParser->parseExpression($column_name);
			if($alias) $actual .= " as $alias";
			$this->assertEquals($expected, $actual);
		}

		function testStarExpressionIsNotEscaped(){
			$this->_test("*", NULL, '*');
		}

		function testSimpleColumnNameGetsEscaped(){
			$this->_test("member_srl", NULL
						, $this->dbLeftEscapeChar.'member_srl'.$this->dbRightEscapeChar );
		}

		function testUnqualifiedAliasedColumnNameGetsEscaped(){
			$this->_test("member_srl", "id"
					   , $this->dbLeftEscapeChar.'member_srl'.$this->dbRightEscapeChar.' as id');
		}

		function testQualifiedColumnNameGetsEscaped(){
			$this->_test("xe_members.member_srl", NULL
					   , $this->dbLeftEscapeChar.'xe_members'.$this->dbRightEscapeChar.'.'.$this->dbLeftEscapeChar.'member_srl'.$this->dbRightEscapeChar);
		}

		function testQualifiedAliasedColumnNameGetsEscaped(){
			$this->_test("xe_members.member_srl","id"
					 	,$this->dbLeftEscapeChar.'xe_members'.$this->dbRightEscapeChar.'.'.$this->dbLeftEscapeChar.'member_srl'.$this->dbRightEscapeChar.' as id');
		}

		function testCountDoesntGetEscaped(){
			$this->_test("count(*)", NULL, 'count(*)');
		}

		function testAliasedCountDoesntGetEscaped(){
			$this->_test("count(*)", "count", 'count(*) as count');
		}

		function testUnqualifiedColumnExpressionWithOneParameterLessFunction(){
			$this->_test("substring(regdate)", NULL
			, 'substring('.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.')');
		}

		function testAliasedUnqualifiedColumnExpressionWithOneParameterLessFunction(){
			$this->_test("substring(regdate)", "regdate"
			, 'substring('.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.') as regdate');
		}

		function testQualifiedColumnExpressionWithOneParameterLessFunction(){
			$this->_test("substring(xe_member.regdate)", NULL
			, 'substring('.$this->dbLeftEscapeChar.'xe_member'.$this->dbRightEscapeChar.'.'.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.')');
		}

		function testAliasedQualifiedColumnExpressionWithOneParameterLessFunction(){
			$this->_test("substring(xe_member.regdate)", "regdate"
			, 'substring('.$this->dbLeftEscapeChar.'xe_member'.$this->dbRightEscapeChar.'.'.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.') as regdate');
		}

		function testUnqualifiedColumnExpressionWithTwoParameterLessFunctions(){
			$this->_test("lpad(rpad(regdate))", NULL
			, 'lpad(rpad('.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.'))');
		}

		function testAliasedUnqualifiedColumnExpressionWithTwoParameterLessFunctions(){
			$this->_test("lpad(rpad(regdate))", "regdate"
			, 'lpad(rpad('.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.')) as regdate');
		}

		function testQualifiedColumnExpressionWithTwoParameterLessFunctions(){
			$this->_test("lpad(rpad(xe_member.regdate))", NULL
			, 'lpad(rpad('.$this->dbLeftEscapeChar.'xe_member'.$this->dbRightEscapeChar.'.'.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.'))');
		}

		function testAliasedQualifiedColumnExpressionWithTwoParameterLessFunctions(){
			$this->_test("lpad(rpad(xe_member.regdate))", "regdate"
			, 'lpad(rpad('.$this->dbLeftEscapeChar.'xe_member'.$this->dbRightEscapeChar.'.'.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.')) as regdate');
		}

		function testColumnAddition(){
			$this->_test("score1 + score2", "total"
			, $this->dbLeftEscapeChar.'score1'.$this->dbRightEscapeChar.' + '.$this->dbLeftEscapeChar.'score2'.$this->dbRightEscapeChar.' as total');
		}

		function testMultipleParameterFunction(){
			$this->_test("substring(regdate, 1, 8)", NULL
			, 'substring('.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.', 1, 8)');
			$this->_test("substring(regdate, 1, 8)", "regdate"
			, 'substring('.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.', 1, 8) as regdate');
			$this->_test("substring(xe_member.regdate, 1, 8)", NULL
			, 'substring('.$this->dbLeftEscapeChar.'xe_member'.$this->dbRightEscapeChar.'.'.$this->dbLeftEscapeChar.'regdate'.$this->dbRightEscapeChar.', 1, 8)');
		}

		function testFunctionAddition(){
			$this->_test("abs(score) + abs(totalscore)", NULL
			, 'abs('.$this->dbLeftEscapeChar.'score'.$this->dbRightEscapeChar.') + abs('.$this->dbLeftEscapeChar.'totalscore'.$this->dbRightEscapeChar.')');
		}
	}