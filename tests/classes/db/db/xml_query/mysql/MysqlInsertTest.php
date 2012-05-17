<?php

/**
 * @class MysqlInsertTest
 * @brief Constains all test method for insert statements, using Mysql SQL syntax
 * @developer Corina Udrescu (xe_dev@arnia.ro)
 */
class MysqlInsertTest extends MysqlTest 
{
	/**
	 * @brief _test - local helper method
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @access private
	 * @param $xml_file string - Path to XML file containing the query to be tested
	 * @param $argsString string - String containing PHP code that initializez the arguments that the query receives
	 * @param $expected string - Expected SQL query as string
	 * @param $columnList array - Array containing the column names that will be retrieved, in case only a part of the ones in the query file are needed
	 * @return void
	 */
	function _test($xml_file, $argsString, $expected, $columnList = NULL)
	{
		$this->_testQuery($xml_file, $argsString, $expected, 'getInsertSql', $columnList);
	}
	
	function testInsertIntoNumericColumnConvertsValue()
	{
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/member_insert_injection.xml";
		$argsString = '$args->member_srl = 7;
						$args->find_account_question = "1\'";
			';
		$expected = 'insert into `xe_member` (`member_srl`, `find_account_question`) values (7, 1)';
		$this->_test($xml_file, $argsString, $expected);
	}


}

/* End of file MysqlInsertTest.php */
/* Location: ./tests/classes/db/db/xml_query/mysql/MysqlInsertTest.php */
