<?php

namespace Rhymix\Plugins\Adminlogging;

use Rhymix\Framework\AbstractPlugin;
use Rhymix\Framework\Session;
use AdminloggingController;

class Plugin extends AbstractPlugin
{
	public function __construct(object $config)
	{
		$this->before('moduleObject.proc', function($obj) {
			if (stripos($obj->act ?? '', 'admin') === false) {
				return;
			}
			if (!Session::getMemberInfo()->isAdmin()) {
				return;
			}
			$oLogger = AdminloggingController::getInstance();
			$oLogger->insertLog($obj->module, $obj->act);
		});
	}
}
