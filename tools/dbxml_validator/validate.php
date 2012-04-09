#!/bin/env php
<?php
/**
    @file

    Script to validate a query or a SQL statement written in the 
    XpressEngine XML Query Language or the XML Schema language.
    
    XpressEngine is an open source framework for creating your web sites.
    http://xpressengine.org/

    @Author: Adrian Constantin, Arnia Software (adrian.constantin@arnia.ro)
    @Date:   12 mar 2012

    The validation is based on, and is meant to model, the behavior exposed
    by the php classes in classes/xml/xmlquery/ and class/db/queryparts/
    in the XE installation directory.

    Usage:
	validate.php query-file.xml query-file.xml ...
	    or
	validate.php schema-definition.xsd query-file.xml ...
*/

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

/// @brief callback to turn a php error into a php exception
/// So now any error interrupts or terminates script execution
function exception_error_handler($errno, $errstr, $errfile, $errline)
{
    // exit on error
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

// set_error_handler("exception_error_handler");

// Error reporting classes/functions

/// Exception class for user error messages.
class ErrorMessage extends Exception
{
}

/// Error message class to signal and carry
/// the command-line usage description (string) for the script
class SyntaxError extends ErrorMessage
{
}

/// Error in an XML query
class XmlSchemaError extends ErrorMessage
{
    public $xml_file;
    public $xml_line_no;
    public $xml_message;

    /** Composes a message in the format:

	<pre>
	file_name (line_no):
	    message
	</pre>
    */
    public function __construct($file, $line_no, $message)
    {
	parent::__construct("{$file}({$line_no}):\n\t$message");

	$this->xml_file	= $file;
	$this->xml_line_no = $line_no;
	$this->xml_message = $message;
    }
}

/// Clean up libxml errors list when going out of scope (on the destructor)
class LibXmlClearErrors
{
    public function __destruct()
    {
	libxml_clear_errors();
    }
}

function libXmlDisplayError($filename = null, $throw_error = false)
{
    // set up clean-up call to libxml_clear_errors()
    $libXmlClearErrors = new LibXmlClearErrors();

    $libXmlErrors = libxml_get_errors();

    if (count($libXmlErrors))
    {
	if (!$filename)
	    $filename = $libXmlErrors[0]->file;

	$msg = '';

	foreach ($libXmlErrors as $libXmlError)
	{
	    $msg .=

<<<string_delimiter
{$libXmlError->file}({$libXmlError->line}):\n\t {$libXmlError->message}
string_delimiter;
	}

	if ($throw_error)
	    throw new ErrorMessage ($msg);
	else
	    fwrite(STDERR, $msg . "\n");
    }
    else
	if ($throw_error)
	    throw new ErrorMessage('Schema validation failed.');
}

/**
 Checks an XML node for duplicate descendants of a give tag.
 Throws XmlSchemaError if duplicates found.
 */
function checkDuplicateDescendants($xml_file, $node, $child_tag)
{
    $children = $node->getElementsByTagName($child_tag);

    if ($children->length > 1)
	throw 
	    new XmlSchemaError
	    (
		$xml_file,
		$children->item(1)->getLineNo(),
		"Duplicate <{$child_tag}> elements."
	    );
}

/**
 Checks the XML child nodes for unique/key values of one (or more)
 attribute(s)

 @param xml_file
    Name of file with the XML node to be checked. Used in the error
    messages.
 @param node
    The XML node with the children to be checked.
 @param child_tags
    Array with tag names for the children elements
 @param attr_tags
    Array with names of attributes to be checked. If multiple attributes
    are given, than the first one that is present on a child is included
    in the check.
 @param key
    True if child elements are required to expose at least one of the 
    attribute. False if only the child nodes with some of the
    attributes present are to be checked.
*/
function checkUniqueKey($xml_file, $node, $child_tags, $attr_tags, $key)
{
    $key_values = array();

    foreach ($node->childNodes as $child_node)
	if
	    (
		$child_node->nodeType == XML_ELEMENT_NODE
		    &&
		in_array($child_node->tagName, $child_tags)
	    )
	{
	    $key_value = null;

	    foreach ($attr_tags as $attr_tag)
		if ($child_node->hasAttribute($attr_tag))
		{
		    $key_value = $child_node->getAttribute($attr_tag);

		    if (array_key_exists($key_value, $key_values))
			throw
			    new XmlSchemaError
			    (
				$xml_file,
				$child_node->getLineNo(),
				"Duplicate {$attr_tag} found in <{$node->tagName}>."
			    );

		    $key_values[$key_value] = true;
		    break;
		}

	    if (!$key_value && $key)
		throw
		    new XmlSchemaError
		    (
			$xml_file,
			$child_node->getLineNo(),
			"<{$child_node->tagName}>: at least one of the following attributes is expected: "
			    .
			implode(', ', $attr_tags) . '.'
		    );
	}
}

/**
  Checks a SQL table-expression in the FROM clause. The table
  should be either:
    - a named table. If the table is the right table of a join,
      then the join conditions should be given as content.
    - a sub-query. No table name should be given, table alias
      should be present and query attribute should be present
      and have the value "true". Content should include at
      least a select list or a table specification
 */
function checkTableExpression($xml_file, $table_element)
{
    $table_name = null;
    $join_type = null;

    if ($table_element->hasAttribute('name'))
	$table_name = $table_element->getAttribute('name');

    if ($table_element->hasAttribute('type'))
	$join_type = $table_element->getAttribute('type');

    if ($table_element->getAttribute('query') == 'true')
    {
	if ($table_name !== null)
	    throw
		new XmlSchemaError
		(
		    $xml_file,
		    $table_element->getLineNo(),
		    'Subqueries should only use aliases, not names'
		);

	if ($join_type !== null)
	    throw
		new XmlSchemaError
		(
		    $xml_file,
		    $table_element->getLineNo(),
		    'Currently subqueries may not be used as '
			.
		    'the right side table in a join'
		);
	// table alias is already checked by the unique key constraint on
	// the (alias or name) key on the tables element

	// check contents for a select list or a table-specification
	$has_query_clauses = false;

	foreach ($table_element->childNodes as $query_clause)
	    if
		(
		    $query_clause->nodeType == XML_ELEMENT_NODE
			&&
		    (
			$query_clause->tagName == 'columns' 
			    ||
			$query_clause->tagName == 'tables'
		    )
		)
	    {
		$has_query_clauses = true;
	    }

	if (!$has_query_clauses)
	    throw
		new XmlSchemaError
		(
		    $xml_file,
		    $table_element->getLineNo(),
		    'Subquery tables should have at least a select list or a table specification.'
			.
		    "\nANSI SQL-99 declares the table specification as required."
		);
    }
    else
    {
	// base table or view

	if ($join_type !== null)
	{
	    $has_conditions_element = false;

	    foreach ($table_element->childNodes as $child_node)
		if ($child_node->nodeType == XML_ELEMENT_NODE)
		    if ($child_node->tagName == 'conditions')
			if ($has_conditions_element)
			    throw
				new XmlSchemaError
				(
				    $xml_file,
				    $child_node->getLineNo(),
				    'Duplicate <conditions> elements.'
				);
			else
			    $has_conditions_element = true;
		    else
			throw
			    new XmlSchemaError
			    (
				$xml_file,
				$child_node->getLineNo(),
				'<conditions> element must be the only content for a joined <table>.'
			    );

	    if (!$has_conditions_element)
		throw
		    new XmlSchemaError
		    (
			$xml_file,
			$table_element->getLineNo(),
			'Expected <conditions> element as content.'
		    );
	}
	else
	    foreach ($table_element->childNodes as $child_node)
		if ($child_node->nodeType == XML_ELEMENT_NODE)
		    throw
			new XmlSchemaError
			(
			    $xml_file,
			    $child_node->getLineNo(),
			    '<table> element can only have content if it is a sub-query or is joined.'
			);
    }
}

/**
  All table names and aliases should be distinct throughout 
  the <tables> element.

  Subquery tables should be valid queries.
 */
function checkTablesClause($xml_file, $tables_element)
{
    checkUniqueKey
	(
	    $xml_file,
	    $tables_element,
	    array('table'),		// child elements to be checked
	    array('alias', 'name'),	// attributes to be checked
	    true			// attributes are required
	);

    foreach ($tables_element->childNodes as $table)
	if
	    (
		$table->nodeType == XML_ELEMENT_NODE
		    &&
		$table->tagName == 'table'
	    )
	{
	    checkTableExpression($xml_file, $table);

	    if ($table->getAttribute('query') == 'true')
		validate_select_query($xml_file, $table);	// recursive call
	}
}

/**
 Table columns in a select-list should be unique. This is not a
 requirement by the SQL language, and it can be commented out
 below, but it is still common sense.

 Some of the "columns" here are actually small expressions, but
 they can still be included literally in the unique constraint.
 */
function checkSelectListClause($xml_file, $columns_element)
{
    checkUniqueKey
	(
	    $xml_file,
	    $columns_element,
	    array('column', 'query'),	// child elements
	    array('alias', 'name'),	// attributes
	    false			// ignore if no attributes present
	);
}

/** Check that attributes for variable contents validation
    (filter, notnull, minlength, maxlength) are present only if
    the var attribute is present. */
function checkVarContentsValidation
    (
	$xml_file,
	$container_element,
	$child_tag
    )
{
    static
	$key_attr = 'var';

    static
	$var_attrs =
	    array
	    (
		'filter', 'notnull', 'minlength',
		'maxlength'
	    );

    foreach ($container_element->childNodes as $child_node)
	if
	    (
		$child_node->nodeType == XML_ELEMENT_NODE
		    &&
		$child_node->tagName == $child_tag
	    )
	{
	    if (!$child_node->hasAttribute($key_attr))
		foreach ($var_attrs as $var_attr)
		    if ($child_node->hasAttribute($var_attr))
			throw
			    new XmlSchemaError
			    (
				$xml_file,
				$child_node->getLineNo(),
				"<{$child_node->tagName}>: Attribute '{$var_attr}' "
				    .
				"should only be used with the '{$key_attr}' attribute."
			    );
	}
}

/** Checks that a subquery condition does not have a var or default attribute. */
function checkConditionElement($xml_file, $condition)
{
    $child_query_node = false;
    $has_var_attribute = $condition->hasAttribute('var') || $condition->hasAttribute('default');
    $query_line_no = -1;

    foreach ($condition->childNodes as $query_node)
	if
	    (
		$query_node->nodeType == XML_ELEMENT_NODE
		    &&
		$query_node->tagName == 'query'
	    )
	{
	    validate_select_query($xml_file, $query_node);

	    $child_query_node = true;
	    $query_line_no = $query_node->getLineNo();
	}

    if ($child_query_node && $has_var_attribute)
	throw 
	    new XmlSchemaError
	    (
		$xml_file,
		$query_line_no,
		"<query> element found when <condition> has a 'var' or 'default' attribute."
	    );

    if (!($child_query_node || $has_var_attribute))
	throw
	    new XmlSchemaError
	    (
		$xml_file,
		$condition->getLineNo(),
		"<condition>: either a <query> child, 'var' attribute or 'default' attribute expected."
	    );
}


/**
 Checks that conditions have the pipe attribute, and that variable-contents-validation
 attributes are only present if var attribute is present.

 Also recurses into condition groups and expression subqueries
 */
function checkConditionsGroup($xml_file, $conditions)
{
    $first_child = true;

    foreach ($conditions->childNodes as $child_node)
	if ($child_node->nodeType == XML_ELEMENT_NODE)
	{
	    // check for 'pipe' attribute
	    if ($first_child)
		$first_child = false;
	    else
		if (!$child_node->hasAttribute('pipe'))
		    throw
			new XmlSchemaError
			(
			    $xml_file,
			    $child_node->getLineNo(),
			    'Attribute pipe expected for all but the first element'
				.
			    " in <{$conditions->tagName}> content."
			);

	    // recurse in condition groups/queries
	    if ($child_node->tagName == 'group')
		checkConditionsGroup($xml_file, $child_node);
	    else
		if ($child_node->tagName == 'query')
		    validate_select_query($xml_file, $child_node);
		else
		    if ($child_node->tagName == 'condition')
			checkConditionElement($xml_file, $child_node);
	}

	// check variable contents validation attributes
	checkVarContentsValidation($xml_file, $conditions, 'condition');
}

/**
  Ensure at most one <list_count>, <page_count> and
  <page> elements are present. There can be any number of 
  <index> elements listed.
 */
function checkNavigationClauses($xml_file, $navigation_element)
{
    foreach
	(
	    array('list_count', 'page_count', 'page')
		as
	    $navigation_el
	)
    {
	checkDuplicateDescendants
	    (
		$xml_file,
		$navigation_element,
		$navigation_el
	    );
    }
}

/**
 Additional checks to validate a query XML, that can not
 be properly expressed in the schema definition (.xsd) file
 for the query.

 Most likely the conditions explicitly coded and checked for
 here can also be expressed as XPath queries.
 */
function validate_select_query($xml_file, $query_element)
{
    foreach ($query_element->childNodes as $select_clause)
	if ($select_clause->nodeType == XML_ELEMENT_NODE)
	    switch ($select_clause->tagName)
	    {
	    case 'columns':
		checkSelectListClause($xml_file, $select_clause);
		break;
	    case 'tables':
		checkTablesClause($xml_file, $select_clause);
		break;
	    case 'conditions':
		checkConditionsGroup($xml_file, $select_clause);
		break;
	    case 'navigation':
		checkNavigationClauses($xml_file, $select_clause);
		break;
	    }
}

function validate_update_query($xml_file, $query_element)
{
    foreach ($query_element->childNodes as $update_clause)
	if ($update_clause->nodeType == XML_ELEMENT_NODE)
	    switch ($update_clause->tagName)
	    {
	    case 'tables':
		checkTablesClause($xml_file, $update_clause);
		break;
	    case 'conditions':
		checkConditionsGroup($xml_file, $update_clause);
		break;
	    }
}

function validate_delete_query($xml_file, $query_element)
{
    foreach ($query_element->childNodes as $delete_clause)
	if ($delete_clause->nodeType == XML_ELEMENT_NODE)
	    switch ($delete_clause->tagName)
	    {
	    case 'conditions':
		checkConditionsGroup($xml_file, $delete_clause);
		break;
	    }
}

function validate_insert_select_query($xml_file, $query_element)
{
    foreach ($query_element->childNodes as $statement_clause)
	if ($statement_clause->nodeType == XML_ELEMENT_NODE)
	    switch ($statement_clause->tagName)
	    {
	    case 'query':
		validate_select_query($xml_file, $statement_clause);
		break;
	    }
}

$validate_query_type = 
    array
	(
	    // 'insert' =>	
			    // there is currently nothing special to check
			    // for a plain insert, all the needed checks
			    // are already expressed in the .xsd
	    'insert-select' => 'validate_insert_select_query',
	    'update' => 'validate_update_query',
	    'select' => 'validate_select_query',
	    'delete' => 'validate_delete_query'
	);

function validate_xml_query($xml_file, $query_element)
{
    global $validate_query_type;

    $action = $query_element->getAttribute('action');

    if (array_key_exists($action, $validate_query_type))
	$validate_query_type[$action]($xml_file, $query_element);
}

if
    (
	strpos(PHP_SAPI, 'cli') !== FALSE
	    || 
	strpos(PHP_SAPI, 'cgi') !== FALSE
    )
{
    /** Saves working directory and restores it upon destruction.
	Only use with single-threaded php SAPIs like CLI. */
    class RestoreWorkDir
    {
	protected $dirname;

	public function __destruct()
	{
	    try
	    {
		$success = chdir($this->dirname);
	    }
	    catch (Exception $e)
	    {
		print "Failed to restore working dir {$this->dirname}.";
	    }

	    if (!$success)
		print "Failed to restore working dir {$this->dirname}.";
	}

	public function __construct()
	{
	    $this->dirname = getcwd();

	    if (!$this->dirname)
		throw new ErrorMessage("Failed to get current directory.");
	}
    }
}

/** Checks that the query_id is the same as the given file name.
    For portability with case-sensitive file systems, the 
    actual casing of the file name from the file system is used,
    and the subsequent string comparatin is case-sensitive.
    
    Assumes the file is known to exist (has already been opened). */
function validate_query_id($xml_file, $query_id)
{
    $xml_path_info = pathinfo($xml_file);

    $filename_len = strlen($xml_path_info['basename']);
    $lowercase_name = strtolower($xml_path_info['basename']);
    $uppercase_name = strtoupper($xml_path_info['basename']);

    if
	(
	    strlen($lowercase_name) != $filename_len
		||
	    strlen($uppercase_name) != $filename_len
	)
    {
	// multi-byte encodings may result in a different number of characters
	// in the two strings
	throw new ErrorMessage("Unsupported file name encoding.");
    }

    // transform the given file name into a case-insensitive glob() pattern
	     
    $varing_case_filename = '';

    for ($i = 0; $i < $filename_len; $i++)
	if ($lowercase_name[$i] != $uppercase_name[$i])
	    $varing_case_filename .= "[{$lowercase_name[$i]}{$uppercase_name[$i]}]";
	else
	    $varing_case_filename .= $lowercase_name[$i];

    $glob_pattern = $xml_path_info['dirname'];

    $restoreWorkDir = new RestoreWorkDir();

    if ($glob_pattern)
    {
	// change current dir to the xml file directory to keep
	// glob pattern shorter (maximum 260 chars).
	$success = chdir($glob_pattern);

	if (!$success)
	    throw new ErrorMessage("Failed to change work dir to {$glob_pattern}.");
    }

    $glob_pattern = $varing_case_filename;

    // use glob() to get the file name from the file system
    // realpath() would have the same effect, but it is not documented as such
    $matched_files = glob($glob_pattern, GLOB_NOSORT | GLOB_NOESCAPE | GLOB_ERR);

    unset($RestoreWorkDir);	// restore work dir after call to glob()

    if ($matched_files === FALSE || !is_array($matched_files))
	throw new ErrorMessage("Directory listing for $xml_file failed.");

    switch (count($matched_files))
    {
    case 0:
	throw new ErrorMessage("Directory listing for $xml_file failed.");
    case 1:
	return (pathinfo($matched_files[0], PATHINFO_FILENAME) == $query_id);
    default:
	// more than one files with the same name and different case
	// case-sensitive file system
	foreach ($mached_files as $matched_file)
	    if
		(
		    pathinfo($matched_file, PATHINFO_BASENAME)
			==
		    $xml_path_info['basename']
		)
	    {
		return ($xml_path_info['filename'] == $query_id);
	    }

	throw new ErrorMessage("Directory listing for $xml_file failed.");
    }

    throw new ErrorMessage("Internal application error.");  // unreachable
}

/** Validate a table definition in the XML Schema Language.
    Check that the size attributes is only given for FLOAT and [VAR]CHAR
    types, and that it is always present for VARCHAR.

    Check that auto_increment is only given for (big)number types.
    
    Check for CUBRID-only/mysql+MSsql-only attributes 'auto_increment'
    and 'tinytext'. */
function validate_schema_doc($xml_file, $table_element)
{
    foreach ($table_element->childNodes as $col_node)
	if
	    (
		$col_node->nodeType == XML_ELEMENT_NODE
		    &&
		$col_node->tagName == 'column'
	    )
	{
	    $col_type = $col_node->getAttribute('type');
	    $col_size = NULL;

	    // check auto-increment column
	    if ($col_node->hasAttribute('auto_increment'))
	    {
		fwrite
		    (
			fopen('php://stdout', 'wt'), 
			$xml_file . '(' . $col_node->getLineNo() . ")\n\t"
			    .
			"<column>: attribute 'auto_increment' is currently supported only by SQL Server and mysql backends.\n"
		    );

		static
		    $autoinc_types = array('number', 'bignumber');

		if (!in_array($col_type, $autoinc_types))
		    throw
			new XmlSchemaError
			(
			    $xml_file,
			    $col_node->getLineNo(),
			    "<column>: attribute 'auto_increment' only expected for one of the following types: "
				.
			    implode(', ', $autoinc_types) . '.'
			);
	    }

	    // check tinytext
	    if ($col_type == 'tinytext')
		fwrite
		    (
			fopen('php://stdout', 'wt'), 
			$xml_file . '(' . $col_node->getLineNo() . ")\n\t"
			    .
			"<column>: type \"tinytext\" is supported only by CUBRID.\n"
		    );

	    // check size attribute
	    if ($col_node->hasAttribute('size'))
		$col_size = $col_node->getAttribute('size');

	    if ($col_type == 'varchar' && $col_size === NULL)
		throw 
		    new XmlSchemaError
			(
			    $xml_file,
			    $col_node->getLineNo(),
			    "<column>: 'size' attribute expected for \"varchar\" type."
			);

	    static
		$varsize_types = array('char', 'varchar', 'float');
		    

	    if ($col_size !== NULL  && !in_array($col_type, $varsize_types))
		throw 
		    new XmlSchemaError
			(
			    $xml_file,
			    $col_node->getLineNo(),
			    "<column>: 'size' attribute only expected for the following types: "
				.
			    implode(', ', $varsize_types) ."."
			);
	}
}

/**
  Class to accumulate the highest return code when multiple files are being
  processed, list return codes, and save/restore the code as needed.
  
  Use specific error codes depending on the validation stage and results,
  so the unit tests can tell what validation step has failed. */
class returnCode
{
    protected $save;
    protected $exit_code;

    const RETCODE_VALIDATOR_INTERNAL = 60;
    const RETCODE_GENERIC_XML_SYNTAX = 50;
    const RETCODE_QUERY_ELEMENT = 40;
    const RETCODE_XSD_VALIDATION = 30;
    const RETCODE_BUILTIN_CHECKS =    20;
    const RETCODE_DB_SCHEMA_MATCH =10;	// no schema match is currently implemented.
    const RETCODE_SUCCESS = 0;


    public function code($val = -1)
    {
	if ($val == -1)
	    return $this->exit_code;
	else
	    if ($this->exit_code < $val)
		$this->exit_code = $val;
    }

    public function push($val)
    {
	$this->save = $this->exit_code;
	$this->code($val);
    }

    public function pop()
    {
	$this->exit_code = $this->save;
	$this->save = self::RETCODE_VALIDATOR_INTERNAL;
    }

    public function __construct($val = 0)
    {
	$this->save = self::RETCODE_VALIDATOR_INTERNAL;
	$this->exit_code = $val;
    }
}

class UnlinkFile
{
    public $file_name;

    public function __destruct()
    {
	if ($this->file_name)
	{
	    unlink($this->file_name);
	    $this->file_name = NULL;
	}
    }

    public function __construct($file_name)
    {
	$this->file_name = $file_name;
    }
}

// main program entry point

try
{
    // Explicitly set time zone, to silence some php warning about it
    date_default_timezone_set('Europe/Bucharest');

    define('CMD_NAME', basename($argv[0]));
    $cmdname = CMD_NAME;

    // php manual says resources should not normally be declared constant
    if (!defined('STDERR'))
	define('STDERR', fopen('php://stderr', 'wt'));

    if (!defined('__DIR__'))
	define('__DIR__', dirname(__FILE__)); 


    $retcode = new returnCode(returnCode::RETCODE_SUCCESS);
    $schema_language = NULL;
    $skip_query_id = NULL;
    $xe_path = NULL;
    $validate_only = NULL;
    $query_args = NULL;
    $query_args_file = NULL;

    while ($argc >= 2 && $argv[1][0] == '-')
    {
	$option = $argv[1];

	unset($argv[1]);
	$argv = array_values($argv);
	$argc = count($argv);

	switch($option)
	{
	case '-s':
	case '--schema':
	case '--schema-language':
	    if ($query_args !== NULL)
		throw new SyntaxError("Both --args-string and --schema-language options given.");

	    if ($query_args_file !== NULL)
		throw new SyntaxError("Both --args-file and --schema-language options given.");
	    $schema_language = TRUE;
	    break;

	case '--skip-query-id':
	    $skip_query_id = TRUE;
	    break;

	case '--validate-only':
	    $validate_only = TRUE;
	    break;

	case '--xe-path':
	case '--xe':
	    if ($argc < 2)
		throw
		    new SyntaxError("Option '{$option}' requires an argument., see `{$cmdname} --help`");

	    $xe_path = $argv[1];

	    unset($argv[1]);
	    $argv = array_values($argv);
	    $argc = count($argv);
	    break;

	case '--arguments-string':
	case '--args-string':
	case '--arguments':
	case '--args':
	    if ($schema_language !== NULL)
		throw new SyntaxError("Both --schema-language and --args-string options given.");

	    if ($query_args_file !== NULL)
		throw new SyntaxError("Both --args-string and --args-file options given.");

	    if ($argc < 2)
		throw
		    new SyntaxError("Option '{$option}' requires an argument., see `{$cmdname} --help`");

	    $query_args = $argv[1];

	    unset($argv[1]);
	    $argv = array_values($argv);
	    $argc = count($argv);
	    break;

	case '--arguments-file':
	case '--args-file':
	    if ($schema_language !== NULL)
		throw new SyntaxError("Both --schema-language and --args-file options given.");

	    if ($query_args !== NULL)
		throw new SyntaxError("Both --args-string and --args-file options given.");

	    if ($argc < 2)
		throw
		    new SyntaxError("Option '{$option}' requires an argument., see `{$cmdname} --help`");

	    $query_args_file = $argv[1];

	    unset($argv[1]);
	    $argv = array_values($argv);
	    $argc = count($argv);
	    break;
	
	case '--help':
	case '--usage':
	case '/?':
	case '-?':
	case '-h':
	case '--':
	    // break out of both the switch 
	    // and while statements
	    break 2;
	default:
	    throw
		new SyntaxError("Unknown option $option, see {$cmdname} --help.");
	}
    }

    if
	(
	    $argc < 2
		||
	    (
		$argc == 2
		    &&
		in_array($argv[1], array('--help', '--usage', '/?', '-?', '-h'))
	    )
	)
    {
	throw
	    new SyntaxError
	    (
<<<string_delimiter
Validates an XML document against a given schema definition (XSD), using the standard php library.
Syntax:
    {$cmdname} schema.xsd document.xml...
    {$cmdname} [ --schema-language ] [--skip-query-id] ... [--] document.xml...
Where:
    --schema-language
    --schema
    -s
	If given, the document(s) are validated against XE XML Schema Language,
	otherwise document(s) are validated against XE XML Query Language.

    --skip-query-id
	Do not check the query id, which should normally match the file name.

    --xe-path
    --xe
	Path to XE installation. Used to load the database-specific parsers to generate
	SQL from the XML language files.

    --validate-only
	Only check XML schemas, no SQL generated with the database-specific parsers.

    --args-string   " 'name' => 'val..', 'name' => 'val...' "
    --args-file	    args/file/name.php
	Variables and values for the query, if it has any (only for XML Query Language).
	Use a comma-separated 'var-name' => 'var_value...' pairs, in php syntax for an
	array constructor. The validator script will directly eval()/include() this content.
	The file named with --args-file should include an array() constructor around the
	name-value list, and should immediately return it, without a named array variable.
	E.g.:
	    return 
		array
		    (
			'name' => 'val',
			'name' => 'val',
			...
		    );


    schema.xsd	    if given, is the file name for the schema definition to validate the
		    document against

    document.xml    is the file name for the XML document to be validated against the schema.
		    Multiple .xml files can be given.
string_delimiter
	    );
    }

    $query_user_args = array();

    // check $xe_path, $query_args
    if (!$validate_only)
    {
	if ($xe_path == NULL)
	{
	    // assume validator.php is in directory .../xe/tools/dbxml_validator/ in an XE installation
	    $xe_path = dirname(dirname(realpath(__DIR__)));
	}

	if (!file_exists($xe_path . '/index.php'))
	    throw
		new ErrorMessage("File index.php not found in {$xe_path}.");

	if (!defined('_XE_PATH_'))
	    define('_XE_PATH_', $xe_path . '/');

	/** Replaces the Context class in XE */
	class Context
	{
	    protected static $db_info = NULL;

	    public static function isInstalled()
	    {
		return TRUE;
	    }

	    public static function getLangType()
	    {
		return 'en';
	    }

	    public static function getLang()
	    {
		return 'en';
	    }

	    public static function getDBType()
	    {
		if (self::$db_info)
		    return self::$db_info->master_db['db_type'];
		else
		    return NULL;
	    }

	    public static function setDBInfo($db_info)
	    {
		self::$db_info = $db_info;
	    }

	    public static function getDBInfo()
	    {
		return self::$db_info;
	    }
	    
	    public static function convertEncodingStr($str)
	    {
		return $str;
	    }

	    public static function setNoDBInfo()
	    {
		$db_info = (object)NULL;
		$db_info->master_db =
		    array
			(
			    'db_type' => NULL,
			    'db_hostname' => NULL,
			    'db_port' => NULL,
			    'db_userid' => NULL,
			    'db_password' => NULL,
			    'db_database' => NULL,
			    'db_table_prefix' => NULL,
			    'is_connected' => TRUE	// that will skip connection attempts
			);
		$db_info->slave_db = array($db_info->master_db);
		$db_info->use_prepared_statements = TRUE;

		self::setDBInfo($db_info);
	    }

	    public static function setMysqlDBInfo()
	    {
		$db_info = (object)NULL;
		$db_info->master_db =
		    array
			(
			    'db_type' => 'mysql',
			    'db_hostname' => NULL,
			    'db_port' => NULL,
			    'db_userid' => NULL,
			    'db_password' => NULL,
			    'db_database' => NULL,
			    'db_table_prefix' => NULL,
			    'resource' => TRUE,
			    'is_connected' => TRUE	// that will skip connection attempts
			);
		$db_info->slave_db = array($db_info->master_db);
		$db_info->use_prepared_statements = TRUE;

		self::setDBInfo($db_info);

		if
		    (
			array_key_exists('__DB__', $GLOBALS)
			    &&
			array_key_exists($db_info->master_db['db_type'], $GLOBALS['__DB__'])
		    )
		{
		}
		else
		    $GLOBALS['__DB__'][$db_info->master_db['db_type']] =
			new DBMysqlConnectWrapper();

		$oDB = new DB();
		$oDB->getParser(true);
	    }

	    public static function setMysqliDBInfo()
	    {
		$db_info = (object)NULL;
		$db_info->master_db =
		    array
			(
			    'db_type' => 'mysqli',
			    'db_hostname' => NULL,
			    'db_port' => NULL,
			    'db_userid' => NULL,
			    'db_password' => NULL,
			    'db_database' => NULL,
			    'db_table_prefix' => NULL,
			    'resource' => TRUE,
			    'is_connected' => TRUE	// that will skip connection attempts
			);
		$db_info->slave_db = array($db_info->master_db);
		$db_info->use_prepared_statements = TRUE;

		self::setDBInfo($db_info);

		if
		    (
			array_key_exists('__DB__', $GLOBALS)
			    &&
			array_key_exists($db_info->master_db['db_type'], $GLOBALS['__DB__'])
		    )
		{
		}
		else
		    $GLOBALS['__DB__'][$db_info->master_db['db_type']] =
			new DBMysqliConnectWrapper();

		$oDB = new DB();
		$oDB->getParser(true);
	    }

	    public static function setCubridDBInfo()
	    {
		$db_info = (object)NULL;
		$db_info->master_db =
		    array
			(
			    'db_type' => 'cubrid',
			    'db_hostname' => NULL,
			    'db_port' => NULL,
			    'db_userid' => NULL,
			    'db_password' => NULL,
			    'db_database' => NULL,
			    'db_table_prefix' => NULL,
			    'resource' => TRUE,
			    'is_connected' => TRUE	// that will skip connection attempts
			);
		$db_info->slave_db = array($db_info->master_db);
		$db_info->use_prepared_statements = TRUE;

		self::setDBInfo($db_info);

		if
		    (
			array_key_exists('__DB__', $GLOBALS)
			    &&
			array_key_exists($db_info->master_db['db_type'], $GLOBALS['__DB__'])
		    )
		{
		}
		else
		    $GLOBALS['__DB__'][$db_info->master_db['db_type']] =
			new DBCubridConnectWrapper();

		$oDB = new DB();
		$oDB->getParser(true);
	    }

	    public static function setMssqlDBInfo()
	    {
		$db_info = (object)NULL;
		$db_info->master_db =
		    array
			(
			    'db_type' => 'mssql',
			    'db_hostname' => NULL,
			    'db_port' => NULL,
			    'db_userid' => NULL,
			    'db_password' => NULL,
			    'db_database' => NULL,
			    'db_table_prefix' => NULL,
			    'resource' => TRUE,
			    'is_connected' => TRUE	// that will skip connection attempts
			);
		$db_info->slave_db = array($db_info->master_db);
		$db_info->use_prepared_statements = TRUE;

		self::setDBInfo($db_info);

		if
		    (
			array_key_exists('__DB__', $GLOBALS)
			    &&
			array_key_exists($db_info->master_db['db_type'], $GLOBALS['__DB__'])
		    )
		{
		}
		else
		    $GLOBALS['__DB__'][$db_info->master_db['db_type']] =
			new DBMssqlConnectWrapper();

		$oDB = new DB();
		$oDB->getParser(true);
	    }
	}

	class any_prop_obj_base
	{
	    public function __get($property)
	    {
		return NULL;
	    }
	}

	class langArgFilterErrorMessage
	{
	    public function __get($property)
	    {
		return 'Argument filter error';
	    }
	}

	global $lang;
	$lang = new any_prop_obj_base();    // to return NULL on non-existent properties
	$lang->filter = New langArgFilterErrorMessage();

	if (!defined('__XE__'))
	    define('__XE__', TRUE);

	if (!defined('__ZBXE__'))
	    define('__ZBXE__', TRUE);

	if (!defined('__DEBUG__'))
	    define('__DEBUG__', 0);

	if (!defined('__DEBUG_QUERY__'))
	    define('__DEBUG_QUERY__', 0);

	include(_XE_PATH_ . 'classes/object/Object.class.php');
	include(_XE_PATH_ . 'classes/handler/Handler.class.php');
	include(_XE_PATH_ . 'classes/file/FileHandler.class.php');
	include(_XE_PATH_ . 'classes/page/PageHandler.class.php');

	Context::setNoDBInfo();

	require_once(_XE_PATH_ . 'classes/db/DB.class.php');
	require_once(_XE_PATH_ . 'classes/db/DBMysql.class.php');
	require_once(_XE_PATH_ . 'classes/db/DBMysqli.class.php');
	require_once(_XE_PATH_ . 'classes/db/DBMysql_innodb.class.php');
	require_once(_XE_PATH_ . 'classes/db/DBCubrid.class.php');
	require_once(_XE_PATH_ . 'classes/db/DBMssql.class.php');
	require_once(_XE_PATH_ . 'classes/xml/XmlParser.class.php');
	require_once(_XE_PATH_ . 'classes/xml/XmlQueryParser.150.class.php');

	require_once(__DIR__ . '/connect_wrapper.php');

	// check $query_args, $query_args_file
	if ($query_args_file)
	    try
	    {
		$query_user_args = require($query_args_file);
	    }
	    catch (Exception $exc)
	    {
		fwrite(STDERR, "Error in arguments file.\n");
		throw $exc;
	    }
	else
	    if ($query_args)
		try
		{
		    eval( '$query_user_args = array(' . $query_args . ');');
		}
		catch (Exception $exc)
		{
		    fwrite(STDERR, "Error in arguments string.\n");
		    throw $exc;
		}

    }

    libxml_use_internal_errors(true);

    $schema_file = null;
    $schemas_set =
	array
	    (
		'delete' => __DIR__ . '/xml_delete.xsd',
		'update' => __DIR__ . '/xml_update.xsd',
		'select' => __DIR__ . '/xml_select.xsd',
		'insert' => __DIR__ . '/xml_insert.xsd',
		'insert-select' => __DIR__ . '/xml_insert_select.xsd'
	    );
    $table_schema = __DIR__ . '/xml_create_table.xsd';

    $domDocument = new DOMDocument();

    $i = 1;

    if (pathinfo($argv[1], PATHINFO_EXTENSION) == 'xsd')
	$schema_file = $argv[$i++];
    
    for ( ; $i < count($argv); $i++)
	try
	{
	    $document_schema = $schema_file;
	    $success = false;

	    $retcode->push(returnCode::RETCODE_GENERIC_XML_SYNTAX);
	    if ($domDocument->load($argv[$i]))
	    {
		$retcode->pop();

		$queryElement = $domDocument->documentElement;

		if
		    (
			!$schema_file && !$schema_language
			    &&
			(
			    $queryElement->tagName != 'query'
				||
			    !array_key_exists($queryElement->getAttribute('action'), $schemas_set)
			)
		    )
		{
		    $retcode->code(returnCode::RETCODE_QUERY_ELEMENT);

		    throw
			new ErrorMessage
			    (
<<<text_delimiter
{$argv[$i]}:
	Root element should be <query> and should have an action attribute of:
	insert, insert-select, select, update or delete.
	Otherwise an explicit schema, to validate the document with, should be
	specified as first argument on the command line.
text_delimiter
			    );
		}

		if
		    (	!$schema_file && !$schema_language && !$skip_query_id
			    &&
			!validate_query_id($argv[$i], $queryElement->getAttribute('id'))
		    )
		{
		    $retcode->code(returnCode::RETCODE_QUERY_ELEMENT);
		    $query_id = $queryElement->getAttribute('id');

		    throw
			new ErrorMessage
			(
			    "{$argv[$i]}(" . $queryElement->getLineNo() . "):\n\tQuery 'id' attribute value \"{$query_id}\" should match file name."
			);
		}

		if ($schema_language)
		    $document_schema = $table_schema;
		else
		    if (!$document_schema)
			$document_schema = $schemas_set[$queryElement->getAttribute('action')];

		$retcode->push(returnCode::RETCODE_XSD_VALIDATION);
		if ($domDocument->schemaValidate($document_schema))
		{
		    $retcode->pop();

		    if ($schema_language)
			validate_schema_doc($argv[$i], $domDocument->documentElement);
		    else
			validate_xml_query($argv[$i], $domDocument->documentElement);
		    $success = true;
		}

		if (!$validate_only)
		{
		    // Generate SQL with the db provider back-ends

		    if (function_exists('sys_get_temp_dir'))
			$tmpdir = sys_get_temp_dir();
		    else
		    {
			$tmpdir = getenv('TEMP');
			if (!$tmpdir)
			    $tmpdir = getenv('TMP');
			if (!$tmpdir)
			    $tmpdir = '/tmp';
		    }


		    global $_SERVER;

		    if (!is_array($_SERVER))
			$_SERVER = array();

		    if (!array_key_exists('REMOTE_ADDR', $_SERVER))
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		    $set_db_info_methods =
			array
			    (
				'mysql'	    => 'setMysqlDBInfo',
				'mysqli'    => 'setMysqliDBInfo',
				'cubrid'    => 'setCubridDBInfo',
				'mssql'	    => 'setMssqlDBInfo'
			    );

		    foreach ($set_db_info_methods as $db_type => $set_info_method)
		    {
			Context::$set_info_method(); // calls setMysqlDBInfo()/setCubridDBInfo()/...

			if ($schema_language)
			{
			    $GLOBALS['__DB__'][$db_type]->queries = '';
			    $GLOBALS['__DB__'][$db_type]->createTableByXmlFile($argv[$i]);

			    print "\n";
			    print pathinfo($argv[$i], PATHINFO_FILENAME);
			    print " {$db_type} query:\n";
			    print $GLOBALS['__DB__'][$db_type]->queries;
			    print "\n";
			}
			else
			{
			    $unlink_tmpfile =
				new UnlinkFile(tempnam($tmpdir, 'xe_'));

			    // copied from classes/db/DB.class.php
			    $oParser = new XmlQueryParser();
			    $args_array = $oParser->parse
				(
				    pathinfo($argv[$i], PATHINFO_FILENAME), // query id
				    $argv[$i],				    // xml file
				    $unlink_tmpfile->file_name		    // cache file
				);

			    $GLOBALS['__DB__'][$db_type]->queries = '';
			    $k = 1;
			    foreach ($args_array as $arg)
			    {
				if
				    (
					// why would there be a query arg without a var name ?
					isset($arg->variable_name)
					    &&
					!array_key_exists($arg->variable_name, $query_user_args)
				    )
				{
				    if (isset($arg->argument_validator))
					if
					    (
						false	// some default values are to be parser by php, some are not...
						    &&
						isset($arg->argument_validator->default_value)
						    &&
						isset($arg->argument_validator->default_value->value))
					{
					    $query_user_args[$arg->variable_name] =
						eval('return ' . $arg->argument_validator->default_value->toString() . ';');
					}
					else
					    if ($arg->argument_validator->filter)
						switch ($arg->argument_validator->filter)
						{
						case 'email':
						case 'email_address':
						    $query_user_args[$arg->variable_name] = 
							'user@mail.com';
						    break;

						case 'homepage':
						    $query_user_args[$arg->variable_name] =
							'http://user.domain.srv/page_path';
						    break;

						case 'userid':
						case 'user_id':
						    $query_user_args[$arg->variable_name] =
							'user_login_name';
						    break;

						case 'number':
						case 'numbers':
						    $query_user_args[$arg->variable_name] = 
							10982431;
						    break;

						case 'alpha':
						    $query_user_args[$arg->variable_name] =
							'textStringLine';
						    break;

						case 'alpha_number':
						    $query_user_args[$arg->variable_name] =
							'textString1234Line2';
						    break;
						}

				    if (!array_key_exists($arg->variable_name, $query_user_args))
					$query_user_args[$arg->variable_name] = sprintf('%06d', $k);

				    if (isset($arg->argument_validator))
				    {
					if (isset($arg->argument_validator->min_length))
					{
					    $query_user_args[$arg->variable_name] =
						str_pad
						    (
							$query_user_args[$arg->variable_name],
							$arg->argument_validator->min_length,
							isset($arg->argument_validator->filter) &&
							    (
								$arg->argument_validator->filter == 'number'
								    ||
								$arg->argument_validator->filter == 'numbers'
							    )
							 ? '0' : 'M'
						    );
					}

					if (isset($arg->argument_validator->max_length))
					    $query_user_args[$arg->variable_name] =
						substr
						    (
							$query_user_args[$arg->variable_name],
							0, 
							$arg->argument_validator->max_length
						    );
				    }
				}

				$k++;
			    }

			    $resultset = 
				$GLOBALS['__DB__'][$db_type]->_executeQuery
				    (
					$unlink_tmpfile->file_name,	// cache_file
					(object)$query_user_args,	// source_args
					basename($argv[$i]),		// query_id
					array()				// arg_columns
				    );

			    if (is_a($resultset, 'Object') && !$resultset->toBool())
				throw new XmlSchemaError($argv[$i], -1, 'mysql SQL query generation failed');
			    else
			    {
				print "\n";
				print pathinfo($argv[$i], PATHINFO_FILENAME);
				print " {$db_type} query:\n";
				print $GLOBALS['__DB__'][$db_type]->queries;
				print "\n";
			    }
			}
		    }
		}
	    }

	    if (!$success)
	    {
		libXmlDisplayError($argv[$i], true);
	    }
	}
	catch (XmlSchemaError $exc)
	{
	    $retcode->code(returnCode::RETCODE_BUILTIN_CHECKS);

	    fwrite(STDERR, $exc->getMessage()."\n");
	}
	catch (ErrorMessage $exc)
	{
	    if ($retcode->code() == returnCode::RETCODE_SUCCESS)
		$retcode->code(returnCode::RETCODE_VALIDATOR_INTERNAL);

	    fwrite(STDERR, $exc->getMessage()."\n");
	    libXmlDisplayError($argv[$i]);
	}
	catch (ErrorException $exc)
	{
	    if ($retcode->code() == returnCode::RETCODE_SUCCESS)
		$retcode->code(returnCode::RETCODE_VALIDATOR_INTERNAL);

	    fwrite(STDERR, "{$exc->getFile()}({$exc->getLine()}):\n\t{$exc->getMessage()}.\n");
	    fwrite(STDERR, $exc->getTraceAsString());
	    libXmlDisplayError($argv[$i]);
	}
	catch (Exception $exc)
	{
	    $retcode.code(returnCode::RETCODE_VALIDATOR_INTERNAL);

	    fwrite(STDERR, $exc->getMessage()."\n");
	    fwrite(STDERR, $exc->getTraceAsString());
	    libXmlDisplayError($argv[$i]);
	}

	exit($retcode->code());
}
catch (SyntaxError $syntax)
{
    fwrite(STDERR, $syntax->getMessage()."\n");
    exit(254);	// wrong command line
		// 255 is reserved by php (for parse errors, etc.)
}
catch (ErrorMessage $exc)
{
    fwrite(STDERR, $exc->getMessage()."\n");
    libXmlDisplayError();
    exit(returnCode::RETCODE_VALIDATOR_INTERNAL);	// internal validator error
}
catch (Exception $exc)
{
    fwrite(STDERR, $exc->getFile() . '('.$exc->getLine().")\n\t". $exc->getMessage()."\n");
    fwrite(STDERR, $exc->getTraceAsString());
    libXmlDisplayError();
    exit(returnCode::RETCODE_VALIDATOR_INTERNAL);	// internal validator error
}

