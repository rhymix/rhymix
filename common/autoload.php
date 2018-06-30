<?php

/**
 * Skip if Rhymix is already loaded.
 */
if (defined('RX_VERSION'))
{
	return;
}

/**
 * Set error reporting rules.
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

/**
 * Suppress date/time errors until the internal time zone is set (see below).
 */
date_default_timezone_set(@date_default_timezone_get());

/**
 * Set the default character encoding.
 */
ini_set('default_charset', 'UTF-8');
if (function_exists('iconv_set_encoding') && version_compare(PHP_VERSION, '5.6', '<'))
{
	iconv_set_encoding('internal_encoding', 'UTF-8');
}
if (function_exists('mb_internal_encoding'))
{
	mb_internal_encoding('UTF-8');
}
if (function_exists('mb_regex_encoding'))
{
	mb_regex_encoding('UTF-8');
}

/**
 * Load constants and common functions.
 */
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/legacy.php';

/**
 * Define the list of legacy class names for the autoloader.
 */
$GLOBALS['RX_AUTOLOAD_FILE_MAP'] = array_change_key_case(array(
	'CacheHandler' => 'classes/cache/CacheHandler.class.php',
	'Context' => 'classes/context/Context.class.php',
	'DB' => 'classes/db/DB.class.php',
	'Query' => 'classes/db/queryparts/Query.class.php',
	'Subquery' => 'classes/db/queryparts/Subquery.class.php',
	'Condition' => 'classes/db/queryparts/condition/Condition.class.php',
	'ConditionGroup' => 'classes/db/queryparts/condition/ConditionGroup.class.php',
	'ConditionSubquery' => 'classes/db/queryparts/condition/ConditionSubquery.class.php',
	'ConditionWithArgument' => 'classes/db/queryparts/condition/ConditionWithArgument.class.php',
	'ConditionWithoutArgument' => 'classes/db/queryparts/condition/ConditionWithoutArgument.class.php',
	'ClickCountExpression' => 'classes/db/queryparts/expression/ClickCountExpression.class.php',
	'DeleteExpression' => 'classes/db/queryparts/expression/DeleteExpression.class.php',
	'Expression' => 'classes/db/queryparts/expression/Expression.class.php',
	'InsertExpression' => 'classes/db/queryparts/expression/InsertExpression.class.php',
	'SelectExpression' => 'classes/db/queryparts/expression/SelectExpression.class.php',
	'StarExpression' => 'classes/db/queryparts/expression/StarExpression.class.php',
	'UpdateExpression' => 'classes/db/queryparts/expression/UpdateExpression.class.php',
	'UpdateExpressionWithoutArgument' => 'classes/db/queryparts/expression/UpdateExpressionWithoutArgument.class.php',
	'Limit' => 'classes/db/queryparts/limit/Limit.class.php',
	'OrderByColumn' => 'classes/db/queryparts/order/OrderByColumn.class.php',
	'IndexHint' => 'classes/db/queryparts/table/IndexHint.class.php',
	'JoinTable' => 'classes/db/queryparts/table/JoinTable.class.php',
	'MysqlTableWithHint' => 'classes/db/queryparts/table/MysqlTableWithHint.class.php',
	'Table' => 'classes/db/queryparts/table/Table.class.php',
	'DisplayHandler' => 'classes/display/DisplayHandler.class.php',
	'HTMLDisplayHandler' => 'classes/display/HTMLDisplayHandler.php',
	'JSCallbackDisplayHandler' => 'classes/display/JSCallbackDisplayHandler.php',
	'JSONDisplayHandler' => 'classes/display/JSONDisplayHandler.php',
	'VirtualXMLDisplayHandler' => 'classes/display/VirtualXMLDisplayHandler.php',
	'XMLDisplayHandler' => 'classes/display/XMLDisplayHandler.php',
	'EditorHandler' => 'classes/editor/EditorHandler.class.php',
	'ExtraVar' => 'classes/extravar/Extravar.class.php',
	'ExtraItem' => 'classes/extravar/Extravar.class.php',
	'FileHandler' => 'classes/file/FileHandler.class.php',
	'FileObject' => 'classes/file/FileObject.class.php',
	'FrontEndFileHandler' => 'classes/frontendfile/FrontEndFileHandler.class.php',
	'Handler' => 'classes/handler/Handler.class.php',
	'XEHttpRequest' => 'classes/httprequest/XEHttpRequest.class.php',
	'Mail' => 'classes/mail/Mail.class.php',
	'Mobile' => 'classes/mobile/Mobile.class.php',
	'ModuleHandler' => 'classes/module/ModuleHandler.class.php',
	'ModuleObject' => 'classes/module/ModuleObject.class.php',
	'PageHandler' => 'classes/page/PageHandler.class.php',
	'EmbedFilter' => 'classes/security/EmbedFilter.class.php',
	'IpFilter' => 'classes/security/IpFilter.class.php',
	'Password' => 'classes/security/Password.class.php',
	'Purifier' => 'classes/security/Purifier.class.php',
	'Security' => 'classes/security/Security.class.php',
	'UploadFileFilter' => 'classes/security/UploadFileFilter.class.php',
	'TemplateHandler' => 'classes/template/TemplateHandler.class.php',
	'Validator' => 'classes/validator/Validator.class.php',
	'WidgetHandler' => 'classes/widget/WidgetHandler.class.php',
	'GeneralXmlParser' => 'classes/xml/GeneralXmlParser.class.php',
	'Xml_Node_' => 'classes/xml/XmlParser.class.php',
	'XmlGenerator' => 'classes/xml/XmlGenerator.class.php',
	'XmlJsFilter' => 'classes/xml/XmlJsFilter.class.php',
	'XmlLangParser' => 'classes/xml/XmlLangParser.class.php',
	'XmlParser' => 'classes/xml/XmlParser.class.php',
	'XmlQueryParser' => 'classes/xml/XmlQueryParser.class.php',
	'DBParser' => 'classes/xml/xmlquery/DBParser.class.php',
	'QueryParser' => 'classes/xml/xmlquery/QueryParser.class.php',
	'Argument' => 'classes/xml/xmlquery/argument/Argument.class.php',
	'ConditionArgument' => 'classes/xml/xmlquery/argument/ConditionArgument.class.php',
	'SortArgument' => 'classes/xml/xmlquery/argument/SortArgument.class.php',
	'DefaultValue' => 'classes/xml/xmlquery/queryargument/DefaultValue.class.php',
	'QueryArgument' => 'classes/xml/xmlquery/queryargument/QueryArgument.class.php',
	'SortQueryArgument' => 'classes/xml/xmlquery/queryargument/SortQueryArgument.class.php',
	'QueryArgumentValidator' => 'classes/xml/xmlquery/queryargument/validator/QueryArgumentValidator.class.php',
	'ColumnTag' => 'classes/xml/xmlquery/tags/column/ColumnTag.class.php',
	'InsertColumnTag' => 'classes/xml/xmlquery/tags/column/InsertColumnTag.class.php',
	'InsertColumnTagWithoutArgument' => 'classes/xml/xmlquery/tags/column/InsertColumnTagWithoutArgument.class.php',
	'InsertColumnsTag' => 'classes/xml/xmlquery/tags/column/InsertColumnsTag.class.php',
	'SelectColumnTag' => 'classes/xml/xmlquery/tags/column/SelectColumnTag.class.php',
	'SelectColumnsTag' => 'classes/xml/xmlquery/tags/column/SelectColumnsTag.class.php',
	'UpdateColumnTag' => 'classes/xml/xmlquery/tags/column/UpdateColumnTag.class.php',
	'UpdateColumnsTag' => 'classes/xml/xmlquery/tags/column/UpdateColumnsTag.class.php',
	'ConditionGroupTag' => 'classes/xml/xmlquery/tags/condition/ConditionGroupTag.class.php',
	'ConditionTag' => 'classes/xml/xmlquery/tags/condition/ConditionTag.class.php',
	'ConditionsTag' => 'classes/xml/xmlquery/tags/condition/ConditionsTag.class.php',
	'JoinConditionsTag' => 'classes/xml/xmlquery/tags/condition/JoinConditionsTag.class.php',
	'GroupsTag' => 'classes/xml/xmlquery/tags/group/GroupsTag.class.php',
	'IndexTag' => 'classes/xml/xmlquery/tags/navigation/IndexTag.class.php',
	'LimitTag' => 'classes/xml/xmlquery/tags/navigation/LimitTag.class.php',
	'NavigationTag' => 'classes/xml/xmlquery/tags/navigation/NavigationTag.class.php',
	'QueryTag' => 'classes/xml/xmlquery/tags/query/QueryTag.class.php',
	'HintTableTag' => 'classes/xml/xmlquery/tags/table/HintTableTag.class.php',
	'TableTag' => 'classes/xml/xmlquery/tags/table/TableTag.class.php',
	'TablesTag' => 'classes/xml/xmlquery/tags/table/TablesTag.class.php',
	'Bmp' => 'common/libraries/bmp.php',
	'Ftp' => 'common/libraries/ftp.php',
	'Tar' => 'common/libraries/tar.php',
	'CryptoCompat' => 'common/libraries/cryptocompat.php',
	'VendorPass' => 'common/libraries/vendorpass.php',
), CASE_LOWER);

/**
 * Define the autoloader.
 */
spl_autoload_register(function($class_name)
{
	$filename = false;
	$lc_class_name = str_replace('\\', '/', strtolower($class_name));
	switch (substr($lc_class_name, 0, 10))
	{
		// Rhymix Framework classes.
		case 'rhymix/fra':
			$filename = RX_BASEDIR . 'common/framework/' . substr($lc_class_name, 17) . '.php';
			break;
		// Rhymix Plugin classes.
		case 'rhymix/plu':
			$filename = RX_BASEDIR . 'plugins/' . substr($lc_class_name, 15) . '.php';
			break;
		// XE compatible classes.
		default:
			if (isset($GLOBALS['RX_AUTOLOAD_FILE_MAP'][$lc_class_name]))
			{
				$filename = RX_BASEDIR . $GLOBALS['RX_AUTOLOAD_FILE_MAP'][$lc_class_name];
			}
			elseif (preg_match('/^([a-zA-Z0-9_]+?)(Admin)?(View|Controller|Model|Item|Api|Wap|Mobile)?$/', $class_name, $matches))
			{
				$filename = RX_BASEDIR . 'modules/' . strtolower($matches[1] . '/' . $matches[1]);
				if (isset($matches[2]) && $matches[2]) $filename .= '.admin';
				$filename .= (isset($matches[3]) && $matches[3]) ? ('.' . strtolower($matches[3])) : '.class';
				$filename .= '.php';
			}
	}
	if ($filename && file_exists($filename))
	{
		include $filename;
	}
});

/**
 * Also include the Composer autoloader.
 */
require_once RX_BASEDIR . 'vendor/autoload.php';

/**
 * Load essential classes.
 */
require_once RX_BASEDIR . 'classes/object/Object.class.php';

/**
 * Load user configuration.
 */
if(file_exists(RX_BASEDIR . 'config/config.user.inc.php'))
{
	require_once RX_BASEDIR . 'config/config.user.inc.php';
}

/**
 * Load system configuration.
 */
Rhymix\Framework\Config::init();

/**
 * Install the debugger.
 */
Rhymix\Framework\Debug::registerErrorHandlers(error_reporting());

/**
 * Set the internal timezone.
 */
$internal_timezone = Rhymix\Framework\DateTime::getTimezoneNameByOffset(config('locale.internal_timezone'));
date_default_timezone_set($internal_timezone);

/**
 * Initialize the cache handler.
 */
Rhymix\Framework\Cache::init(Rhymix\Framework\Config::get('cache'));
