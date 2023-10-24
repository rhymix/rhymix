<?php

class StorageTest extends \Codeception\Test\Unit
{
	public function _before()
	{
		Rhymix\Framework\Storage::deleteDirectory(\RX_BASEDIR . 'tests/_output', false);
	}

	public function _after()
	{
		@chmod(\RX_BASEDIR . 'tests/_output', 0755);
		Rhymix\Framework\Storage::deleteDirectory(\RX_BASEDIR . 'tests/_output', false);
	}

	public function _failed()
	{
		@chmod(\RX_BASEDIR . 'tests/_output', 0755);
		Rhymix\Framework\Storage::deleteDirectory(\RX_BASEDIR . 'tests/_output', false);
	}

	public function testExists()
	{
		$this->assertTrue(Rhymix\Framework\Storage::exists(__FILE__));
		$this->assertTrue(Rhymix\Framework\Storage::exists(__DIR__));
		$this->assertFalse(Rhymix\Framework\Storage::exists(__FILE__ . '.nonexistent.suffix'));
		$this->assertFalse(Rhymix\Framework\Storage::exists(__DIR__ . '/nonexistent.subdirectory'));
	}

	public function testIsFile()
	{
		$this->assertTrue(Rhymix\Framework\Storage::isFile(__FILE__));
		$this->assertFalse(Rhymix\Framework\Storage::isFile(__DIR__));
		$this->assertFalse(Rhymix\Framework\Storage::isFile(__FILE__ . '.nonexistent.suffix'));
		$this->assertFalse(Rhymix\Framework\Storage::isFile(__DIR__ . '/nonexistent.subdirectory'));
	}

	public function testIsEmptyFile()
	{
		$emptyfile = \RX_BASEDIR . 'tests/_output/emptyfile.txt';
		file_put_contents($emptyfile, '');

		$this->assertTrue(Rhymix\Framework\Storage::isEmptyFile($emptyfile));
		$this->assertFalse(Rhymix\Framework\Storage::isEmptyFile($emptyfile . '.nonexistent.suffix'));
		$this->assertFalse(Rhymix\Framework\Storage::isEmptyFile(__FILE__));
		$this->assertFalse(Rhymix\Framework\Storage::isEmptyFile(__DIR__));
	}

	public function testIsDirectory()
	{
		$this->assertTrue(Rhymix\Framework\Storage::isDirectory(__DIR__));
		$this->assertFalse(Rhymix\Framework\Storage::isDirectory(__FILE__));
		$this->assertFalse(Rhymix\Framework\Storage::isDirectory(__FILE__ . '.nonexistent.suffix'));
		$this->assertFalse(Rhymix\Framework\Storage::isDirectory(__DIR__ . '/nonexistent.subdirectory'));
	}

	public function testIsEmptyDirectory()
	{
		$emptydir = \RX_BASEDIR . 'tests/_output/emptydir';
		mkdir($emptydir);

		$this->assertTrue(Rhymix\Framework\Storage::isEmptyDirectory($emptydir));
		$this->assertFalse(Rhymix\Framework\Storage::isEmptyDirectory($emptydir . '/nonexistent.subdirectory'));
		$this->assertFalse(Rhymix\Framework\Storage::isEmptyDirectory(__FILE__));
		$this->assertFalse(Rhymix\Framework\Storage::isEmptyDirectory(__DIR__));
	}

	public function testIsSymlink()
	{
		if (strncasecmp(\PHP_OS, 'Win', 3) === 0)
		{
			return;
		}

		$symlink_source = \RX_BASEDIR . 'tests/_output/link.source.txt';
		$symlink_target = \RX_BASEDIR . 'tests/_output/link.target.txt';
		file_put_contents($symlink_target, 'foobar');
		symlink($symlink_target, $symlink_source);

		$this->assertTrue(Rhymix\Framework\Storage::isSymlink($symlink_source));
		$this->assertFalse(Rhymix\Framework\Storage::isSymlink($symlink_target));
		unlink($symlink_target);

		$this->assertTrue(Rhymix\Framework\Storage::isSymlink($symlink_source));
		$this->assertFalse(Rhymix\Framework\Storage::isValidSymlink($symlink_source));
		$this->assertFalse(Rhymix\Framework\Storage::isSymlink($symlink_target));
		$this->assertFalse(Rhymix\Framework\Storage::isValidSymlink($symlink_target));
	}

	public function testIsReadable()
	{
		$this->assertTrue(Rhymix\Framework\Storage::isReadable(__FILE__));
		$this->assertTrue(Rhymix\Framework\Storage::isReadable(__DIR__));
		$this->assertFalse(Rhymix\Framework\Storage::isReadable(__FILE__ . '.nonexistent.suffix'));
		$this->assertFalse(Rhymix\Framework\Storage::isReadable('/dev/nonexistent'));
	}

	public function testIsWritable()
	{
		$testfile = \RX_BASEDIR . 'tests/_output/testfile.txt';
		file_put_contents($testfile, 'foobar');

		$this->assertTrue(Rhymix\Framework\Storage::isWritable(__FILE__));
		$this->assertTrue(Rhymix\Framework\Storage::isWritable(__DIR__));
		$this->assertTrue(Rhymix\Framework\Storage::isWritable($testfile));
		$this->assertTrue(Rhymix\Framework\Storage::isWritable(dirname($testfile)));
		$this->assertFalse(Rhymix\Framework\Storage::isWritable($testfile . '.nonexistent.suffix'));
		$this->assertFalse(Rhymix\Framework\Storage::isWritable('/dev/nonexistent'));
	}

	public function testGetSize()
	{
		$this->assertEquals(filesize(__FILE__), Rhymix\Framework\Storage::getSize(__FILE__));
		$this->assertFalse(Rhymix\Framework\Storage::getSize(__DIR__));
		$this->assertFalse(Rhymix\Framework\Storage::getSize(__FILE__ . '.nonexistent.suffix'));
		$this->assertFalse(Rhymix\Framework\Storage::getSize('/dev/nonexistent'));
	}

	public function testRead()
	{
		// Simple read test
		$this->assertEquals(file_get_contents(__FILE__), Rhymix\Framework\Storage::read(__FILE__));
		$this->assertFalse(Rhymix\Framework\Storage::read(__FILE__ . '.nonexistent.suffix'));
		$this->assertFalse(Rhymix\Framework\Storage::read(__DIR__));
		$this->assertFalse(Rhymix\Framework\Storage::read('/dev/nonexistent'));

		// Stream read test
		$fp = Rhymix\Framework\Storage::read(__FILE__, true);
		$this->assertTrue(is_resource($fp));
		$this->assertEquals(file_get_contents(__FILE__), fread($fp, filesize(__FILE__)));
		fclose($fp);
	}

	public function testWrite()
	{
		$testfile = \RX_BASEDIR . 'tests/_output/subdirectory/testfile.txt';
		$copyfile = \RX_BASEDIR . 'tests/_output/subdirectory/copyfile.txt';

		// Simple write test
		$this->assertTrue(Rhymix\Framework\Storage::write($testfile, 'foobarbazzjazz'));
		$this->assertTrue(file_exists($testfile));
		$this->assertEquals('foobarbazzjazz', file_get_contents($testfile));
		$this->assertEquals(0666 & ~Rhymix\Framework\Storage::getUmask(), fileperms($testfile) & 0777);

		// Append test
		$this->assertTrue(Rhymix\Framework\Storage::write($testfile, 'rhymix', 'a', 0666));
		$this->assertTrue(file_exists($testfile));
		$this->assertEquals('foobarbazzjazzrhymix', file_get_contents($testfile));
		$this->assertEquals(0666, fileperms($testfile) & 0777);

		// Stream copy test
		$stream = fopen($testfile, 'r');
		$this->assertTrue(Rhymix\Framework\Storage::write($copyfile, $stream));
		$this->assertEquals('foobarbazzjazzrhymix', file_get_contents($copyfile));
		fclose($stream);

		// Stream append test
		$stream = fopen($testfile, 'r');
		$this->assertTrue(Rhymix\Framework\Storage::write($copyfile, $stream, 'a'));
		$this->assertEquals('foobarbazzjazzrhymixfoobarbazzjazzrhymix', file_get_contents($copyfile));
		fclose($stream);

		// Partial stream append test
		$stream = fopen($testfile, 'r');
		fseek($stream, 14);
		$this->assertTrue(Rhymix\Framework\Storage::write($copyfile, $stream, 'a'));
		$this->assertEquals('foobarbazzjazzrhymixfoobarbazzjazzrhymixrhymix', file_get_contents($copyfile));
		fclose($stream);

		// Empty file write test
		$this->assertTrue(Rhymix\Framework\Storage::write($testfile . '1', ''));
		$this->assertTrue(file_exists($testfile . '1'));
		$this->assertEquals(0, filesize($testfile . '1'));
		$this->assertEmpty(glob($testfile . '1.tmp.*'));

		// Empty stream copy test
		$stream = fopen('php://temp', 'r');
		$this->assertTrue(Rhymix\Framework\Storage::write($testfile . '2', $stream));
		$this->assertTrue(file_exists($testfile . '2'));
		$this->assertEquals(0, filesize($testfile . '2'));
		$this->assertEmpty(glob($testfile . '2.tmp.*'));
		fclose($stream);

		// Umask test
		if (strncasecmp(\PHP_OS, 'Win', 3) !== 0)
		{
			$umask = Rhymix\Framework\Storage::getUmask();
			Rhymix\Framework\Storage::setUmask(0046);
			$this->assertEquals(0046, Rhymix\Framework\Storage::getUmask());
			$this->assertTrue(Rhymix\Framework\Storage::write($testfile, 'foobarbazzjazz'));
			$this->assertEquals('foobarbazzjazz', file_get_contents($testfile));
			$this->assertEquals(0620, fileperms($testfile) & 0777);
			Rhymix\Framework\Storage::setUmask($umask);
		}
	}

	public function testReadWritePHPData()
	{
		$testfile = \RX_BASEDIR . 'tests/_output/test.php';
		$data = array('foo' => 'bar', 'baz' => array('rhymix' => '\'"special\\chars' . chr(0) . chr(255), 'test' => 'wow'));
		$comment = 'Hello world */' . PHP_EOL . '?><?php return; ?>';

		$this->assertTrue(Rhymix\Framework\Storage::writePHPData($testfile, $data));
		$this->assertEquals($data, Rhymix\Framework\Storage::readPHPData($testfile));

		$this->assertTrue(Rhymix\Framework\Storage::writePHPData($testfile, $data, $comment));
		$this->assertEquals($data, Rhymix\Framework\Storage::readPHPData($testfile));
	}

	public function testCopy()
	{
		$source = \RX_BASEDIR . 'tests/_output/copy.source.txt';
		$target = \RX_BASEDIR . 'tests/_output/copy.target.txt';
		$target_dir = \RX_BASEDIR . 'tests/_output';
		file_put_contents($source, 'foobarbaz');
		chmod($source, 0646);

		// Copy with exact destination filename
		$this->assertTrue(Rhymix\Framework\Storage::copy($source, $target));
		$this->assertTrue(file_exists($target));
		$this->assertTrue(file_get_contents($target) === 'foobarbaz');

		// Copy into directory with source filename
		$this->assertTrue(Rhymix\Framework\Storage::copy($source, $target_dir));
		$this->assertTrue(file_exists($target_dir . '/copy.source.txt'));
		$this->assertTrue(file_get_contents($target_dir . '/copy.source.txt') === 'foobarbaz');

		// Copy into directory with no write permissions
		chmod($target_dir, 0555);
		file_put_contents($source, 'foobarbaz has changed');
		$this->assertTrue(Rhymix\Framework\Storage::copy($source, $target));
		$this->assertTrue(file_exists($target));
		$this->assertTrue(file_get_contents($target) === 'foobarbaz has changed');
		chmod($target_dir, 0755);

		if (strncasecmp(\PHP_OS, 'Win', 3) !== 0)
		{
			$this->assertEquals(0646, fileperms($target) & 0777);
			$this->assertTrue(Rhymix\Framework\Storage::copy($source, $target, 0755));
			$this->assertEquals(0755, fileperms($target) & 0777);
		}
	}

	public function testMove()
	{
		$source = \RX_BASEDIR . 'tests/_output/move.source.txt';
		$target = \RX_BASEDIR . 'tests/_output/move.target.txt';
		file_put_contents($source, 'foobarbaz');

		$this->assertTrue(Rhymix\Framework\Storage::move($source, $target));
		$this->assertTrue(file_exists($target));
		$this->assertTrue(file_get_contents($target) === 'foobarbaz');
		$this->assertFalse(file_exists($source));
	}

	public function testDelete()
	{
		$testfile = \RX_BASEDIR . 'tests/_output/testfile.txt';
		file_put_contents($testfile, 'foobar');

		$this->assertTrue(Rhymix\Framework\Storage::delete($testfile));
		$this->assertFalse(file_exists($testfile));
	}

	public function testCreateDirectory()
	{
		$emptydir = \RX_BASEDIR . 'tests/_output/emptydir';

		$this->assertTrue(Rhymix\Framework\Storage::createDirectory($emptydir));
		$this->assertTrue(file_exists($emptydir) && is_dir($emptydir));

		// Umask test
		if (strncasecmp(\PHP_OS, 'Win', 3) !== 0)
		{
			$umask = Rhymix\Framework\Storage::getUmask();
			Rhymix\Framework\Storage::setUmask(0037);
			$this->assertEquals(0037, Rhymix\Framework\Storage::getUmask());
			$this->assertTrue(Rhymix\Framework\Storage::createDirectory($emptydir . '/umasktest'));
			$this->assertTrue(is_dir($emptydir . '/umasktest'));
			$this->assertEquals(0740, fileperms($emptydir . '/umasktest') & 0777);
			Rhymix\Framework\Storage::setUmask($umask);
		}
	}

	public function testReadDirectory()
	{
		$testdir = \RX_BASEDIR . 'tests/_output/testdir';
		mkdir($testdir);
		mkdir($testdir . '/subdir');
		file_put_contents($testdir . '/.dotfile', '');
		file_put_contents($testdir . '/foo', 'foo');
		file_put_contents($testdir . '/bar', 'bar');
		file_put_contents($testdir . '/baz', 'baz');

		$this->assertEquals(array($testdir . '/bar', $testdir . '/baz', $testdir . '/foo'), Rhymix\Framework\Storage::readDirectory($testdir));
		$this->assertEquals(array('bar', 'baz', 'foo'), Rhymix\Framework\Storage::readDirectory($testdir, false));
		$this->assertEquals(array('bar', 'baz', 'foo', 'subdir'), Rhymix\Framework\Storage::readDirectory($testdir, false, true, false));
		$this->assertEquals(array('.dotfile', 'bar', 'baz', 'foo'), Rhymix\Framework\Storage::readDirectory($testdir, false, false, true));
		$this->assertEquals(array('.dotfile', 'bar', 'baz', 'foo', 'subdir'), Rhymix\Framework\Storage::readDirectory($testdir, false, false, false));
		$this->assertFalse(Rhymix\Framework\Storage::readDirectory('/opt/nonexistent/foobar'));
	}

	public function testCopyDirectory()
	{
		$sourcedir = \RX_BASEDIR . 'tests/_output/sourcedir';
		mkdir($sourcedir);
		mkdir($sourcedir . '/subdir');
		file_put_contents($sourcedir . '/bar', 'bar');
		file_put_contents($sourcedir . '/subdir/baz', 'baz');
		$targetdir = \RX_BASEDIR . 'tests/_output/targetdir';

		$this->assertTrue(Rhymix\Framework\Storage::copyDirectory($sourcedir, $targetdir));
		$this->assertTrue(file_exists($targetdir . '/bar'));
		$this->assertTrue(file_exists($targetdir . '/subdir/baz'));
		if (is_writable('/opt')) @touch('/opt/nonexistent');
		$this->assertFalse(@Rhymix\Framework\Storage::copyDirectory($sourcedir, '/opt/nonexistent/foobar'));
	}

	public function testMoveDirectory()
	{
		$sourcedir = \RX_BASEDIR . 'tests/_output/sourcedir';
		mkdir($sourcedir);
		mkdir($sourcedir . '/subdir');
		file_put_contents($sourcedir . '/bar', 'bar');
		file_put_contents($sourcedir . '/subdir/baz', 'baz');
		$targetdir = \RX_BASEDIR . 'tests/_output/targetdir';

		$this->assertTrue(Rhymix\Framework\Storage::moveDirectory($sourcedir, $targetdir));
		$this->assertTrue(file_exists($targetdir . '/bar'));
		$this->assertTrue(file_exists($targetdir . '/subdir/baz'));
		$this->assertFalse(file_exists($sourcedir));
	}

	public function testDeleteDirectory()
	{
		$sourcedir = \RX_BASEDIR . 'tests/_output/sourcedir';
		mkdir($sourcedir);
		mkdir($sourcedir . '/subdir');
		file_put_contents($sourcedir . '/bar', 'bar');
		file_put_contents($sourcedir . '/subdir/baz', 'baz');
		$nonexistent = \RX_BASEDIR . 'tests/_output/targetdir';

		$this->assertTrue(Rhymix\Framework\Storage::deleteDirectory($sourcedir));
		$this->assertFalse(file_exists($sourcedir . '/subdir/baz'));
		$this->assertFalse(file_exists($sourcedir));
		$this->assertFalse(Rhymix\Framework\Storage::deleteDirectory($nonexistent));
	}

	public function testDeleteDirectoryKeepRoot()
	{
		$sourcedir = \RX_BASEDIR . 'tests/_output/sourcedir';
		mkdir($sourcedir);
		mkdir($sourcedir . '/subdir');
		file_put_contents($sourcedir . '/bar', 'bar');
		file_put_contents($sourcedir . '/subdir/baz', 'baz');
		$nonexistent = \RX_BASEDIR . 'tests/_output/targetdir';

		$this->assertTrue(Rhymix\Framework\Storage::deleteDirectory($sourcedir, false));
		$this->assertFalse(file_exists($sourcedir . '/subdir/baz'));
		$this->assertTrue(file_exists($sourcedir));
		$this->assertFalse(Rhymix\Framework\Storage::deleteDirectory($nonexistent));
	}

	public function testDeleteEmptyDirectory()
	{
		$sourcedir = \RX_BASEDIR . 'tests/_output/sourcedir';
		mkdir($sourcedir);
		file_put_contents($sourcedir . '/foo', 'bar');
		mkdir($sourcedir . '/subdir');
		mkdir($sourcedir . '/subdir/subsubdir');
		mkdir($sourcedir . '/subdir/subsubdir/subsubdir');
		file_put_contents($sourcedir . '/subdir/subsubdir/subsubdir/foo', 'bar');

		$this->assertFalse(Rhymix\Framework\Storage::deleteEmptyDirectory($sourcedir . '/subdir/subsubdir/subsubdir'));
		Rhymix\Framework\Storage::delete($sourcedir . '/subdir/subsubdir/subsubdir/foo');
		$this->assertTrue(Rhymix\Framework\Storage::deleteEmptyDirectory($sourcedir . '/subdir/subsubdir/subsubdir'));
		$this->assertFalse(file_exists($sourcedir . '/subdir/subsubdir/subsubdir'));
		$this->assertTrue(file_exists($sourcedir . '/subdir/subsubdir'));
		$this->assertTrue(file_exists($sourcedir . '/foo'));
		$this->assertTrue(Rhymix\Framework\Storage::deleteEmptyDirectory($sourcedir . '/subdir/subsubdir', true));
		$this->assertFalse(file_exists($sourcedir . '/subdir/subsubdir'));
		$this->assertFalse(file_exists($sourcedir . '/subdir'));
		$this->assertTrue(file_exists($sourcedir));
		$this->assertTrue(file_exists($sourcedir . '/foo'));
	}

	public function testRecommendUmask()
	{
		$umask = Rhymix\Framework\Storage::recommendUmask();

		if (strncasecmp(\PHP_OS, 'Win', 3) !== 0)
		{
			if (fileowner(__FILE__) == exec('id -u'))
			{
				$this->assertEquals('0022', $umask);
			}
			else
			{
				$this->assertEquals('0000', $umask);
			}
		}
		else
		{
			$this->assertEquals('0000', $umask);
		}

		$this->assertFalse(file_exists(\RX_BASEDIR . 'files/cache/uidcheck'));
	}
}
