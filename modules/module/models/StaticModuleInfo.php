<?php

namespace Rhymix\Modules\Module\Models;

/**
 * This class represents a static module,
 * i.e. a module that is invoked without a prefix (mid).
 */
class StaticModuleInfo extends ModuleInfo
{
	/*
	 * Default attributes.
	 */
	public string $module = '';
	public string $mid = '';

}
