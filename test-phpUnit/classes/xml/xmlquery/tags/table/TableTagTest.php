<?php
	require(_XE_PATH_ . 'test-phpUnit/config/config.inc.php');

         /**
         * Test class for TableTag.
         */
	class TableTagTest extends CubridTest {

                var $xmlPath = "data/";

                function TableTagTest(){
                    $this->xmlPath = str_replace('TableTagTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
                }

                /**
                 * Tests a simple <table> tag:
                 * <table name="modules" />
                 */
		function testTableTagWithName(){
			$xml_file = $this->xmlPath . "table_name.xml";
			$xml_obj = Helper::getXmlObject($xml_file);
			$tag = new TableTag($xml_obj->table);

                        $expected = "new Table('\"xe_modules\"', '\"modules\"')";
                        $actual = $tag->getTableString();
			$this->assertEquals($expected, $actual);
		}

                /**
                 * Tests a <table> tag with name and alias
                 * <table name="modules" alias="mod" />
                 */
		function testTableTagWithNameAndAlias(){
			$xml_file = $this->xmlPath . "table_name_alias.xml";
			$xml_obj = Helper::getXmlObject($xml_file);

			$tag = new TableTag($xml_obj->table);

                        $expected = "new Table('\"xe_modules\"', '\"mod\"')";
                        $actual = $tag->getTableString();
			$this->assertEquals($expected, $actual);
		}

                /**
                 * Tests a <table> tag used for joins
                 * <table name="module_categories" alias="module_categories" type="left join">
                 *       <conditions>
                 *           <condition operation="equal" column="module_categories.module_category_srl" default="modules.module_category_srl" />
                 *       </conditions>
                 * </table>
                 *
                 */
		function testTableTagWithJoinCondition(){
			$xml_file = $this->xmlPath . "table_name_alias_type.xml";
			$xml_obj = Helper::getXmlObject($xml_file);

			$tag = new TableTag($xml_obj->table);

                        $actual = $tag->getTableString();

                        $expected = 'new JoinTable(\'"xe_module_categories"\', \'"module_categories"\', "left join", array(
                            new ConditionGroup(array(
                            new ConditionWithoutArgument(\'"module_categories"."module_category_srl"\',\'"modules"."module_category_srl"\',"equal")
                            ))
                            ))';
                        $actual = Helper::cleanString($actual);
                        $expected = Helper::cleanString($expected);

			$this->assertEquals($expected, $actual);
		}

                /**
                 * If a table tag has the type attribute and condition children
                 * it means it is meant to be used inside a join
                 */
                function testTagWithTypeIsJoinTable(){
                    $xml_file = $this->xmlPath . "table_name_alias_type.xml";
                    $xml_obj = Helper::getXmlObject($xml_file);

                    $tag = new TableTag($xml_obj->table);

                    $this->assertEquals(true, $tag->isJoinTable());
                }

                /**
                 * Tests that a simple table tag is not a join table
                 */
                function testTagWithoutTypeIsNotJoinTable(){
                    $xml_file = $this->xmlPath . "table_name_alias.xml";
                    $xml_obj = Helper::getXmlObject($xml_file);

                    $tag = new TableTag($xml_obj->table);

                    $this->assertEquals(false, $tag->isJoinTable());
                }

                /**
                 * If no alias is specified, test that table name is used
                 */
                function testTableAliasWhenAliasNotSpecified(){
                    $xml_file = $this->xmlPath . "table_name.xml";
                    $xml_obj = Helper::getXmlObject($xml_file);

                    $tag = new TableTag($xml_obj->table);

                    $this->assertEquals("modules", $tag->getTableAlias());
                }

                /**
                 * If alias is specified, test that it is used
                 */
                function testTableAliasWhenAliasSpecified(){
                    $xml_file = $this->xmlPath . "table_name_alias.xml";
                    $xml_obj = Helper::getXmlObject($xml_file);

                    $tag = new TableTag($xml_obj->table);

                    $this->assertEquals("mod", $tag->getTableAlias());
                }

                /**
                 * Table name propery should returned unescaped and unprefixed table name
                 * (The one in the XML file)
                 */
                function testTableName(){
                    $xml_file = $this->xmlPath . "table_name_alias.xml";
                    $xml_obj = Helper::getXmlObject($xml_file);

                    $tag = new TableTag($xml_obj->table);

                    $this->assertEquals("modules", $tag->getTableName());
                }

	}