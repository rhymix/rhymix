<?php

namespace Rhymix\Modules\Module\Models;

/**
 * This class represents an instance of a module,
 * i.e. a module that is invoked with a prefix (mid).
 */
class ModuleInstance extends ModuleInfo
{
	/*
	 * Default attributes.
	 */
	public int $module_srl;
	public string $module;

}
