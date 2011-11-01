<?php

    class MssqlIndexHintTest extends MssqlTest {
        var $xmlPath = 'data/';

        function MssqlIndexHintTest(){
            $this->xmlPath = str_replace('MssqlIndexHintTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
        }

        function _test($xml_file, $argsString, $expected){
            $this->_testQuery($xml_file, $argsString, $expected, 'getSelectSql');
        }

        function testOneUseIndexHintAndOneTable(){
                $xml_file = $this->xmlPath . "one_index_hint_one_table.xml";
                $argsString = '';
                $expected = 'select * from [xe_member] as [member] with(index([idx_member_list_order]))';
                $this->_test($xml_file, $argsString, $expected);
        }


        function testTwoUseIndexHintsAndOneTable(){
                $xml_file = $this->xmlPath . "two_index_hints_one_table.xml";
                $argsString = '';
                $expected = 'select * from [xe_member] as [member] with(index([idx_member_list_order]), index([idx_member_srl]))';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testThreeUseIndexHintsAndTwoTables(){
                $xml_file = $this->xmlPath . "three_index_hints_two_tables.xml";
                $argsString = '';
                $expected = 'select * from [xe_member] as [member] with(index([idx_member_list_order]), index([idx_member_srl]))
                    , [xe_document] as [document] with(index([idx_document_srl]))';
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
                $expected = 'select * from [xe_member] as [member] with(index([idx_member_list_order]))';
                $this->_test($xml_file, $argsString, $expected);
        }


        function testIgnoreIndexHintIsSkipped(){
                $xml_file = $this->xmlPath . "ignore_index_hint.xml";
                $argsString = '';
                $expected = 'select * from [xe_member] as [member]';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testMysqlIndexHintIsSkipped(){
                $xml_file = $this->xmlPath . "mysql_index_hint.xml";
                $argsString = '';
                $expected = 'select * from [xe_member] as [member]';
                $this->_test($xml_file, $argsString, $expected);
        }
    }
?>
