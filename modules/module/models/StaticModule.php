<?php

namespace Rhymix\Modules\Module\Models;

/**
 * This class represents a static module,
 * i.e. a module that is invoked without a prefix (mid).
 */
class StaticModule extends ModuleInfo
{
	/**
	 * Default attributes.
	 */
	public string $module;

}
