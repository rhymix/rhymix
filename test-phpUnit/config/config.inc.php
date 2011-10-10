<?php
	error_reporting(E_ALL ^ E_NOTICE);
	define('_XE_PATH_', str_replace('test-phpUnit/config/config.inc.php', '', str_replace('\\', '/', __FILE__)));
	define('_TEST_PATH_', _XE_PATH_ . 'test-phpUnit/');

	if(!defined('__DEBUG__')) define('__DEBUG__', 4);
        define('__ZBXE__', true);
        define('__XE__',   true);

	require_once(_XE_PATH_.'test-phpUnit/Helper.class.php');
        require_once(_XE_PATH_.'test-phpUnit/QueryTester.class.php');
	require_once(_XE_PATH_.'test-phpUnit/db/DBTest.php');
        require_once(_XE_PATH_.'test-phpUnit/db/CubridTest.php');
        require_once(_XE_PATH_.'test-phpUnit/db/CubridOnlineTest.php');
        require_once(_XE_PATH_.'test-phpUnit/db/MssqlTest.php');
        require_once(_XE_PATH_.'test-phpUnit/db/MssqlOnlineTest.php');
        require_once(_XE_PATH_.'test-phpUnit/db/MysqlTest.php');

        require_once(_XE_PATH_.'config/config.inc.php');
//	require_once(_XE_PATH_.'classes/object/Object.class.php');
//	require_once(_XE_PATH_.'classes/handler/Handler.class.php');
//	require_once(_XE_PATH_.'classes/context/Context.class.php');
//	require_once(_XE_PATH_.'classes/file/FileHandler.class.php');
//	require_once(_XE_PATH_.'classes/xml/XmlParser.class.php');
	require_once(_XE_PATH_.'classes/xml/XmlQueryParser.class.php');
//

	require_once(_XE_PATH_.'classes/db/DB.class.php');
	require_once(_XE_PATH_.'classes/db/DBCubrid.class.php');
	require_once(_XE_PATH_.'classes/db/DBMssql.class.php');
        require_once(_XE_PATH_.'classes/db/DBMysql.class.php');

    require_once(_XE_PATH_.'classes/xml/xmlquery/DBParser.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/argument/Argument.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/argument/SortArgument.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/argument/ConditionArgument.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/DefaultValue.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/Expression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/SelectExpression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/InsertExpression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/UpdateExpression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/UpdateExpressionWithoutArgument.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/table/Table.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/table/JoinTable.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/table/CubridTableWithHint.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/table/MysqlTableWithHint.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/table/MssqlTableWithHint.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/table/IndexHint.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/condition/ConditionGroup.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/condition/Condition.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/condition/ConditionWithArgument.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/condition/ConditionWithoutArgument.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/condition/ConditionSubquery.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/StarExpression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/order/OrderByColumn.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/limit/Limit.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/Query.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/Subquery.class.php');

    require_once(_XE_PATH_.'classes/xml/xmlquery/tags/table/TableTag.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/tags/table/HintTableTag.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/tags/condition/ConditionTag.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/QueryArgument.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/queryargument/SortQueryArgument.class.php');
?>