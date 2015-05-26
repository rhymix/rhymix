<?php
namespace Codeception\Module;

class InstallHelper extends \Codeception\Module
{
    public function _initialize()
    {
        \Codeception\Util\FileSystem::deleteDir('files');
    }
}
