<?php
	error_reporting(E_ALL ^ E_NOTICE); 
	define('_XE_PATH_', str_replace('test-phpUnit/config.inc.php', '', str_replace('\\', '/', __FILE__)));
	//define('_TEST_PATH_', substr(_XE_PATH_,0,strrpos(substr(_XE_PATH_,0.-1),'/')));
	
	if(!defined('__DEBUG__')) define('__DEBUG__', 4);

	require_once(_XE_PATH_.'test-phpUnit/Helper.class.php');
	
	require_once(_XE_PATH_.'classes/object/Object.class.php');	
	require_once(_XE_PATH_.'classes/handler/Handler.class.php');	
	require_once(_XE_PATH_.'classes/context/Context.class.php');	
	require_once(_XE_PATH_.'classes/file/FileHandler.class.php');
	require_once('QueryTester.class.php');
	require_once(_XE_PATH_.'classes/xml/XmlParser.class.php');
	require_once(_XE_PATH_.'classes/xml/XmlQueryParser.class.php');
	
	
	require_once(_XE_PATH_.'classes/db/DB.class.php');
	require_once(_XE_PATH_.'classes/db/DBCubrid.class.php');
	
    require_once(_XE_PATH_.'classes/xml/xmlquery/DBParser.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/argument/Argument.class.php');
    require_once(_XE_PATH_.'classes/xml/xmlquery/argument/ConditionArgument.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/Expression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/SelectExpression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/InsertExpression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/UpdateExpression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/table/Table.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/table/JoinTable.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/condition/ConditionGroup.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/condition/Condition.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/expression/StarExpression.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/order/OrderByColumn.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/limit/Limit.class.php');
    require_once(_XE_PATH_.'classes/db/queryparts/Query.class.php');
	
?>