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
	
	function testInsertSelectStatement2()
	{
		$xml_file = _XE_PATH_ . "modules/wiki/queries/insertLinkedDocuments.xml";
		$argsString = '$args->document_srl = 7;
						$args->module_srl = 10;
						$args->alias_list = array("unu", "doi");
			';
		$expected = 'insert into `xe_wiki_links` 
							(`cur_doc_srl`, `link_doc_srl`) 
						select 7, `document_srl` 
							from `xe_document_aliases` as `document_aliases` 
							where `module_srl` = 10 
								and `alias_title` in (\'unu\',\'doi\')';
		$this->_test($xml_file, $argsString, $expected);
	}	
}

/* End of file MysqlInsertTest.php */
/* Location: ./tests/classes/db/db/xml_query/mysql/MysqlInsertTest.php */
