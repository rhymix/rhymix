<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');
        
         /**
         * Test class for TablesTag.
         */
	class TablesTagTest extends CubridTest {
	
                var $xmlPath = "data/";
                           
                function TablesTagTest(){
                    $this->xmlPath = str_replace('TablesTagTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
                }
		
                /**
                 * Tests a simple <tables> tag:
                 * <tables>
                 *     <table name="member" />
                 * </tables>
                 */
		function testTablesTagWithOneTable(){
			$xml_file = $this->xmlPath . "tables_one_table.xml";
			$xml_obj = Helper::getXmlObject($xml_file);
			$tag = new TablesTag($xml_obj->tables);
                        
                        $expected = "array(new Table('\"xe_member\"', '\"member\"'))";
                        $actual = $tag->toString();
                        
                        $this->_testCachedOutput($expected, $actual);
		}	
             
                /**
                 * Tests a simple <tables> tag:
                 * <tables>
                 *     <table name="member_group" alias="a" />
                 *     <table name="member_group_member" alias="b" />
                 * </tables>
                 */
		function testTablesTagWithTwoTablesNoJoin(){
			$xml_file = $this->xmlPath . "tables_two_tables_no_join.xml";
			$xml_obj = Helper::getXmlObject($xml_file);
			$tag = new TablesTag($xml_obj->tables);
                        
                        $expected = "array(
                                        new Table('\"xe_member_group\"', '\"a\"') 
                                       ,new Table('\"xe_member_group_member\"', '\"b\"')
                                    )";
                        $actual = $tag->toString();
                        
                        $this->_testCachedOutput($expected, $actual);
		}	                
                
                /**
                 * Tests a simple <tables> tag:
                 * <tables>
                 *      <table name="files" alias="files" />
                 *      <table name="member" alias="member" type="left join">
                 *          <conditions>
                 *              <condition operation="equal" column="files.member_srl" default="member.member_srl" />
                 *           </conditions>
                 *       </table>
                 * </tables>
                 */
		function testTablesTagWithTwoTablesWithJoin(){
			$xml_file = $this->xmlPath . "tables_two_tables_with_join.xml";
			$xml_obj = Helper::getXmlObject($xml_file);
			$tag = new TablesTag($xml_obj->tables);
                        
                        $expected = "array(
                                        new Table('\"xe_files\"', '\"files\"') 
                                       ,new JoinTable('\"xe_member\"'
                                                    , '\"member\"'
                                                    , \"left join\"
                                                    , array(
                                                        new ConditionGroup(
                                                            array(
                                                                new Condition(
                                                                    '\"files\".\"member_srl\"'
                                                                    ,'\"member\".\"member_srl\"'
                                                                    ,\"equal\"
                                                                )
                                                            )
                                                         )
                                                      )
                                        )
                                    )";
                        $actual = $tag->toString();
                        
                        $this->_testCachedOutput($expected, $actual);
		}	  
                
                /**
                 * Tests a simple <tables> tag:
                 * <tables>
                 *      <table name="files" alias="files" />
                 *      <table name="member" alias="member" type="left join">
                 *          <conditions>
                 *              <condition operation="equal" column="files.member_srl" default="member.member_srl" />
                 *           </conditions>
                 *       </table>
                 * </tables>
                 */
		function testGetTables(){
			$xml_file = $this->xmlPath . "tables_two_tables_with_join.xml";
			$xml_obj = Helper::getXmlObject($xml_file);
			$tag = new TablesTag($xml_obj->tables);
                        
                        $tables = $tag->getTables();
                        
                        $this->assertEquals(2, count($tables));
                        $this->assertTrue(is_a($tables[0], 'TableTag'));
                        $this->assertTrue(is_a($tables[1], 'TableTag'));
		}	                
	}