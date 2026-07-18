<?php

namespace Rhymix\Plugins\Autolink;

use Rhymix\Framework\AbstractPlugin;
use Context;

class Plugin extends AbstractPlugin
{
	public function __construct(object $config)
	{
		$this->after('moduleObject.proc', function() {
			if (Context::getResponseMethod() === 'HTML')
			{
				Context::loadFile(['./plugins/autolink/autolink.js', 'body']);
			}
		});
	}
}
