<?php

namespace Rhymix\Framework\Exceptions;

/**
 * The "feature disabled" exception class.
 */
class FeatureDisabled extends \Rhymix\Framework\Exception
{
	public function __construct($message = '', $code = 0, $previous = null)
	{
		if ($message === '')
		{
			$message = lang('msg_feature_disabled');
		}
		parent::__construct($message, $code, $previous);
	}
}
