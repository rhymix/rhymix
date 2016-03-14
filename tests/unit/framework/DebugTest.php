<?php

class DebugTest extends \Codeception\TestCase\Test
{
	public function testDebugEntry()
	{
		$file = __FILE__;
		$line = __LINE__ + 2;
		Rhymix\Framework\Debug::$write_to_error_log = false;
		Rhymix\Framework\Debug::addEntry('foobar entry');
		$entries = Rhymix\Framework\Debug::getEntries();
		$this->assertEquals(1, count($entries));
		$this->assertEquals('foobar entry', $entries[0]->message);
		$this->assertEquals($file, $entries[0]->file);
		$this->assertEquals($line, $entries[0]->line);
	}
	
	public function testDebugError()
	{
		$file = __FILE__;
		$line = __LINE__ + 2;
		Rhymix\Framework\Debug::$write_to_error_log = false;
		Rhymix\Framework\Debug::addError(~0, 'Rhymix', $file, $line, null);
		$errors = Rhymix\Framework\Debug::getErrors();
		$this->assertGreaterThanOrEqual(1, count($errors));
		$error = array_pop($errors);
		$this->assertContains('Rhymix', $error->message);
		$this->assertEquals($file, $error->file);
		$this->assertEquals($line, $error->line);
	}
	
	public function testDebugQuery()
	{
		Rhymix\Framework\Debug::addQuery(array(
			'result' => 'fail',
			'errno' => 1234,
			'errstr' => 'This is a unit test',
			'connection' => 'foobar',
			'query_id' => 'rhymix.unitTest',
			'query' => 'SELECT foo FROM bar',
			'elapsed_time' => 0.1234,
			'called_file' => __FILE__,
			'called_line' => __LINE__,
			'called_method' => 'rhymix.unitTest',
			'backtrace' => array(),
		));
		$queries = Rhymix\Framework\Debug::getQueries();
		$this->assertGreaterThanOrEqual(1, count($queries));
		$query = array_pop($queries);
		$this->assertEquals('SELECT foo FROM bar', $query->query_string);
		$this->assertEquals('This is a unit test', $query->message);
		$this->assertEquals(1234, $query->error_code);
	}
	
	public function testDebugTranslateFilename()
	{
		$original_filename = __FILE__;
		$trans_filename = substr($original_filename, strlen(\RX_BASEDIR));
		$this->assertEquals($trans_filename, Rhymix\Framework\Debug::translateFilename($original_filename));
		
		$original_filename = __FILE__;
		$alias_filename = $original_filename . '.foobar';
		$trans_filename = substr($alias_filename, strlen(\RX_BASEDIR));
		Rhymix\Framework\Debug::addFilenameAlias($alias_filename, $original_filename);
		$this->assertEquals($trans_filename, Rhymix\Framework\Debug::translateFilename($original_filename));
	}
}
