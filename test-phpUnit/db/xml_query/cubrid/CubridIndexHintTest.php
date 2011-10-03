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
                $expected = 'select * from "xe_member" as "member" using index "member"."idx_member_list_order"';
                $this->_test($xml_file, $argsString, $expected);
        }


        function testTwoUseIndexHintsAndOneTable(){
                $xml_file = $this->xmlPath . "two_index_hints_one_table.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member" using index "member"."idx_member_list_order", "member"."idx_member_srl"';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testThreeUseIndexHintsAndTwoTables(){
                $xml_file = $this->xmlPath . "three_index_hints_two_tables.xml";
                $argsString = '';
                $expected = 'select * from "xe_member" as "member", "xe_document" as "document"
                    using index "member"."idx_member_list_order", "member"."idx_member_srl", "document"."idx_document_srl"';
                $this->_test($xml_file, $argsString, $expected);
        }

    }
?>
