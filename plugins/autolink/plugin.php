<?php

namespace Rhymix\Plugins\Autolink;

use Rhymix\Framework\AbstractPlugin;
use Rhymix\Framework\Responses\HTMLResponse;
use Context;

class Plugin extends AbstractPlugin
{
	public function __construct(object $config)
	{
		$this->after('moduleObject.proc', function($oModule) {
			if (Context::getResponseMethod() === 'HTML' || (isset($oModule->response) && $oModule->response instanceof HTMLResponse))
			{
				Context::loadFile(['./plugins/autolink/autolink.js', 'body']);
			}
		});
	}
}
