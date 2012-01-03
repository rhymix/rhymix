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

	/**
	 * @brief testInsertSelectStatement - checks that when query action is 'insert-selct' an 'INSERT INTO .. SELECT ...' statement is properly generated
	 * @developer Corina Udrescu (xe_dev@arnia.ro)
	 * @access public
	 * @return void
	 */
	function testInsertSelectStatement()
	{
		$xml_file = _TEST_PATH_ . "db/xml_query/mysql/data/insert_select.xml";
		$argsString = '$args->condition_value = 7;';
		$expected = 'insert into `xe_table1` (`column1`, `column2`, `column3`) 
						select `column4`, `column5`, `column6` 
							from `xe_table2` as `table2` 
							where `column4` >= 7';
		$this->_test($xml_file, $argsString, $expected);
	}
}

/* End of file MysqlInsertTest.php */
/* Location: ./tests/classes/db/db/xml_query/mysql/MysqlInsertTest.php */
