<?php

namespace Rhymix\Modules\Extravar\Models;

class ValueCollection
{
	/**
	 * Properties for compatibility with legacy ExtraVar class.
	 */
	public $module_srl;
	public $keys = [];

	/**
	 * This method only exists for compatibility with legacy ExtraVar class.
	 * Normally, you should just call new ValueCollection($module_srl).
	 *
	 * @deprecated
	 * @param int $module_srl
	 * @return self
	 */
	public static function getInstance(int $module_srl): self
	{
		return new self($module_srl);
	}

	/**
	 * Constructor.
	 *
	 * @param int $module_srl
	 * @param array $keys
	 */
	public function __construct(int $module_srl, $keys = [])
	{
		$this->module_srl = $module_srl;
		$this->setExtraVarKeys($keys);
	}

	/**
	 * Set the list of extra keys for this module.
	 *
	 * @param array $keys
	 * @return void
	 */
	public function setExtraVarKeys($keys)
	{
		if (!is_array($keys) || !count($keys))
		{
			return;
		}

		foreach ($keys as $val)
		{
			$this->keys[$val->idx] = new Value($val->module_srl, $val->idx, $val->name, $val->type, $val->default, $val->desc, $val->is_required, $val->search, $val->value ?? null, $val->eid, $val->parent_type ?? 'document', $val->is_strict, $val->options);
		}
	}

	/**
	 * Returns an array of Value.
	 *
	 * @return array
	 */
	public function getExtraVars(): array
	{
		return $this->keys;
	}
}
