<?php
namespace Codeception\Module;

use Codeception\Util\FileSystem;

class InstallHelper extends \Codeception\Module
{
    public function _before()
    {
        FileSystem::deleteDir('files');
    }
}
