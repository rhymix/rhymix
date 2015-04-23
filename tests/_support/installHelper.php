<?php
namespace Codeception\Module;

class installHelper extends \Codeception\Module
{
    public function _initialize()
    {
        \Codeception\Util\FileSystem::deleteDir('files');
    }
}
