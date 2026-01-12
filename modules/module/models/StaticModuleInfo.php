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
	public ?int $module_srl = null;
	public ?string $module = null;
	public ?string $mid = null;

}
