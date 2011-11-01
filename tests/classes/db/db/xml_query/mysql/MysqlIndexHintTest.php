<?php

    class MysqlIndexHintTest extends MysqlTest {
        var $xmlPath = 'data/';

        function MysqlIndexHintTest(){
            $this->xmlPath = str_replace('MysqlIndexHintTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
        }

        function _test($xml_file, $argsString, $expected){
            $this->_testQuery($xml_file, $argsString, $expected, 'getSelectSql');
        }

        function testOneUseIndexHintAndOneTable(){
                $xml_file = $this->xmlPath . "one_index_hint_one_table.xml";
                $argsString = '';
                $expected = 'select * from `xe_member` as `member` use index (`idx_member_list_order`)';
                $this->_test($xml_file, $argsString, $expected);
        }


        function testTwoUseIndexHintsAndOneTable(){
                $xml_file = $this->xmlPath . "two_index_hints_one_table.xml";
                $argsString = '';
                $expected = 'select * from `xe_member` as `member` use index (`idx_member_list_order`, `idx_member_srl`)';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testThreeUseIndexHintsAndTwoTables(){
                $xml_file = $this->xmlPath . "three_index_hints_two_tables.xml";
                $argsString = '';
                $expected = 'select * from `xe_member` as `member` use index (`idx_member_list_order`, `idx_member_srl`)
                    , `xe_document` as `document` use index (`idx_document_srl`)';
                $this->_test($xml_file, $argsString, $expected);
        }

        function testThreeIndexHintsAndTwoTables_ForceAndIgnore(){
                $xml_file = $this->xmlPath . "three_index_hints_two_tables_combined.xml";
                $argsString = '';
                $expected = 'select * from `xe_member` as `member` force index (`idx_member_list_order`, `idx_member_srl`)
                    , `xe_document` as `document` ignore index (`idx_document_srl`)';
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
                $expected = 'select * from `xe_member` as `member` use index (`idx_member_list_order`)';
                $this->_test($xml_file, $argsString, $expected);
        }

    }
?>
