<?php

    class CubridIndexHintTest extends CubridTest {
        var $xmlPath = 'data/';

        function CubridIndexHintTest(){
            $this->xmlPath = str_replace('CubridIndexHintTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
        }

        function _test($xml_file, $argsString, $expected){
            var_dump($xml_file);
            $this->_testQuery($xml_file, $argsString, $expected, 'getSelectSql');
        }

        function testOneUseIndexHintAndOneTable(){
                $xml_file = $this->xmlPath . "one_index_hint_one_table.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member" using index "member"."xe_idx_member_list_order"';
                $this->_test($xml_file, $argsString, $expected);
        }


        function testTwoUseIndexHintsAndOneTable(){
                $xml_file = $this->xmlPath . "two_index_hints_one_table.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member" using index "member"."xe_idx_member_list_order", "member"."xe_idx_member_srl"';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testThreeUseIndexHintsAndTwoTables(){
                $xml_file = $this->xmlPath . "three_index_hints_two_tables.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member", "xe_document" as "document"
                    using index "member"."xe_idx_member_list_order", "member"."xe_idx_member_srl", "document"."xe_idx_document_srl"';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testThreeUseIndexHintsAndTwoTablesCombined(){
                $xml_file = $this->xmlPath . "three_index_hints_two_tables_combined.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member", "xe_document" as "document"
                    using index "member"."xe_idx_member_list_order", "member"."xe_idx_member_srl"(+), "document"."xe_idx_document_srl"';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testIgnoreIndexHintIsSkipped(){
                $xml_file = $this->xmlPath . "ignore_index_hint.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member"';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testMysqlIndexHintIsSkipped(){
                $xml_file = $this->xmlPath . "mysql_index_hint.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member"';
                $this->_test($xml_file, $argsString, $expected);
        }

        /**
         * If CUBRID database is used, indexes are created with prefix.
         *
         * e.g.: xe_indx_list_order
         */
        function testPrefixIsAddedToIndexName(){
                $xml_file = $this->xmlPath . "one_index_hint_one_table.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member" using index "member"."xe_idx_member_list_order"';
                $this->_test($xml_file, $argsString, $expected);
        }

        /**
         * Tests that index is added if "for" attribute is "ALL"
         *
         * example: <index_hint for="ALL"> ... </index_hint>
         */
        function testIndexHintForAll(){
                $xml_file = $this->xmlPath . "index_hint_for_all.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member" using index "member"."xe_idx_member_list_order"';
                $this->_test($xml_file, $argsString, $expected);
        }

    }
?>
