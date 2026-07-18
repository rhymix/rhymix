<?php

namespace Rhymix\Plugins\Photoswipe;

use Rhymix\Framework\AbstractPlugin;
use Context;

class Plugin extends AbstractPlugin
{
	public function __construct(object $config)
	{
		$this->config = $config;
		if (!isset($this->config->display_filename))
		{
			$this->config->display_filename = 'N';
		}

		$this->after('moduleObject.proc', [$this, 'loadPhotoSwipe']);
	}

	public function loadPhotoSwipe()
	{
		if (Context::getResponseMethod() === 'HTML')
		{
			Context::loadFile(['./plugins/photoswipe/dist/photoswipe.css']);
			Context::loadFile(['./plugins/photoswipe/dist/default-skin/default-skin.css']);
			Context::loadFile(['./plugins/photoswipe/dist/photoswipe.min.js', 'body']);
			Context::loadFile(['./plugins/photoswipe/dist/photoswipe-ui-default.min.js', 'body']);
			Context::loadFile(['./plugins/photoswipe/photoswipe.js', 'body']);
			Context::addHtmlFooter('<script type="application/json" id="photoswipe-config">' . json_encode($this->config) . '</script>');
		}
	}
}
