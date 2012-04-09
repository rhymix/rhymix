<?php

if (!defined('__DIR__'))
    define('__DIR__', realpath(dirname(__FILE__))); 

/** The tests here are meant only for the built-in checks in validator.php, 
    and not for the entire syntax expressed by the .xsd files. */
class XmlQueriesTest extends PHPUnit_Framework_TestCase
{
    // taken from validator.php

    const RETCODE_VALIDATOR_INTERNAL = 60;
    const RETCODE_GENERIC_XML_SYNTAX = 50;
    const RETCODE_QUERY_ELEMENT = 40;
    const RETCODE_XSD_VALIDATION = 30;
    const RETCODE_BUILTIN_CHECKS =    20;
    const RETCODE_DB_SCHEMA_MATCH =10;	// no schema match is currently implemented.
    const RETCODE_SUCCESS = 0;

    public $validator_cmd;

    public function setUp()
    {
	 $this->validator_cmd = "php " . escapeshellarg(__DIR__ . '/../validate.php') . " ";
    }
 
    // recursive glob
    // On Windows glob() is case-sensitive.
    public function globr($sDir, $sPattern, $nFlags = NULL) 
    { 
	// Get the list of all matching files currently in the 
	// directory. 

	$aFiles = glob("$sDir/$sPattern", $nFlags); 

	$this->assertTrue(is_array($aFiles), 'directory listing failed.');

	$aDirs = glob("$sDir/*", GLOB_ONLYDIR | GLOB_NOSORT | GLOB_NOESCAPE | GLOB_ERR);
	$this->assertTrue(is_array($aDirs), 'directory listing failed.');

	foreach ($aDirs as $sSubDir) 
	{ 
	    if ($sSubDir != '.' && $sSubDir != '..')
	    {
		$aSubFiles = $this->globr($sSubDir, $sPattern, $nFlags); 
		$aFiles = array_merge($aFiles, $aSubFiles); 
	    }
	} 

	// return merged array with all (recursive) files
	return $aFiles; 
    } 

    /** Tests all XML Query and Schema Language files (in all modules/addons/widgets) in XE */
    public function invoke_testReleasedXMLLangFiles
	(
	    $released_files,
	    $expected_return_code,
	    $validator_args = ''
	)
    {
	// this file is in tools/dbxml_validator/tests
	$xe_dir = __DIR__ . '/../../..';

	$this->assertTrue(file_exists($xe_dir . '/index.php'), 'XE directory not found');

	$cnt = 0;
	$cmd = $this->validator_cmd;
	$xml_files = array();

	foreach ($released_files as $released_file_mask)
	    $xml_files =
		array_merge
		(
		    $xml_files,
		    $this->globr
			(
			    $xe_dir,
			    $released_file_mask,
			    GLOB_NOSORT | GLOB_NOESCAPE | GLOB_ERR
			)
		);

	while ($cnt < count($xml_files))
	{
	    $cmd = $this->validator_cmd . $validator_args;

	    // Validate 50 files at once
	    foreach (array_slice($xml_files, $cnt, 50) as $xml_file)
		$cmd .= " " . escapeshellarg($xml_file);

	    exec($cmd . ' 2>&1', $validator_output, $return_code);

	    $output_text = trim(trim(implode("\n", $validator_output)), "\n");

	    // Validator should not crash/exit-with-an-error.
	    $this->assertLessThanOrEqual
		(
		    $expected_return_code,
		    $return_code,
		    "{$cmd}\n\n{$output_text}\nValidator returned code {$return_code}."
		);

	    $cnt += 50;
	}
    }

    public function testReleasedXMLQueryLangFiles()
    {
	$this->invoke_testReleasedXMLLangFiles
	    (
		array('queries/*.xml', 'xml_query/*.xml'),
		self::RETCODE_QUERY_ELEMENT
	    );

	$this->markTestIncomplete('XML Query Language files should be fixed first.');
    }

    public function testReleasedXMLSchemaLangFiles()
    {
	$this->invoke_testReleasedXMLLangFiles
	    (
		array('schemas/*.xml'),
		self::RETCODE_BUILTIN_CHECKS,
		' --schema-language'
	    );

	$this->markTestIncomplete('XML Schema Language files should be fixed first.');
    }

    public function invoke_testInvalidXmlFiles($filename, $err_code, $args = '')
    {
	$cmd = $this->validator_cmd . ' '. $args . ' ' . escapeshellarg($filename);
	$validator_output = array();
	$return_code = 0;

	exec($cmd . '2>&1', $validator_output, $return_code);

	$output_text = trim(trim(implode("\n", $validator_output)), "\n");

	// Validator should not crash/exit-with-an-error.
	$this->assertEquals
	    (
		$err_code,
		$return_code,
		"{$cmd}\n{$output_text}\nValidator returned code {$return_code}."
	    );

	// Validator should output some error on the test files
	$basefilename = basename($filename);
	$this->assertNotEmpty($output_text, "Error reporting failed for {$basefilename} validation.");

    }

    public function testInvalidQueryId()
    {
	return $this->invoke_testInvalidXmlFiles(__DIR__.'/data/wrongQueryId.xml', self::RETCODE_QUERY_ELEMENT);
    }

    /**
     * @dataProvider getFilesList
     */
    public function testInvalidXMLQueryFiles($filename)
    {
	return $this->invoke_testInvalidXmlFiles($filename, self::RETCODE_BUILTIN_CHECKS);
    }

    public function getDirFilesList($dir_name)
    {
	$output = array();

	$dir = opendir(__DIR__ . '/' . $dir_name);

	if ($dir)
	{
	    $entry = readdir($dir);

	    while ($entry !== FALSE)
	    {
		$fname = __DIR__ . '/' . $dir_name .'/' . $entry;

		if (!is_dir($fname)&& $entry != 'wrongQueryId.xml')
		    $output[] = array($fname);

		$entry = readdir($dir);
	    }

	    closedir($dir);
	}
	else
	    $this->assertFalse(TRUE);

        return $output;
    }

    public function getFilesList()
    {
	return $this->getDirFilesList('data');
    }

    public function getSchemaFilesList()
    {
	return $this->getDirFilesList('data/schema');
    }

    public function getSchemaWarningFilesList()
    {
	return $this->getDirFilesList('data/schema/warnings');
    }

    /**
     * @dataProvider getSchemaFilesList
     */
    public function testInvalidXMLSchemaFiles($filename)
    {
	return $this->invoke_testInvalidXmlFiles($filename, self::RETCODE_BUILTIN_CHECKS, '--schema-language');
    }

    /**
     * @dataProvider getSchemaWarningFilesList
     */
    public function testWarningXMLSchemaFiles($filename)
    {
	return $this->invoke_testInvalidXmlFiles($filename, self::RETCODE_SUCCESS, '--schema-language');
    }
}

