<?php

class ProfileImageCacheTest extends \Codeception\Test\Unit
{
	// A member_srl that no real member uses.
	private $member_srl = 987654321;
	private $created_dir;

	public function _before()
	{
		Rhymix\Framework\Cache::init(array('type' => 'dummy'));
		MemberController::clearMemberCache($this->member_srl);

		// Remember the first directory this test creates, so that _after() removes only that.
		$dir = \RX_BASEDIR . 'files/member_extra_info/profile_image/' . getNumberingPath($this->member_srl);
		$this->created_dir = null;
		while (!is_dir($dir))
		{
			$this->created_dir = $dir;
			$dir = dirname($dir);
		}
	}

	public function _after()
	{
		MemberController::clearMemberCache($this->member_srl);
		if ($this->created_dir !== null && is_dir($this->created_dir))
		{
			Rhymix\Framework\Storage::deleteDirectory($this->created_dir);
		}
		else
		{
			foreach (['jpg', 'png'] as $ext)
			{
				$file = $this->getPath($ext);
				if (file_exists($file))
				{
					Rhymix\Framework\Storage::delete($file);
				}
			}
		}
	}

	private function getPath($ext)
	{
		return \RX_BASEDIR . sprintf('files/member_extra_info/profile_image/%s%d.%s', getNumberingPath($this->member_srl), $this->member_srl, $ext);
	}

	private function writeImage($ext)
	{
		// 1x1 PNG; getimagesize() reads the header, not the extension.
		$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
		$this->assertTrue(Rhymix\Framework\Storage::write($this->getPath($ext), $png));
		clearstatcache();
	}

	private function removeImage($ext)
	{
		$this->assertTrue(Rhymix\Framework\Storage::delete($this->getPath($ext)));
		clearstatcache();
	}

	private function forgetRequestMemo()
	{
		unset($GLOBALS['__member_info__']['profile_image'][$this->member_srl]);
	}

	private function getCached()
	{
		return Rhymix\Framework\Cache::get('member:profile_image:' . $this->member_srl);
	}

	public function testMissStoresImageAndHitSkipsFileSystem()
	{
		$this->assertNull($this->getCached());
		$this->writeImage('png');

		$info = MemberModel::getProfileImage($this->member_srl);
		$this->assertInstanceOf('stdClass', $info);
		$this->assertSame(1, $info->width);
		$this->assertSame(1, $info->height);
		$this->assertStringContainsString($this->member_srl . '.png?t=', $info->src);
		$this->assertSame('./' . substr($this->getPath('png'), strlen(\RX_BASEDIR)), $info->file);
		$this->assertEquals($info, $this->getCached());

		// The file is gone but nothing invalidated the entry: the object cache answers.
		$this->removeImage('png');
		$this->forgetRequestMemo();
		$this->assertEquals($info, MemberModel::getProfileImage($this->member_srl));
	}

	public function testNoImageIsCachedAndReturnsNull()
	{
		$this->assertNull(MemberModel::getProfileImage($this->member_srl));
		$this->assertSame([], $this->getCached());

		// An image added without invalidation is not seen: the "no image" entry answers.
		$this->writeImage('png');
		$this->forgetRequestMemo();
		$this->assertNull(MemberModel::getProfileImage($this->member_srl));
	}

	public function testClearMemberCacheInvalidates()
	{
		$this->assertNull(MemberModel::getProfileImage($this->member_srl));
		$this->writeImage('png');

		MemberController::clearMemberCache($this->member_srl);
		$this->assertNull($this->getCached());
		$info = MemberModel::getProfileImage($this->member_srl);
		$this->assertInstanceOf('stdClass', $info);
		$this->assertStringContainsString($this->member_srl . '.png?t=', $info->src);

		$this->removeImage('png');
		MemberController::clearMemberCache($this->member_srl);
		$this->assertNull(MemberModel::getProfileImage($this->member_srl));
		$this->assertSame([], $this->getCached());
	}

	public function testBypassReadsFileSystemAndRefreshesCache()
	{
		// Stale "no image" in both the request memo and the object cache.
		$this->assertNull(MemberModel::getProfileImage($this->member_srl));
		$this->writeImage('jpg');

		$info = MemberModel::getProfileImage($this->member_srl, false);
		$this->assertInstanceOf('stdClass', $info);
		$this->assertStringContainsString($this->member_srl . '.jpg?t=', $info->src);
		$this->assertEquals($info, $this->getCached());
		$this->assertEquals($info, MemberModel::getProfileImage($this->member_srl));

		// Stale image entry: the bypass sees that the file is gone.
		$this->removeImage('jpg');
		$this->assertNull(MemberModel::getProfileImage($this->member_srl, false));
		$this->assertSame([], $this->getCached());
		$this->assertNull(MemberModel::getProfileImage($this->member_srl));
	}
}
