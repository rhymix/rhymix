<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

    class CubridSubqueryTest extends CubridTest {
        var $xmlPath = 'data/';
        
        function CubridSubqueryTest(){
            $this->xmlPath = str_replace('CubridSubqueryTest.php', '', str_replace('\\', '/', __FILE__)) . $this->xmlPath;
        }
        
        function _test($xml_file, $argsString, $expected){
            $this->_testQuery($xml_file, $argsString, $expected, 'getSelectSql');
        }
        
        function testSelectUncorrelated1(){
                $xml_file = $this->xmlPath . "select_uncorrelated1.xml";
                $argsString = '$args->user_id = 4;
                                 ';
                $expected = 'select "column_a" as "value_a"
                            , (select max("column_b") as "count" 
                               from "xe_table_b" as "table_b"
                               ) as "value_b" 
                             from "xe_table_a" as "table_a" 
                             where "column_a" = 4';
                $this->_test($xml_file, $argsString, $expected);
        }        
        
        function testSelectUncorrelated2(){
                $xml_file = $this->xmlPath . "select_uncorrelated2.xml";
                $argsString = '$args->user_id = 4;
                                $args->user_name = 7;
                                ';
                $expected = 'SELECT "column_a" as "value_a"
                                    , "column_b" as "value_b"
                                    , "column_c" as "value_c"
                                    , (SELECT max("column_b") as "count"  
                                       FROM "xe_table_b" as "table_b"   
                                       WHERE  "column_ab" = 7) as "value_b"  
                            FROM "xe_table_a" as "table_a"   
                            WHERE "column_a" = 4';
                $this->_test($xml_file, $argsString, $expected);
        }      
        
        function testFromUncorrelated1(){
                $xml_file = $this->xmlPath . "from_uncorrelated1.xml";
                $argsString = '$args->user_id = 4;
                                $args->user_name = 7;
                                ';
                $expected = 'select max("documentcountbymember"."count") as "maxcount" 
                             from (
                                            select "member_srl" as "member_srl"
                                                            , count(*) as "count" 
                                            from "xe_documents" as "documents" 
                                            group by "member_srl"
                                    ) as "documentcountbymember"';
                $this->_test($xml_file, $argsString, $expected);
        }  
        
//        function testFromUncorrelated2(){
//                $xml_file = $this->xmlPath . "from_uncorrelated1.xml";
//                $argsString = '$args->user_id = 4;
//                                $args->user_name = 7;
//                                ';
//                $expected = 'select max("documentcountbymember"."count") as "maxcount" 
//                             from (
//                                            select "member_srl" as "member_srl"
//                                                            , count(*) as "count" 
//                                            from "xe_documents" as "documents" 
//                                            group by "member_srl"
//                                    ) as "documentcountbymember"';
//                $this->_test($xml_file, $argsString, $expected);
//        }             
        
        function testFromUncorrelated2(){
                $xml_file = $this->xmlPath . "from_uncorrelated2.xml";
                $argsString = '$args->member_srl = 4;
                                $args->module_srl = 7;
                                ';
                $expected = 'select max("documentcountbymember"."count") as "maxcount" 
                             from (
                                            select "member_srl" as "member_srl"
                                                            , count(*) as "count" 
                                            from "xe_documents" as "documents" 
                                            where "module_srl" = 7
                                            group by "member_srl"
                                    ) as "documentcountbymember"
                             where "member_srl" = 4
                ';
                $this->_test($xml_file, $argsString, $expected);
        }         
        
        function testSelectCorrelated1(){
                $xml_file = $this->xmlPath . "select_correlated1.xml";
                $argsString = '$args->user_id = 7;';
                $expected = 'select *, 
                            (select count(*) as "count" 
                                from "xe_documents" as "documents" 
                                where "documents"."user_id" = "member"."user_id"
                             ) as "totaldocumentcount" 
                            from "xe_member" as "member" 
                            where "user_id" = \'7\'';
                $this->_test($xml_file, $argsString, $expected);
        }             

        function testSelectCorrelated2(){
                $xml_file = $this->xmlPath . "select_correlated2.xml";
                $argsString = '$args->user_id = 7;
                    $args->module_srl = 17;
                    ';
                $expected = 'select *, 
                            (select count(*) as "count" 
                                from "xe_documents" as "documents" 
                                where "documents"."user_id" = "member"."user_id"
                                    and "module_srl" = 17
                             ) as "totaldocumentcount" 
                            from "xe_member" as "member" 
                            where "user_id" = \'7\'';
                $this->_test($xml_file, $argsString, $expected);
        }           
        
        function testWhereCorrelated1(){
                $xml_file = $this->xmlPath . "where_correlated1.xml";
                $argsString = '';
                $expected = 'select * 
                             from "xe_member" as "member" 
                             where "regdate" = (
                                                select max("regdate") as "maxregdate" 
                                                from "xe_documents" as "documents"
                                                where "documents"."user_id" = "member"."user_id"
                                                )';
                $this->_test($xml_file, $argsString, $expected);
        }             
        
        function testWhereCorrelated2(){
                $xml_file = $this->xmlPath . "where_correlated2.xml";
                $argsString = '$args->module_srl = 12; $args->member_srl = 19;';
                $expected = 'select * 
                             from "xe_member" as "member" 
                             where "member_srl" = 19  
                                and "regdate" = (
                                                select max("regdate") as "maxregdate" 
                                                from "xe_documents" as "documents"
                                                where "documents"."user_id" = "member"."user_id"
                                                    and "module_srl" = 12
                                                )         
                                                ';
                $this->_test($xml_file, $argsString, $expected);
        }             
        
        function testFromCorrelated1(){
                $xml_file = $this->xmlPath . "from_correlated1.xml";
                $argsString = '';
                $expected = 'select "m"."member_srl"
                            , "m"."nickname"
                            , "m"."regdate"
                            , "a"."count" 
                            from (
                                select "member_srl" as "member_srl"
                                        , count(*) as "count" 
                                from "xe_documents" as "documents" 
                                group by "member_srl"
                               ) as "a" 
                               left join "xe_member" as "m" on "m"."member" = "a"."member_srl"';
                $this->_test($xml_file, $argsString, $expected);
        }     
        
        function testFromCorrelated2(){
                $xml_file = $this->xmlPath . "from_correlated2.xml";
                $argsString = '$args->module_srl = 12; $args->count = 20;';
                $expected = 'select "m"."member_srl"
                            , "m"."nickname"
                            , "m"."regdate"
                            , "a"."count" 
                            from (
                                select "member_srl" as "member_srl"
                                        , count(*) as "count" 
                                from "xe_documents" as "documents" 
                                where "module_srl" = 12
                                group by "member_srl"
                               ) as "a" 
                               left join "xe_member" as "m" on "m"."member" = "a"."member_srl"
                            where "a"."count" >= 20
';
                $this->_test($xml_file, $argsString, $expected);
        }            
    }
?>
