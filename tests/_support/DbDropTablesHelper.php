<?php
namespace Codeception\Module;

class DbDropTablesHelper extends \Codeception\Module\Db
{
    protected $requiredFields = [];

    public function cleanup()
    {
        $dbh = $this->driver->getDbh();
        if (!$dbh) {
            throw new ModuleConfigException(
                __CLASS__,
                "No connection to database. Remove this module from config if you don't need database repopulation"
            );
        }

        try {
            $this->driver->cleanup();
        } catch (\Exception $e) {
            throw new ModuleException(__CLASS__, $e->getMessage());
        }
    }
}
