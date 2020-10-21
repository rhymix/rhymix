<?php

class DBTest extends \Codeception\TestCase\Test
{
	public function testGetInstance()
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$this->assertTrue($oDB instanceof Rhymix\Framework\DB);
		$this->assertTrue($oDB->isConnected());
		$this->assertTrue($oDB->getHandle() instanceof Rhymix\Framework\Helpers\DBHelper);
	}
	
	public function testPrepare()
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$prefix = Rhymix\Framework\Config::get('db.master.prefix');
		
		$stmt = $oDB->prepare('SELECT * FROM documents WHERE document_srl = ?');
		$this->assertTrue($stmt instanceof Rhymix\Framework\Helpers\DBStmtHelper);
		if ($prefix)
		{
			$this->assertEquals('SELECT * FROM `' . $prefix . 'documents` AS `documents` WHERE document_srl = ?', $stmt->queryString);
		}
		else
		{
			$this->assertEquals('SELECT * FROM documents WHERE document_srl = ?', $stmt->queryString);
		}
		
		$this->assertTrue($stmt->execute([123]));
		$this->assertTrue($stmt->execute([456]));
		$this->assertTrue($stmt->execute([789]));
		$this->assertTrue(is_array($stmt->fetchAll()));
		$this->assertTrue($stmt->closeCursor());
	}
	
	public function testQuery()
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$prefix = Rhymix\Framework\Config::get('db.master.prefix');
		
		$stmt = $oDB->query('SELECT * FROM documents WHERE document_srl = 123');
		$this->assertTrue($stmt instanceof Rhymix\Framework\Helpers\DBStmtHelper);
		if ($prefix)
		{
			$this->assertEquals('SELECT * FROM `' . $prefix . 'documents` AS `documents` WHERE document_srl = 123', $stmt->queryString);
		}
		else
		{
			$this->assertEquals('SELECT * FROM documents WHERE document_srl = 123', $stmt->queryString);
		}
		
		$this->assertTrue(is_array($stmt->fetchAll()));
		$this->assertTrue($stmt->closeCursor());
		
		$stmt = $oDB->query('SELECT * FROM documents WHERE document_srl = ?', [123]);
		$this->assertTrue($stmt instanceof Rhymix\Framework\Helpers\DBStmtHelper);
		if ($prefix)
		{
			$this->assertEquals('SELECT * FROM `' . $prefix . 'documents` AS `documents` WHERE document_srl = ?', $stmt->queryString);
		}
		else
		{
			$this->assertEquals('SELECT * FROM documents WHERE document_srl = ?', $stmt->queryString);
		}
		
		$this->assertTrue(is_array($stmt->fetchAll()));
		$this->assertTrue($stmt->closeCursor());
		
		$stmt = $oDB->query('SELECT * FROM documents WHERE document_srl = ? AND status = ?', 123, 'PUBLIC');
		$this->assertTrue($stmt instanceof Rhymix\Framework\Helpers\DBStmtHelper);
		if ($prefix)
		{
			$this->assertEquals('SELECT * FROM `' . $prefix . 'documents` AS `documents` WHERE document_srl = ? AND status = ?', $stmt->queryString);
		}
		else
		{
			$this->assertEquals('SELECT * FROM documents WHERE document_srl = ? AND status = ?', $stmt->queryString);
		}
		
		$this->assertTrue(is_array($stmt->fetchAll()));
		$this->assertTrue($stmt->closeCursor());
	}
	
	public function testAddPrefixes()
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$prefix = Rhymix\Framework\Config::get('db.master.prefix');
		
		$source = 'SELECT a, b, c FROM documents JOIN comments ON documents.document_srl = comment.document_srl WHERE documents.member_srl = ?';
		$target = 'SELECT a, b, c FROM `' . $prefix . 'documents` AS `documents` JOIN `' . $prefix . 'comments` AS `comments` ' .
			'ON documents.document_srl = comment.document_srl WHERE documents.member_srl = ?';
		$this->assertEquals($target, $oDB->addPrefixes($source));
		
		$source = 'SELECT a AS aa FROM documents as foo JOIN bar ON documents.a = bar.a';
		$target = 'SELECT a AS aa FROM `' . $prefix . 'documents` AS `foo` JOIN `' . $prefix . 'bar` AS `bar` ON documents.a = bar.a';
		$this->assertEquals($target, $oDB->addPrefixes($source));
		
		$source = 'INSERT INTO documents (a, b, c) VALUES (?, ?, ?)';
		$target = 'INSERT INTO `' . $prefix . 'documents` (a, b, c) VALUES (?, ?, ?)';
		$this->assertEquals($target, $oDB->addPrefixes($source));
		
		$source = 'INSERT INTO documents (a, b, c) SELECT d, e, f FROM old_documents WHERE g = ?';
		$target = 'INSERT INTO `' . $prefix . 'documents` (a, b, c) SELECT d, e, f FROM `' . $prefix . 'old_documents` AS `old_documents` WHERE g = ?';
		$this->assertEquals($target, $oDB->addPrefixes($source));
		
		$source = 'UPDATE documents SET a = ?, b = ? WHERE c = ?';
		$target = 'UPDATE `' . $prefix . 'documents` SET a = ?, b = ? WHERE c = ?';
		$this->assertEquals($target, $oDB->addPrefixes($source));
		
		$source = 'DELETE FROM documents WHERE d = ?';
		$target = 'DELETE FROM `' . $prefix . 'documents` WHERE d = ?';
		$this->assertEquals($target, $oDB->addPrefixes($source));
		
		$source = 'update documents set a = ?, b = ? where c = ?';
		$this->assertEquals($source, $oDB->addPrefixes($source));
		
		$source = 'delete from documents where d = ?';
		$this->assertEquals($source, $oDB->addPrefixes($source));
	}
	
	public function testIsTableColumnIndexExists()
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$this->assertTrue($oDB->isTableExists('documents'));
		$this->assertTrue($oDB->isColumnExists('documents', 'document_srl'));
		$this->assertTrue($oDB->isIndexExists('documents', 'idx_regdate'));
		$this->assertFalse($oDB->isTableExists('nxdocuments'));
		$this->assertFalse($oDB->isColumnExists('documents', 'document_nx'));
		$this->assertFalse($oDB->isIndexExists('documents', 'idx_regex'));
	}
	
	public function testGetColumnInfo()
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$info = $oDB->getColumnInfo('documents', 'document_srl');
		$this->assertTrue(is_object($info));
		$this->assertEquals('document_srl', $info->name);
		$this->assertEquals('bigint', $info->dbtype);
		$this->assertEquals('bignumber', $info->xetype);
		$this->assertNull($info->default_value);
		$this->assertTrue($info->notnull);
	}
	
	public function testIsValidOldPassword()
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$password = 'foobar^\'1233243';
		$saved1 = '*AA82FF6C7930626A138D0CF3E42D9581D60A85BB';
		$saved2 = '5567cb961d0e218b';
		$saved3 = str_replace('A', 'E', $saved1);
		$saved4 = str_replace('6', '9', $saved2);
		$this->assertTrue($oDB->isValidOldPassword($password, $saved1));
		$this->assertTrue($oDB->isValidOldPassword($password, $saved2));
		$this->assertFalse($oDB->isValidOldPassword($password, $saved3));
		$this->assertFalse($oDB->isValidOldPassword($password, $saved4));
	}
	
	public function testAddQuotes()
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$string = 'hello world \' or 1 = 1';
		$result = 'hello world \\\' or 1 = 1';
		$this->assertEquals($result, $oDB->addQuotes($string));
		$this->assertEquals('foobar', $oDB->addQuotes('foobar'));
		$this->assertEquals('123.45', $oDB->addQuotes(123.45));
		$this->assertEquals('-12345', $oDB->addQuotes(-12345));
	}
}
